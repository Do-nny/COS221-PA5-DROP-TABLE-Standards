<?php
require_once __DIR__ . '/db.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function requireLogin(string $redirect = '/'): void {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function requireUserType(string $type, string $redirect = '/'): void {
    requireLogin($redirect);
    if ($_SESSION['user_type'] !== $type) {
        header("Location: $redirect");
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'user_id'   => $_SESSION['user_id'],
        'user_type' => $_SESSION['user_type'],
        'name'      => $_SESSION['user_name'],
        'email'     => $_SESSION['user_email'],
    ];
}

function loginTraveler(string $email, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT user_id, first_name, last_name, email, password_hash FROM traveler WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        startSession();
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['user_type'] = 'traveler';
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email']= $user['email'];
        return true;
    }
    return false;
}

function loginAgency(string $email, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT user_id, agency_name, email, password_hash FROM travel_agency WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        startSession();
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['user_type'] = 'agency';
        $_SESSION['user_name'] = $user['agency_name'];
        $_SESSION['user_email']= $user['email'];
        return true;
    }
    return false;
}

function registerTraveler(array $data): array {
    $db = getDB();
    // Check for existing email
    $stmt = $db->prepare("SELECT user_id FROM traveler WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) return ['success' => false, 'message' => 'Email already registered.'];

    $hash = password_hash($data['password'], PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO traveler (phone_number, email, password_hash, id_number, residing_country, date_of_birth, first_name, last_name) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $data['phone'], $data['email'], $hash, $data['id_number'],
        $data['country'], $data['dob'], $data['first_name'], $data['last_name']
    ]);
    return ['success' => true];
}

function registerAgency(array $data): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT user_id FROM travel_agency WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) return ['success' => false, 'message' => 'Email already registered.'];

    $hash = password_hash($data['password'], PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO travel_agency (phone_number, email, password_hash, agency_name) VALUES (?,?,?,?)");
    $stmt->execute([$data['phone'], $data['email'], $hash, $data['agency_name']]);
    $agencyId = $db->lastInsertId();

    // Insert additional phone numbers
    if (!empty($data['extra_phones'])) {
        foreach ($data['extra_phones'] as $ph) {
            $ph = trim($ph);
            if ($ph) {
                $stmt2 = $db->prepare("INSERT IGNORE INTO agency_phone_number (user_id, agency_cellphone_number) VALUES (?,?)");
                $stmt2->execute([$agencyId, $ph]);
            }
        }
    }
    return ['success' => true];
}

function logout(): void {
    startSession();
    session_destroy();
}

// CSRF protection
function csrfToken(): string {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}
