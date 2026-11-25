<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledCampaign extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';

    public const AUDIENCE_INDEXES = 'indexes';
    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_GROUP = 'group';

    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';

    public const WEEKDAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    protected $fillable = [
        'name',
        'template_group',
        'audience',
        'student_indexes',
        'faculty_code',
        'major_code',
        'batch',
        'semester',
        'frequency',
        'weekday',
        'start_date',
        'send_time',
        'timezone',
        'total_sends',
        'status',
        'sent_count',
        'last_sent_date',
        'last_result',
        'created_by',
    ];

    protected $casts = [
        'student_indexes' => 'array',
        'last_result' => 'array',
        'start_date' => 'date',
        'last_sent_date' => 'date',
        'semester' => 'integer',
        'weekday' => 'integer',
        'total_sends' => 'integer',
        'sent_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isFinished(): bool
    {
        return $this->sent_count >= $this->total_sends;
    }

    public function isWeekly(): bool
    {
        return $this->frequency === self::FREQUENCY_WEEKLY;
    }

    /**
     * Whether $date (campaign timezone) is a sending day for this campaign.
     */
    public function isSendDay(Carbon $date): bool
    {
        return !$this->isWeekly() || $date->dayOfWeek === $this->weekday;
    }

    /**
     * Next date/time (campaign timezone) a notification will go out.
     */
    public function nextSendAt(): ?Carbon
    {
        if ($this->status !== self::STATUS_ACTIVE || $this->isFinished()) {
            return null;
        }

        $now = now($this->timezone);
        $start = Carbon::parse($this->start_date->toDateString(), $this->timezone);
        $date = $start->greaterThan($now) ? $start : $now->copy()->startOfDay();

        if ($this->last_sent_date?->toDateString() === $date->toDateString()) {
            $date->addDay();
        }

        while (!$this->isSendDay($date)) {
            $date->addDay();
        }

        // Can be slightly in the past when today's slot is pending the next scheduler run
        return Carbon::parse($date->toDateString() . ' ' . $this->send_time, $this->timezone);
    }

    public function scheduleLabel(): string
    {
        return $this->isWeekly()
            ? 'Weekly on ' . (self::WEEKDAYS[$this->weekday] ?? '?') . ' at ' . $this->send_time
            : 'Daily at ' . $this->send_time;
    }

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            self::AUDIENCE_ALL => 'All students',
            self::AUDIENCE_GROUP => collect([
                'Faculty ' . $this->faculty_code,
                $this->major_code ? 'Major ' . $this->major_code : null,
                $this->batch ? 'Batch ' . $this->batch : null,
                $this->semester ? 'Semester ' . $this->semester : null,
            ])->filter()->implode(' · '),
            default => count($this->student_indexes ?? []) . ' student(s)',
        };
    }
}
