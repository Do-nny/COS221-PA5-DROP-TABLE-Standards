<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserType('traveler', BASE_URL . '/');

$db = getDB();

// ── Build query with filters ──────────────────────────────────────────
$where  = ["tp.package_status != 'cancelled'"];
$params = [];

$filterDest     = trim($_GET['dest']     ?? '');
$filterMinPrice = trim($_GET['min_price']?? '');
$filterMaxPrice = trim($_GET['max_price']?? '');
$filterAgency   = trim($_GET['agency']   ?? '');
$filterStatus   = trim($_GET['status']   ?? '');
$sortBy         = $_GET['sort'] ?? 'start_date';

if ($filterDest) {
    $where[] = "(d.city LIKE ? OR d.country LIKE ?)";
    $params[] = "%$filterDest%"; $params[] = "%$filterDest%";
}
if ($filterMinPrice !== '') { $where[] = "tp.price_per_individual >= ?"; $params[] = $filterMinPrice; }
if ($filterMaxPrice !== '') { $where[] = "tp.price_per_individual <= ?"; $params[] = $filterMaxPrice; }
if ($filterAgency)  { $where[] = "ta.agency_name LIKE ?"; $params[] = "%$filterAgency%"; }
if ($filterStatus)  { $where[] = "tp.package_status = ?"; $params[] = $filterStatus; }

$orderMap = [
    'start_date'  => 'tp.start_date ASC',
    'price_asc'   => 'tp.price_per_individual ASC',
    'price_desc'  => 'tp.price_per_individual DESC',
    'rating'      => 'avg_rating DESC',
    'duration'    => 'DATEDIFF(tp.end_date, tp.start_date) ASC',
];
$order = $orderMap[$sortBy] ?? 'tp.start_date ASC';
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT tp.*,
       ta.agency_name,
       GROUP_CONCAT(DISTINCT CONCAT(d.city, ', ', d.country) ORDER BY d.city SEPARATOR ' · ') AS destinations,
       ROUND(AVG(pr.rating), 1) AS avg_rating,
       COUNT(DISTINCT pr.review_id) AS review_count,
       DATEDIFF(tp.end_date, tp.start_date) AS duration_days,
       MIN(tpi.image_url) AS hero_image
FROM travel_package tp
JOIN travel_agency ta ON ta.user_id = tp.agency_id
LEFT JOIN package_destinations pd ON pd.package_id = tp.package_id
LEFT JOIN destination d ON d.destination_id = pd.destination_id
LEFT JOIN booking b ON b.package_id = tp.package_id
LEFT JOIN package_review pr ON pr.booking_id = b.booking_id
LEFT JOIN travel_package_images tpi ON tpi.package_id = tp.package_id
$whereSQL
GROUP BY tp.package_id
ORDER BY $order
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll();

// Agencies for filter dropdown
$agencies = $db->query("SELECT user_id, agency_name FROM travel_agency ORDER BY agency_name")->fetchAll();

