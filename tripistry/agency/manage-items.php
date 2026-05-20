<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserType('agency', BASE_URL . '/');

$db   = getDB();
$user = currentUser();
$aid  = $user['user_id'];
$pid  = (int)($_GET['id'] ?? 0);

if (!$pid) { header('Location: dashboard.php'); exit; }

// Ownership check
$stmt = $db->prepare("SELECT * FROM travel_package WHERE package_id=? AND agency_id=?");
$stmt->execute([$pid, $aid]); $pkg = $stmt->fetch();
if (!$pkg) { header('Location: dashboard.php'); exit; }

$flash = '';

// ── Handle POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $flash = 'error:Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_flight') {
            $db->beginTransaction();
            $db->exec("INSERT INTO travel_item () VALUES ()");
            $iid = $db->lastInsertId();
            $db->prepare("INSERT INTO flight (item_id,airline,flight_price,departure_date_time,arrival_date_time,departure_airport,arrival_airport)
                VALUES (?,?,?,?,?,?,?)")->execute([
                $iid,
                trim($_POST['airline'] ?? ''),
                (float)($_POST['flight_price'] ?? 0),
                $_POST['dep_dt'] ?? '',
                $_POST['arr_dt'] ?? '',
                trim($_POST['dep_airport'] ?? ''),
                trim($_POST['arr_airport'] ?? ''),
            ]);
            $db->prepare("INSERT IGNORE INTO includes (package_id,item_id) VALUES (?,?)")->execute([$pid,$iid]);
            $db->commit();
            $flash = 'success:Flight added.';
        }

        elseif ($action === 'add_accommodation') {
            $db->beginTransaction();
            $db->exec("INSERT INTO travel_item () VALUES ()");
            $iid = $db->lastInsertId();
            $db->prepare("INSERT INTO accommodation (item_id,accommodation_name,accommodation_price,checkin_date_time,checkout_date_time,price_per_night_pp,accommodation_street,accommodation_number,accommodation_town)
                VALUES (?,?,?,?,?,?,?,?,?)")->execute([
                $iid,
                trim($_POST['acc_name'] ?? ''),
                (float)($_POST['acc_price'] ?? 0),
                $_POST['checkin'] ?? '',
                $_POST['checkout'] ?? '',
                (float)($_POST['price_night'] ?? 0),
                trim($_POST['acc_street'] ?? ''),
                trim($_POST['acc_number'] ?? ''),
                trim($_POST['acc_town'] ?? ''),
            ]);
            $db->prepare("INSERT IGNORE INTO includes (package_id,item_id) VALUES (?,?)")->execute([$pid,$iid]);
            $db->commit();
            $flash = 'success:Accommodation added.';
        }

        elseif ($action === 'add_restaurant') {
            $db->beginTransaction();
            $db->exec("INSERT INTO travel_item () VALUES ()");
            $iid = $db->lastInsertId();
            $db->prepare("INSERT INTO restaurant (item_id,restaurant_name,average_price_pp,operational_hours,restaurant_street,restaurant_number,restaurant_town)
                VALUES (?,?,?,?,?,?,?)")->execute([
                $iid,
                trim($_POST['rest_name'] ?? ''),
                (float)($_POST['avg_price'] ?? 0),
                trim($_POST['hours'] ?? '') ?: null,
                trim($_POST['rest_street'] ?? ''),
                trim($_POST['rest_number'] ?? ''),
                trim($_POST['rest_town'] ?? ''),
            ]);
            $db->prepare("INSERT IGNORE INTO includes (package_id,item_id) VALUES (?,?)")->execute([$pid,$iid]);
            $db->commit();
            $flash = 'success:Restaurant added.';
        }

        elseif ($action === 'add_attraction') {
            $db->beginTransaction();
            $db->exec("INSERT INTO travel_item () VALUES ()");
            $iid = $db->lastInsertId();
            $db->prepare("INSERT INTO tourist_attraction (item_id,attraction_name,activity_fee,operational_hours,attraction_street,attraction_number,attraction_town)
                VALUES (?,?,?,?,?,?,?)")->execute([
                $iid,
                trim($_POST['attr_name'] ?? ''),
                (float)($_POST['activity_fee'] ?? 0),
                trim($_POST['attr_hours'] ?? '') ?: null,
                trim($_POST['attr_street'] ?? ''),
                trim($_POST['attr_number'] ?? ''),
                trim($_POST['attr_town'] ?? ''),
            ]);
            $db->prepare("INSERT IGNORE INTO includes (package_id,item_id) VALUES (?,?)")->execute([$pid,$iid]);
            $db->commit();
            $flash = 'success:Attraction added.';
        }

        elseif ($action === 'remove_item') {
            $iid = (int)($_POST['item_id'] ?? 0);
            $db->prepare("DELETE FROM includes WHERE package_id=? AND item_id=?")->execute([$pid, $iid]);
            $flash = 'success:Item removed from package.';
        }

        elseif ($action === 'add_group_trip') {
            $max = (int)($_POST['max_people'] ?? 0);
            $cur = (int)($_POST['no_of_people'] ?? 0);
            // Check if group_trip exists
            $gt = $db->prepare("SELECT group_trip_id FROM group_trip WHERE package_id=? LIMIT 1");
            $gt->execute([$pid]); $gt = $gt->fetch();
            if ($gt) {
                $db->prepare("UPDATE group_trip SET max_people=?,no_of_people=? WHERE package_id=? AND group_trip_id=?")->execute([$max,$cur,$pid,$gt['group_trip_id']]);
            } else {
                $db->prepare("INSERT INTO group_trip (package_id,group_trip_id,no_of_people,max_people) VALUES (?,1,?,?)")->execute([$pid,$cur,$max]);
            }
            $flash = 'success:Group trip settings saved.';
        }
    }
    header("Location: manage-items.php?id=$pid&flash=" . urlencode($flash)); exit;
}

