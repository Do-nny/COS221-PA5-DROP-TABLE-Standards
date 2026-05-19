<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
startSession();

// Redirect if already logged in
if (isLoggedIn()) {
    $user = currentUser();
    header('Location: ' . BASE_URL . ($user['user_type'] === 'traveler' ? '/traveler/browse.php' : '/agency/dashboard.php'));
    exit;
}

$error = '';
$tab   = 'traveler';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $tab   = $_POST['tab'] ?? 'traveler';
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        if (empty($email) || empty($pass)) {
            $error = 'Please fill in all fields.';
        } else {
            $ok = $tab === 'agency' ? loginAgency($email, $pass) : loginTraveler($email, $pass);
            if ($ok) {
                $dest = $tab === 'agency' ? '/agency/dashboard.php' : '/traveler/browse.php';
                header('Location: ' . BASE_URL . $dest);
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Tripistry</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
  <!-- Visual side -->
  <div class="auth-visual" style="display:flex;">
    <div class="auth-visual-content">
      <div class="auth-logo">Tripistry</div>
      <div class="auth-tagline">Your world, curated.</div>
      <div class="gold-line" style="margin:16px auto;"></div>
      <p class="auth-desc">
        Handpicked travel packages from expert agencies. Explore, compare, and book your perfect journey.
      </p>
      <div style="margin-top:48px;display:flex;flex-direction:column;gap:20px;">
        <?php
        $highlights = [
          ['✈', 'Curated Packages', 'Hand-selected by trusted agencies'],
          ['🏨', 'Premium Stays', 'Hotels matched to your style'],
          ['🌍', 'Group Trips', 'Travel with like-minded adventurers'],
        ];
        foreach ($highlights as [$icon, $title, $sub]):
        ?>
        <div style="display:flex;align-items:center;gap:14px;text-align:left;">
          <div style="width:40px;height:40px;background:var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;"><?= $icon ?></div>
          <div>
            <div style="color:var(--text);font-weight:600;font-size:0.9rem;"><?= $title ?></div>
            <div style="color:var(--text-muted);font-size:0.78rem;"><?= $sub ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Form side -->
  <div class="auth-form-side">
    <div class="auth-form-wrap">
      <h2 class="auth-title">Welcome back</h2>
      <p class="auth-subtitle">Sign in to continue your journey.</p>

      <!-- Tab toggle -->
      <div class="auth-tabs">
        <button class="auth-tab <?= $tab === 'traveler' ? 'active' : '' ?>" onclick="setTab('traveler')">Traveller</button>
        <button class="auth-tab <?= $tab === 'agency' ? 'active' : '' ?>" onclick="setTab('agency')">Travel Agency</button>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="tab" id="tab-input" value="<?= htmlspecialchars($tab) ?>">

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control"
                 placeholder="you@example.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:8px;">Sign In</button>

        <div class="auth-divider">or</div>

        <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline btn-block">Create an account</a>
      </form>
    </div>
  </div>
</div>

<script>
function setTab(t) {
  document.getElementById('tab-input').value = t;
  document.querySelectorAll('.auth-tab').forEach((el, i) => {
    el.classList.toggle('active', (i === 0 && t === 'traveler') || (i === 1 && t === 'agency'));
  });
}
</script>
</body>
</html>
