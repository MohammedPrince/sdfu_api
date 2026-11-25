<?php

namespace App\Services;

use App\Models\CampaignTemplate;
use App\Models\ScheduledCampaign;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ScheduledCampaignService
{
    public function __construct(
        private FirebaseNotificationService $firebaseNotificationService
    ) {
    }

    /**
     * Send the next message for every active campaign that is due now.
     * Safe to run every minute: a campaign sends at most once per day.
     *
     * @return int number of campaigns that sent
     */
    public function processDueCampaigns(): int
    {
        $sent = 0;

        $campaigns = ScheduledCampaign::query()
            ->where('status', ScheduledCampaign::STATUS_ACTIVE)
            ->get();

        foreach ($campaigns as $campaign) {

            if ($campaign->isFinished()) {
                $campaign->update(['status' => ScheduledCampaign::STATUS_COMPLETED]);
                continue;
            }

            $now = now($campaign->timezone);

            if (
                $campaign->start_date->toDateString() > $now->toDateString()
                || !$campaign->isSendDay($now)
                || $now->format('H:i') < $campaign->send_time
            ) {
                continue;
            }

            if ($this->sendNext($campaign, $now->toDateString())) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send the campaign's next template, at most once per day.
     *
     * @param string|null $today Y-m-d in the campaign timezone (defaults to today)
     * @return bool false when today's message was already sent or nothing could be sent
     */
    public function sendNext(ScheduledCampaign $campaign, ?string $today = null): bool
    {
        $today ??= now($campaign->timezone)->toDateString();

        // Atomically claim today's slot so overlapping runs never double-send
        $claimed = ScheduledCampaign::query()
            ->whereKey($campaign->id)
            ->where('status', ScheduledCampaign::STATUS_ACTIVE)
            ->where(function ($query) use ($today) {
                $query->whereNull('last_sent_date')
                    ->orWhere('last_sent_date', '<', $today);
            })
            ->update(['last_sent_date' => $today]);

        if ($claimed === 0) {
            return false;
        }

        $campaign->refresh();

        $number = $campaign->sent_count + 1;
        $template = $this->templateFor($campaign->template_group, $number);

        if (!$template) {
            Log::warning('Scheduled campaign: template set is empty', [
                'campaign_id' => $campaign->id,
                'template_group' => $campaign->template_group,
            ]);

            return false;
        }

        $replacements = [
            '{number}' => $number,
            '{total}' => $campaign->total_sends,
            '{remaining}' => max(0, $campaign->total_sends - $number),
        ];

        $users = $this->audience($campaign);

        $result = $this->firebaseNotificationService->sendToUsers(
            users: $users,
            notification_type: $template->type,
            title: strtr($template->title, $replacements),
            body: strtr($template->body, $replacements),
            data: [
                'campaign_id' => $campaign->id,
                'campaign_number' => $number,
            ],
        );

        if ($campaign->audience === ScheduledCampaign::AUDIENCE_INDEXES) {
            $result['not_found'] = max(0, count($campaign->student_indexes ?? []) - $users->count());
        }

        $campaign->sent_count = $number;
        $campaign->last_result = [
            'number' => $number,
            'template_id' => $template->id,
            'sent_at' => now()->toISOString(),
            ...$result,
        ];

        if ($campaign->isFinished()) {
            $campaign->status = ScheduledCampaign::STATUS_COMPLETED;
        }

        $campaign->save();

        Log::info('Scheduled campaign: message sent', [
            'campaign_id' => $campaign->id,
            'number' => $number,
            'result' => $result,
        ]);

        return true;
    }

    /**
     * Students targeted by a campaign.
     */
    private function audience(ScheduledCampaign $campaign): Collection
    {
        $query = User::query()->where('role_id', 2);

        return match ($campaign->audience) {

            ScheduledCampaign::AUDIENCE_ALL => $query->get(),

            ScheduledCampaign::AUDIENCE_GROUP => $query
                ->where('faculty_code', $campaign->faculty_code)
                ->when($campaign->major_code, fn($q, $v) => $q->where('major_code', $v))
                ->when($campaign->batch, fn($q, $v) => $q->where('batch', $v))
                ->when($campaign->semester, fn($q, $v) => $q->where('semester', $v))
                ->get(),

            default => $query
                ->whereIn('stud_index', $campaign->student_indexes ?? [])
                ->get(),
        };
    }

    /**
     * Template for message #$number; cycles through the set when the
     * campaign has more sends than templates.
     */
    private function templateFor(string $group, int $number): ?CampaignTemplate
    {
        $templates = CampaignTemplate::query()
            ->where('template_group', $group)
            ->orderBy('sequence')
            ->get();

        if ($templates->isEmpty()) {
            return null;
        }

        return $templates->firstWhere('sequence', $number)
            ?? $templates[($number - 1) % $templates->count()];
    }
}
