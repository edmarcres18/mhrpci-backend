<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Inventories - Share</title>
    <style>
        :root { --fg:#0a0a0a; --muted:#6b7280; --border:#e5e7eb; --bg:#ffffff; --soft:#f9fafb; --accent:#16a34a; }
        html,body { height: 100%; }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Noto Sans', 'Helvetica Neue', Arial, 'Apple Color Emoji', 'Segoe UI Emoji'; margin: 0; padding: 0; color: var(--fg); background: var(--bg); }
        .wm { position: fixed; inset: 0; pointer-events: none; z-index: 0; opacity: 0.06; display: grid; place-items: center; }
        .wm > div { transform: rotate(-25deg); font-size: 64px; font-weight: 700; letter-spacing: 8px; white-space: nowrap; }
        .wrap { position: relative; z-index: 1; padding: 24px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .brand { display:flex; align-items:center; gap:12px; }
        .badge { display:inline-flex; align-items:center; gap:8px; font-size:12px; color:var(--muted); padding:4px 10px; border:1px solid var(--border); border-radius:999px; background: var(--soft); }
        .title { font-size: 22px; font-weight: 700; letter-spacing:.2px; }
        .muted { color: var(--muted); font-size: 14px; }
        .card { border: 1px solid var(--border); border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.04); background: var(--bg); }
        .card-header { background: var(--soft); border-bottom: 1px solid var(--border); padding: 16px 20px; display:flex; justify-content: space-between; align-items:center; }
        .card-title { font-size:18px; font-weight:600; }
        .card-actions { display:flex; gap:8px; align-items:center; }
        .btn { appearance:none; border:1px solid var(--border); background:#fff; color:var(--fg); border-radius:8px; padding:8px 12px; font-size:12px; cursor:pointer; }
        .btn:disabled { opacity: .6; cursor: not-allowed; }
        .card-content { padding: 0 20px 16px; }
        .table-wrap { width:100%; overflow:auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; line-height:1.4; }
        thead th { text-align: left; font-weight: 600; color: var(--muted); border-bottom:1px solid var(--border); padding: 10px 8px; white-space: nowrap; }
        tbody td { border-bottom:1px solid var(--border); padding: 12px 8px; vertical-align: top; }
        tbody tr:nth-child(odd) { background:#fafafa; }
        .status { display:inline-flex; align-items:center; gap:6px; }
        .status i { width: 12px; height: 12px; border-radius: 50%; display:inline-block; }
        .status-active i { background: var(--accent); }
        .status-inactive i { background: #ef4444; }
        details summary { list-style:none; cursor:pointer; }
        details summary::-webkit-details-marker { display:none; }
        .summary { display:flex; align-items:center; justify-content:space-between; }
        .summary .left { display:flex; flex-direction:column; gap:4px; }
        .summary .right { display:flex; align-items:center; gap:8px; }
        .footer-note { margin-top: 24px; font-size: 12px; color: var(--muted); text-align: center; }
    </style>
    <script>
        function toggleDetails(id){ var d=document.getElementById(id); if(d) d.open=!d.open; }
    </script>
</head>
<body>
    <div class="wm"><div>{{ config('app.name') }} • Shared Link</div></div>
    <div class="wrap">
        <div class="container">
            <div class="header">
                <div class="brand">
                    <div class="title">{{ config('app.name') }}</div>
                    <span class="badge">Shared • Read-only</span>
                </div>
                <div class="muted">Generated {{ now()->format('Y-m-d H:i') }}</div>
            </div>
            <div class="header" style="margin-top:-12px;">
                <div class="brand" style="gap:8px;">
                    <a class="btn" href="/">Back to site</a>
                    <a class="btn" href="/inventories/share-logs">Access logs</a>
                </div>
            </div>
            @foreach ($groups as $index => $group)
                <div class="card" style="margin-bottom: 16px;">
                    <div class="card-header">
                        <div class="card-title">{{ $group['accountable'] }}</div>
                        <div class="card-actions">
                            <span class="badge">Items: {{ count($group['items']) }}</span>
                            <button class="btn" onclick="toggleDetails('acc-{{ $index }}')">Show</button>
                        </div>
                    </div>
                    <div class="card-content">
                        <details id="acc-{{ $index }}" open>
                            <summary class="summary">
                                <div class="left">
                                    <div class="muted">Inventory items</div>
                                </div>
                                <div class="right">
                                    <span class="badge">Accountable: {{ $group['accountable'] }}</span>
                                </div>
                            </summary>
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Specification</th>
                                            <th>Brand</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($group['items'] as $item)
                                            <tr>
                                                <td>{{ $item->inventory_name }}</td>
                                                <td>{{ $item->inventory_specification }}</td>
                                                <td>{{ $item->inventory_brand }}</td>
                                                <td>
                                                    @php $st = strtolower((string) $item->inventory_status); @endphp
                                                    <span class="status status-{{ $st === 'active' ? 'active' : 'inactive' }}"><i></i>{{ $item->inventory_status }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    </div>
                </div>
            @endforeach
            <div class="footer-note">This is a shared, read-only view. Content may be subject to change by administrators.</div>
        </div>
    </div>
</body>
</html>
