<?php
require __DIR__ . '/config.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];
$old = ['first_name' => '', 'last_name' => '', 'email' => ''];
$agreed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['first_name'] = clean($_POST['first_name'] ?? '');
    $old['last_name']  = clean($_POST['last_name'] ?? '');
    $old['email']      = clean($_POST['email'] ?? '');
    $password          = $_POST['password'] ?? '';
    $confirm           = $_POST['confirm_password'] ?? '';
    $agreed            = isset($_POST['terms']);

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
    } else {
        if ($e = validate_name($old['first_name'], 'First name')) { $errors['first_name'] = $e; }
        if ($e = validate_name($old['last_name'], 'Last name'))   { $errors['last_name']  = $e; }
        if ($e = validate_email($old['email']))                   { $errors['email']      = $e; }
        if ($e = validate_password($password))                    { $errors['password']   = $e; }

        if ($confirm === '') {
            $errors['confirm_password'] = 'Please enter your password again.';
        } elseif (!isset($errors['password']) && !hash_equals($password, $confirm)) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
        if (!$agreed) {
            $errors['terms'] = 'You must agree to the Terms and Conditions.';
        }

        if (!$errors) {
            $stmt = db()->prepare('SELECT 1 FROM users WHERE email = ? COLLATE NOCASE');
            $stmt->execute([$old['email']]);
            if ($stmt->fetch()) {
                $errors['email'] = 'An account with this email already exists. Try logging in.';
            } else {
                $ins = db()->prepare(
                    'INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)'
                );
                $ins->execute([
                    $old['first_name'],
                    $old['last_name'],
                    mb_strtolower($old['email']),
                    password_hash($password, PASSWORD_DEFAULT),
                ]);
                flash('success', 'Account created! You can log in now.');
                redirect('login.php');
            }
        }
    }
}

function field_class(array $errors, string $key): string
{
    return isset($errors[$key]) ? 'has-error' : '';
}

$title = 'Create account';
require __DIR__ . '/partials/auth_top.php';
?>
<header class="head head-register">
    <h1>Create an account</h1>
    <p class="sub small">Already have an account? <a href="login.php">Login</a></p>
</header>
<hr>

<?php if (isset($errors['form'])): ?>
    <div class="alert alert-error" role="alert"><?= h($errors['form']) ?></div>
<?php endif; ?>

<form method="post" action="register.php" novalidate data-validate="register">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <div class="row">
        <div class="field <?= field_class($errors, 'first_name') ?>">
            <input type="text" name="first_name" id="first_name" placeholder="First name"
                   autocomplete="given-name" value="<?= h($old['first_name']) ?>" aria-label="First name" required>
            <p class="error" data-error-for="first_name"><?= h($errors['first_name'] ?? '') ?></p>
        </div>
        <div class="field <?= field_class($errors, 'last_name') ?>">
            <input type="text" name="last_name" id="last_name" placeholder="Last name"
                   autocomplete="family-name" value="<?= h($old['last_name']) ?>" aria-label="Last name" required>
            <p class="error" data-error-for="last_name"><?= h($errors['last_name'] ?? '') ?></p>
        </div>
    </div>

    <div class="field <?= field_class($errors, 'email') ?>">
        <input type="email" name="email" id="email" placeholder="Enter email" autocomplete="email"
               value="<?= h($old['email']) ?>" aria-label="Email" required>
        <p class="error" data-error-for="email"><?= h($errors['email'] ?? '') ?></p>
    </div>

    <div class="field <?= field_class($errors, 'password') ?>">
        <input type="password" name="password" id="password" placeholder="Enter your password"
               autocomplete="new-password" aria-label="Password" required>
        <button type="button" class="icon toggle-pw" aria-label="Show password" data-toggle="password">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.500-7 10-7 10 7 10 7-3.500 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
        <p class="hint">8+ characters with uppercase, lowercase and a number.</p>
        <p class="error" data-error-for="password"><?= h($errors['password'] ?? '') ?></p>
    </div>

    <div class="field <?= field_class($errors, 'confirm_password') ?>">
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Enter your password again"
               autocomplete="new-password" aria-label="Confirm password" required>
        <button type="button" class="icon toggle-pw" aria-label="Show password" data-toggle="confirm_password">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.500-7 10-7 10 7 10 7-3.500 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
        <p class="error" data-error-for="confirm_password"><?= h($errors['confirm_password'] ?? '') ?></p>
    </div>

    <div class="field terms <?= field_class($errors, 'terms') ?>">
        <label class="check">
            <input type="checkbox" name="terms" id="terms" <?= $agreed ? 'checked' : '' ?>>
            <span class="check-ui" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.500l4.500 4.500L19 7.500"/></svg>
            </span>
            <span>By checking the box you agree to our <a href="#" data-soon>Terms</a> and <a href="#" data-soon>Conditions</a>.</span>
        </label>
        <p class="error" data-error-for="terms"><?= h($errors['terms'] ?? '') ?></p>
    </div>

    <button type="submit" class="btn-primary">Create account</button>
</form>

<div class="divider"><span>Or continue with</span></div>
<?php require __DIR__ . '/partials/social.php'; ?>
<?php require __DIR__ . '/partials/auth_bottom.php'; ?>
