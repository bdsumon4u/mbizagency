<div style="display: flex; flex-direction: column; gap: 10px;">
    <details open style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
        <summary style="padding: 10px 12px; background: #f9fafb; font-size: 14px; font-weight: 600; cursor: pointer;">
            বর্তমান ডলার রেট
        </summary>
        <div style="padding: 2px;">
            <div>
            @if (blank($rates))
                <div style="font-size: 14px; color: #6b7280;">
                    No effective price rate found. Please contact admin.
                </div>
            @else
                <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 12px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead style="background: #f9fafb;">
                            <tr>
                                <th style="padding: 4px 6px; text-align: right; border-bottom: 1px solid #e5e7eb;">Min USD</th>
                                <th style="padding: 4px 6px; text-align: right; border-bottom: 1px solid #e5e7eb;">Dollar Rate</th>
                                <th style="padding: 4px 6px; text-align: center; border-bottom: 1px solid #e5e7eb;">Rate Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rates as $rate)
                                <tr style="background: {{ $rate['type'] === 'special' ? '#67ff97' : '#fff3cd' }}; font-weight: {{ $rate['type'] === 'special' ? 'bold' : 'normal' }};">
                                    <td style="padding: 4px 6px; border-top: 1px solid #e5e7eb; text-align: right;">${{ $rate['min_usd'] }}</td>
                                    <td style="padding: 4px 6px; border-top: 1px solid #e5e7eb; text-align: right;">BDT {{ $rate['dollar_rate'] }}</td>
                                    <td style="padding: 4px 6px; border-top: 1px solid #e5e7eb; text-align: center;">{{ $rate['display_type'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            </div>
        </div>
    </details>
</div>
