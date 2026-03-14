<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_role(['super_admin']);

$pdo = db();
$error = null;

$allSections = $pdo->query(
    'SELECT id, slug, name
     FROM sections
     ORDER BY name ASC'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $role = trim((string) ($_POST['role'] ?? 'viewer'));
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sectionIds = array_map(
        'intval',
        $_POST['section_ids'] ?? []
    );

    $allowedRoles = ['super_admin', 'analyst', 'viewer'];

    if ($userId <= 0) {
        $error = 'Invalid user selected.';
    } elseif ($displayName === '') {
        $error = 'Display name is required.';
    } elseif (!in_array($role, $allowedRoles, true)) {
        $error = 'Invalid role selected.';
    }

    if ($error === null) {
        $updateSql = 'UPDATE admin_users
                      SET display_name = ?, role = ?, is_active = ?
                      WHERE id = ?';

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            $displayName,
            $role,
            $isActive,
            $userId,
        ]);

        $deleteSql = 'DELETE FROM user_sections WHERE user_id = ?';
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([$userId]);

        if ($role === 'analyst' && $sectionIds !== []) {
            $insertSql = 'INSERT INTO user_sections (user_id, section_id) VALUES (?, ?)';
            $insertStmt = $pdo->prepare($insertSql);

            foreach ($sectionIds as $sectionId) {
                $insertStmt->execute([$userId, $sectionId]);
            }
        }

        flash('User updated successfully.', 'success');
        redirect(base_url('admin-users.php'));
    }
}

$userRows = $pdo->query(
    'SELECT
        id,
        username,
        display_name,
        role,
        is_active,
        created_at
     FROM admin_users
     ORDER BY id ASC'
)->fetchAll();

$sectionMapRows = $pdo->query(
    'SELECT
        us.user_id,
        s.id AS section_id,
        s.slug,
        s.name
     FROM user_sections us
     INNER JOIN sections s ON s.id = us.section_id
     ORDER BY us.user_id ASC, s.name ASC'
)->fetchAll();

$userSections = [];
foreach ($sectionMapRows as $row) {
    $userId = (int) $row['user_id'];

    if (!isset($userSections[$userId])) {
        $userSections[$userId] = [];
    }

    $userSections[$userId][] = [
        'id' => (int) $row['section_id'],
        'slug' => (string) $row['slug'],
        'name' => (string) $row['name'],
    ];
}

render_header('User Management');
?>
<section class="card">
    <h2>User management</h2>
    <p>Super admins can update display names, roles, active status, and analyst section access.</p>

    <?php if ($error): ?>
        <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>
</section>

<?php foreach ($userRows as $userRow): ?>
    <?php
    $userId = (int) $userRow['id'];
    $assignedSections = $userSections[$userId] ?? [];
    $assignedSectionIds = array_map(
        static fn(array $section): int => (int) $section['id'],
        $assignedSections
    );
    ?>
    <section class="card">
        <h2><?= e((string) $userRow['username']) ?></h2>
        <p><strong>Created:</strong> <?= e((string) $userRow['created_at']) ?></p>

        <form method="post" class="stack-form">
            <input type="hidden" name="user_id" value="<?= $userId ?>">

            <label>
                Display name
                <input
                    type="text"
                    name="display_name"
                    value="<?= e((string) $userRow['display_name']) ?>"
                    required
                >
            </label>

            <label>
                Role
                <select name="role" required>
                    <option value="super_admin" <?= $userRow['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    <option value="analyst" <?= $userRow['role'] === 'analyst' ? 'selected' : '' ?>>Analyst</option>
                    <option value="viewer" <?= $userRow['role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                </select>
            </label>

            <label class="checkbox-row">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    <?= (int) $userRow['is_active'] === 1 ? 'checked' : '' ?>
                >
                <span>Active user</span>
            </label>

            <fieldset class="section-fieldset">
                <legend>Analyst section access</legend>

                <p class="fieldset-help">
                    These section assignments only apply when the user role is set to Analyst.
                </p>

                <div class="checkbox-group">
                    <?php foreach ($allSections as $section): ?>
                        <label class="checkbox-row">
                            <input
                                type="checkbox"
                                name="section_ids[]"
                                value="<?= (int) $section['id'] ?>"
                                <?= in_array((int) $section['id'], $assignedSectionIds, true) ? 'checked' : '' ?>
                            >
                            <span><?= e((string) $section['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <?php if ($assignedSections !== []): ?>
                <p>
                    <strong>Current assigned sections:</strong>
                    <?php
                    echo e(implode(
                        ', ',
                        array_map(
                            static fn(array $section): string => (string) $section['name'],
                            $assignedSections
                        )
                    ));
                    ?>
                </p>
            <?php else: ?>
                <p><strong>Current assigned sections:</strong> None</p>
            <?php endif; ?>

            <button class="button" type="submit">Update user</button>
        </form>
    </section>
<?php endforeach; ?>

<?php
render_footer();