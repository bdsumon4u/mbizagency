@php
    $screenshotUrl = null;

    if (filled($record->screenshot)) {
        $screenshotUrl = str_starts_with($record->screenshot, 'http')
            ? $record->screenshot
            : \Illuminate\Support\Facades\Storage::url($record->screenshot);
    }
@endphp

<div class="space-y-4">
    <div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Screenshot</h3>
        @if ($screenshotUrl)
            <img
                src="{{ $screenshotUrl }}"
                alt="Order screenshot"
                class="mt-2 max-h-96 w-full rounded-lg border border-gray-200 object-contain dark:border-gray-700"
            />
        @else
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No screenshot uploaded.</p>
        @endif
    </div>

    <div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Note</h3>
        <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
            {{ $record->note ?: 'No note provided.' }}
        </p>
    </div>
</div>
