<?php

namespace App\Console\Commands;

use App\Services\ConfigSyncService;
use Illuminate\Console\Command;

class SyncTrackerConfig extends Command
{
    protected $signature = 'tvf:sync-tracker-config';

    protected $description = 'Sincroniza configuración de ofertas/partners/dominios a Redis para el tracker Go';

    public function handle(ConfigSyncService $service): int
    {
        $service->syncAll();
        $this->info('Tracker config synced to Redis.');

        return self::SUCCESS;
    }
}
