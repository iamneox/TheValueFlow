<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('payment_terms')->default('net_30');
            $table->enum('status', ['active', 'paused', 'pending'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->enum('status', ['active', 'paused', 'pending'])->default('pending');
            $table->enum('payment_terms', ['net_10', 'net_20', 'net_30'])->default('net_30');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('tracking_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'paused', 'blacklisted'])->default('active');
            $table->timestamp('last_blacklist_check_at')->nullable();
            $table->boolean('is_blacklisted')->default(false);
            $table->json('blacklist_details')->nullable();
            $table->unsignedInteger('check_interval_hours')->default(24);
            $table->timestamps();
        });

        Schema::create('domain_blacklist_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_domain_id')->constrained()->cascadeOnDelete();
            $table->string('list_name');
            $table->boolean('is_listed')->default(false);
            $table->json('details')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['CPL', 'CPC', 'CPM', 'CPA']);
            $table->string('category')->nullable();
            $table->string('country')->nullable();
            $table->decimal('revenue', 12, 4)->default(0);
            $table->decimal('payout', 12, 4)->default(0);
            $table->string('from_name_advertiser')->nullable();
            $table->enum('status', ['active', 'pending', 'paused'])->default('pending');
            $table->json('allowed_countries')->nullable();
            $table->json('allowed_days')->nullable();
            $table->string('allowed_hours_start')->nullable();
            $table->string('allowed_hours_end')->nullable();
            $table->unsignedInteger('daily_cap')->nullable();
            $table->unsignedInteger('monthly_cap')->nullable();
            $table->enum('cap_type', ['clicks', 'conversions', 'revenue'])->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('tracking_domain_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('offer_landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('url');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('offer_partner_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->enum('access_type', ['whitelist', 'blacklist']);
            $table->timestamps();
            $table->unique(['offer_id', 'partner_id']);
        });

        Schema::create('custom_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_name')->nullable();
            $table->decimal('payout', 12, 4);
            $table->timestamps();
        });

        Schema::create('custom_caps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('cap_type', ['daily', 'monthly']);
            $table->enum('metric', ['clicks', 'conversions', 'revenue']);
            $table->unsignedInteger('value');
            $table->timestamps();
        });

        Schema::create('creatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['banner', 'image', 'html', 'email', 'zip']);
            $table->string('name');
            $table->string('file_path')->nullable();
            $table->text('html_content')->nullable();
            $table->string('subject')->nullable();
            $table->string('sender_name')->nullable();
            $table->text('mandatory_mentions')->nullable();
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->timestamps();
        });

        Schema::create('traffic_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('source_id')->unique();
            $table->string('name');
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();
        });

        Schema::create('tracking_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('traffic_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tracking_domain_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('aff_sub1')->nullable();
            $table->string('aff_sub2')->nullable();
            $table->string('aff_sub3')->nullable();
            $table->string('aff_sub4')->nullable();
            $table->string('aff_sub5')->nullable();
            $table->timestamps();
        });

        Schema::create('raw_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('click_id', 36)->unique();
            $table->foreignId('offer_id')->nullable();
            $table->foreignId('partner_id')->nullable();
            $table->string('source_id')->nullable();
            $table->string('tracking_domain')->nullable();
            $table->timestamp('clicked_at');
            $table->string('ip', 45)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('device')->nullable();
            $table->string('os')->nullable();
            $table->string('browser')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('aff_sub1')->nullable();
            $table->string('aff_sub2')->nullable();
            $table->string('aff_sub3')->nullable();
            $table->string('aff_sub4')->nullable();
            $table->string('aff_sub5')->nullable();
            $table->boolean('is_unique')->default(true);
            $table->boolean('is_duplicate')->default(false);
            $table->boolean('is_invalid')->default(false);
            $table->string('invalid_reason')->nullable();
            $table->index(['offer_id', 'clicked_at']);
            $table->index(['partner_id', 'clicked_at']);
            $table->index('clicked_at');
        });

        Schema::create('raw_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->nullable();
            $table->foreignId('partner_id')->nullable();
            $table->string('source_id')->nullable();
            $table->enum('type', ['email_open', 'landing']);
            $table->string('click_id', 36)->nullable();
            $table->timestamp('impressed_at');
            $table->string('ip', 45)->nullable();
            $table->string('country', 2)->nullable();
            $table->index(['offer_id', 'impressed_at']);
            $table->index(['partner_id', 'impressed_at']);
            $table->index('impressed_at');
        });

        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->string('click_id', 36)->nullable()->index();
            $table->foreignId('offer_id')->nullable();
            $table->foreignId('partner_id')->nullable();
            $table->string('transaction_id')->nullable()->index();
            $table->string('conversion_id')->nullable();
            $table->enum('status', ['approved', 'pending', 'rejected'])->default('approved');
            $table->decimal('payout', 12, 4)->default(0);
            $table->decimal('revenue', 12, 4)->default(0);
            $table->enum('method', ['postback', 'pixel', 'api'])->default('postback');
            $table->timestamp('converted_at');
            $table->index(['offer_id', 'converted_at']);
            $table->index(['partner_id', 'converted_at']);
        });

        Schema::create('stats_hourly', function (Blueprint $table) {
            $table->id();
            $table->timestamp('hour');
            $table->foreignId('offer_id')->nullable();
            $table->foreignId('partner_id')->nullable();
            $table->string('source_id')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('device')->nullable();
            $table->string('os')->nullable();
            $table->string('browser')->nullable();
            $table->string('city')->nullable();
            $table->char('dimensions_hash', 40)->default('');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('gross_clicks')->default(0);
            $table->unsignedBigInteger('unique_clicks')->default(0);
            $table->unsignedBigInteger('duplicate_clicks')->default(0);
            $table->unsignedBigInteger('invalid_clicks')->default(0);
            $table->unsignedBigInteger('conversions')->default(0);
            $table->decimal('revenue', 14, 4)->default(0);
            $table->decimal('payout', 14, 4)->default(0);
            $table->unique(['hour', 'offer_id', 'partner_id', 'source_id', 'dimensions_hash'], 'stats_hourly_unique');
            $table->index(['hour', 'offer_id']);
            $table->index(['hour', 'partner_id']);
        });

        Schema::create('adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->enum('metric', ['clicks', 'leads', 'revenue', 'payout']);
            $table->decimal('value', 14, 4);
            $table->string('transaction_id')->nullable();
            $table->enum('method', ['manual', 'transaction_id']);
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('adjustment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained()->cascadeOnDelete();
            $table->json('old_values');
            $table->json('new_values');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'sent', 'paid'])->default('draft');
            $table->decimal('total_amount', 14, 4)->default(0);
            $table->date('due_date')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('campaign_name');
            $table->unsignedBigInteger('quantity')->default(0);
            $table->enum('quantity_type', ['leads', 'clicks', 'impressions']);
            $table->decimal('payout', 12, 4)->default(0);
            $table->decimal('total_payout', 14, 4)->default(0);
            $table->boolean('is_manual')->default(false);
            $table->timestamps();
        });

        Schema::create('postbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url');
            $table->enum('type', ['global', 'offer'])->default('global');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('postback_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postback_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->string('name');
            $table->string('file_path');
            $table->string('type')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('partner_signup_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('client_signup_requests', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_signup_requests');
        Schema::dropIfExists('partner_signup_requests');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('postback_logs');
        Schema::dropIfExists('postbacks');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('adjustment_histories');
        Schema::dropIfExists('adjustments');
        Schema::dropIfExists('stats_hourly');
        Schema::dropIfExists('conversions');
        Schema::dropIfExists('raw_impressions');
        Schema::dropIfExists('raw_clicks');
        Schema::dropIfExists('tracking_links');
        Schema::dropIfExists('traffic_sources');
        Schema::dropIfExists('creatives');
        Schema::dropIfExists('custom_caps');
        Schema::dropIfExists('custom_payouts');
        Schema::dropIfExists('offer_partner_access');
        Schema::dropIfExists('offer_landing_pages');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('domain_blacklist_checks');
        Schema::dropIfExists('tracking_domains');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_id');
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn('is_active');
        });
        Schema::dropIfExists('partners');
        Schema::dropIfExists('clients');
    }
};
