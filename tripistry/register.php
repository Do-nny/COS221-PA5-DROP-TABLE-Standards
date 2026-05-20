<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
startSession();

if (isLoggedIn()) {
    $u = currentUser();
    header('Location: ' . BASE_URL . ($u['user_type'] === 'traveler' ? '/traveler/browse.php' : '/agency/dashboard.php'));
    exit;
}

$error   = '';
$success = '';
$tab     = $_POST['tab'] ?? 'traveler';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $pass  = $_POST['password']  ?? '';
        $pass2 = $_POST['password2'] ?? '';
        if ($pass !== $pass2) {
            $error = 'Passwords do not match.';
        } elseif (strlen($pass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            if ($tab === 'agency') {
                $result = registerAgency([
                    'phone'        => trim($_POST['phone'] ?? ''),
                    'email'        => trim($_POST['email'] ?? ''),
                    'password'     => $pass,
                    'agency_name'  => trim($_POST['agency_name'] ?? ''),
                    'extra_phones' => [],
                ]);
            } else {
                $result = registerTraveler([
                    'phone'      => trim($_POST['phone'] ?? ''),
                    'email'      => trim($_POST['email'] ?? ''),
                    'password'   => $pass,
                    'id_number'  => trim($_POST['id_number'] ?? ''),
                    'country'    => trim($_POST['country'] ?? ''),
                    'dob'        => $_POST['dob'] ?? '',
                    'first_name' => trim($_POST['first_name'] ?? ''),
                    'last_name'  => trim($_POST['last_name'] ?? ''),
                ]);
            }
            if ($result['success']) {
                $success = 'Account created! You can now <a href="' . BASE_URL . '/">sign in</a>.';
            } else {
                $error = $result['message'];
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
  <title>Register — Tripistry</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-visual">
    <div class="auth-visual-content">
      <div class="auth-logo">Tripistry</div>
      <div class="auth-tagline">Join the community.</div>
      <div class="gold-line" style="margin:16px auto;"></div>
      <p class="auth-desc">Register as a traveller to explore and book, or as an agency to create and manage travel packages.</p>
    </div>
  </div>

  <div class="auth-form-side" style="overflow-y:auto;align-items:flex-start;padding-top:40px;">
    <div class="auth-form-wrap">
      <h2 class="auth-title">Create account</h2>
      <p class="auth-subtitle"><a href="<?= BASE_URL ?>/">Already have an account?</a></p>

      <div class="auth-tabs">
        <button class="auth-tab <?= $tab === 'traveler' ? 'active' : '' ?>" onclick="setTab('traveler')">Traveller</button>
        <button class="auth-tab <?= $tab === 'agency' ? 'active' : '' ?>" onclick="setTab('agency')">Travel Agency</button>
      </div>

      <?php if ($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success">✓ <?= $success ?></div><?php endif; ?>

      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="tab" id="tab-input" value="<?= htmlspecialchars($tab) ?>">

        <!-- Traveller fields -->
        <div id="traveler-fields" style="<?= $tab === 'traveler' ? '' : 'display:none' ?>">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-control" placeholder="Alice" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control" placeholder="Mokoena" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">ID Number</label>
            <input type="text" name="id_number" class="form-control" placeholder="SA8801015001" value="<?= htmlspecialchars($_POST['id_number'] ?? '') ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Date of Birth</label>
              <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Residing Country</label>
              <input type="text" name="country" class="form-control" placeholder="South Africa" value="<?= htmlspecialchars($_POST['country'] ?? '') ?>">
            </div>
          </div>
        </div>

        <!-- Agency fields -->
        <div id="agency-fields" style="<?= $tab === 'agency' ? '' : 'display:none' ?>">
          <div class="form-group">
            <label class="form-label">Agency Name</label>
            <input type="text" name="agency_name" class="form-control" placeholder="Sunset Travel" value="<?= htmlspecialchars($_POST['agency_name'] ?? '') ?>">
          </div>
        </div>

        <!-- Shared fields -->
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control" placeholder="+27821112222" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Min. 8 characters">
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="password2" class="form-control" placeholder="Repeat password">
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Create Account</button>
      </form>
    </div>
  </div>
</div>

<script>
function setTab(t) {
  document.getElementById('tab-input').value = t;
  document.querySelectorAll('.auth-tab').forEach((el,i) => el.classList.toggle('active', (i===0&&t==='traveler')||(i===1&&t==='agency')));
  document.getElementById('traveler-fields').style.display = t==='traveler' ? '' : 'none';
  document.getElementById('agency-fields').style.display   = t==='agency'   ? '' : 'none';
}
</script>
</body>
</html>
