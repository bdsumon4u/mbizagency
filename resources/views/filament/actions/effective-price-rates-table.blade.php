<div>
    <div style="margin-bottom: 8px; font-size: 14px; font-weight: 600;">
        Effective Price Rates
    </div>

    @if (blank($rates))
        <div style="font-size: 14px; color: #6b7280;">
            No effective price rate found. Please contact admin.
        </div>
    @else
        <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 12px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 8px 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">Rate Type</th>
                        <th style="padding: 8px 12px; text-align: right; border-bottom: 1px solid #e5e7eb;">Min USD</th>
                        <th style="padding: 8px 12px; text-align: right; border-bottom: 1px solid #e5e7eb;">Dollar Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rates as $rate)
                        <tr>
                            <td style="padding: 8px 12px; border-top: 1px solid #e5e7eb;">{{ $rate['type'] }}</td>
                            <td style="padding: 8px 12px; border-top: 1px solid #e5e7eb; text-align: right;">{{ $rate['min_usd'] }}</td>
                            <td style="padding: 8px 12px; border-top: 1px solid #e5e7eb; text-align: right;">{{ $rate['dollar_rate'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
