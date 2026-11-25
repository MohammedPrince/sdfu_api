<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignTemplate;
use App\Models\ScheduledCampaign;
use App\Services\ScheduledCampaignService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduledCampaignController extends Controller
{
    public function __construct(
        public ScheduledCampaignService $scheduledCampaignService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Campaigns
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_group' => ['required', 'string', Rule::exists('campaign_templates', 'template_group')],

            'audience' => ['required', Rule::in([
                ScheduledCampaign::AUDIENCE_INDEXES,
                ScheduledCampaign::AUDIENCE_ALL,
                ScheduledCampaign::AUDIENCE_GROUP,
            ])],
            'student_indexes' => ['required_if:audience,indexes', 'nullable', 'string', 'max:10000'],
            'faculty_code' => ['required_if:audience,group', 'nullable', 'string', 'max:50'],
            'major_code' => ['nullable', 'string', 'max:50'],
            'batch' => ['nullable', 'string', 'max:50'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:12'],

            'frequency' => ['required', Rule::in([
                ScheduledCampaign::FREQUENCY_DAILY,
                ScheduledCampaign::FREQUENCY_WEEKLY,
            ])],
            'weekday' => ['required_if:frequency,weekly', 'nullable', 'integer', 'between:0,6'],
            'start_date' => ['required', 'date', 'after_or_equal:yesterday'],
            'send_time' => ['required', 'date_format:H:i'],
            'total_sends' => ['required', 'integer', 'min:1', 'max:520'],
        ]);

        $audience = $validated['audience'];
        $studentIndexes = null;

        if ($audience === ScheduledCampaign::AUDIENCE_INDEXES) {

            $studentIndexes = collect(preg_split('/[\s,]+/', $validated['student_indexes'] ?? ''))
                ->map(fn($index) => trim($index))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($studentIndexes)) {
                return redirect()
                    ->route('admin.notifications')
                    ->withErrors(['student_indexes' => 'Please enter at least one student index.'])
                    ->withInput();
            }
        }

        $isGroup = $audience === ScheduledCampaign::AUDIENCE_GROUP;
        $isWeekly = $validated['frequency'] === ScheduledCampaign::FREQUENCY_WEEKLY;

        ScheduledCampaign::create([
            'name' => $validated['name'],
            'template_group' => $validated['template_group'],
            'audience' => $audience,
            'student_indexes' => $studentIndexes,
            'faculty_code' => $isGroup ? $validated['faculty_code'] : null,
            'major_code' => $isGroup ? ($validated['major_code'] ?? null) : null,
            'batch' => $isGroup ? ($validated['batch'] ?? null) : null,
            'semester' => $isGroup ? ($validated['semester'] ?? null) : null,
            'frequency' => $validated['frequency'],
            'weekday' => $isWeekly ? (int) $validated['weekday'] : null,
            'start_date' => $validated['start_date'],
            'send_time' => $validated['send_time'],
            'total_sends' => $validated['total_sends'],
            'status' => ScheduledCampaign::STATUS_ACTIVE,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.notifications')
            ->with('success', "Campaign \"{$validated['name']}\" scheduled.");
    }

    public function toggle(ScheduledCampaign $campaign)
    {
        if ($campaign->status === ScheduledCampaign::STATUS_COMPLETED) {
            return redirect()
                ->route('admin.notifications')
                ->withErrors(['campaign' => 'This campaign is already completed.']);
        }

        $campaign->update([
            'status' => $campaign->status === ScheduledCampaign::STATUS_ACTIVE
                ? ScheduledCampaign::STATUS_PAUSED
                : ScheduledCampaign::STATUS_ACTIVE,
        ]);

        return redirect()
            ->route('admin.notifications')
            ->with('success', "Campaign \"{$campaign->name}\" is now {$campaign->status}.");
    }

    public function sendNow(ScheduledCampaign $campaign)
    {
        if ($campaign->status !== ScheduledCampaign::STATUS_ACTIVE) {
            return redirect()
                ->route('admin.notifications')
                ->withErrors(['campaign' => 'Only active campaigns can be sent.']);
        }

        if (!$this->scheduledCampaignService->sendNext($campaign)) {
            return redirect()
                ->route('admin.notifications')
                ->withErrors(['campaign' => "Today's message for \"{$campaign->name}\" was already sent."]);
        }

        $result = $campaign->refresh()->last_result ?? [];

        return redirect()
            ->route('admin.notifications')
            ->with('success', sprintf(
                'Message %d of "%s" sent to %d student(s), %d with an active device.',
                $result['number'] ?? $campaign->sent_count,
                $campaign->name,
                $result['students'] ?? 0,
                $result['with_device'] ?? 0
            ));
    }

    public function destroy(ScheduledCampaign $campaign)
    {
        $campaign->delete();

        return redirect()
            ->route('admin.notifications')
            ->with('success', 'Campaign deleted.');
    }

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    */

    public function storeTemplate(Request $request)
    {
        $validated = $this->validateTemplate($request);

        CampaignTemplate::create($validated);

        return redirect()
            ->route('admin.notifications')
            ->with('success', "Template #{$validated['sequence']} added to \"{$validated['template_group']}\".");
    }

    public function updateTemplate(Request $request, CampaignTemplate $template)
    {
        $validated = $this->validateTemplate($request, $template);

        $template->update($validated);

        return redirect()
            ->route('admin.notifications')
            ->with('success', "Template #{$template->sequence} of \"{$template->template_group}\" updated.");
    }

    public function destroyTemplate(CampaignTemplate $template)
    {
        $template->delete();

        return redirect()
            ->route('admin.notifications')
            ->with('success', "Template #{$template->sequence} of \"{$template->template_group}\" deleted.");
    }

    private function validateTemplate(Request $request, ?CampaignTemplate $template = null): array
    {
        $group = trim((string) $request->input('template_group'));
        $request->merge(['template_group' => $group]);

        return $request->validate([
            'template_group' => ['required', 'string', 'max:100'],
            'sequence' => [
                'required',
                'integer',
                'min:1',
                'max:520',
                Rule::unique('campaign_templates', 'sequence')
                    ->where('template_group', $group)
                    ->ignore($template?->id),
            ],
            'type' => ['required', Rule::in(array_keys(CampaignTemplate::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'sequence.unique' => 'This set already has a template with that number.',
        ]);
    }
}
