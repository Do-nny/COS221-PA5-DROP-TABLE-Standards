<?php
require_once __DIR__ . '/auth.php';
$user = currentUser();
$baseUrl = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? SITE_NAME) ?> — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <a href="<?= $baseUrl ?>/" class="navbar-brand">Trip<span>istry</span></a>

    <div class="navbar-links">
      <?php if ($user): ?>
        <?php if ($user['user_type'] === 'traveler'): ?>
          <a href="<?= $baseUrl ?>/traveler/browse.php"
             class="<?= strpos($_SERVER['PHP_SELF'],'browse')!==false?'active':'' ?>">Explore</a>
          <a href="<?= $baseUrl ?>/traveler/bookings.php"
             class="<?= strpos($_SERVER['PHP_SELF'],'bookings')!==false?'active':'' ?>">My Trips</a>
        <?php else: ?>
          <a href="<?= $baseUrl ?>/agency/dashboard.php"
             class="<?= strpos($_SERVER['PHP_SELF'],'dashboard')!==false?'active':'' ?>">Dashboard</a>
          <a href="<?= $baseUrl ?>/agency/package-form.php">New Package</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="flex items-center gap-3">
      <?php if ($user): ?>
        <div class="navbar-user">
          <span class="badge"><?= $user['user_type'] === 'traveler' ? 'Traveller' : 'Agency' ?></span>
          <span><?= htmlspecialchars($user['name']) ?></span>
        </div>
        <a href="<?= $baseUrl ?>/logout.php" class="btn btn-ghost btn-sm">Sign out</a>
      <?php else: ?>
        <a href="<?= $baseUrl ?>/" class="btn btn-outline btn-sm">Sign in</a>
        <a href="<?= $baseUrl ?>/register.php" class="btn btn-primary btn-sm">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div id="toast-container"></div>