$flash = $_GET['flash'] ?? $_GET['msg'] ?? '';
if ($flash === 'saved') $flash = 'success:Package saved. Now add your items.';

// ── Fetch current items ──────────────────────────────────────────────
$stmtItems = $db->prepare("
    SELECT ti.item_id,
           COALESCE(f.airline, a.accommodation_name, r.restaurant_name, ta2.attraction_name) AS item_name,
           CASE WHEN f.item_id IS NOT NULL THEN 'flight'
                WHEN a.item_id IS NOT NULL THEN 'accommodation'
                WHEN r.item_id IS NOT NULL THEN 'restaurant'
                WHEN ta2.item_id IS NOT NULL THEN 'attraction' END AS item_type,
           f.flight_price, f.departure_date_time, f.arrival_date_time, f.departure_airport, f.arrival_airport,
           a.accommodation_price, a.checkin_date_time, a.checkout_date_time,
           r.average_price_pp, r.operational_hours,
           ta2.activity_fee, ta2.operational_hours AS attr_hours
    FROM includes inc
    JOIN travel_item ti ON ti.item_id = inc.item_id
    LEFT JOIN flight f ON f.item_id = ti.item_id
    LEFT JOIN accommodation a ON a.item_id = ti.item_id
    LEFT JOIN restaurant r ON r.item_id = ti.item_id
    LEFT JOIN tourist_attraction ta2 ON ta2.item_id = ti.item_id
    WHERE inc.package_id = ?
");
$stmtItems->execute([$pid]); $items = $stmtItems->fetchAll();

$stmtGT = $db->prepare("SELECT * FROM group_trip WHERE package_id=? LIMIT 1");
$stmtGT->execute([$pid]); $groupTrip = $stmtGT->fetch();

$typeIcon = ['flight'=>'✈','accommodation'=>'🏨','restaurant'=>'🍽','attraction'=>'🎭'];

$pageTitle = 'Manage Items';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-wrap">
  <?php if ($flash): ?>
    <?php [$ft,$fm] = explode(':',$flash,2); ?>
    <div class="alert alert-<?= $ft==='error'?'error':'success' ?>"><?= htmlspecialchars($fm) ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div style="display:flex;gap:12px;margin-bottom:12px;">
      <a href="dashboard.php" class="btn btn-ghost btn-sm">← Dashboard</a>
      <a href="package-form.php?id=<?= $pid ?>" class="btn btn-outline btn-sm">Edit Package</a>
    </div>
    <h1>Package Items</h1>
    <p>Package #<?= $pid ?> — Add flights, accommodation, restaurants & attractions.</p>
  </div>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:32px;align-items:start;">
    <!-- Current items list -->
    <div>
      <h3 style="margin-bottom:16px;font-size:1rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);">Current Items (<?= count($items) ?>)</h3>
      <?php if (empty($items)): ?>
        <div class="empty-state" style="margin-bottom:24px;">
          <div class="icon">📦</div>
          <p>No items yet. Use the forms to add items.</p>
        </div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:32px;">
          <?php foreach ($items as $item): ?>
            <div class="card" style="padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
              <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:1.5rem;"><?= $typeIcon[$item['item_type']] ?? '📦' ?></span>
                <div>
                  <div style="font-weight:600;color:var(--text);"><?= htmlspecialchars($item['item_name'] ?? 'Item #'.$item['item_id']) ?></div>
                  <div class="text-xs text-muted">
                    <?= ucfirst($item['item_type'] ?? '') ?> · ID #<?= $item['item_id'] ?>
                    <?php if ($item['flight_price']): ?> · R <?= number_format($item['flight_price'],2) ?><?php endif; ?>
                    <?php if ($item['accommodation_price']): ?> · R <?= number_format($item['accommodation_price'],2) ?><?php endif; ?>
                    <?php if ($item['average_price_pp']): ?> · R <?= number_format($item['average_price_pp'],2) ?>/pp<?php endif; ?>
                    <?php if ($item['activity_fee'] !== null && $item['item_type']==='attraction'): ?> · R <?= number_format($item['activity_fee'],2) ?> fee<?php endif; ?>
                  </div>
                </div>
              </div>
              <form method="POST" style="margin:0;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="remove_item">
                <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm"
                        data-confirm="Remove this item from the package?">Remove</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Group Trip -->
      <div class="card">
        <h3 style="margin-bottom:16px;">🧭 Group Trip Settings</h3>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="add_group_trip">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Current Participants</label>
              <input type="number" name="no_of_people" class="form-control" min="0"
                     value="<?= $groupTrip['no_of_people'] ?? 0 ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Max Capacity</label>
              <input type="number" name="max_people" class="form-control" min="1"
                     value="<?= $groupTrip['max_people'] ?? 20 ?>">
            </div>
          </div>
          <button type="submit" class="btn btn-success btn-sm">Save Group Settings</button>
        </form>
      </div>
    </div>

    <!-- Add item forms -->
    <div>
      <!-- Tabs for item types -->
      <div class="tabs" style="margin-bottom:20px;">
        <button class="tab-btn active" data-tab-target="item-tabs" data-panel="flight">✈ Flight</button>
        <button class="tab-btn" data-tab-target="item-tabs" data-panel="hotel">🏨 Hotel</button>
        <button class="tab-btn" data-tab-target="item-tabs" data-panel="rest">🍽 Rest.</button>
        <button class="tab-btn" data-tab-target="item-tabs" data-panel="attr">🎭 Attr.</button>
      </div>
      <div data-tab-group="item-tabs">

        <!-- Flight -->
        <div class="tab-panel active" data-tab-panel="item-tabs" data-panel="flight">
          <div class="card">
            <h4 style="margin-bottom:16px;">Add Flight</h4>
            <form method="POST">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="add_flight">
              <div class="form-group">
                <label class="form-label">Airline *</label>
                <input type="text" name="airline" class="form-control" placeholder="South African Airways" required>
              </div>
              <div class="form-group">
                <label class="form-label">Price (R) *</label>
                <input type="number" name="flight_price" class="form-control" step="0.01" placeholder="850.00" required>
              </div>
              <div class="form-group">
                <label class="form-label">Departure Airport</label>
                <input type="text" name="dep_airport" class="form-control" placeholder="OR Tambo (JNB)">
              </div>
              <div class="form-group">
                <label class="form-label">Departure Date/Time</label>
                <input type="datetime-local" name="dep_dt" class="form-control">
              </div>
              <div class="form-group">
                <label class="form-label">Arrival Airport</label>
                <input type="text" name="arr_airport" class="form-control" placeholder="Cape Town Intl (CPT)">
              </div>
              <div class="form-group">
                <label class="form-label">Arrival Date/Time</label>
                <input type="datetime-local" name="arr_dt" class="form-control">
              </div>
              <button type="submit" class="btn btn-primary btn-block">Add Flight</button>
            </form>
          </div>
        </div>

        <!-- Hotel -->
        <div class="tab-panel" data-tab-panel="item-tabs" data-panel="hotel">
          <div class="card">
            <h4 style="margin-bottom:16px;">Add Accommodation</h4>
            <form method="POST">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="add_accommodation">
              <div class="form-group">
                <label class="form-label">Name *</label>
                <input type="text" name="acc_name" class="form-control" placeholder="The Table Bay Hotel" required>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Total Price (R)</label>
                  <input type="number" name="acc_price" class="form-control" step="0.01" placeholder="9000.00">
                </div>
                <div class="form-group">
                  <label class="form-label">Per Night/pp (R)</label>
                  <input type="number" name="price_night" class="form-control" step="0.01" placeholder="100.00">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Check-In</label>
                  <input type="datetime-local" name="checkin" class="form-control">
                </div>
                <div class="form-group">
                  <label class="form-label">Check-Out</label>
                  <input type="datetime-local" name="checkout" class="form-control">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Street</label>
                  <input type="text" name="acc_street" class="form-control" placeholder="Quay 6">
                </div>
                <div class="form-group">
                  <label class="form-label">Number</label>
                  <input type="text" name="acc_number" class="form-control" placeholder="6">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Town/City</label>
                <input type="text" name="acc_town" class="form-control" placeholder="Cape Town">
              </div>
              <button type="submit" class="btn btn-primary btn-block">Add Accommodation</button>
            </form>
          </div>
        </div>

        <!-- Restaurant -->
        <div class="tab-panel" data-tab-panel="item-tabs" data-panel="rest">
          <div class="card">
            <h4 style="margin-bottom:16px;">Add Restaurant</h4>
            <form method="POST">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="add_restaurant">
              <div class="form-group">
                <label class="form-label">Name *</label>
                <input type="text" name="rest_name" class="form-control" placeholder="La Colombe" required>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Avg Price/pp (R)</label>
                  <input type="number" name="avg_price" class="form-control" step="0.01" placeholder="85.00">
                </div>
                <div class="form-group">
                  <label class="form-label">Hours</label>
                  <input type="text" name="hours" class="form-control" placeholder="12:00–22:00">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Street</label>
                  <input type="text" name="rest_street" class="form-control">
                </div>
                <div class="form-group">
                  <label class="form-label">Number</label>
                  <input type="text" name="rest_number" class="form-control">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Town/City</label>
                <input type="text" name="rest_town" class="form-control">
              </div>
              <button type="submit" class="btn btn-primary btn-block">Add Restaurant</button>
            </form>
          </div>
        </div>

        <!-- Attraction -->
        <div class="tab-panel" data-tab-panel="item-tabs" data-panel="attr">
          <div class="card">
            <h4 style="margin-bottom:16px;">Add Attraction</h4>
            <form method="POST">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="add_attraction">
              <div class="form-group">
                <label class="form-label">Name *</label>
                <input type="text" name="attr_name" class="form-control" placeholder="Table Mountain Aerial Cableway" required>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Activity Fee (R)</label>
                  <input type="number" name="activity_fee" class="form-control" step="0.01" placeholder="0.00" value="0">
                </div>
                <div class="form-group">
                  <label class="form-label">Hours</label>
                  <input type="text" name="attr_hours" class="form-control" placeholder="08:00–18:00">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Street</label>
                  <input type="text" name="attr_street" class="form-control">
                </div>
                <div class="form-group">
                  <label class="form-label">Number</label>
                  <input type="text" name="attr_number" class="form-control">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Town/City</label>
                <input type="text" name="attr_town" class="form-control">
              </div>
              <button type="submit" class="btn btn-primary btn-block">Add Attraction</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
