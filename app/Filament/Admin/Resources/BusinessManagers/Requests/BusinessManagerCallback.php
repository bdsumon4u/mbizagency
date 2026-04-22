<?php

namespace App\Filament\Admin\Resources\BusinessManagers\Requests;

use App\Filament\Admin\Resources\BusinessManagers\BusinessManagerResource;
use App\Filament\Admin\Resources\BusinessManagers\Pages\ListBusinessManagers;
use App\Services\FacebookBusinessManagerSyncService;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BusinessManagerCallback
{
    protected static string $resource = BusinessManagerResource::class;

    public function __construct(
        private readonly FacebookBusinessManagerSyncService $facebookBusinessManagerSyncService,
    ) {}

    public function __invoke(Request $request)
    {
        $state = $request->input('state');
        $code = $request->input('code');

        if (! $state || ! $code) {
            Notification::make()
                ->title('Invalid state or code.')
                ->danger()
                ->send();

            return redirect(ListBusinessManagers::getUrl());
        }

        $cache = Cache::get('facebook_oauth_state:'.$state);
        if (! $cache) {
            Notification::make()
                ->title('Invalid state or code.')
                ->danger()
                ->send();

            return redirect(ListBusinessManagers::getUrl());
        }

        $response = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
            'client_id' => config('services.facebook.app_id'),
            'client_secret' => config('services.facebook.app_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('facebook.oauth.callback'),
        ]);

        $data = $response->json();
        if ($data['error'] ?? false) {
            Notification::make()
                ->title((string) ($data['error']['message'] ?? 'Invalid access token.'))
                ->danger()
                ->send();

            return redirect(ListBusinessManagers::getUrl());
        }

        $syncedCount = $this->facebookBusinessManagerSyncService->sync(
            accessToken: (string) ($data['access_token'] ?? ''),
            userId: null,
        );

        if ($syncedCount === 0) {
            Notification::make()
                ->title('Facebook connected, but no accessible business manager found.')
                ->warning()
                ->send();

            return redirect(ListBusinessManagers::getUrl());
        }

        Notification::make()
            ->title("Business managers synced successfully. Total: {$syncedCount}.")
            ->success()
            ->send();

        return redirect(ListBusinessManagers::getUrl());
    }
}
