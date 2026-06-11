<?php

namespace App\Console\Commands;

use App\Services\BlacklistService;
use Illuminate\Console\Command;

class CheckDomainBlacklists extends Command
{
    protected $signature = 'tvf:check-blacklists';

    protected $description = 'Comprueba dominios de tracking contra DNSBLs y Google Safe Browsing';

    public function handle(BlacklistService $service): int
    {
        $service->checkAllDue();
        $this->info('Blacklist check completed.');

        return self::SUCCESS;
    }
}
