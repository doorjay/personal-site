<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_role(['super_admin', 'analyst']);
require_section('system_health');

$pdo = db();

$dailyRows = $pdo->query(
    'SELECT
        DATE(ts) AS event_day,
        COUNT(*) AS total_events,
        COUNT(DISTINCT session_id) AS unique_sessions
     FROM events
     GROUP BY DATE(ts)
     ORDER BY event_day ASC'
)->fetchAll();

$missingRows = $pdo->query(
    'SELECT
        SUM(CASE WHEN element IS NULL OR TRIM(element) = "" THEN 1 ELSE 0 END) AS missing_element_count,
        SUM(CASE WHEN value IS NULL OR TRIM(value) = "" THEN 1 ELSE 0 END) AS missing_value_count,
        SUM(CASE WHEN extra IS NULL THEN 1 ELSE 0 END) AS missing_extra_count
     FROM events'
)->fetch();

$totalEvents = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$totalSessions = (int) $pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn();

$dailyLabels = array_map(
    static fn(array $row): string => (string) $row['event_day'],
    $dailyRows
);

$dailyValues = array_map(
    static fn(array $row): int => (int) $row['total_events'],
    $dailyRows
);

render_header('System Health');
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
        <span>Days with event data</span>
        <strong><?= number_format(count($dailyRows)) ?></strong>
    </article>
</section>

<section class="card">
    <h2>System health overview</h2>
    <p>This category focuses on collection reliability over time, including daily event volume and whether tracked records are missing expected fields.</p>
</section>

<section class="card">
    <h2>Events collected per day</h2>
    <canvas id="systemHealthDailyChart" height="120"></canvas>
</section>

<section class="card">
    <h2>Daily collection summary</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Total events</th>
                    <th>Unique sessions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dailyRows as $row): ?>
                    <tr>
                        <td><?= e((string) $row['event_day']) ?></td>
                        <td><?= number_format((int) $row['total_events']) ?></td>
                        <td><?= number_format((int) $row['unique_sessions']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Missing-field checks</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Check</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Missing element field</td>
                    <td><?= number_format((int) ($missingRows['missing_element_count'] ?? 0)) ?></td>
                </tr>
                <tr>
                    <td>Missing value field</td>
                    <td><?= number_format((int) ($missingRows['missing_value_count'] ?? 0)) ?></td>
                </tr>
                <tr>
                    <td>Missing extra field</td>
                    <td><?= number_format((int) ($missingRows['missing_extra_count'] ?? 0)) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Analyst interpretation</h2>
    <p>This system health view helps confirm that analytics collection is happening consistently over time and highlights whether important tracked fields are frequently empty. A sudden drop in daily event counts or a spike in missing values would suggest a tracking or instrumentation issue worth reviewing.</p>
</section>

<script>
const systemHealthLabels = <?= json_encode($dailyLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const systemHealthValues = <?= json_encode($dailyValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

new Chart(document.getElementById('systemHealthDailyChart'), {
    type: 'line',
    data: {
        labels: systemHealthLabels,
        datasets: [{
            label: 'Total events per day',
            data: systemHealthValues,
            borderWidth: 2,
            tension: 0.2
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
                    text: 'Date'
                }
            }
        }
    }
});
</script>
<?php
render_footer();