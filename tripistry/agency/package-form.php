<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserType('agency', BASE_URL . '/');

$db   = getDB();
$user = currentUser();
$aid  = $user['user_id'];
$pid  = (int)($_GET['id'] ?? 0);

// Editing existing?
$pkg = null;
$pkgDests = [];
$pkgImages = [];
$itinDays = [];

if ($pid) {
    $stmt = $db->prepare("SELECT * FROM travel_package WHERE package_id=? AND agency_id=?");
    $stmt->execute([$pid, $aid]);
    $pkg = $stmt->fetch();
    if (!$pkg) { header('Location: dashboard.php'); exit; }

    $ds = $db->prepare("SELECT destination_id FROM package_destinations WHERE package_id=?");
    $ds->execute([$pid]); $pkgDests = $ds->fetchAll(PDO::FETCH_COLUMN);

    $imgs = $db->prepare("SELECT image_url FROM travel_package_images WHERE package_id=?");
    $imgs->execute([$pid]); $pkgImages = $imgs->fetchAll(PDO::FETCH_COLUMN);

    $itin = $db->prepare("SELECT * FROM itinerary_day WHERE package_id=? ORDER BY day_number");
    $itin->execute([$pid]); $itinDays = $itin->fetchAll();
}

$destinations = $db->query("SELECT * FROM destination ORDER BY country, city")->fetchAll();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $startDate   = $_POST['start_date'] ?? '';
        $endDate     = $_POST['end_date']   ?? '';
        $pricePerPP  = (float)($_POST['price_per_individual'] ?? 0);
        $totalPrice  = (float)($_POST['total_price'] ?? 0);
        $status      = $_POST['package_status'] ?? 'active';
        $selDests    = $_POST['destinations']   ?? [];
        $imageUrls   = array_filter(array_map('trim', explode("\n", $_POST['image_urls'] ?? '')));
        $dayActivities = $_POST['day_activity'] ?? [];

        if (!$startDate || !$endDate || !$pricePerPP) {
            $error = 'Please fill in all required fields.';
        } elseif (strtotime($endDate) <= strtotime($startDate)) {
            $error = 'End date must be after start date.';
        } else {
            try {
                $db->beginTransaction();
                if ($pid) {
                    $db->prepare("UPDATE travel_package SET start_date=?,end_date=?,price_per_individual=?,total_price_of_package=?,package_status=? WHERE package_id=?"
                    )->execute([$startDate,$endDate,$pricePerPP,$totalPrice,$status,$pid]);
                } else {
                    $db->prepare("INSERT INTO travel_package (start_date,end_date,price_per_individual,total_price_of_package,package_status,agency_id) VALUES (?,?,?,?,?,?)"
                    )->execute([$startDate,$endDate,$pricePerPP,$totalPrice,$status,$aid]);
                    $pid = $db->lastInsertId();
                }
                // Destinations
                $db->prepare("DELETE FROM package_destinations WHERE package_id=?")->execute([$pid]);
                foreach ($selDests as $did) {
                    $db->prepare("INSERT IGNORE INTO package_destinations (package_id,destination_id) VALUES (?,?)")->execute([$pid,(int)$did]);
                }
                // Images
                $db->prepare("DELETE FROM travel_package_images WHERE package_id=?")->execute([$pid]);
                foreach ($imageUrls as $url) {
                    if ($url) $db->prepare("INSERT INTO travel_package_images (package_id,image_url) VALUES (?,?)")->execute([$pid,$url]);
                }
                // Itinerary days
                $db->prepare("DELETE FROM itinerary_day WHERE package_id=?")->execute([$pid]);
                foreach ($dayActivities as $dayNum => $activity) {
                    $activity = trim($activity);
                    if ($activity) {
                        $db->prepare("INSERT INTO itinerary_day (package_id,day_number,day_activity) VALUES (?,?,?)")->execute([$pid,(int)$dayNum+1,$activity]);
                    }
                }
                $db->commit();
                header("Location: manage-items.php?id=$pid&msg=saved"); exit;
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Error saving package: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = $pkg ? 'Edit Package' : 'New Package';
require_once __DIR__ . '/../includes/header.php';

// Calculate duration for day inputs
$duration = $pkg ? (int)((strtotime($pkg['end_date']) - strtotime($pkg['start_date'])) / 86400) : 7;
?>

<div class="container-narrow page-wrap">
  <div class="page-header">
    <a href="dashboard.php" class="btn btn-ghost btn-sm" style="margin-bottom:12px;">← Dashboard</a>
    <h1><?= $pkg ? 'Edit Package' : 'New Package' ?></h1>
    <?php if ($pkg): ?><p>Package #<?= $pid ?> · <?= htmlspecialchars($pkg['package_status']) ?></p><?php endif; ?>
  </div>

  <?php if ($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>

  <form method="POST">
    <?= csrfField() ?>

    <!-- Basics -->
    <div class="card" style="margin-bottom:24px;">
      <h3 style="margin-bottom:20px;">Package Details</h3>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Start Date *</label>
          <input type="date" name="start_date" class="form-control" required
                 value="<?= htmlspecialchars($pkg['start_date'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">End Date *</label>
          <input type="date" name="end_date" class="form-control" required
                 value="<?= htmlspecialchars($pkg['end_date'] ?? '') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Price Per Person (R) *</label>
          <input type="number" name="price_per_individual" class="form-control" step="0.01" min="0"
                 placeholder="1500.00" required
                 value="<?= htmlspecialchars($pkg['price_per_individual'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Total Package Price (R)</label>
          <input type="number" name="total_price" class="form-control" step="0.01" min="0"
                 placeholder="15000.00"
                 value="<?= htmlspecialchars($pkg['total_price_of_package'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="package_status" class="form-control">
          <?php foreach (['active','inactive','fully_booked','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= ($pkg['package_status']??'active')===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Destinations -->
    <div class="card" style="margin-bottom:24px;">
      <h3 style="margin-bottom:12px;">Destinations</h3>
      <p style="font-size:0.85rem;margin-bottom:16px;">Select the destinations included in this package.</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;">
        <?php foreach ($destinations as $d): ?>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 12px;border-radius:var(--radius-sm);border:1px solid var(--border);font-size:0.875rem;">
            <input type="checkbox" name="destinations[]" value="<?= $d['destination_id'] ?>"
                   <?= in_array($d['destination_id'], $pkgDests)?'checked':'' ?>>
            <?= htmlspecialchars($d['city'].', '.$d['country']) ?>
          </label>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);">
        <p class="text-xs text-muted">Missing a destination? Add it in the database directly, or ask your admin.</p>
      </div>
    </div>

    <!-- Images -->
    <div class="card" style="margin-bottom:24px;">
      <h3 style="margin-bottom:12px;">Package Images</h3>
      <div class="form-group">
        <label class="form-label">Image URLs (one per line)</label>
        <textarea name="image_urls" class="form-control" rows="4"
                  placeholder="https://cdn.example.com/packages/hero.jpg&#10;https://cdn.example.com/packages/2.jpg"><?= htmlspecialchars(implode("\n", $pkgImages)) ?></textarea>
        <div class="form-hint">Paste full image URLs, one per line.</div>
      </div>
    </div>

    <!-- Itinerary -->
    <div class="card" style="margin-bottom:24px;">
      <h3 style="margin-bottom:12px;">Daily Itinerary</h3>
      <p class="text-sm text-muted" style="margin-bottom:16px;">Describe each day's activities. Leave blank to skip a day.</p>
      <?php $existingDays = array_column($itinDays, 'day_activity', 'day_number'); ?>
      <?php for ($d=1; $d<=max($duration, 7, count($itinDays)); $d++): ?>
        <div class="form-group">
          <label class="form-label">Day <?= $d ?></label>
          <input type="text" name="day_activity[<?= $d-1 ?>]" class="form-control"
                 placeholder="Describe day <?= $d ?> activities…"
                 value="<?= htmlspecialchars($existingDays[$d] ?? '') ?>">
        </div>
      <?php endfor; ?>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end;">
      <a href="dashboard.php" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn btn-primary btn-lg">
        <?= $pkg ? 'Save Changes' : 'Create Package' ?> →
      </button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
