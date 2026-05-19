<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserType('agency', BASE_URL . '/');

$db   = getDB();
$user = currentUser();
$aid  = $user['user_id'];

// Stats
$stats = $db->prepare("
    SELECT
        COUNT(DISTINCT tp.package_id) AS total_packages,
        COUNT(DISTINCT b.booking_id) AS total_bookings,
        COALESCE(SUM(b.total_price), 0) AS total_revenue,
        ROUND(AVG(pr.rating), 1) AS avg_rating
    FROM travel_package tp
    LEFT JOIN booking b ON b.package_id = tp.package_id AND b.booking_status != 'cancelled'
    LEFT JOIN package_review pr ON pr.booking_id = b.booking_id
    WHERE tp.agency_id = ?
");
$stats->execute([$aid]); $stats = $stats->fetch();

// Agency reviews
$agencyRevs = $db->prepare("
    SELECT ar.*, 'Agency review' AS reviewer FROM agency_review ar WHERE ar.agency_id = ? ORDER BY ar.review_date DESC LIMIT 5
");
$agencyRevs->execute([$aid]); $agencyRevs = $agencyRevs->fetchAll();

// Handle status change & delete
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { $flash = 'error:Invalid request.'; }
    else {
        $pid    = (int)($_POST['package_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        // Verify ownership
        $own = $db->prepare("SELECT package_id FROM travel_package WHERE package_id=? AND agency_id=?");
        $own->execute([$pid, $aid]);
        if ($own->fetch()) {
            if ($action === 'delete') {
                try {
                    $db->prepare("DELETE FROM travel_package WHERE package_id=?")->execute([$pid]);
                    $flash = 'success:Package deleted.';
                } catch (PDOException $e) {
                    // Gracefully intercept integrity constraints (e.g., existing bookings link)
                    if ($e->getCode() == '23000') {
                        $flash = 'error:Cannot delete this package because active or historical traveller bookings are linked to it.';
                    } else {
                        $flash = 'error:An error occurred while trying to delete the package.';
                    }
                }
            } elseif ($action === 'status') {
                $newStatus = $_POST['new_status'] ?? '';
                if (in_array($newStatus, ['active','inactive','fully_booked','cancelled'])) {
                    $db->prepare("UPDATE travel_package SET package_status=? WHERE package_id=?")->execute([$newStatus, $pid]);
                    $flash = "success:Status updated to $newStatus.";
                }
            }
        }
    }
    header('Location: dashboard.php?flash=' . urlencode($flash)); exit;
}

$flash = $_GET['flash'] ?? '';

// Packages with isolated aggregates to prevent Cartesian product / multi-destination fan-out inflation
$pkgs = $db->prepare("
    SELECT tp.*,
           Dests.destinations,
           COALESCE(Bookings.bookings_count, 0) AS bookings_count,
           COALESCE(Bookings.revenue, 0) AS revenue,
           Bookings.avg_rating
    FROM travel_package tp
    LEFT JOIN (
        SELECT pd.package_id,
               GROUP_CONCAT(DISTINCT CONCAT(d.city,', ',d.country) SEPARATOR ' · ') AS destinations
        FROM package_destinations pd
        JOIN destination d ON d.destination_id = pd.destination_id
        GROUP BY pd.package_id
    ) Dests ON Dests.package_id = tp.package_id
    LEFT JOIN (
        SELECT b.package_id,
               COUNT(DISTINCT b.booking_id) AS bookings_count,
               SUM(CASE WHEN b.booking_status != 'cancelled' THEN b.total_price ELSE 0 END) AS revenue,
               ROUND(AVG(pr.rating), 1) AS avg_rating
        FROM booking b
        LEFT JOIN package_review pr ON pr.booking_id = b.booking_id
        GROUP BY b.package_id
    ) Bookings ON Bookings.package_id = tp.package_id
    WHERE tp.agency_id = ?
    ORDER BY tp.start_date DESC
");
$pkgs->execute([$aid]); $packages = $pkgs->fetchAll();

$pageTitle = 'Agency Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-wrap">
  <?php if ($flash): ?>
    <?php [$ft, $fm] = explode(':', $flash, 2); ?>
    <div class="alert alert-<?= $ft === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($fm) ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div class="page-header-flex">
      <div>
        <div class="section-label">Agency Dashboard</div>
        <h1><?= htmlspecialchars($user['name']) ?></h1>
      </div>
      <a href="package-form.php" class="btn btn-primary">+ New Package</a>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:40px;">
    <?php
    $statItems = [
      ['📦', 'Packages', $stats['total_packages'] ?? 0, ''],
      ['🎫', 'Bookings', $stats['total_bookings'] ?? 0, ''],
      ['💰', 'Revenue', 'R '.number_format($stats['total_revenue'] ?? 0, 0), ''],
      ['⭐', 'Avg Rating', $stats['avg_rating'] ? $stats['avg_rating'].'/5' : 'N/A', ''],
    ];
    foreach ($statItems as [$icon, $label, $val, $sub]):
    ?>
      <div class="card" style="text-align:center;">
        <div style="font-size:1.8rem;margin-bottom:6px;"><?= $icon ?></div>
        <div style="font-family:var(--font-display);font-size:1.8rem;color:var(--gold);"><?= $val ?></div>
        <div class="text-sm text-muted"><?= $label ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <h2 style="font-size:1.3rem;">Your Packages</h2>
  </div>

  <?php if (empty($packages)): ?>
    <div class="empty-state">
      <div class="icon">✈</div>
      <h3>No packages yet</h3>
      <p>Create your first travel package to start attracting bookings.</p>
      <a href="package-form.php" class="btn btn-primary mt-3">Create Package</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Destinations</th>
            <th>Dates</th>
            <th>Price/pp</th>
            <th>Bookings</th>
            <th>Revenue</th>
            <th>Rating</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($packages as $p): ?>
          <tr>
            <td>
              <span style="color:var(--text);font-weight:500;"><?= htmlspecialchars($p['destinations'] ?? 'N/A') ?></span>
              <div class="text-xs text-muted">Package #<?= $p['package_id'] ?></div>
            </td>
            <td class="text-sm">
              <?= date('d M Y', strtotime($p['start_date'])) ?><br>
              <span class="text-muted">→ <?= date('d M Y', strtotime($p['end_date'])) ?></span>
            </td>
            <td class="text-gold font-display" style="font-size:1.1rem;">R <?= number_format($p['price_per_individual'], 0) ?></td>
            <td><?= $p['bookings_count'] ?></td>
            <td>R <?= number_format($p['revenue'], 0) ?></td>
            <td>
              <?php if ($p['avg_rating']): ?>
                <span class="text-gold">⭐ <?= $p['avg_rating'] ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td><span class="status-badge status-<?= $p['package_status'] ?>"><?= str_replace('_',' ',$p['package_status']) ?></span></td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="package-form.php?id=<?= $p['package_id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                <a href="manage-items.php?id=<?= $p['package_id'] ?>" class="btn btn-outline btn-sm">Items</a>

                <form method="POST" style="display:inline;">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="status">
                  <input type="hidden" name="package_id" value="<?= $p['package_id'] ?>">
                  <select name="new_status" class="form-control" style="padding:6px 8px;font-size:0.78rem;height:auto;"
                          onchange="this.form.submit()">
                    <?php foreach(['active','inactive','fully_booked','cancelled'] as $s): ?>
                      <option value="<?= $s ?>" <?= $p['package_status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>

                <form method="POST" style="display:inline;">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="package_id" value="<?= $p['package_id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm"
                          data-confirm="Permanently delete this package and all its data?">Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($agencyRevs): ?>
  <div style="margin-top:48px;">
    <h2 style="font-size:1.3rem;margin-bottom:16px;">Agency Reviews</h2>
    <?php foreach ($agencyRevs as $rev): ?>
      <div class="review-card">
        <div class="review-header">
          <div>
            <span class="reviewer">Anonymous Traveller</span>
            <div class="stars" style="margin-top:4px;">
              <?php for ($s=1;$s<=5;$s++): ?>
                <span class="star <?= $s<=$rev['rating']?'filled':'' ?>">★</span>
              <?php endfor; ?>
            </div>
          </div>
          <span class="review-date"><?= date('d M Y', strtotime($rev['review_date'])) ?></span>
        </div>
        <?php if ($rev['comment']): ?>
          <div class="review-comment"><?= htmlspecialchars($rev['comment']) ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>