<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_login();
require_role(['super_admin', 'analyst']);

render_header('Charts');
?>
<section class="card">
    <h2>Event counts by type</h2>
    <p>his bar chart shows how many tracked events were recorded for each event type in the MariaDB <i>events</i> table.</p>
    <canvas id="eventsByTypeChart" height="120"></canvas>
</section>

<section class="card">
    <h2>Top pages by total events</h2>
    <p>This bar chart shows which tracked page URLs generated the highest number of recorded events.</p>
    <canvas id="eventsByPageChart" height="120"></canvas>
</section>

<script>
async function loadChartData() {
    const response = await fetch('<?= e(base_url('api/chart-data.php')) ?>', {
        credentials: 'same-origin'
    });

    if (!response.ok) {
        throw new Error('Failed to load chart data');
    }

    return response.json();
}

function buildChart(elementId, chartType, labels, values, label) {
    const ctx = document.getElementById(elementId);
    if (!ctx) {
        return;
    }

    new Chart(ctx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: values,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true
                },
                tooltip: {
                    enabled: true
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
                        text: chartType === 'bar' && elementId === 'eventsByTypeChart'
                            ? 'Event type'
                            : 'Page URL'
                    }
                }
            }
        }
    });
}

loadChartData()
    .then(function (payload) {
        buildChart(
            'eventsByTypeChart',
            'bar',
            payload.eventsByType.labels,
            payload.eventsByType.values,
            'Number of events'
        );

        buildChart(
            'eventsByPageChart',
            'bar',
            payload.eventsByPage.labels,
            payload.eventsByPage.values,
            'Total events per page'
        );
    })
    .catch(function (error) {
        console.error(error);
        const sections = document.querySelectorAll('.card');
        sections.forEach(function (section) {
            const msg = document.createElement('p');
            msg.textContent = 'Unable to load chart data.';
            section.appendChild(msg);
        });
    });
</script>
<?php
render_footer();
