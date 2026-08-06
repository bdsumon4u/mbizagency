<?php

use App\Enums\OrderStatus;
use App\Filament\Widgets\OrderHistoryStatsWidget;
use App\Models\AdAccount;
use App\Models\Admin;
use App\Models\BusinessManager;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('widget returns correct stats for admin and user', function () {
    // Create Users & Admin
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $admin = Admin::query()->create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);

    // Create Business Manager
    $bm = BusinessManager::query()->create([
        'bm_id' => '11111',
        'access_token' => 'token_secret',
        'name' => 'BM 1',
    ]);

    // Create Ad Accounts
    $adAccount1 = AdAccount::query()->create([
        'business_manager_id' => $bm->id,
        'user_id' => $user1->id,
        'act_id' => '12345',
        'name' => 'Account 1',
    ]);
    $adAccount2 = AdAccount::query()->create([
        'business_manager_id' => $bm->id,
        'user_id' => $user2->id,
        'act_id' => '67890',
        'name' => 'Account 2',
    ]);

    // Create Order for user 1 (Approved, Yesterday)
    Order::query()->create([
        'user_id' => $user1->id,
        'ad_account_id' => $adAccount1->id,
        'usd_amount' => 150.00,
        'dollar_rate' => 110.00,
        'bdt_amount' => 16500.00,
        'status' => OrderStatus::APPROVED,
        'created_at' => now()->subDay(),
    ]);

    // Create Order for user 1 (Approved, Today)
    Order::query()->create([
        'user_id' => $user1->id,
        'ad_account_id' => $adAccount1->id,
        'usd_amount' => 100.00,
        'dollar_rate' => 110.00,
        'bdt_amount' => 11000.00,
        'status' => OrderStatus::APPROVED,
        'created_at' => now(),
    ]);

    // Create Order for user 1 (Approved, This Month but not today/yesterday)
    Order::query()->create([
        'user_id' => $user1->id,
        'ad_account_id' => $adAccount1->id,
        'usd_amount' => 200.00,
        'dollar_rate' => 110.00,
        'bdt_amount' => 22000.00,
        'status' => OrderStatus::APPROVED,
        'created_at' => now()->startOfMonth()->addDays(2),
    ]);

    // Create Order for user 1 (Approved, Last Month)
    $lastMonth = now()->startOfMonth()->subMonth();
    Order::query()->create([
        'user_id' => $user1->id,
        'ad_account_id' => $adAccount1->id,
        'usd_amount' => 300.00,
        'dollar_rate' => 110.00,
        'bdt_amount' => 33000.00,
        'status' => OrderStatus::APPROVED,
        'created_at' => $lastMonth->copy()->addDays(2),
    ]);

    // Create Order for user 1 (Pending)
    Order::query()->create([
        'user_id' => $user1->id,
        'ad_account_id' => $adAccount1->id,
        'usd_amount' => 50.00,
        'dollar_rate' => 110.00,
        'bdt_amount' => 5500.00,
        'status' => OrderStatus::PENDING,
        'created_at' => now(),
    ]);

    // Create Order for user 2 (Approved, Today)
    Order::query()->create([
        'user_id' => $user2->id,
        'ad_account_id' => $adAccount2->id,
        'usd_amount' => 500.00,
        'dollar_rate' => 120.00,
        'bdt_amount' => 60000.00,
        'status' => OrderStatus::APPROVED,
        'created_at' => now(),
    ]);

    // --- TEST ADMIN ROLE ---
    $this->actingAs($admin, 'admin');
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $widget = new OrderHistoryStatsWidget;
    $stats = $widget->getStats();

    // Verify stats return exactly 5 items
    expect($stats)->toHaveCount(5);

    // 1. Pending Deposit: sum of all pending (user1: 50) = $50.00
    expect($stats[0]['label'])->toBe('Pending Deposit');
    expect($stats[0]['value'])->toBe('$50.00');
    expect($stats[0]['bdt_value'])->toBe('৳5,500.00');

    // 2. Today: sum of all approved today (user1: 100, user2: 500) = $600.00
    expect($stats[1]['label'])->toBe('Today');
    expect($stats[1]['value'])->toBe('$600.00');
    expect($stats[1]['bdt_value'])->toBe('৳71,000.00');

    // 3. Yesterday: sum of all approved yesterday (user1: 150) = $150.00
    expect($stats[2]['label'])->toBe('Yesterday');
    expect($stats[2]['value'])->toBe('$150.00');
    expect($stats[2]['bdt_value'])->toBe('৳16,500.00');

    // 4. This Month: sum of all approved in this month (user1: 150+100+200, user2: 500) = $950.00
    expect($stats[3]['label'])->toBe('This Month');
    expect($stats[3]['value'])->toBe('$950.00');
    expect($stats[3]['bdt_value'])->toBe('৳109,500.00');

    // 5. Last Month: sum of all approved in last month (user1: 300) = $300.00
    expect($stats[4]['label'])->toBe('Last Month');
    expect($stats[4]['value'])->toBe('$300.00');
    expect($stats[4]['bdt_value'])->toBe('৳33,000.00');

    // --- TEST USER 1 ROLE ---
    $this->actingAs($user1, 'web');
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $statsUser1 = $widget->getStats();

    // Verify stats return exactly 5 items
    expect($statsUser1)->toHaveCount(5);

    // 1. Pending Deposit: sum of user1 pending (50) = $50.00
    expect($statsUser1[0]['label'])->toBe('Pending Deposit');
    expect($statsUser1[0]['value'])->toBe('$50.00');

    // 2. Today: sum of user1 approved today (100) = $100.00
    expect($statsUser1[1]['label'])->toBe('Today');
    expect($statsUser1[1]['value'])->toBe('$100.00');

    // 3. Yesterday: sum of user1 approved yesterday (150) = $150.00
    expect($statsUser1[2]['label'])->toBe('Yesterday');
    expect($statsUser1[2]['value'])->toBe('$150.00');

    // 4. This Month: sum of user1 approved in this month (150+100+200) = $450.00
    expect($statsUser1[3]['label'])->toBe('This Month');
    expect($statsUser1[3]['value'])->toBe('$450.00');

    // 5. Last Month: sum of user1 approved in last month (300) = $300.00
    expect($statsUser1[4]['label'])->toBe('Last Month');
    expect($statsUser1[4]['value'])->toBe('$300.00');
});
