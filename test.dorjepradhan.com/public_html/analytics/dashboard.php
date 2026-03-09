<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_login();

$pdo = db();
$totals = [
    'events' => 0,
    'sessions' => 0,
    'pages' => 0,
];

$totals['events'] = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$totals['sessions'] = (int) $pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn();
$totals['pages'] = (int) $pdo->query('SELECT COUNT(DISTINCT page_url) FROM events')->fetchColumn();

$latestEvents = $pdo->query(
    'SELECT e.ts, e.event_type, e.page_url, s.session_key
     FROM events e
     INNER JOIN sessions s ON s.id = e.session_id
     ORDER BY e.id DESC
     LIMIT 8'
)->fetchAll();

render_header('Dashboard');
?>
<section class="stats-grid">
    <article class="stat-card">
        <span>Total events</span>
        <strong><?= number_format($totals['events']) ?></strong>
    </article>
    <article class="stat-card">
        <span>Total sessions</span>
        <strong><?= number_format($totals['sessions']) ?></strong>
    </article>
    <article class="stat-card">
        <span>Tracked pages</span>
        <strong><?= number_format($totals['pages']) ?></strong>
    </article>
</section>

<section class="card">
    <h2>Checkpoint map</h2>
    <ul>
        <li><strong>Authentication:</strong> this dashboard, the reports page, and the charts page all require login.</li>
        <li><strong>Datastore to table:</strong> the Reports Table page reads live rows from MariaDB.</li>
        <li><strong>Datastore to chart:</strong> the Charts page renders Chart.js charts using MariaDB data.</li>
    </ul>
</section>

<section class="card">
    <h2>Recent events</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Type</th>
                    <th>Page URL</th>
                    <th>Session Key</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latestEvents as $event): ?>
                    <tr>
                        <td><?= e((string) $event['ts']) ?></td>
                        <td><?= e((string) $event['event_type']) ?></td>
                        <td class="mono"><?= e((string) $event['page_url']) ?></td>
                        <td class="mono"><?= e(substr((string) $event['session_key'], 0, 16)) ?>...</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
render_footer();
