<?php
declare(strict_types=1);

/**
 * Shared bootstrap: session, database, and small helpers.
 * Every page includes this file first.
 */

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

/* ---------- Database (SQLite, created automatically) ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dir = __DIR__ . '/data';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . $dir . '/app.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name  TEXT NOT NULL,
                email      TEXT NOT NULL UNIQUE,
                password   TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }
    return $pdo;
}

/* ---------- Output escaping ---------- */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------- Input cleaning ---------- */
function clean(string $value): string
{
    return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
}

/* ---------- CSRF protection ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool
{
    return isset($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

/* ---------- Flash messages (shown once) ---------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Auth helpers ---------- */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, first_name, last_name, email, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/* ---------- Validation rules shared by pages ---------- */
function validate_name(string $value, string $label): ?string
{
    if ($value === '') {
        return "$label is required.";
    }
    $len = mb_strlen($value);
    if ($len < 2 || $len > 50) {
        return "$label must be 2 to 50 characters.";
    }
    if (!preg_match("/^[\p{L}][\p{L}\s'\-.]*$/u", $value)) {
        return "$label can only contain letters, spaces, hyphens and apostrophes.";
    }
    return null;
}

function validate_email(string $value): ?string
{
    if ($value === '') {
        return 'Email is required.';
    }
    if (mb_strlen($value) > 254 || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return 'Enter a valid email address, like name@example.com.';
    }
    return null;
}

function validate_password(string $value): ?string
{
    if ($value === '') {
        return 'Password is required.';
    }
    if (strlen($value) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (strlen($value) > 72) {
        return 'Password must be 72 characters or fewer.';
    }
    if (!preg_match('/[a-z]/', $value) || !preg_match('/[A-Z]/', $value) || !preg_match('/\d/', $value)) {
        return 'Password needs an uppercase letter, a lowercase letter and a number.';
    }
    return null;
}
