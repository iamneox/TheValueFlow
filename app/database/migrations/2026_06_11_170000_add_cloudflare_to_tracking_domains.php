<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracking_domains', function (Blueprint $table) {
            $table->string('cloudflare_zone_id')->nullable()->after('domain');
            $table->string('cloudflare_record_id')->nullable()->after('cloudflare_zone_id');
            $table->boolean('cloudflare_proxied')->default(true)->after('cloudflare_record_id');
            $table->boolean('bot_fight_mode')->default(false)->after('cloudflare_proxied');
            $table->timestamp('cloudflare_synced_at')->nullable()->after('bot_fight_mode');
            $table->text('cloudflare_sync_error')->nullable()->after('cloudflare_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('tracking_domains', function (Blueprint $table) {
            $table->dropColumn([
                'cloudflare_zone_id',
                'cloudflare_record_id',
                'cloudflare_proxied',
                'bot_fight_mode',
                'cloudflare_synced_at',
                'cloudflare_sync_error',
            ]);
        });
    }
};
