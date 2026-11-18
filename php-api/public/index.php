<?php
declare(strict_types=1);

// With "php -S", let real files (item photos in /uploads) be served directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
    if (is_file($file) && basename($file) !== 'index.php') {
        return false;
    }
}

// ---------- config ----------
$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) { continue; }
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim(trim($v), "\"'"));
    }
}
function cfg(string $k, string $default = ''): string { $v = getenv($k); return ($v === false || $v === '') ? $default : $v; }

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Vary: Origin');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = array_filter(array_map('trim', explode(',', cfg('CORS_ALLOWED_ORIGINS', 'http://127.0.0.1:4200,http://localhost:4200,http://localhost,capacitor://localhost'))));
$originAllowed = $origin !== '' && in_array($origin, $allowedOrigins, true);
if (! $originAllowed && $origin !== '') {
    $originParts = parse_url($origin);
    $apiHost = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    $originAllowed = is_array($originParts)
        && in_array(strtolower($originParts['scheme'] ?? ''), ['http', 'https'], true)
        && (int) ($originParts['port'] ?? 0) === 4200
        && is_string($apiHost)
        && strcasecmp($originParts['host'] ?? '', $apiHost) === 0;
}
if ($originAllowed) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    if ($origin !== '' && ! $originAllowed) { fail(403, 'This app origin is not allowed.'); }
    http_response_code(204);
    exit;
}

// ---------- helpers ----------
function out(int $code, array $data): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function fail(int $code, string $message, array $errors = []): never { out($code, ['ok' => false, 'message' => $message, 'errors' => (object) $errors]); }
function ensure(array $err): void { if ($err) { fail(422, 'Please fix the highlighted fields.', $err); } }

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) { return $pdo; }
    try {
        return $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', cfg('DB_HOST', '127.0.0.1'), cfg('DB_PORT', '3306'), cfg('DB_DATABASE', 'lost_found_db')),
            cfg('DB_USERNAME', 'root'), cfg('DB_PASSWORD', ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 5]
        );
    } catch (PDOException $e) {
        error_log('LostFound database connection failed: ' . $e->getMessage());
        fail(503, 'The database is unavailable. Start MySQL in XAMPP and try again.');
    }
}
function q(string $sql, array $params = []): PDOStatement { $s = db()->prepare($sql); $s->execute($params); return $s; }