$pageTitle = 'Explore Packages';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-wrap">
  <!-- Page header -->
  <div class="page-header">
    <div class="page-header-flex">
      <div>
        <div class="section-label">Discover</div>
        <h1>Explore Packages</h1>
        <p><?= count($packages) ?> package<?= count($packages) !== 1 ? 's' : '' ?> available</p>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <form method="GET" id="filter-form">
    <div class="filters-bar">
      <div class="form-group" style="flex:2;min-width:180px;">
        <label class="form-label">Destination</label>
        <input type="text" name="dest" class="form-control"
               placeholder="City or country…" value="<?= htmlspecialchars($filterDest) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Min Price (R)</label>
        <input type="number" name="min_price" class="form-control" placeholder="0"
               value="<?= htmlspecialchars($filterMinPrice) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Max Price (R)</label>
        <input type="number" name="max_price" class="form-control" placeholder="Any"
               value="<?= htmlspecialchars($filterMaxPrice) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Agency</label>
        <select name="agency" class="form-control">
          <option value="">All agencies</option>
          <?php foreach ($agencies as $a): ?>
            <option value="<?= htmlspecialchars($a['agency_name']) ?>"
              <?= $filterAgency === $a['agency_name'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($a['agency_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All</option>
          <option value="active" <?= $filterStatus==='active'?'selected':'' ?>>Active</option>
          <option value="fully_booked" <?= $filterStatus==='fully_booked'?'selected':'' ?>>Fully Booked</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Sort By</label>
        <select name="sort" class="form-control">
          <option value="start_date" <?= $sortBy==='start_date'?'selected':'' ?>>Departure Date</option>
          <option value="price_asc"  <?= $sortBy==='price_asc' ?'selected':'' ?>>Price: Low→High</option>
          <option value="price_desc" <?= $sortBy==='price_desc'?'selected':'' ?>>Price: High→Low</option>
          <option value="rating"     <?= $sortBy==='rating'    ?'selected':'' ?>>Top Rated</option>
          <option value="duration"   <?= $sortBy==='duration'  ?'selected':'' ?>>Shortest First</option>
        </select>
      </div>
      <div style="display:flex;gap:8px;align-items:flex-end;padding-bottom:0;">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="browse.php" class="btn btn-ghost btn-sm">Reset</a>
      </div>
    </div>
  </form>

  <!-- Results -->
  <?php if (empty($packages)): ?>
    <div class="empty-state">
      <div class="icon">🌍</div>
      <h3>No packages found</h3>
      <p>Try adjusting your filters to find more results.</p>
    </div>
  <?php else: ?>
    <div class="package-grid">
      <?php foreach ($packages as $pkg): ?>
        <a href="package.php?id=<?= $pkg['package_id'] ?>" style="text-decoration:none;color:inherit;">
          <div class="package-card">
            <div class="package-card-img">
              <?php if ($pkg['hero_image']): ?>
                <img src="<?= htmlspecialchars($pkg['hero_image']) ?>"
                     alt="" onerror="this.style.display='none'">
              <?php endif; ?>
              <!-- Gradient placeholder showing destination -->
              <div style="position:absolute;inset:0;background:linear-gradient(135deg,
                <?= ['#0c2a4a','#1a3a2e','#2a1a3a','#0a2a2a','#2a1a0a'][crc32($pkg['agency_name'])%5] ?> 0%,
                <?= ['#1a4a6a','#2a5a3e','#4a2a5a','#1a4a4a','#4a2a1a'][crc32($pkg['package_id'])%5] ?> 100%);
                display:flex;align-items:center;justify-content:center;">
                <span style="font-size:3rem;opacity:0.3;">✈</span>
              </div>
              <div class="destination-badge">
                <?= htmlspecialchars(explode(',', $pkg['destinations'] ?? 'Unknown')[0]) ?>
              </div>
            </div>
            <div class="package-card-body">
              <div class="package-card-title">
                <?= htmlspecialchars($pkg['destinations'] ?? 'Multi-Destination') ?>
              </div>
              <div class="package-card-meta">
                <span>🏢 <?= htmlspecialchars($pkg['agency_name']) ?></span>
                <span>📅 <?= $pkg['duration_days'] ?> days</span>
                <?php if ($pkg['avg_rating']): ?>
                  <span>⭐ <?= $pkg['avg_rating'] ?> (<?= $pkg['review_count'] ?>)</span>
                <?php endif; ?>
              </div>
              <div style="font-size:0.78rem;color:var(--text-faint);">
                <?= date('d M Y', strtotime($pkg['start_date'])) ?> →
                <?= date('d M Y', strtotime($pkg['end_date'])) ?>
              </div>
              <div class="package-card-footer">
                <div class="package-price">
                  R <?= number_format($pkg['price_per_individual'], 0) ?>
                  <small>/person</small>
                </div>
                <span class="status-badge status-<?= $pkg['package_status'] ?>">
                  <?= str_replace('_', ' ', $pkg['package_status']) ?>
                </span>
              </div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
