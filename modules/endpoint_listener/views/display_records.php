<!doctype html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="utf-8">
    <title>Endpoint Listener Log</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/trongate.css">
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; padding: 1.5rem; background: #f7f7f7; background-color: #4682b455; }
        h1 { font-size: 33px; margin-top: 0; text-align:center; }
        table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        th, td { padding: .75rem .5rem; border: 1px solid #e0e0e0; text-align: left; }
        th { background: #333; color: #eee; font-weight: 600; }
        tr:nth-child(even) { background: #fafbfc; }
        .no-wrap { white-space: nowrap; }
        /* <details> tweaks */
        details summary { cursor: pointer; font-weight: 600; }
        details pre { margin: .5rem 0 0; white-space: pre-wrap; word-break: break-word; font-size: .8em; }
        button { padding: 0; min-height:24px; min-width:24px; }
        .payload-header { display: inline-flex; align-items: center; gap: 0.5rem; }
    </style>
</head>
<body>
    <h1>Endpoint Listener Log</h1>
    <p class="text-center"><?= anchor('endpoint_listener/clear', 'Clear Records', array('class' => 'button')) ?></p>
    <?php if (empty($rows)): ?>
        <p class="text-center">No records found.</p>
    <?php else: ?>
        <table class="sm">
            <thead>
                <tr>
                    <th class="no-wrap">ID</th>
                    <th>URL</th>
                    <th class="no-wrap">Date</th>
                    <th class="no-wrap">Method</th>
                    <th class="no-wrap">IP</th>
                    <th>User-Agent</th>
                    <th>Payload / Headers</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="no-wrap"><?= htmlspecialchars((string) $row->id) ?></td>
                    <td class="url-cell">
                        <button type="button" class="copy-btn mt-0 xs" title="Copy URL">📋</button>
                     <?= anchor($row->url, htmlspecialchars($row->url), array('target' => '_blank')) ?></td>
                    <td class="no-wrap"><?= date('Y-m-d H:i:s', (int) $row->date_created) ?></td>
                    <td class="no-wrap"><?= htmlspecialchars($row->request_type) ?></td>
                    <td class="no-wrap"><?= htmlspecialchars($row->ip_address) ?></td>
                    <td><?= htmlspecialchars(substr($row->user_agent ?? '', 0, 80)) ?></td>
                    <td>
                        <details>
                            <summary>Expand</summary>

                            <?php if ($row->payload): ?>
                                <div class="mt-1 payload-header">
                                    <strong>Payload:</strong>
                                    <button type="button" class="copy-payload-btn mt-0 xs" data-payload="<?= htmlspecialchars($row->payload) ?>" title="Copy Payload">📋</button>
                                </div>
                                <?php json(json_decode($row->payload, true)); ?>
                            <?php endif; ?>

                            <?php if ($row->headers): ?>
                                <div class="mt-1"><strong>Headers:</strong></div>
                                <?php json(json_decode($row->headers, true)); ?>
                            <?php endif; ?>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<script>
document.addEventListener('click', function (e) {
    // Copy URL button
    if (e.target.matches('.copy-btn')) {
        const urlText = e.target.parentNode.querySelector('a').textContent.trim();
        navigator.clipboard.writeText(urlText)
            .then(() => {
                const original = e.target.textContent;
                e.target.textContent = '✅';
                setTimeout(() => e.target.textContent = original, 1200);
            })
            .catch(err => alert('Could not copy: ' + err));
    }

    // Copy payload button
    if (e.target.matches('.copy-payload-btn')) {
        const payloadData = e.target.getAttribute('data-payload');
        navigator.clipboard.writeText(payloadData)
            .then(() => {
                const original = e.target.textContent;
                e.target.textContent = '✅';
                setTimeout(() => e.target.textContent = original, 1200);
            })
            .catch(err => alert('Could not copy payload: ' + err));
    }
});
</script>

</body>
</html>
