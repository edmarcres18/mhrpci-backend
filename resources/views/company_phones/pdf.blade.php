<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Company Phones Directory</title>
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
        .col-dept { width: 24%; }
        .col-phone { width: 24%; }
        .col-person { width: 26%; }
        .col-position { width: 16%; }
        .col-ext { width: 10%; }
        .footer { position: fixed; bottom: 10px; left: 0; right: 0; text-align: right; font-size: 11px; color: #6B7280; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">{{ config('app.name') }}</div>
            <div class="title">Company Phones Directory</div>
            <div class="meta">Generated {{ now()->format('Y-m-d H:i') }} • {{ count($items) }} records</div>
        </div>
    </div>
    <div class="rule"></div>

    <table>
        <thead>
            <tr>
                <th class="col-dept">Department</th>
                <th class="col-phone">Phone Number</th>
                <th class="col-person">Person In Charge</th>
                <th class="col-position">Position</th>
                <th class="col-ext">Ext.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td class="col-dept">{{ $item->department }}</td>
                    <td class="col-phone">{{ $item->phone_number }}</td>
                    <td class="col-person">{{ $item->person_in_charge }}</td>
                    <td class="col-position">{{ $item->position }}</td>
                    <td class="col-ext">{{ $item->extension }}</td>
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

