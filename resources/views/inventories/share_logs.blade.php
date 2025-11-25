<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Access Logs</title>
    <style>
        :root { --fg:#0a0a0a; --muted:#6b7280; --border:#e5e7eb; --soft:#f9fafb; }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Noto Sans', 'Helvetica Neue', Arial; margin:0; padding:24px; color:var(--fg); }
        .container { max-width: 1100px; margin:0 auto; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
        .title { font-size:22px; font-weight:700; }
        .muted { color:var(--muted); }
        .nav { display:flex; gap:8px; }
        .btn { appearance:none; border:1px solid var(--border); background:#fff; color:var(--fg); border-radius:8px; padding:8px 12px; font-size:12px; cursor:pointer; }
        .table-wrap { width:100%; overflow:auto; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        thead th { text-align:left; font-weight:600; color:var(--muted); border-bottom:1px solid var(--border); padding:10px 8px; white-space:nowrap; }
        tbody td { border-bottom:1px solid var(--border); padding:12px 8px; vertical-align:top; }
        tbody tr:nth-child(odd) { background:#fafafa; }
        .badge { display:inline-block; font-size:12px; color:#111827; background:#e5e7eb; border-radius:999px; padding:2px 8px; }
        .ok { background:#d1fae5; }
        .err { background:#fee2e2; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <div class="title">Share Access Logs</div>
                <div class="muted">Recent access events</div>
            </div>
            <div class="nav">
                <a class="btn" href="/inventories">Back to Inventories</a>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Scope</th>
                        <th>Accountable(s)</th>
                        <th>Email</th>
                        <th>IP</th>
                        <th>User Agent</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $log->share->scope }}</td>
                            <td>
                                @if ($log->share->scope === 'single')
                                    {{ $log->share->inventory_accountable }}
                                @elseif ($log->share->scope === 'multiple')
                                    {{ implode(', ', (array) $log->share->accountable_list) }}
                                @else
                                    All
                                @endif
                            </td>
                            <td>{{ $log->email }}</td>
                            <td>{{ $log->ip }}</td>
                            <td>{{ $log->user_agent }}</td>
                            <td>
                                @if ($log->success)
                                    <span class="badge ok">Allowed</span>
                                @else
                                    <span class="badge err">Denied</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
