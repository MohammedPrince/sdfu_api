<?php

namespace App\Console\Commands;

use App\Services\ScheduledCampaignService;
use Illuminate\Console\Command;

class SendScheduledCampaigns extends Command
{
    protected $signature = 'notifications:scheduled-campaigns';

    protected $description = 'Send the daily/weekly scheduled campaign notifications that are due';

    public function handle(ScheduledCampaignService $service): int
    {
        $count = $service->processDueCampaigns();

        $this->info("Scheduled campaigns sent: {$count}");

        return self::SUCCESS;
    }
}
