<div {{ $getExtraAttributeBag() }}>
    <div class="fi-ta-text-has-descriptions fi-ta-text">
        <p class="fi-size-sm  fi-ta-text-item">
            {{ $getState() }}
        </p>
        @php ($act_id = $getRecord()->act_id ?? $getRecord()->adAccount?->act_id)
        <p class="fi-size-sm  fi-ta-text-description">
            <a class="hover:underline flex items-center gap-1" href="https://business.facebook.com/adsmanager/manage/accounts?act={{ $act_id }}" target="_blank" rel="noopener noreferrer">
                {{ $act_id }}<x-heroicon-s-arrow-top-right-on-square class="h-4 w-4 text-blue-600" />
            </a>
        </p>
    </div>
</div>
