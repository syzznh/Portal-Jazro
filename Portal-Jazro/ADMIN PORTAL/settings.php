<?php
require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function getAdminRecord(PDO $pdo): array {
    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    $userId = (int)($_SESSION['user_id'] ?? 0);

    if ($adminId > 0) {
        $stmt = $pdo->prepare("SELECT a.admin_id, a.user_id, a.admin_name, a.phone_number,
                                      u.username, u.email
                               FROM admin a
                               INNER JOIN `user` u ON u.user_id = a.user_id
                               WHERE a.admin_id = ? AND u.role = 'ADMIN'");
        $stmt->execute([$adminId]);
    } elseif ($userId > 0) {
        $stmt = $pdo->prepare("SELECT a.admin_id, a.user_id, a.admin_name, a.phone_number,
                                      u.username, u.email
                               FROM admin a
                               INNER JOIN `user` u ON u.user_id = a.user_id
                               WHERE a.user_id = ? AND u.role = 'ADMIN'");
        $stmt->execute([$userId]);
    } else {
        // Compatibility fallback for this single-admin development portal.
        $stmt = $pdo->query("SELECT a.admin_id, a.user_id, a.admin_name, a.phone_number,
                                    u.username, u.email
                             FROM admin a
                             INNER JOIN `user` u ON u.user_id = a.user_id
                             WHERE u.role = 'ADMIN'
                             ORDER BY a.admin_id ASC LIMIT 1");
    }

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        throw new RuntimeException('Admin account was not found. Please check the admin and user tables.');
    }
    return $admin;
}

$message = '';
$messageType = 'success';
$activeSection = $_GET['section'] ?? 'profile';
$allowedSections = ['profile', 'academy', 'security'];
if (!in_array($activeSection, $allowedSections, true)) $activeSection = 'profile';

try {
    $admin = getAdminRecord($pdo);

    // This database currently has one organization record and no organization_id
    // relationship in the admin table, so the page edits the first organization.
    $organization = $pdo->query("SELECT organization_id, organization_name, organization_type, location, ctc_number
                                 FROM organization ORDER BY organization_id ASC LIMIT 1")
                       ->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $postedAdminId = (int)$admin['admin_id'];
        $postedUserId = (int)$admin['user_id'];

        if ($action === 'update_profile') {
            $adminName = trim($_POST['admin_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone_number'] ?? '');

            if ($adminName === '' || $username === '' || $email === '') {
                throw new RuntimeException('Please complete admin name, username and email.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please enter a valid email address.');
            }

            $check = $pdo->prepare("SELECT user_id FROM `user` WHERE email = ? AND user_id <> ? LIMIT 1");
            $check->execute([$email, $postedUserId]);
            if ($check->fetchColumn()) {
                throw new RuntimeException('This email address is already used by another account.');
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE admin SET admin_name = ?, phone_number = ? WHERE admin_id = ?");
            $stmt->execute([$adminName, $phone !== '' ? $phone : null, $postedAdminId]);
            $stmt = $pdo->prepare("UPDATE `user` SET username = ?, email = ? WHERE user_id = ? AND role = 'ADMIN'");
            $stmt->execute([$username, $email, $postedUserId]);
            $pdo->commit();

            $message = 'Admin profile updated successfully.';
            $activeSection = 'profile';
            $admin = getAdminRecord($pdo);
        }

        elseif ($action === 'update_academy') {
            $name = trim($_POST['organization_name'] ?? '');
            $type = trim($_POST['organization_type'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $contact = trim($_POST['ctc_number'] ?? '');

            if ($name === '') throw new RuntimeException('Academy name is required.');

            if ($organization) {
                $stmt = $pdo->prepare("UPDATE organization
                                       SET organization_name = ?, organization_type = ?, location = ?, ctc_number = ?
                                       WHERE organization_id = ?");
                $stmt->execute([$name, $type ?: null, $location ?: null, $contact ?: null, (int)$organization['organization_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO organization (organization_name, organization_type, location, ctc_number)
                                       VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $type ?: null, $location ?: null, $contact ?: null]);
            }

            $organization = $pdo->query("SELECT organization_id, organization_name, organization_type, location, ctc_number
                                         FROM organization ORDER BY organization_id ASC LIMIT 1")
                               ->fetch(PDO::FETCH_ASSOC) ?: null;
            $message = 'Academy information updated successfully.';
            $activeSection = 'academy';
        }

        elseif ($action === 'security_notice') {
            // Password changes are intentionally not written here until the existing
            // login handler is confirmed to use password_verify(). The supplied SQL
            // dump contains legacy plain-text passwords, and changing only this page
            // to password_hash() could prevent the current login page from working.
            $message = 'Password change is not enabled yet. The login handler must be updated to use password_verify() first.';
            $messageType = 'error';
            $activeSection = 'security';
        }
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    $message = $e->getMessage();
    $messageType = 'error';
    if (!isset($admin) || !is_array($admin)) $admin = ['admin_id'=>0,'user_id'=>0,'admin_name'=>'Administrator','phone_number'=>'','username'=>'','email'=>''];
}

$displayName = $admin['admin_name'] ?? 'Administrator';
$initial = strtoupper(substr(trim($displayName), 0, 1) ?: 'A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings | JAZRO Admin Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root{--blue:#2878f5;--blue-dark:#1758dc;--navy:#163664;--muted:#7c8eaa;--line:#e4edfa;--bg:#f5f9ff}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--navy);font-family:Inter,Arial,sans-serif;font-size:13px}button,input{font:inherit}
.admin-sidebar{position:fixed;inset:0 auto 0 0;z-index:50;display:flex;flex-direction:column;width:245px;padding:24px 15px 17px;border-right:1px solid #e3edfb;background:#fff}
.admin-brand{display:flex;align-items:center;gap:11px;min-height:72px;padding:0 7px 20px;border-bottom:1px solid var(--line);text-decoration:none}.admin-brand img{width:59px;height:59px;object-fit:contain;flex-shrink:0}.admin-brand span{color:var(--blue);font-size:10px;font-weight:800;letter-spacing:1.6px}
.nav-caption{margin:26px 10px 12px;color:#91a2bb;font-size:9px;font-weight:800;letter-spacing:1.6px}.admin-nav{display:grid;gap:6px;flex:1 1 auto;min-height:0;overflow-y:auto;overflow-x:hidden;align-content:start;padding-bottom:15px;scrollbar-width:thin;scrollbar-color:#dce8f8 transparent}.admin-nav::-webkit-scrollbar{width:5px}.admin-nav::-webkit-scrollbar-thumb{background:#dce8f8;border-radius:10px}
.admin-nav a{display:flex;align-items:center;gap:12px;min-height:43px;padding:11px 13px;border-radius:10px;color:#60738f;font-size:12px;font-weight:600;text-decoration:none}.admin-nav a:hover{background:#f0f6ff;color:var(--blue)}.admin-nav a.active{background:#e8f2ff;color:var(--blue);box-shadow:inset 3px 0 var(--blue)}.nav-icon{display:grid;width:23px;height:23px;place-items:center;flex-shrink:0;border-radius:7px;background:#f0f6ff;font-size:14px}
.nav-group{display:grid;gap:4px}.nav-group-title{width:100%;display:flex;align-items:center;gap:12px;min-height:43px;padding:11px 13px;border:0;border-radius:10px;background:transparent;color:#60738f;text-align:left;font-size:12px;font-weight:600}.nav-group-title:hover,.nav-group-title.active{background:#e8f2ff;color:var(--blue)}.nav-chevron{margin-left:auto}.nav-submenu{display:grid;gap:3px;margin:0 0 5px 35px;padding-left:12px;border-left:1px solid #dce8f8}.nav-submenu[hidden]{display:none!important}.nav-submenu a{min-height:auto;padding:9px 10px;font-size:10px}
.sidebar-bottom{margin-top:auto;padding-top:15px;border-top:1px solid var(--line)}.sidebar-bottom a{display:flex;align-items:center;gap:10px;padding:11px 12px;color:#60738f;font-size:11px;text-decoration:none}.sidebar-bottom small{display:block;padding:14px 12px 0;color:#9aaac0;font-size:9px}
.main{margin-left:245px;min-width:0;min-height:100vh;display:flex;flex-direction:column}.topbar{position:sticky;top:0;z-index:40;min-height:72px;padding:12px 30px;background:rgba(255,255,255,.97);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:15px}.top-title strong{display:block;font-size:15px}.top-title small{display:block;margin-top:4px;color:var(--muted);font-size:10px}.top-right{display:flex;align-items:center;gap:12px}.avatar{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#55baff,#2870f4);color:#fff;font-weight:800}.account strong{display:block;font-size:11px}.account small{display:block;margin-top:4px;color:var(--muted);font-size:10px}.mobile-menu{display:none;width:38px;height:38px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--blue)}.backdrop{display:none}
.content{width:100%;max-width:1600px;margin:0 auto;padding:26px 30px}.hero{position:relative;overflow:hidden;padding:28px 32px;margin-bottom:22px;border-radius:20px;background:linear-gradient(115deg,#173f7e,#2878f5 68%,#819cf2);color:#fff;box-shadow:0 12px 28px rgba(38,101,190,.13)}.hero:after{content:'⚙';position:absolute;right:55px;top:5px;font-size:115px;opacity:.12;transform:rotate(-12deg)}.hero-tag{display:inline-block;padding:8px 13px;border:1px solid rgba(255,255,255,.3);border-radius:30px;background:rgba(255,255,255,.15);font-size:9px;font-weight:800;letter-spacing:1.5px}.hero h1{position:relative;z-index:1;margin:13px 0 7px;font-size:28px;letter-spacing:-.7px}.hero p{position:relative;z-index:1;margin:0;color:#e5efff;font-size:12px;line-height:1.7;max-width:650px}
.layout{display:grid;grid-template-columns:250px minmax(0,1fr);gap:18px;align-items:start}.settings-menu,.settings-panel{border:1px solid var(--line);border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(36,91,160,.035)}.settings-menu{padding:10px}.settings-menu a{display:flex;align-items:center;gap:11px;padding:13px 12px;border-radius:10px;color:#60738f;text-decoration:none;font-size:11px;font-weight:700;margin-bottom:4px}.settings-menu a.active{background:#eaf2ff;color:var(--blue)}.menu-icon{width:32px;height:32px;display:grid;place-items:center;border-radius:9px;background:#f0f6ff;font-size:15px}.settings-panel{padding:25px}.section-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:22px}.section-head h2{margin:0;font-size:18px}.section-head p{margin:6px 0 0;color:var(--muted);font-size:11px;line-height:1.6}.section-label{color:var(--blue);font-size:9px;font-weight:800;letter-spacing:1.4px;margin-bottom:7px}
.profile-banner{display:flex;align-items:center;gap:15px;padding:17px;margin-bottom:22px;border:1px solid #dce9ff;border-radius:13px;background:linear-gradient(110deg,#f0f6ff,#f8faff)}.profile-avatar{width:54px;height:54px;border-radius:15px;display:grid;place-items:center;background:linear-gradient(145deg,#55baff,#2870f4);color:white;font-size:22px;font-weight:800}.profile-banner strong{display:block;font-size:13px}.profile-banner small{display:block;margin-top:5px;color:var(--muted);font-size:10px}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.form-group{display:flex;flex-direction:column;gap:7px}.form-group.full{grid-column:1/-1}.form-group label{font-size:10px;font-weight:700;color:#425976}.form-control{width:100%;min-height:43px;padding:11px 12px;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--navy);outline:none;font-size:11px}.form-control:focus{border-color:#8bb6ff;box-shadow:0 0 0 3px #eaf2ff}.help{color:var(--muted);font-size:9px;line-height:1.5}.form-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:22px;padding-top:18px;border-top:1px solid #edf2fa}.primary{padding:12px 17px;border:0;border-radius:9px;background:var(--blue);color:#fff;font-size:11px;font-weight:700;cursor:pointer}.primary:hover{background:var(--blue-dark)}.notice{padding:12px 15px;margin-bottom:18px;border-radius:9px;font-size:11px}.notice.success{background:#e5f8ef;color:#13885a}.notice.error{background:#fff0f1;color:#c74655}.security-box{display:flex;gap:13px;padding:16px;border:1px solid #f1dfb4;border-radius:12px;background:#fffaf0;color:#79591d;line-height:1.7;font-size:11px}.security-box strong{display:block;margin-bottom:4px}.info-list{display:grid;gap:12px;margin-top:20px}.info-row{display:flex;justify-content:space-between;gap:15px;padding:13px 0;border-bottom:1px solid #edf2fa;font-size:11px}.info-row span:first-child{color:var(--muted)}
@media(max-width:850px){.layout{grid-template-columns:1fr}.settings-menu{display:flex;overflow-x:auto;gap:5px}.settings-menu a{white-space:nowrap;margin:0}.menu-icon{width:27px;height:27px}}@media(max-width:700px){.admin-sidebar{width:245px;height:100dvh;transform:translateX(-105%);transition:transform .25s}.admin-sidebar.open{transform:translateX(0)}.backdrop{position:fixed;inset:0;z-index:45;display:block;background:rgba(20,45,80,.25);opacity:0;pointer-events:none}.backdrop.show{opacity:1;pointer-events:auto}.main{margin-left:0}.mobile-menu{display:grid;place-items:center;flex-shrink:0}.topbar{min-height:62px;padding:10px 15px}.top-title{flex:1}.account{display:none}.content{padding:18px 14px}.hero{padding:23px 20px}.hero h1{font-size:23px}.hero:after{right:15px;font-size:80px}.settings-panel{padding:18px}.form-grid{grid-template-columns:1fr}.form-group.full{grid-column:auto}}
</style>
</head>
<body>
<aside class="admin-sidebar" id="sidebar">
    <a class="admin-brand" href="dashboard.php"><img src="assets/images/jazro-logo.png" alt="JAZRO Logo"><span>ADMIN PORTAL</span></a>
    <p class="nav-caption">MAIN MENU</p>
    <?php include __DIR__ . '/learning_activities_menu.php'; ?>
    <div class="sidebar-bottom"><a href="index.php" class="logout-link"><span class="nav-icon">↪</span><span>Log Out</span></a><small>JAZRO ROBOTIC ACADEMY · 2026</small></div>
</aside>
<div class="backdrop" id="backdrop"></div>
<main class="main">
<header class="topbar">
    <button class="mobile-menu" id="mobileMenu" type="button">☰</button>
    <div class="top-title"><strong>Settings</strong><small>Manage your account and portal preferences</small></div>
    <div class="top-right"><div class="avatar"><?= h($initial) ?></div><div class="account"><strong><?= h($displayName) ?></strong><small>Administrator</small></div></div>
</header>
<section class="content">
    <div class="hero"><span class="hero-tag">PORTAL CONFIGURATION</span><h1>Settings</h1><p>Manage your administrator profile and keep JAZRO academy information up to date.</p></div>
    <?php if ($message !== ''): ?><div class="notice <?= h($messageType) ?>"><?= h($message) ?></div><?php endif; ?>
    <div class="layout">
        <nav class="settings-menu" aria-label="Settings sections">
            <a href="?section=profile" class="<?= $activeSection==='profile'?'active':'' ?>"><span class="menu-icon">👤</span>Admin Profile</a>
            <a href="?section=academy" class="<?= $activeSection==='academy'?'active':'' ?>"><span class="menu-icon">🏢</span>Academy Information</a>
            <a href="?section=security" class="<?= $activeSection==='security'?'active':'' ?>"><span class="menu-icon">🔒</span>Security</a>
        </nav>
        <section class="settings-panel">
        <?php if ($activeSection === 'profile'): ?>
            <div class="section-head"><div><div class="section-label">ACCOUNT SETTINGS</div><h2>Admin Profile</h2><p>Update the administrator's personal and login information.</p></div></div>
            <div class="profile-banner"><div class="profile-avatar"><?= h($initial) ?></div><div><strong><?= h($admin['admin_name']) ?></strong><small>Administrator account · JAZRO Admin Portal</small></div></div>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-grid">
                    <div class="form-group"><label for="admin_name">Admin Name *</label><input class="form-control" id="admin_name" name="admin_name" value="<?= h($admin['admin_name']) ?>" required></div>
                    <div class="form-group"><label for="phone_number">Phone Number</label><input class="form-control" id="phone_number" name="phone_number" value="<?= h($admin['phone_number']) ?>" placeholder="e.g. 01X-XXXXXXX"></div>
                    <div class="form-group"><label for="username">Username *</label><input class="form-control" id="username" name="username" value="<?= h($admin['username']) ?>" required></div>
                    <div class="form-group"><label for="email">Email Address *</label><input class="form-control" type="email" id="email" name="email" value="<?= h($admin['email']) ?>" required></div>
                </div>
                <div class="form-actions"><button class="primary" type="submit">Save Profile Changes</button></div>
            </form>
        <?php elseif ($activeSection === 'academy'): ?>
            <div class="section-head"><div><div class="section-label">ORGANIZATION SETTINGS</div><h2>Academy Information</h2><p>Maintain the main JAZRO academy details displayed in the portal.</p></div></div>
            <form method="POST">
                <input type="hidden" name="action" value="update_academy">
                <div class="form-grid">
                    <div class="form-group full"><label for="organization_name">Academy Name *</label><input class="form-control" id="organization_name" name="organization_name" value="<?= h($organization['organization_name'] ?? 'JAZRO Robotic Academy') ?>" required></div>
                    <div class="form-group"><label for="organization_type">Organization Type</label><input class="form-control" id="organization_type" name="organization_type" value="<?= h($organization['organization_type'] ?? '') ?>" placeholder="e.g. Education / Robotics"></div>
                    <div class="form-group"><label for="ctc_number">Contact Number</label><input class="form-control" id="ctc_number" name="ctc_number" value="<?= h($organization['ctc_number'] ?? '') ?>" placeholder="Academy contact number"></div>
                    <div class="form-group full"><label for="location">Address / Location</label><input class="form-control" id="location" name="location" value="<?= h($organization['location'] ?? '') ?>" placeholder="Enter academy address"></div>
                </div>
                <div class="form-actions"><button class="primary" type="submit">Save Academy Information</button></div>
            </form>
            <?php if (!$organization): ?><p class="help">No organization record exists yet. Saving this form will create the first organization record.</p><?php endif; ?>
        <?php else: ?>
            <div class="section-head"><div><div class="section-label">ACCOUNT PROTECTION</div><h2>Security</h2><p>Important information about protecting the administrator account.</p></div></div>
            <div class="security-box"><span style="font-size:22px">🔐</span><div><strong>Password change needs login integration</strong>The current database dump stores the existing passwords in a legacy plain-text format. Before enabling password changes safely, the login page must be updated to verify hashed passwords using <code>password_verify()</code>. This prevents a password change here from accidentally locking the admin out.</div></div>
            <div class="info-list"><div class="info-row"><span>Account role</span><strong>Administrator</strong></div><div class="info-row"><span>Username</span><strong><?= h($admin['username']) ?></strong></div><div class="info-row"><span>Email</span><strong><?= h($admin['email']) ?></strong></div></div>
        <?php endif; ?>
        </section>
    </div>
</section>
</main>
<script>
const sidebar=document.getElementById('sidebar');
const backdrop=document.getElementById('backdrop');
const mobileMenu=document.getElementById('mobileMenu');
if(mobileMenu){mobileMenu.addEventListener('click',()=>{sidebar.classList.add('open');backdrop.classList.add('show');});}
if(backdrop){backdrop.addEventListener('click',()=>{sidebar.classList.remove('open');backdrop.classList.remove('show');});}
</script>
</body>
</html>
