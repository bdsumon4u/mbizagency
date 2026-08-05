<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class Dashboard extends BaseDashboard
{
    public $defaultAction = 'notice';

    public function mount(): void
    {
        if (Cache::has('notice_modal_dismissed_'.(auth()->id() ?? session()->getId()))) {
            $this->defaultAction = null;
        }
    }

    protected function recordNoticeDismissed(): void
    {
        Cache::put('notice_modal_dismissed_'.(auth()->id() ?? session()->getId()), true, now()->addHours(24));
    }

    public function unmountAction(bool $canCancelParentActions = true): void
    {
        $mountedAction = $this->getMountedAction();

        if ($mountedAction?->getName() === 'notice') {
            $this->recordNoticeDismissed();
        }

        parent::unmountAction($canCancelParentActions);
    }

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
            ->action(fn () => $this->recordNoticeDismissed());
    }
}
