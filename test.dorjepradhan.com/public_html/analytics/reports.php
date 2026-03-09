<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_login();

$pdo = db();

$pageRows = $pdo->query(
    'SELECT
        page_url,
        COUNT(*) AS total_events,
        COUNT(DISTINCT session_id) AS unique_sessions,
        MIN(ts) AS first_event,
        MAX(ts) AS last_event
     FROM events
     GROUP BY page_url
     ORDER BY total_events DESC, page_url ASC'
)->fetchAll();

$typeRows = $pdo->query(
    'SELECT
        event_type,
        COUNT(*) AS total_events,
        COUNT(DISTINCT session_id) AS unique_sessions
     FROM events
     GROUP BY event_type
     ORDER BY total_events DESC, event_type ASC'
)->fetchAll();

render_header('Reports Table');
?>
<section class="card">
    <h2>Page activity summary</h2>
    <p>This table is rendered from the existing <code>events</code> table in MariaDB.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Page URL</th>
                    <th>Total events</th>
                    <th>Unique sessions</th>
                    <th>First event</th>
                    <th>Last event</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pageRows as $row): ?>
                    <tr>
                        <td class="mono"><?= e((string) $row['page_url']) ?></td>
                        <td><?= number_format((int) $row['total_events']) ?></td>
                        <td><?= number_format((int) $row['unique_sessions']) ?></td>
                        <td><?= e((string) $row['first_event']) ?></td>
                        <td><?= e((string) $row['last_event']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Event types summary</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Event type</th>
                    <th>Total events</th>
                    <th>Unique sessions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($typeRows as $row): ?>
                    <tr>
                        <td><?= e((string) $row['event_type']) ?></td>
                        <td><?= number_format((int) $row['total_events']) ?></td>
                        <td><?= number_format((int) $row['unique_sessions']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
render_footer();
