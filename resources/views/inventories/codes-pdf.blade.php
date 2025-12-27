<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Codes</title>
    <style>
        @page { margin: 10mm; size: letter portrait; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
        .page {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            grid-auto-rows: {{ $type === 'barcode' ? '135px' : '115px' }};
            gap: 4px;
            margin: 0 auto;
            page-break-after: auto;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 4px;
            text-align: center;
            box-sizing: border-box;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card img {
            width: {{ $type === 'barcode' ? '3in' : '1in' }};
            height: {{ $type === 'barcode' ? '1in' : '1in' }};
            display: block;
            object-fit: contain;
        }
    </style>
</head>
<body>
    @php
        $perPage = $type === 'barcode' ? 28 : 42; // first page only
        $pageItems = array_slice($items, 0, $perPage);
        $fillers = max(0, $perPage - count($pageItems));
    @endphp
    <div class="page">
        @foreach($pageItems as $item)
            <div class="card">
                @if($item['image'])
                    <img src="{{ $item['image'] }}" alt="{{ $item['item_code'] }}" />
                @endif
            </div>
        @endforeach
        @for($i = 0; $i < $fillers; $i++)
            <div class="card"></div>
        @endfor
    </div>
</body>
</html>
