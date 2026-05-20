<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserType('traveler', BASE_URL . '/');

$db   = getDB();
$user = currentUser();

$stmt = $db->prepare("
    SELECT b.*,
           tp.start_date, tp.end_date, tp.price_per_individual,
           ta.agency_name,
           GROUP_CONCAT(DISTINCT CONCAT(d.city, ', ', d.country) SEPARATOR ' · ') AS destinations,
           pr.review_id, pr.rating AS my_rating
    FROM booking b
    JOIN travel_package tp ON tp.package_id = b.package_id
    JOIN travel_agency ta ON ta.user_id = tp.agency_id
    LEFT JOIN package_destinations pd ON pd.package_id = tp.package_id
    LEFT JOIN destination d ON d.destination_id = pd.destination_id
    LEFT JOIN package_review pr ON pr.booking_id = b.booking_id
    WHERE b.user_id = ?
    GROUP BY b.booking_id
    ORDER BY b.booking_date DESC
");
$stmt->execute([$user['user_id']]);
$bookings = $stmt->fetchAll();

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bid = (int)($_POST['booking_id'] ?? 0);
        $check = $db->prepare("SELECT booking_id, booking_status FROM booking WHERE booking_id = ? AND user_id = ?");
        $check->execute([$bid, $user['user_id']]);
        $row = $check->fetch();
        if ($row && $row['booking_status'] === 'confirmed') {
            $db->prepare("UPDATE booking SET booking_status='cancelled', payment_status='refunded' WHERE booking_id=?")->execute([$bid]);
        }
    }
    header('Location: bookings.php?msg=cancelled'); exit;
}

$flash = $_GET['msg'] ?? '';
$pageTitle = 'My Trips';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-wrap">
  <div class="page-header">
    <h1>My Trips</h1>
    <p><?= count($bookings) ?> booking<?= count($bookings)!==1?'s':'' ?></p>
  </div>

  <?php if ($flash === 'cancelled'): ?><div class="alert alert-info">Booking cancelled and refund initiated.</div><?php endif; ?>

  <?php if (empty($bookings)): ?>
    <div class="empty-state">
      <div class="icon">🧳</div>
      <h3>No bookings yet</h3>
      <p>Start exploring packages and book your first adventure.</p>
      <a href="browse.php" class="btn btn-primary mt-3">Explore Packages</a>
    </div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:16px;">
      <?php foreach ($bookings as $b): ?>
        <div class="card" style="padding:0;overflow:hidden;">
          <div style="display:grid;grid-template-columns:1fr auto;align-items:stretch;">
            <div style="padding:20px 24px;">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <span class="status-badge status-<?= $b['booking_status'] ?>"><?= $b['booking_status'] ?></span>
                <span class="status-badge status-<?= $b['payment_status'] ?>"><?= $b['payment_status'] ?></span>
                <span class="text-xs text-muted">Booking #<?= $b['booking_id'] ?></span>
              </div>
              <div style="font-family:var(--font-display);font-size:1.25rem;color:var(--text);margin-bottom:4px;">
                <?= htmlspecialchars($b['destinations'] ?? 'Travel Package') ?>
              </div>
              <div class="text-sm text-muted">
                🏢 <?= htmlspecialchars($b['agency_name']) ?> &nbsp;·&nbsp;
                📅 <?= date('d M Y', strtotime($b['start_date'])) ?> → <?= date('d M Y', strtotime($b['end_date'])) ?> &nbsp;·&nbsp;
                👥 <?= $b['number_of_people'] ?> traveller<?= $b['number_of_people']!==1?'s':'' ?>
              </div>
              <?php if ($b['my_rating']): ?>
                <div class="stars" style="margin-top:8px;">
                  <?php for ($s=1;$s<=5;$s++): ?>
                    <span class="star <?= $s<=$b['my_rating']?'filled':'' ?>">★</span>
                  <?php endfor; ?>
                  <span class="text-xs text-muted" style="margin-left:4px;">Your review</span>
                </div>
              <?php endif; ?>
            </div>
            <div style="background:var(--surface);padding:20px 24px;display:flex;flex-direction:column;align-items:flex-end;justify-content:space-between;border-left:1px solid var(--border);min-width:180px;">
              <div style="text-align:right;">
                <div style="font-family:var(--font-display);font-size:1.6rem;color:var(--gold);">R <?= number_format($b['total_price'], 0) ?></div>
                <div class="text-xs text-muted">Total paid</div>
              </div>
              <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;margin-top:12px;">
                <a href="package.php?id=<?= $b['package_id'] ?>" class="btn btn-outline btn-sm">View</a>
                <?php if ($b['booking_status'] === 'confirmed'): ?>
                  <form method="POST" style="margin:0;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm"
                            data-confirm="Cancel this booking? A refund will be initiated.">Cancel</button>
                  </form>
                <?php endif; ?>
                <?php if (!$b['review_id'] && in_array($b['booking_status'], ['confirmed','completed'])): ?>
                  <a href="package.php?id=<?= $b['package_id'] ?>#reviews" class="btn btn-ghost btn-sm">Review</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
