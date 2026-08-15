<?php

use App\Enums\OrderStatus;
use App\Filament\Admin\Pages\Reports\DepositReport;
use App\Filament\Admin\Pages\Reports\TopCustomersReport;
use App\Filament\Admin\Pages\Reports\UnusedAdAccountsReport;
use App\Models\AdAccount;
use App\Models\Admin;
use App\Models\BusinessManager;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin can access report pages', function () {
    $admin = Admin::query()->create([
        'name' => 'Admin User',
        'email' => 'admin@reports.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($admin, 'admin');
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(DepositReport::canAccess())->toBeTrue();
    expect(TopCustomersReport::canAccess())->toBeTrue();
    expect(UnusedAdAccountsReport::canAccess())->toBeTrue();
});

test('regular user cannot access admin report pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web');
    Filament::setCurrentPanel(Filament::getPanel('app'));

    expect(DepositReport::canAccess())->toBeFalse();
    expect(TopCustomersReport::canAccess())->toBeFalse();
    expect(UnusedAdAccountsReport::canAccess())->toBeFalse();
});

test('top customers table widget aggregates monthly deposits', function () {
    $admin = Admin::query()->create([
        'name' => 'Admin User',
        'email' => 'admin2@reports.com',
        'password' => bcrypt('password'),
    ]);

    $user1 = User::factory()->create(['name' => 'Top Spender User']);
    $user2 = User::factory()->create(['name' => 'Low Spender User']);

    $bm = BusinessManager::query()->create([
        'bm_id' => '99999',
        'access_token' => 'token_secret',
        'name' => 'BM Test',
    ]);

    $adAccount1 = AdAccount::query()->create([
        'business_manager_id' => $bm->id,
        'user_id' => $user1->id,
        'act_id' => '11111',
        'name' => 'Account 1',
    ]);

    $adAccount2 = AdAccount::query()->create([
        'business_manager_id' => $bm->id,
        'user_id' => $user2->id,
        'act_id' => '22222',
        'name' => 'Account 2',
    ]);

    // Order for User 1 (Approved, 500 USD)
    Order::query()->create([
        'user_id' => $user1->id,
        'ad_account_id' => $adAccount1->id,
        'usd_amount' => 500.00,
        'dollar_rate' => 110.00,
        'bdt_amount' => 55000.00,
        'status' => OrderStatus::APPROVED,
        'created_at' => now(),
    ]);

    // Order for User 2 (Approved, 100 USD)
    Order::query()->create([
        'user_id' => $user2->id,
        'ad_account_id' => $adAccount2->id,
        'usd_amount' => 100.00,
        'dollar_rate' => 110.00,
        'bdt_amount' => 11000.00,
        'status' => OrderStatus::APPROVED,
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin');
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(TopCustomersReport::class)
        ->assertCanSeeTableRecords([$user1, $user2]);
});

test('unused ad accounts table identifies inactive accounts', function () {
    $admin = Admin::query()->create([
        'name' => 'Admin User',
        'email' => 'admin3@reports.com',
        'password' => bcrypt('password'),
    ]);

    $user = User::factory()->create();

    $bm = BusinessManager::query()->create([
        'bm_id' => '88888',
        'access_token' => 'token_secret',
        'name' => 'BM Test 2',
    ]);

    // Ad account with old topup (45 days ago)
    $inactiveAdAccount = AdAccount::query()->create([
        'business_manager_id' => $bm->id,
        'user_id' => $user->id,
        'act_id' => '33333',
        'name' => 'Inactive Account',
    ]);

    Order::query()->create([
        'user_id' => $user->id,
        'ad_account_id' => $inactiveAdAccount->id,
        'usd_amount' => 200.00,
        'dollar_rate' => 110.00,
        'bdt_amount' => 22000.00,
        'status' => OrderStatus::APPROVED,
        'created_at' => now()->subDays(45),
    ]);

    // Active ad account (topup today)
    $activeAdAccount = AdAccount::query()->create([
        'business_manager_id' => $bm->id,
        'user_id' => $user->id,
        'act_id' => '44444',
        'name' => 'Active Account',
    ]);

    Order::query()->create([
        'user_id' => $user->id,
        'ad_account_id' => $activeAdAccount->id,
        'usd_amount' => 200.00,
        'dollar_rate' => 110.00,
        'bdt_amount' => 22000.00,
        'status' => OrderStatus::APPROVED,
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin');
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(UnusedAdAccountsReport::class)
        ->assertCanSeeTableRecords([$inactiveAdAccount])
        ->assertCanNotSeeTableRecords([$activeAdAccount]);
});
