<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Codes</title>
    <style>
        @page { margin: 8mm; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #111; }
        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 2mm;
        }
        .card {
            border: 0.3mm solid #ddd;
            border-radius: 1.5mm;
            padding: 2mm;
            text-align: center;
            box-sizing: border-box;
            background: #fff;
            height: 33mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            break-inside: avoid;
        }
        .card img {
            width: 25.4mm;  /* 1 inch */
            height: 25.4mm; /* 1 inch */
            display: block;
            margin: 1mm auto 0;
            object-fit: contain;
        }
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
