<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Auth\LoginPage;
use App\Filament\Pages\OrderHistory;
use App\Filament\Widgets\PendingOrdersTableWidget;
use App\Http\Controllers\ApproveOrderController;
use App\Http\Controllers\RejectOrderController;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(LoginPage::class)
            ->passwordReset()
            ->emailVerification()
            ->colors([
                'primary' => Color::Neutral,
            ])
            ->sidebarWidth('16rem')
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight('2.25rem')
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
                OrderHistory::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                PendingOrdersTableWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authenticatedRoutes(function (): void {
                Route::get('/orders/{order}/approve', ApproveOrderController::class)
                    ->middleware('signed')
                    ->name('orders.approve');
                Route::get('/orders/{order}/reject', RejectOrderController::class)
                    ->middleware('signed')
                    ->name('orders.reject');
            })
            ->authGuard('admin')
            ->spa();
    }
}
