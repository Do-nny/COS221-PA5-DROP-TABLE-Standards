<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserType('traveler', BASE_URL . '/');

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
$user = currentUser();

if (!$id) { header('Location: browse.php'); exit; }

// ── Fetch package ──────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT tp.*,
           ta.agency_name, ta.email AS agency_email, ta.user_id AS agency_id,
           GROUP_CONCAT(DISTINCT CONCAT(d.city, ', ', d.country) SEPARATOR ' · ') AS destinations,
           ROUND(AVG(pr.rating), 1) AS avg_rating,
           COUNT(DISTINCT pr.review_id) AS review_count,
           DATEDIFF(tp.end_date, tp.start_date) AS duration_days
    FROM travel_package tp
    JOIN travel_agency ta ON ta.user_id = tp.agency_id
    LEFT JOIN package_destinations pd ON pd.package_id = tp.package_id
    LEFT JOIN destination d ON d.destination_id = pd.destination_id
    LEFT JOIN booking b ON b.package_id = tp.package_id
    LEFT JOIN package_review pr ON pr.booking_id = b.booking_id
    WHERE tp.package_id = ?
    GROUP BY tp.package_id
");
$stmt->execute([$id]);
$pkg = $stmt->fetch();
if (!$pkg) { header('Location: browse.php'); exit; }

// Images
$images = $db->prepare("SELECT image_url FROM travel_package_images WHERE package_id = ?");
$images->execute([$id]); $images = $images->fetchAll(PDO::FETCH_COLUMN);

// Itinerary
$stmt2 = $db->prepare("SELECT * FROM itinerary_day WHERE package_id = ? ORDER BY day_number");
$stmt2->execute([$id]); $days = $stmt2->fetchAll();

