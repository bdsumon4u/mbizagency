<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\View;

class Dashboard extends BaseDashboard
{
    public $defaultAction = 'notice';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function noticeAction(): Action
    {
        return Action::make('notice')
            ->modalHeading('')
            ->modalWidth(Width::Medium)
            ->modalContent(View::make('filament.pages.partials.notice-modal-content'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalAlignment('center')
            ->extraModalWindowAttributes(['style' => 'margin-top: 3rem;'])
            ->modalIcon(false)
            ->action(fn () => null);
    }
}
