<?php
require __DIR__ . '/config.php';

$user = current_user();
if (!$user) {
    flash('error', 'Please log in to continue.');
    redirect('login.php');
}

$title = 'Dashboard';
require __DIR__ . '/partials/auth_top.php';
?>
<header class="head">
    <h1>Hello, <?= h($user['first_name']) ?>.</h1>
    <p class="sub">You're logged in.</p>
</header>
<hr>

<dl class="profile">
    <dt>Name</dt><dd><?= h($user['first_name'] . ' ' . $user['last_name']) ?></dd>
    <dt>Email</dt><dd><?= h($user['email']) ?></dd>
    <dt>Member since</dt><dd><?= h(date('F j, Y', strtotime($user['created_at']))) ?></dd>
</dl>

<form method="post" action="logout.php">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <button type="submit" class="btn-primary">Log out</button>
</form>
<?php require __DIR__ . '/partials/auth_bottom.php'; ?>