// Items per day
$stmt3 = $db->prepare("
    SELECT idi.day_number, idi.item_time,
           COALESCE(f.airline, a.accommodation_name, r.restaurant_name, ta2.attraction_name) AS item_name,
           CASE WHEN f.item_id IS NOT NULL THEN 'flight'
                WHEN a.item_id IS NOT NULL THEN 'accommodation'
                WHEN r.item_id IS NOT NULL THEN 'restaurant'
                WHEN ta2.item_id IS NOT NULL THEN 'attraction'
                ELSE 'item' END AS item_type
    FROM itinerary_day_item idi
    LEFT JOIN flight f ON f.item_id = idi.item_id
    LEFT JOIN accommodation a ON a.item_id = idi.item_id
    LEFT JOIN restaurant r ON r.item_id = idi.item_id
    LEFT JOIN tourist_attraction ta2 ON ta2.item_id = idi.item_id
    WHERE idi.package_id = ?
    ORDER BY idi.day_number, idi.item_time
");
$stmt3->execute([$id]); $dayItems = $stmt3->fetchAll();
$dayItemsMap = [];
foreach ($dayItems as $di) $dayItemsMap[$di['day_number']][] = $di;

// Included items (non-itinerary)
$stmtInc = $db->prepare("
    SELECT ti.item_id,
           COALESCE(f.airline, a.accommodation_name, r.restaurant_name, ta2.attraction_name) AS item_name,
           CASE WHEN f.item_id IS NOT NULL THEN '✈ Flight'
                WHEN a.item_id IS NOT NULL THEN '🏨 Hotel'
                WHEN r.item_id IS NOT NULL THEN '🍽 Restaurant'
                WHEN ta2.item_id IS NOT NULL THEN '🎭 Attraction'
                ELSE 'Item' END AS item_type
    FROM includes inc
    JOIN travel_item ti ON ti.item_id = inc.item_id
    LEFT JOIN flight f ON f.item_id = ti.item_id
    LEFT JOIN accommodation a ON a.item_id = ti.item_id
    LEFT JOIN restaurant r ON r.item_id = ti.item_id
    LEFT JOIN tourist_attraction ta2 ON ta2.item_id = ti.item_id
    WHERE inc.package_id = ?
");
$stmtInc->execute([$id]); $includes = $stmtInc->fetchAll();

// Group trip info
$stmtGT = $db->prepare("SELECT * FROM group_trip WHERE package_id = ? LIMIT 1");
$stmtGT->execute([$id]); $groupTrip = $stmtGT->fetch();

// Reviews
$stmtRev = $db->prepare("
    SELECT pr.*, t.first_name, t.last_name, b.number_of_people
    FROM package_review pr
    JOIN booking b ON b.booking_id = pr.booking_id
    JOIN traveler t ON t.user_id = b.user_id
    WHERE b.package_id = ?
    ORDER BY pr.review_date DESC
");
$stmtRev->execute([$id]); $reviews = $stmtRev->fetchAll();

// Has the current user already booked? (for review eligibility)
$stmtMyBook = $db->prepare("
    SELECT b.booking_id, b.booking_status, b.payment_status, pr.review_id
    FROM booking b
    LEFT JOIN package_review pr ON pr.booking_id = b.booking_id
    WHERE b.package_id = ? AND b.user_id = ?
    LIMIT 1
");
$stmtMyBook->execute([$id, $user['user_id']]); $myBooking = $stmtMyBook->fetch();

// ── Handle POST: booking ───────────────────────────────────────────
$bookError = $bookSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bookError = 'Invalid request.';
    } elseif ($pkg['package_status'] !== 'active') {
        $bookError = 'This package is no longer available for booking.';
    } else {
        $numPeople = (int)($_POST['num_people'] ?? 1);
        if ($numPeople < 1) { $bookError = 'Must book for at least 1 person.'; }
        else {
            $total = $numPeople * $pkg['price_per_individual'];
            $db->prepare("
                INSERT INTO booking (number_of_people, total_price, booking_status, payment_status, package_id, user_id)
                VALUES (?, ?, 'confirmed', 'paid', ?, ?)
            ")->execute([$numPeople, $total, $id, $user['user_id']]);
            $bookSuccess = "Booking confirmed for $numPeople person(s)! Total: R " . number_format($total, 2);
            // Refresh booking info
            $stmtMyBook->execute([$id, $user['user_id']]); $myBooking = $stmtMyBook->fetch();
        }
    }
}

// ── Handle POST: review ────────────────────────────────────────────
$revError = $revSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $revError = 'Invalid request.';
    } elseif (!$myBooking || $myBooking['booking_status'] === 'cancelled') {
        $revError = 'You can only review packages you have booked.';
    } elseif ($myBooking['review_id']) {
        $revError = 'You have already left a review for this package.';
    } else {
        $rating  = max(1, min(5, (int)($_POST['rating']  ?? 0)));
        $comment = trim($_POST['comment'] ?? '');
        if (!$rating) { $revError = 'Please select a rating.'; }
        else {
            $db->prepare("
                INSERT INTO package_review (rating, comment, booking_id) VALUES (?,?,?)
            ")->execute([$rating, $comment ?: null, $myBooking['booking_id']]);
            $revSuccess = 'Review submitted!';
            header("Location: package.php?id=$id&msg=reviewed"); exit;
        }
    }
}

$flashMsg = $_GET['msg'] ?? '';
$pageTitle = $pkg['destinations'] ?? 'Package';
require_once __DIR__ . '/../includes/header.php';

$typeIcon = ['flight'=>'✈','accommodation'=>'🏨','restaurant'=>'🍽','attraction'=>'🎭'];
?>

<div class="container page-wrap">
  <!-- Hero -->
  <div class="package-hero">
    <?php if ($images): ?>
      <img src="<?= htmlspecialchars($images[0]) ?>" alt=""
           onerror="this.style.display='none'">
    <?php endif; ?>
    <div style="position:absolute;inset:0;background:linear-gradient(135deg,
      <?= ['#0c2a4a','#1a3a2e','#2a1a3a'][crc32($pkg['agency_name'])%3] ?> 0%,
      <?= ['#1a4a6a','#2a5a3e','#4a2a5a'][crc32($id)%3] ?> 100%);
      display:flex;align-items:center;justify-content:center;">
      <span style="font-size:6rem;opacity:0.1;">✈</span>
    </div>
    <div class="package-hero-overlay"></div>
    <div class="package-hero-info">
      <div class="section-label" style="margin-bottom:4px;"><?= htmlspecialchars($pkg['agency_name']) ?></div>
      <h1 style="color:white;margin-bottom:8px;"><?= htmlspecialchars($pkg['destinations'] ?? 'Travel Package') ?></h1>
      <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:0.85rem;color:rgba(255,255,255,0.7);">
        <span>📅 <?= date('d M Y', strtotime($pkg['start_date'])) ?> → <?= date('d M Y', strtotime($pkg['end_date'])) ?></span>
        <span>⏱ <?= $pkg['duration_days'] ?> nights</span>
        <?php if ($pkg['avg_rating']): ?>
          <span>⭐ <?= $pkg['avg_rating'] ?>/5 (<?= $pkg['review_count'] ?> reviews)</span>
        <?php endif; ?>
        <span class="status-badge status-<?= $pkg['package_status'] ?>"><?= str_replace('_',' ',$pkg['package_status']) ?></span>
      </div>
    </div>
  </div>

  <?php if ($flashMsg === 'reviewed'): ?>
    <div class="alert alert-success">✓ Your review has been submitted!</div>
  <?php endif; ?>
  <?php if ($bookSuccess): ?><div class="alert alert-success">✓ <?= htmlspecialchars($bookSuccess) ?></div><?php endif; ?>
  <?php if ($bookError):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($bookError) ?></div><?php endif; ?>

  <div class="package-detail-grid">
    <!-- Left: details -->
    <div>
      <!-- Tabs -->
      <div class="tabs">
        <button class="tab-btn active" data-tab-target="pkg-tabs" data-panel="overview">Overview</button>
        <button class="tab-btn" data-tab-target="pkg-tabs" data-panel="itinerary">Itinerary</button>
        <button class="tab-btn" data-tab-target="pkg-tabs" data-panel="reviews">
          Reviews <?php if ($pkg['review_count']): ?><span style="color:var(--text-faint);font-size:0.8rem;">(<?= $pkg['review_count'] ?>)</span><?php endif; ?>
        </button>
      </div>
      <div data-tab-group="pkg-tabs">

        <!-- Overview tab -->
        <div class="tab-panel active" data-tab-panel="pkg-tabs" data-panel="overview">

          <?php if ($includes): ?>
          <div class="detail-section">
            <h3>What's Included</h3>
            <div class="item-chips">
              <?php foreach ($includes as $inc): ?>
                <div class="item-chip">
                  <span class="chip-icon"><?= $typeIcon[$inc['item_type']] ?? '📦' ?></span>
                  <?= htmlspecialchars($inc['item_name'] ?? 'Item') ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($groupTrip): ?>
          <div class="detail-section">
            <h3>Group Trip Info</h3>
            <div class="card" style="padding:16px;">
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;text-align:center;">
                <div>
                  <div style="font-size:1.6rem;font-family:var(--font-display);color:var(--gold);"><?= $groupTrip['no_of_people'] ?></div>
                  <div class="text-sm text-muted">Travellers</div>
                </div>
                <div>
                  <div style="font-size:1.6rem;font-family:var(--font-display);color:var(--gold);"><?= $groupTrip['max_people'] ?></div>
                  <div class="text-sm text-muted">Max Capacity</div>
                </div>
                <div>
                  <div style="font-size:1.6rem;font-family:var(--font-display);color:var(--<?= $groupTrip['no_of_people'] >= $groupTrip['max_people'] ? 'danger' : 'teal' ?>);">
                    <?= $groupTrip['max_people'] - $groupTrip['no_of_people'] ?>
                  </div>
                  <div class="text-sm text-muted">Spots Left</div>
                </div>
              </div>
              <?php if ($groupTrip['max_people'] > 0): ?>
                <div style="margin-top:12px;height:4px;background:var(--surface);border-radius:2px;overflow:hidden;">
                  <div style="height:100%;width:<?= min(100, round($groupTrip['no_of_people']/$groupTrip['max_people']*100)) ?>%;background:var(--gold);border-radius:2px;"></div>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Agency info -->
          <div class="detail-section">
            <h3>About the Agency</h3>
            <div class="card" style="padding:16px;display:flex;align-items:center;gap:16px;">
              <div style="width:48px;height:48px;background:var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">🏢</div>
              <div>
                <div style="font-weight:600;color:var(--text);"><?= htmlspecialchars($pkg['agency_name']) ?></div>
                <div class="text-sm text-muted"><?= htmlspecialchars($pkg['agency_email']) ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Itinerary tab -->
        <div class="tab-panel" data-tab-panel="pkg-tabs" data-panel="itinerary">
          <?php if ($days): ?>
            <?php foreach ($days as $day): ?>
              <div class="itinerary-day">
                <div class="itinerary-day-num"><?= $day['day_number'] ?></div>
                <div style="flex:1;">
                  <div style="font-weight:600;color:var(--text);margin-bottom:4px;">Day <?= $day['day_number'] ?></div>
                  <div class="text-sm" style="color:var(--text-muted);margin-bottom:8px;"><?= htmlspecialchars($day['day_activity']) ?></div>
                  <?php if (!empty($dayItemsMap[$day['day_number']])): ?>
                    <div class="item-chips">
                      <?php foreach ($dayItemsMap[$day['day_number']] as $di): ?>
                        <div class="item-chip">
                          <?= $typeIcon[$di['item_type']] ?? '📦' ?>
                          <?= htmlspecialchars($di['item_name'] ?? 'Item') ?>
                          <?php if ($di['item_time']): ?>
                            <span style="color:var(--text-faint);">@ <?= substr($di['item_time'],0,5) ?></span>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="empty-state">
              <div class="icon">📅</div>
              <p>No itinerary details yet.</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Reviews tab -->
        <div class="tab-panel" data-tab-panel="pkg-tabs" data-panel="reviews">
          <?php if ($revError):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($revError) ?></div><?php endif; ?>
          <?php if ($revSuccess): ?><div class="alert alert-success">✓ <?= htmlspecialchars($revSuccess) ?></div><?php endif; ?>

          <?php if ($myBooking && !$myBooking['review_id'] && $myBooking['booking_status'] !== 'cancelled'): ?>
            <div class="card" style="margin-bottom:24px;">
              <h4 style="margin-bottom:16px;">Leave a Review</h4>
              <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="review">
                <div class="form-group">
                  <label class="form-label">Rating</label>
                  <div class="star-rating-widget">
                    <input type="hidden" name="rating" value="0">
                    <?php for ($s=1;$s<=5;$s++): ?>
                      <span class="star star-input" style="font-size:1.8rem;">★</span>
                    <?php endfor; ?>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Comment (optional)</label>
                  <textarea name="comment" class="form-control" placeholder="Share your experience…"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Review</button>
              </form>
            </div>
          <?php elseif ($myBooking && $myBooking['review_id']): ?>
            <div class="alert alert-info">✓ You have already reviewed this package.</div>
          <?php elseif (!$myBooking): ?>
            <div class="alert alert-info">Book this package to leave a review.</div>
          <?php endif; ?>

          <?php if ($reviews): ?>
            <?php foreach ($reviews as $rev): ?>
              <div class="review-card">
                <div class="review-header">
                  <div>
                    <span class="reviewer"><?= htmlspecialchars($rev['first_name'].' '.$rev['last_name']) ?></span>
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
          <?php else: ?>
            <div class="empty-state">
              <div class="icon">⭐</div>
              <p>No reviews yet. Be the first!</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Booking sidebar -->
    <div>
      <div class="booking-sidebar">
        <div class="price-display">
          <div class="amount">R <?= number_format($pkg['price_per_individual'], 0) ?></div>
          <div class="per">per person</div>
          <?php if ($pkg['avg_rating']): ?>
            <div class="stars" style="margin-top:8px;justify-content:center;">
              <?php for ($s=1;$s<=5;$s++): ?>
                <span class="star <?= $s<=round($pkg['avg_rating'])?'filled':'' ?>">★</span>
              <?php endfor; ?>
              <span style="font-size:0.78rem;color:var(--text-muted);margin-left:4px;"><?= $pkg['avg_rating'] ?></span>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($pkg['package_status'] === 'active' && !$myBooking): ?>
          <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="book">
            <input type="hidden" id="price-pp" value="<?= $pkg['price_per_individual'] ?>">
            <div class="form-group">
              <label class="form-label">Number of People</label>
              <input type="number" name="num_people" id="num-people" class="form-control"
                     min="1" max="<?= $groupTrip ? ($groupTrip['max_people'] - $groupTrip['no_of_people']) : 99 ?>"
                     value="1">
            </div>
            <div class="price-breakdown">
              <div class="price-row">
                <span>Price per person</span>
                <span>R <?= number_format($pkg['price_per_individual'], 2) ?></span>
              </div>
              <div class="price-row total">
                <span>Total</span>
                <span id="calc-total">R <?= number_format($pkg['price_per_individual'], 2) ?></span>
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Book Now</button>
          </form>

        <?php elseif ($myBooking): ?>
          <div class="alert alert-success" style="margin-bottom:12px;">
            ✓ You've booked this package!
          </div>
          <div class="price-breakdown">
            <div class="price-row">
              <span>Booking ID</span>
              <span>#<?= $myBooking['booking_id'] ?></span>
            </div>
            <div class="price-row">
              <span>Status</span>
              <span class="status-badge status-<?= $myBooking['booking_status'] ?>"><?= $myBooking['booking_status'] ?></span>
            </div>
          </div>
          <a href="../traveler/bookings.php" class="btn btn-outline btn-block mt-3">View My Trips</a>

        <?php elseif ($pkg['package_status'] === 'fully_booked'): ?>
          <div class="alert alert-error">This package is fully booked.</div>
        <?php else: ?>
          <div class="alert alert-info">This package is not available for booking.</div>
        <?php endif; ?>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);">
          <div style="font-size:0.78rem;color:var(--text-faint);display:flex;flex-direction:column;gap:6px;">
            <span>📅 <?= date('d M', strtotime($pkg['start_date'])) ?> – <?= date('d M Y', strtotime($pkg['end_date'])) ?></span>
            <span>⏱ <?= $pkg['duration_days'] ?> nights</span>
            <span>💰 Total pkg: R <?= number_format($pkg['total_price_of_package'], 0) ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
