<?php

namespace App\Filament\Admin\Resources\BusinessManagers\Pages;

use App\Filament\Admin\Resources\BusinessManagers\BusinessManagerResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FacebookOAuthRedirect extends Page
{
    protected static string $resource = BusinessManagerResource::class;

    public function mount(): void
    {
        $appId = (string) config('services.facebook.app_id');
        $redirectUri = route('facebook.oauth.callback');

        if ($appId === '') {
            Notification::make()
                ->title('Facebook App ID is not configured.')
                ->danger()
                ->send();

            $this->redirect(ListBusinessManagers::getUrl(), true);
        }

        $state = Str::random(40);
        request()->session()->put('facebook_oauth_state', $state);
        Cache::put('facebook_oauth_state:'.$state, true, now()->addMinutes(10));

        $scopes = array_values(array_filter(array_map(
            static fn (mixed $scope): string => mb_trim((string) $scope),
            (array) config('services.facebook.oauth_scopes', ['business_management', 'ads_management'])
        )));

        $query = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'state' => $state,
        ]);

        $this->redirect("https://www.facebook.com/v21.0/dialog/oauth?{$query}");
    }
}
