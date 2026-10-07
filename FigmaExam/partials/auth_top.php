<?php
/** Opens the page: background, tagline, and the glass panel. Expects $title. */
$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> | Dunes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="stage">
    <section class="hero" aria-hidden="true">
        <svg class="mark" viewBox="0 0 24 14" width="34" height="20"><path d="M1 12 C6 2, 10 2, 12 7 S19 12, 23 4" fill="none" stroke="#dbe2ef" stroke-width="1.6" stroke-linecap="round"/></svg>
        <p class="tagline">Find your way.<br>Your <strong>space</strong> is waiting.</p>
    </section>

    <section class="panel">
        <div class="panel-inner">
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>" role="status"><?= h($flash['message']) ?></div>
            <?php endif; ?>
