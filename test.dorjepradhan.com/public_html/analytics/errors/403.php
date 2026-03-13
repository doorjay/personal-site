<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

render_header('403 Forbidden');
?>
<section class="card narrow-card">
    <h2>Access denied</h2>
    <p>You do not have permission to view this page.</p>
    <p><a class="button" href="<?= e(base_url('dashboard.php')) ?>">Back to dashboard</a></p>
</section>
<?php
render_footer();