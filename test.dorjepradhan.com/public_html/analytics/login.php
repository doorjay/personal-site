<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

if (is_logged_in()) {
    redirect(base_url('dashboard.php'));
}

$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (login_attempt($username, $password)) {
        flash('Login successful.', 'success');
        redirect(base_url('dashboard.php'));
    }

    $error = 'Invalid username or password.';
}

render_header('Login');
?>
<section class="card narrow-card">
    <p>Use this page to access the protected analytics backend. Direct access to the protected pages will redirect here unless you are signed in.</p>

    <?php if ($error): ?>
        <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="stack-form">
        <label>
            Username
            <input type="text" name="username" value="<?= e($username) ?>" required>
        </label>
        <label>
            Password
            <input type="password" name="password" required>
        </label>
        <button class="button" type="submit">Log in</button>
    </form>
</section>
<?php
render_footer();
