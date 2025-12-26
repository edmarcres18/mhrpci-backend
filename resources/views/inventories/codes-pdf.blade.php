<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Codes</title>
    <style>
        @page { margin: 16mm; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        .meta { font-size: 11px; margin-bottom: 10px; color: #333; }
        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .card {
            width: 100px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            box-sizing: border-box;
            background: #fff;
        }
        .card img {
            width: 1in;
            height: 1in;
            display: block;
            margin: 6px auto 10px;
            object-fit: contain;
        }
        .footer { font-size: 10px; color: #444; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="grid">
        @foreach($items as $item)
            <div class="card">
                @if($item['image'])
                    <img src="{{ $item['image'] }}" alt="{{ $item['item_code'] }}" />
                @endif
            </div>
        @endforeach
    </div>
</body>
</html>
