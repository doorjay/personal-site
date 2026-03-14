<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

http_response_code(404);

render_header('404 Not Found');
?>
<section class="card narrow-card">
    <h2>Page not found</h2>
    <p>The page or report you requested could not be found.</p>
    <p>
        <a class="button" href="<?= e(base_url('saved-reports.php')) ?>">Go to Saved Reports</a>
    </p>
</section>
<?php
render_footer();