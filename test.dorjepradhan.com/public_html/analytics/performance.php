<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_role(['super_admin', 'analyst']);
require_section('performance');

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
     ORDER BY total_events DESC, unique_sessions DESC, page_url ASC
     LIMIT 8'
)->fetchAll();

$totalEvents = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$totalSessions = (int) $pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn();
$totalPages = (int) $pdo->query('SELECT COUNT(DISTINCT page_url) FROM events')->fetchColumn();

$pageLabels = array_map(
    static fn(array $row): string => (string) $row['page_url'],
    $pageRows
);

$pageValues = array_map(
    static fn(array $row): int => (int) $row['total_events'],
    $pageRows
);

render_header('Performance');
?>
<section class="stats-grid">
    <article class="stat-card">
        <span>Total events</span>
        <strong><?= number_format($totalEvents) ?></strong>
    </article>
    <article class="stat-card">
        <span>Total sessions</span>
        <strong><?= number_format($totalSessions) ?></strong>
    </article>
    <article class="stat-card">
        <span>Tracked pages</span>
        <strong><?= number_format($totalPages) ?></strong>
    </article>
</section>

<section class="card">
    <h2>Performance overview</h2>
    <p>This category focuses on which pages are generating the most tracked activity and how broadly that activity is distributed across sessions.</p>
</section>

<section class="card">
    <h2>Top pages by total events</h2>
    <canvas id="performancePagesChart" height="120"></canvas>
</section>

<section class="card">
    <h2>Page performance table</h2>
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
    <h2>Analyst interpretation</h2>
    <p>The strongest-performing pages are the ones with the highest total event counts and repeat session activity. This view helps identify which pages are receiving the most interaction and whether usage is concentrated on a small number of pages or spread more broadly across the site.</p>
</section>

<script>
const performanceLabels = <?= json_encode($pageLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const performanceValues = <?= json_encode($pageValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

new Chart(document.getElementById('performancePagesChart'), {
    type: 'bar',
    data: {
        labels: performanceLabels,
        datasets: [{
            label: 'Total events',
            data: performanceValues,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Event count'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Page URL'
                }
            }
        }
    }
});
</script>
<?php
render_footer();