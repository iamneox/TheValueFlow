<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Offer;
use App\Models\OfferLandingPage;
use App\Models\Partner;
use App\Models\TrackingDomain;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['super_admin', 'affiliate_manager', 'sales_manager', 'partner', 'client'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@thevalueflow.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('super_admin');

        $client = Client::firstOrCreate(
            ['company' => 'Demo Advertiser'],
            ['contact_name' => 'Demo Contact', 'email' => 'client@demo.com', 'status' => 'active']
        );

        $partner = Partner::firstOrCreate(
            ['name' => 'Demo Partner'],
            ['email' => 'partner@demo.com', 'status' => 'active', 'payment_terms' => 'net_30']
        );

        $domain = TrackingDomain::firstOrCreate(
            ['domain' => 'track.demo.thevalueflow.com'],
            ['partner_id' => $partner->id, 'status' => 'active']
        );

        $offer = Offer::firstOrCreate(
            ['slug' => 'demo-offer'],
            [
                'name' => 'Demo Offer CPL',
                'client_id' => $client->id,
                'type' => 'CPL',
                'revenue' => 15,
                'payout' => 10,
                'status' => 'active',
                'tracking_domain_id' => $domain->id,
            ]
        );

        OfferLandingPage::firstOrCreate(
            ['offer_id' => $offer->id, 'url' => 'https://example.com/landing'],
            ['name' => 'Default', 'is_default' => true, 'is_active' => true]
        );

        $partnerUser = User::firstOrCreate(
            ['email' => 'partner@demo.com'],
            [
                'name' => 'Demo Partner User',
                'password' => Hash::make('password'),
                'partner_id' => $partner->id,
                'is_active' => true,
            ]
        );
        $partnerUser->assignRole('partner');

        $clientUser = User::firstOrCreate(
            ['email' => 'client@demo.com'],
            [
                'name' => 'Demo Client User',
                'password' => Hash::make('password'),
                'client_id' => $client->id,
                'is_active' => true,
            ]
        );
        $clientUser->assignRole('client');
    }
}
