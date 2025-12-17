<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>IT Inventories — {{ isset($accountable) ? $accountable : 'All Accountables' }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px; }
        .brand { font-size: 12px; font-weight: 700; letter-spacing: 0.02em; color: #374151; }
        .title { font-size: 18px; font-weight: 700; color: #111827; margin: 2px 0 4px; }
        .meta { font-size: 11px; color: #6B7280; }
        .rule { height: 2px; background: #E5E7EB; margin: 8px 0 12px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; border: 1px solid #9CA3AF; }
        thead th {
            background: #F3F4F6;
            color: #111827;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #D1D5DB;
        }
        tbody td {
            padding: 8px 10px;
            vertical-align: top;
            border: 1px solid #D1D5DB;
        }
        tbody tr:nth-child(even) { background: #F9FAFB; }
        .col-code { width: 20%; }
        .col-name { width: 25%; }
        .col-spec { width: 30%; }
        .col-brand { width: 13%; }
        .col-status { width: 12%; }
        .status { font-weight: 600; color: #1F2937; }
        .footer { position: fixed; bottom: 10px; left: 0; right: 0; text-align: right; font-size: 11px; color: #6B7280; }
    </style>
</head>
<body>
    @if(isset($groups))
        @foreach($groups as $accountable => $items)
            <div class="header">
                <div>
                    <div class="brand">{{ config('app.name') }}</div>
                    <div class="title">IT Inventories — {{ $accountable }}</div>
                    <div class="meta">Generated {{ now()->format('Y-m-d H:i') }} • {{ $items->count() }} items</div>
                </div>
            </div>
            <div class="rule"></div>

            <table>
                <thead>
                    <tr>
                        <th class="col-code">Item Code</th>
                        <th class="col-name">Name</th>
                        <th class="col-spec">Specification</th>
                        <th class="col-brand">Brand</th>
                        <th class="col-status">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td class="col-code">{{ $item->item_code }}</td>
                            <td class="col-name">{{ $item->inventory_name }}</td>
                            <td class="col-spec">{{ $item->inventory_specification }}</td>
                            <td class="col-brand">{{ $item->inventory_brand }}</td>
                            <td class="col-status status">{{ $item->inventory_status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if(!$loop->last)
                <div style="page-break-after: always;"></div>
            @endif
        @endforeach
    @else
        <div class="header">
            <div>
                <div class="brand">{{ config('app.name') }}</div>
                <div class="title">IT Inventories — {{ $accountable }}</div>
                <div class="meta">Generated {{ now()->format('Y-m-d H:i') }} • {{ $items->count() }} items</div>
            </div>
        </div>
        <div class="rule"></div>

        <table>
            <thead>
                <tr>
                    <th class="col-code">Item Code</th>
                    <th class="col-name">Name</th>
                    <th class="col-spec">Specification</th>
                    <th class="col-brand">Brand</th>
                    <th class="col-status">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td class="col-code">{{ $item->item_code }}</td>
                        <td class="col-name">{{ $item->inventory_name }}</td>
                        <td class="col-spec">{{ $item->inventory_specification }}</td>
                        <td class="col-brand">{{ $item->inventory_brand }}</td>
                        <td class="col-status status">{{ $item->inventory_status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

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
