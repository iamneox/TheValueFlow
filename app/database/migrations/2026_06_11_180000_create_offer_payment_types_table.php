<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_payment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['CPL', 'CPC', 'CPM', 'CPA']);
            $table->decimal('revenue', 12, 4)->default(0);
            $table->decimal('payout', 12, 4)->default(0);
            $table->timestamps();
            $table->unique(['offer_id', 'type']);
        });

        foreach (DB::table('offers')->orderBy('id')->get() as $offer) {
            DB::table('offer_payment_types')->insert([
                'offer_id' => $offer->id,
                'type' => $offer->type,
                'revenue' => $offer->revenue,
                'payout' => $offer->payout,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_payment_types');
    }
};
