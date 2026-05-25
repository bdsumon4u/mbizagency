@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="space-y-4">
    <div class="text-sm space-y-1">
        <p><strong>User:</strong> {{ $record->user?->name ?? 'N/A' }}</p>
        <p><strong>Payment Method:</strong> {{ $record->paymentMethod?->name ?? 'N/A' }}</p>
        <p><strong>Amount:</strong> {{ number_format((float) $record->amount, 2) }} BDT</p>
        <p><strong>Processing Fee:</strong> {{ number_format((float) $record->processing_fee, 2) }} BDT</p>
        <p><strong>Payable Amount:</strong> {{ number_format((float) $record->payable_amount, 2) }} BDT</p>
        @if($record->note)
            <p><strong>Note:</strong> {{ $record->note }}</p>
        @endif
    </div>

    @if($record->screenshots)
        <div class="space-y-4 mt-4">
            @foreach($record->screenshots as $screenshot)
                @php
                    $disk = Storage::disk('public');
                    $url = is_string($screenshot) && $disk->exists($screenshot) ? $disk->url($screenshot) : null;
                @endphp
                @if($url)
                    <div class="border rounded-lg overflow-hidden shadow-sm">
                        <img src="{{ $url }}" alt="Proof" class="w-full h-auto" />
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500 italic mt-4">No proof of payment uploaded.</p>
    @endif
</div>
