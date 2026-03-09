<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

logout_user();
session_start();
flash('You have been logged out.', 'success');
redirect(base_url('login.php'));
