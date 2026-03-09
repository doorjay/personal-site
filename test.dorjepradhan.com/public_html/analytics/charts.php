<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_login();

render_header('Charts');
?>
<section class="card">
    <h2>Charts from MariaDB</h2>
    <p>The charts below pull aggregated data from protected PHP API endpoints and render them with Chart.js.</p>
    <canvas id="eventsByTypeChart" height="120"></canvas>
</section>

<section class="card">
    <h2>Top pages by event volume</h2>
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
            scales: {
                y: {
                    beginAtZero: true
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
            'Events'
        );

        buildChart(
            'eventsByPageChart',
            'line',
            payload.eventsByPage.labels,
            payload.eventsByPage.values,
            'Events'
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
