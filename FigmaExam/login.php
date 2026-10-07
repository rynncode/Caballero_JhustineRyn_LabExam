<?php
require __DIR__ . '/config.php';

if (current_user()) {
    redirect('dashboard.php');
}

const MAX_ATTEMPTS = 5;
const LOCK_SECONDS = 300;

$errors = [];
$email = '';
$remember = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
    } elseif (($_SESSION['lock_until'] ?? 0) > time()) {
        $wait = (int) ceil(($_SESSION['lock_until'] - time()) / 60);
        $errors['form'] = "Too many failed attempts. Try again in about $wait minute(s).";
    } else {
        if ($e = validate_email($email)) {
            $errors['email'] = $e;
        }
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        if (!$errors) {
            $stmt = db()->prepare('SELECT * FROM users WHERE email = ? COLLATE NOCASE');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                unset($_SESSION['attempts'], $_SESSION['lock_until']);
                $_SESSION['user_id'] = (int) $user['id'];

                if ($remember) {
                    $p = session_get_cookie_params();
                    setcookie(session_name(), session_id(), [
                        'expires'  => time() + 60 * 60 * 24 * 30,
                        'path'     => $p['path'],
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                }
                flash('success', 'Welcome back, ' . $user['first_name'] . '!');
                redirect('dashboard.php');
            }

            // Same message for unknown email and wrong password (no account enumeration).
            $_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
            if ($_SESSION['attempts'] >= MAX_ATTEMPTS) {
                $_SESSION['lock_until'] = time() + LOCK_SECONDS;
                $_SESSION['attempts'] = 0;
            }
            $errors['form'] = 'Incorrect email or password.';
        }
    }
}

$title = 'Log in';
require __DIR__ . '/partials/auth_top.php';
?>
<header class="head">
    <h1>Welcome back.</h1>
    <p class="sub">Good to see you again.</p>
</header>
<hr>

<?php if (isset($errors['form'])): ?>
    <div class="alert alert-error" role="alert"><?= h($errors['form']) ?></div>
<?php endif; ?>

<form method="post" action="login.php" novalidate data-validate="login">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
        <input type="email" name="email" id="email" placeholder="Email" autocomplete="email"
               value="<?= h($email) ?>" aria-label="Email" required>
        <svg class="icon" viewBox="0 0 24 24" width="26" height="26" aria-hidden="true" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.600-6 8-6s8 2 8 6"/></svg>
        <p class="error" data-error-for="email"><?= h($errors['email'] ?? '') ?></p>
    </div>

    <div class="field <?= isset($errors['password']) ? 'has-error' : '' ?>">
        <input type="password" name="password" id="password" placeholder="Password"
               autocomplete="current-password" aria-label="Password" required>
        <button type="button" class="icon toggle-pw" aria-label="Show password" data-toggle="password">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.500-7 10-7 10 7 10 7-3.500 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
        <p class="error" data-error-for="password"><?= h($errors['password'] ?? '') ?></p>
    </div>

    <label class="switch-row">
        <input type="checkbox" name="remember" class="switch" <?= $remember ? 'checked' : '' ?>>
        <span class="switch-ui" aria-hidden="true"></span>
        <span>Remember me</span>
    </label>

    <button type="submit" class="btn-primary">Log in</button>
    <a class="forgot" href="#" data-soon>Forgot your password?</a>
</form>

<div class="divider"><span>Or continue with</span></div>
<?php require __DIR__ . '/partials/social.php'; ?>

<p class="switch-page">New member? <a href="register.php">Register now</a></p>
<?php require __DIR__ . '/partials/auth_bottom.php'; ?>