function body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) { return []; }
    if (strlen($raw) > 3 * 1024 * 1024) { fail(413, 'Request is too large. Reduce the photo size and try again.'); }
    $d = json_decode($raw, true);
    if (!is_array($d)) { fail(400, 'The request body must be valid JSON.'); }
    return $d;
}
function text(array $in, string $k, int $min, int $max, array &$err, bool $required = true): string
{
    $raw = $in[$k] ?? '';
    $v = is_scalar($raw) ? trim((string) $raw) : '';
    if ($v === '' && !$required) { return ''; }
    $n = mb_strlen($v);
    if ($n < $min || $n > $max) { $err[$k] = $v === '' ? 'This field is required.' : "Use $min to $max characters."; }
    return $v;
}
function logAct(?int $uid, string $action, ?string $details = null): void
{
    q('INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?,?,?,?)', [$uid, $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

// ---------- auth ----------
function issueToken(int $uid): string
{
    $t = bin2hex(random_bytes(32));
    $days = max(1, (int) cfg('TOKEN_DAYS', '7'));
    q("INSERT INTO api_tokens (user_id, token_hash, expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL $days DAY))", [$uid, hash('sha256', $t)]);
    return $t;
}
function bearer(): ?string
{
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    return preg_match('/^Bearer\s+([a-f0-9]{64})$/i', $h, $m) ? strtolower($m[1]) : null;
}
function auth(): array
{
    $t = bearer();
    if (!$t) { fail(401, 'Please sign in.'); }
    $u = q("SELECT u.* FROM api_tokens t JOIN users u ON u.id = t.user_id WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.is_active = 1 AND u.role = 'user'", [hash('sha256', $t)])->fetch();
    if (!$u) { fail(401, 'Your session has expired. Please sign in again.'); }
    return $u;
}
function publicUser(array $u): array
{
    return ['id' => (int) $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'student_id' => $u['student_id'], 'phone' => $u['phone']];
}
function validPassword(string $pw): bool
{
    return strlen($pw) >= 8 && strlen($pw) <= 72 && preg_match('/[A-Za-z]/', $pw) && preg_match('/\d/', $pw);
}

// ---------- photos ----------
function uploadDir(): string
{
    $d = cfg('UPLOAD_DIR', __DIR__ . '/uploads');
    if (!is_dir($d)) { mkdir($d, 0755, true); }
    return $d;
}
function photoUrl(?string $f): ?string
{
    if (!$f) { return null; }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = cfg('PUBLIC_URL', $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return rtrim($base, '/') . '/uploads/' . rawurlencode($f);
}
function savePhoto(mixed $data, array &$err): ?string
{
    if ($data === null || $data === '') { return null; }
    if (!is_string($data)) { $err['photo'] = 'The photo is not valid.'; return null; }
    if (preg_match('#^data:image/[a-z+.-]+;base64,#i', $data)) { $data = substr($data, strpos($data, ',') + 1); }
    $bin = base64_decode($data, true);
    if ($bin === false || strlen($bin) > 2 * 1024 * 1024) { $err['photo'] = 'The photo must be a valid image under 2 MB.'; return null; }
    $info = @getimagesizefromstring($bin);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
    if (!$ext) { $err['photo'] = 'The photo must be a JPG, PNG or WEBP image.'; return null; }
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    file_put_contents(uploadDir() . '/' . $name, $bin);
    return $name;
}

// ---------- items ----------
const ITEM_COLS = "SELECT i.id, i.type, i.name, i.description, i.category_id, c.name AS category, i.location, i.date_event, i.status, i.photo_path,
    (i.reported_by = ?) AS mine, IF(i.reported_by = ?, i.admin_note, NULL) AS admin_note, i.created_at
    FROM items i JOIN categories c ON c.id = i.category_id";

function itemOut(array $r): array
{
    $r['id'] = (int) $r['id'];
    $r['mine'] = (bool) $r['mine'];
    $r['photo_url'] = photoUrl($r['photo_path']);
    unset($r['photo_path']);
    return $r;
}

function listItems(array $me, bool $mineOnly): never
{
    $w = $mineOnly ? ['i.reported_by = ?'] : ["(i.status IN ('listed','claimed') OR i.reported_by = ?)"];
    $p = [$me['id'], $me['id'], $me['id']];
    $type = $_GET['type'] ?? '';
    if (in_array($type, ['lost', 'found'], true)) { $w[] = 'i.type = ?'; $p[] = $type; }
    $cat = (int) ($_GET['category_id'] ?? 0);
    if ($cat > 0) { $w[] = 'i.category_id = ?'; $p[] = $cat; }
    $term = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    if ($term !== '') {
        $like = '%' . addcslashes(mb_substr($term, 0, 80), '%_\\') . '%';
        $w[] = '(i.name LIKE ? OR i.location LIKE ? OR i.description LIKE ?)';
        array_push($p, $like, $like, $like);
    }
    $per = 20;
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $off = ($page - 1) * $per;
    $rows = q(ITEM_COLS . ' WHERE ' . implode(' AND ', $w) . " ORDER BY i.created_at DESC, i.id DESC LIMIT $per OFFSET $off", $p)->fetchAll();
    out(200, ['ok' => true, 'items' => array_map('itemOut', $rows), 'page' => $page, 'has_more' => count($rows) === $per]);
}

function showItem(array $me, int $id): never
{
    $r = q(ITEM_COLS . " WHERE i.id = ? AND (i.status IN ('listed','claimed') OR i.reported_by = ?)", [$me['id'], $me['id'], $id, $me['id']])->fetch();
    if (!$r) { fail(404, 'That item was not found.'); }
    $claim = q('SELECT id, status, admin_remarks, created_at FROM claims WHERE item_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1', [$id, $me['id']])->fetch();
    out(200, ['ok' => true, 'item' => itemOut($r), 'my_claim' => $claim ?: null]);
}

function reportLost(array $me): never
{
    $in = body();
    $err = [];
    $name = text($in, 'name', 2, 150, $err);
    $desc = text($in, 'description', 5, 2000, $err);
    $loc = text($in, 'location', 2, 150, $err);
    $cat = (int) ($in['category_id'] ?? 0);
    if (!$cat || !q('SELECT 1 FROM categories WHERE id = ?', [$cat])->fetch()) { $err['category_id'] = 'Choose a category.'; }
    $date = is_scalar($in['date_event'] ?? null) ? trim((string) $in['date_event']) : '';
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) { $err['date_event'] = 'Enter the date you lost it.'; }
    elseif ($date > date('Y-m-d')) { $err['date_event'] = 'The date cannot be in the future.'; }
    if (!$err && (int) q("SELECT COUNT(*) c FROM items WHERE reported_by = ? AND type = 'lost' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)", [$me['id']])->fetch()['c'] >= 10) {
        fail(429, 'You have reached the limit of 10 lost reports per day.');
    }
    $photo = null;
    if (!$err) { $photo = savePhoto($in['photo'] ?? null, $err); }
    ensure($err);

    q("INSERT INTO items (type, name, description, category_id, location, date_event, status, reported_by, photo_path, created_at, updated_at) VALUES ('lost',?,?,?,?,?,'pending',?,?,NOW(),NOW())",
        [$name, $desc, $cat, $loc, $date, $me['id'], $photo]);
    $id = (int) db()->lastInsertId();
    logAct((int) $me['id'], 'lost_reported', "Item #$id: $name");
    out(201, ['ok' => true, 'message' => 'Report sent. The office will review it.', 'id' => $id]);
}

// ---------- claims ----------
function createClaim(array $me): never
{
    $in = body();
    $err = [];
    $itemId = (int) ($in['item_id'] ?? 0);
    $proof = text($in, 'proof_of_ownership', 10, 2000, $err);
    $det = text($in, 'identifying_details', 0, 1000, $err, false);
    ensure($err);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Serialize submissions by this user and by this item. The item lock
        // also coordinates with the admin approval transaction, preventing a
        // claim from being inserted after the item has already been awarded.
        q('SELECT id FROM users WHERE id = ? FOR UPDATE', [$me['id']])->fetch();
        $item = q('SELECT id, type, status FROM items WHERE id = ? FOR UPDATE', [$itemId])->fetch();
        if (!$item || $item['type'] !== 'found') {
            $pdo->rollBack();
            fail(404, 'That item was not found.');
        }
        if ($item['status'] !== 'listed') {
            $pdo->rollBack();
            fail(409, 'This item can no longer be claimed.');
        }
        if (q("SELECT 1 FROM claims WHERE item_id = ? AND user_id = ? AND status IN ('pending','approved')", [$itemId, $me['id']])->fetch()) {
            $pdo->rollBack();
            fail(409, 'You already have an active claim on this item.');
        }
        if ((int) q("SELECT COUNT(*) c FROM claims WHERE user_id = ? AND status = 'pending'", [$me['id']])->fetch()['c'] >= 5) {
            $pdo->rollBack();
            fail(429, 'You have 5 claims waiting for review. Wait for a decision before claiming more.');
        }
        q('INSERT INTO claims (item_id, user_id, proof_of_ownership, identifying_details, status, created_at, updated_at) VALUES (?,?,?,?,\'pending\',NOW(),NOW())',
            [$itemId, $me['id'], $proof, $det ?: null]);
        $id = (int) $pdo->lastInsertId();
        logAct((int) $me['id'], 'claim_submitted', "Claim #$id on item #$itemId");
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
    out(201, ['ok' => true, 'message' => 'Claim sent. You will see the decision under Activity.', 'id' => $id]);
}

function myClaims(array $me): never
{
    $rows = q('SELECT c.id, c.status, c.admin_remarks, c.proof_of_ownership, c.created_at, c.reviewed_at, i.id AS item_id, i.name AS item_name, i.status AS item_status, i.location AS item_location
        FROM claims c JOIN items i ON i.id = c.item_id WHERE c.user_id = ? ORDER BY c.created_at DESC, c.id DESC', [$me['id']])->fetchAll();
    out(200, ['ok' => true, 'claims' => $rows]);
}

function cancelClaim(array $me, int $id): never
{
    $s = q("DELETE FROM claims WHERE id = ? AND user_id = ? AND status = 'pending'", [$id, $me['id']]);
    if ($s->rowCount() === 0) { fail(409, 'Only your pending claims can be cancelled.'); }
    logAct((int) $me['id'], 'claim_cancelled', "Claim #$id");
    out(200, ['ok' => true, 'message' => 'Claim cancelled.']);
}

// ---------- account ----------
function register(): never
{
    $in = body();
    $err = [];
    $name = text($in, 'name', 2, 120, $err);
    $email = strtolower(text($in, 'email', 5, 150, $err));
    if (!isset($err['email']) && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $err['email'] = 'Enter a valid email address.'; }
    $pw = is_string($in['password'] ?? null) ? $in['password'] : '';
    if (!validPassword($pw)) { $err['password'] = 'Use 8 to 72 characters with letters and numbers.'; }
    $sid = text($in, 'student_id', 0, 40, $err, false);
    $phone = text($in, 'phone', 0, 30, $err, false);
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,30}$/', $phone)) { $err['phone'] = 'Enter a valid phone number.'; }
    ensure($err);

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip !== '' && (int) q("SELECT COUNT(*) c FROM activity_logs WHERE action = 'user_registered' AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)", [$ip])->fetch()['c'] >= 10) {
        fail(429, 'Too many accounts were created from this network. Try again later.');
    }

    if (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
        fail(409, 'That email is already registered.', ['email' => 'That email is already registered.']);
    }
    try {
        q("INSERT INTO users (name, email, password, role, student_id, phone, is_active, created_at, updated_at) VALUES (?,?,?,'user',?,?,1,NOW(),NOW())",
            [$name, $email, password_hash($pw, PASSWORD_BCRYPT), $sid ?: null, $phone ?: null]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            fail(409, 'That email is already registered. Try signing in instead.', ['email' => 'That email is already registered. Try signing in instead.']);
        }
        throw $e;
    }
    $id = (int) db()->lastInsertId();
    logAct($id, 'user_registered', $email);
    $u = q('SELECT * FROM users WHERE id = ?', [$id])->fetch();
    out(201, ['ok' => true, 'token' => issueToken($id), 'user' => publicUser($u)]);
}

function login(): never
{
    $in = body();
    $email = strtolower(is_string($in['email'] ?? null) ? trim($in['email']) : '');
    $pw = is_string($in['password'] ?? null) ? $in['password'] : '';
    if ($email === '' || $pw === '') { fail(422, 'Enter your email and password.'); }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $fails = (int) q("SELECT COUNT(*) c FROM activity_logs WHERE action = 'login_failed' AND details = ? AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$email, $ip])->fetch()['c'];
    $ipFails = $ip === '' ? 0 : (int) q("SELECT COUNT(*) c FROM activity_logs WHERE action = 'login_failed' AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$ip])->fetch()['c'];
    if ($fails >= 5 || $ipFails >= 30) { fail(429, 'Too many failed attempts. Try again in 15 minutes.'); }

    $u = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    if (!$u || !password_verify($pw, $u['password'])) {
        logAct($u ? (int) $u['id'] : null, 'login_failed', $email);
        fail(401, 'Incorrect email or password.');
    }
    if ($u['role'] !== 'user') { fail(403, 'Admins sign in on the web, not in the app.'); }
    if (!$u['is_active']) { fail(403, 'This account is deactivated. Please contact the Lost and Found office.'); }

    q('DELETE FROM api_tokens WHERE expires_at < NOW()');
    logAct((int) $u['id'], 'user_login');
    out(200, ['ok' => true, 'token' => issueToken((int) $u['id']), 'user' => publicUser($u)]);
}

function updateProfile(array $me): never
{
    $in = body();
    $err = [];
    $name = text($in, 'name', 2, 120, $err);
    $phone = text($in, 'phone', 0, 30, $err, false);
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,30}$/', $phone)) { $err['phone'] = 'Enter a valid phone number.'; }
    ensure($err);
    q('UPDATE users SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?', [$name, $phone ?: null, $me['id']]);
    $u = q('SELECT * FROM users WHERE id = ?', [$me['id']])->fetch();
    out(200, ['ok' => true, 'user' => publicUser($u)]);
}

function changePassword(array $me): never
{
    $in = body();
    $cur = is_string($in['current_password'] ?? null) ? $in['current_password'] : '';
    $new = is_string($in['new_password'] ?? null) ? $in['new_password'] : '';
    if (!password_verify($cur, $me['password'])) { fail(422, 'Your current password is incorrect.', ['current_password' => 'Incorrect password.']); }
    if (!validPassword($new)) { fail(422, 'Use 8 to 72 characters with letters and numbers.', ['new_password' => 'Use 8 to 72 characters with letters and numbers.']); }
    q('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?', [password_hash($new, PASSWORD_BCRYPT), $me['id']]);
    q('DELETE FROM api_tokens WHERE user_id = ? AND token_hash <> ?', [$me['id'], hash('sha256', (string) bearer())]);
    logAct((int) $me['id'], 'password_changed');
    out(200, ['ok' => true, 'message' => 'Password changed.']);
}

// ---------- router ----------
try {
    $path = '/' . trim(preg_replace('#^/api#', '', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), '/');
    $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($path === '/' || $path === '/health') {
        // A healthy API must also reach the shared database used by Laravel.
        $database = q('SELECT DATABASE() AS name')->fetch();
        out(200, ['ok' => true, 'service' => 'TEAM BANGAN Lost and Found API', 'database' => $database['name'] ?? null]);
    }
    if ($m === 'POST' && $path === '/register') { register(); }
    if ($m === 'POST' && $path === '/login') { login(); }
    if ($m === 'GET' && $path === '/categories') { out(200, ['ok' => true, 'categories' => q('SELECT id, name FROM categories ORDER BY name')->fetchAll()]); }

    $me = auth();
    if ($m === 'POST' && $path === '/logout') { q('DELETE FROM api_tokens WHERE token_hash = ?', [hash('sha256', (string) bearer())]); out(200, ['ok' => true]); }
    if ($m === 'GET' && $path === '/me') { out(200, ['ok' => true, 'user' => publicUser($me)]); }
    if ($m === 'PUT' && $path === '/me') { updateProfile($me); }
    if ($m === 'PUT' && $path === '/me/password') { changePassword($me); }
    if ($m === 'GET' && $path === '/items') { listItems($me, false); }
    if ($m === 'GET' && $path === '/my/items') { listItems($me, true); }
    if ($m === 'POST' && $path === '/items') { reportLost($me); }
    if ($m === 'GET' && preg_match('#^/items/(\d+)$#', $path, $x)) { showItem($me, (int) $x[1]); }
    if ($m === 'GET' && $path === '/my/claims') { myClaims($me); }
    if ($m === 'POST' && $path === '/claims') { createClaim($me); }
    if ($m === 'DELETE' && preg_match('#^/claims/(\d+)$#', $path, $x)) { cancelClaim($me, (int) $x[1]); }

    fail(404, 'Endpoint not found.');
} catch (Throwable $e) {
    error_log((string) $e);
    fail(500, 'Something went wrong on the server. Please try again.');
}
