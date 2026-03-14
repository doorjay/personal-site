<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_role(['super_admin', 'analyst']);
require_section('behavior');

$pdo = db();

$typeRows = $pdo->query(
    'SELECT
        event_type,
        COUNT(*) AS total_events,
        COUNT(DISTINCT session_id) AS unique_sessions
     FROM events
     GROUP BY event_type
     ORDER BY total_events DESC, event_type ASC'
)->fetchAll();

$elementRows = $pdo->query(
    'SELECT
        element,
        COUNT(*) AS total_events
     FROM events
     WHERE element IS NOT NULL
       AND TRIM(element) <> ""
     GROUP BY element
     ORDER BY total_events DESC, element ASC
     LIMIT 8'
)->fetchAll();

$typeLabels = array_map(
    static fn(array $row): string => (string) $row['event_type'],
    $typeRows
);

$typeValues = array_map(
    static fn(array $row): int => (int) $row['total_events'],
    $typeRows
);

render_header('Behavior');
?>
<section class="card">
    <h2>Behavior overview</h2>
    <p>This category focuses on the kinds of tracked actions users are taking, including which event types happen most often and which page elements receive interaction.</p>
</section>

<section class="card">
    <h2>Event counts by type</h2>
    <canvas id="behaviorEventTypeChart" height="120"></canvas>
</section>

<section class="card">
    <h2>Event type summary</h2>
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

<section class="card">
    <h2>Top interacted elements</h2>
    <?php if (!$elementRows): ?>
        <p>No element interaction data is available yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Element</th>
                        <th>Total events</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($elementRows as $row): ?>
                        <tr>
                            <td class="mono"><?= e((string) $row['element']) ?></td>
                            <td><?= number_format((int) $row['total_events']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Analyst interpretation</h2>
    <p>This behavior view highlights which interaction types are most common and whether users are engaging with specific tracked elements. It helps distinguish broad activity volume from the actual kinds of actions users are taking inside the site experience.</p>
</section>

<script>
const behaviorLabels = <?= json_encode($typeLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const behaviorValues = <?= json_encode($typeValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

new Chart(document.getElementById('behaviorEventTypeChart'), {
    type: 'bar',
    data: {
        labels: behaviorLabels,
        datasets: [{
            label: 'Total events',
            data: behaviorValues,
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
                    text: 'Event type'
                }
            }
        }
    }
});
</script>
<?php
render_footer();