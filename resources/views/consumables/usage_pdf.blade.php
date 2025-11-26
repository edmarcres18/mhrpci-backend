<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>IT Consumables Usage</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px; }
        .brand { font-size: 12px; font-weight: 700; letter-spacing: 0.02em; color: #374151; }
        .title { font-size: 18px; font-weight: 700; color: #111827; margin: 2px 0 4px; }
        .meta { font-size: 11px; color: #6B7280; }
        .rule { height: 2px; background: #E5E7EB; margin: 8px 0 12px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; border: 1px solid #9CA3AF; }
        thead th { background: #F3F4F6; color: #111827; font-weight: 700; text-align: left; padding: 8px 10px; border: 1px solid #D1D5DB; }
        tbody td { padding: 8px 10px; vertical-align: top; border: 1px solid #D1D5DB; }
        tbody tr:nth-child(even) { background: #F9FAFB; }
        .col-name { width: 26%; }
        .col-purpose { width: 26%; }
        .col-user { width: 18%; }
        .col-date { width: 12%; }
        .col-qty { width: 10%; }
        .col-notes { width: 8%; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">{{ config('app.name') }}</div>
            <div class="title">IT Consumables Usage</div>
            <div class="meta">Generated {{ now()->format('Y-m-d H:i') }} • {{ count($items) }} records</div>
        </div>
    </div>
    <div class="rule"></div>

    <table>
        <thead>
            <tr>
                <th class="col-name">Consumable</th>
                <th class="col-purpose">Purpose</th>
                <th class="col-user">Used By</th>
                <th class="col-date">Date</th>
                <th class="col-qty">Qty</th>
                <th class="col-notes">Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td class="col-name">{{ optional($item->consumable)->consumable_name }}</td>
                    <td class="col-purpose">{{ $item->purpose }}</td>
                    <td class="col-user">{{ $item->used_by }}</td>
                    <td class="col-date">{{ optional($item->date_used)->format('Y-m-d') }}</td>
                    <td class="col-qty">{{ $item->quantity_used }}</td>
                    <td class="col-notes">{{ $item->notes }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 10;
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = $pdf->get_width() - $width - 28;
            $y = $pdf->get_height() - 24;
            $pdf->page_text($x, $y, $text, $font, $size, [0.42,0.45,0.5]);
        }
    </script>
</body>
</html>

