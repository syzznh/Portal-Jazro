
<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function getAdminId(PDO $pdo): int {
    if (!empty($_SESSION['admin_id'])) {
        return (int)$_SESSION['admin_id'];
    }

    $id = $pdo->query("SELECT admin_id FROM admin ORDER BY admin_id ASC LIMIT 1")->fetchColumn();

    if (!$id) {
        throw new RuntimeException('Admin record not found.');
    }

    return (int)$id;
}

$message = '';
$messageType = 'success';
$activeTab = $_GET['tab'] ?? 'certificates';

if (!in_array($activeTab, ['certificates', 'reports'], true)) {
    $activeTab = 'certificates';
}

/* =========================================
   HANDLE FORM SUBMISSIONS
========================================= */

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $action = $_POST['action'] ?? '';

        /* SAVE CERTIFICATE */
        if ($action === 'save_certificate') {

            $id = (int)($_POST['certificate_id'] ?? 0);
            $studentId = (int)($_POST['student_id'] ?? 0);
            $classId = (int)($_POST['class_id'] ?? 0);
            $type = $_POST['certificate_type'] ?? '';
            $name = trim($_POST['certificate_name'] ?? '');
            $url = trim($_POST['certificate_url'] ?? '');
            $date = $_POST['issue_date'] ?: null;

            if (!$studentId || !$classId || $name === '') {
                throw new RuntimeException('Please complete all required fields.');
            }

            if (!in_array($type, ['SB', 'SP', 'RC'], true)) {
                throw new RuntimeException('Invalid certificate type.');
            }

            if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('Please enter a valid certificate URL.');
            }

            if ($id > 0) {

                $stmt = $pdo->prepare("
                    UPDATE certificate
                    SET student_id = ?,
                        class_id = ?,
                        certificate_type = ?,
                        certificate_name = ?,
                        certificate_url = ?,
                        issue_date = ?
                    WHERE certificate_id = ?
                ");

                $stmt->execute([
                    $studentId,
                    $classId,
                    $type,
                    $name,
                    $url ?: null,
                    $date,
                    $id
                ]);

                $message = 'Certificate updated successfully.';

            } else {

                $adminId = getAdminId($pdo);

                $stmt = $pdo->prepare("
                    INSERT INTO certificate
                    (student_id, class_id, admin_id, certificate_type,
                     certificate_name, certificate_url, issue_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $studentId,
                    $classId,
                    $adminId,
                    $type,
                    $name,
                    $url ?: null,
                    $date
                ]);

                $message = 'Certificate added successfully.';
            }

            $activeTab = 'certificates';
        }

        /* DELETE CERTIFICATE */
        elseif ($action === 'delete_certificate') {

            $id = (int)($_POST['certificate_id'] ?? 0);

            $stmt = $pdo->prepare("
                DELETE FROM certificate
                WHERE certificate_id = ?
            ");

            $stmt->execute([$id]);

            $message = 'Certificate deleted successfully.';
            $activeTab = 'certificates';
        }

        /* SAVE REPORT CARD */
        elseif ($action === 'save_report') {

            $id = (int)($_POST['reportcard_id'] ?? 0);
            $studentId = (int)($_POST['student_id'] ?? 0);
            $classId = (int)($_POST['class_id'] ?? 0);

            $term = trim($_POST['academic_term'] ?? '');
            $year = (int)($_POST['academic_year'] ?? date('Y'));

            $attendance = (float)($_POST['attendance_score'] ?? 0);
            $skills = (float)($_POST['skills_score'] ?? 0);
            $softskills = (float)($_POST['softskills_score'] ?? 0);
            $assessment = (float)($_POST['portal_assessment_score'] ?? 0);
            $overall = (float)($_POST['overall_score'] ?? 0);

            $grade = trim($_POST['grade'] ?? '');
            $date = $_POST['report_date'] ?: null;
            $comment = trim($_POST['comment'] ?? '');

            if (!$studentId || !$classId) {
                throw new RuntimeException('Please select a student and class.');
            }

            if ($year < 2000 || $year > 2100) {
                throw new RuntimeException('Please enter a valid academic year.');
            }

            foreach ([$attendance, $skills, $softskills, $assessment, $overall] as $score) {
                if ($score < 0 || $score > 100) {
                    throw new RuntimeException('All scores must be between 0 and 100.');
                }
            }

            if ($id > 0) {

                $stmt = $pdo->prepare("
                    UPDATE report_card
                    SET student_id = ?,
                        class_id = ?,
                        academic_term = ?,
                        academic_year = ?,
                        attendance_score = ?,
                        skills_score = ?,
                        softskills_score = ?,
                        portal_assessment_score = ?,
                        overall_score = ?,
                        grade = ?,
                        report_date = ?,
                        comment = ?
                    WHERE reportcard_id = ?
                ");

                $stmt->execute([
                    $studentId,
                    $classId,
                    $term ?: null,
                    $year,
                    $attendance,
                    $skills,
                    $softskills,
                    $assessment,
                    $overall,
                    $grade ?: null,
                    $date,
                    $comment ?: null,
                    $id
                ]);

                $message = 'Report card updated successfully.';

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO report_card
                    (student_id, class_id, academic_term, academic_year,
                     attendance_score, skills_score, softskills_score,
                     portal_assessment_score, overall_score, grade,
                     report_date, comment)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $studentId,
                    $classId,
                    $term ?: null,
                    $year,
                    $attendance,
                    $skills,
                    $softskills,
                    $assessment,
                    $overall,
                    $grade ?: null,
                    $date,
                    $comment ?: null
                ]);

                $message = 'Report card added successfully.';
            }

            $activeTab = 'reports';
        }

        /* DELETE REPORT CARD */
        elseif ($action === 'delete_report') {

            $id = (int)($_POST['reportcard_id'] ?? 0);

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                DELETE FROM report_card_topic
                WHERE reportcard_id = ?
            ");
            $stmt->execute([$id]);

            $stmt = $pdo->prepare("
                DELETE FROM report_card
                WHERE reportcard_id = ?
            ");
            $stmt->execute([$id]);

            $pdo->commit();

            $message = 'Report card deleted successfully.';
            $activeTab = 'reports';
        }
    }

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $message = $e->getMessage();
    $messageType = 'error';
}

/* =========================================
   LOAD DATABASE DATA
========================================= */

$students = $pdo->query("
    SELECT student_id, student_name
    FROM student
    ORDER BY student_name
")->fetchAll(PDO::FETCH_ASSOC);

$classes = $pdo->query("
    SELECT class_id, class_code, class_name
    FROM classes
    ORDER BY class_code
")->fetchAll(PDO::FETCH_ASSOC);

$certificates = $pdo->query("
    SELECT
        c.certificate_id,
        c.student_id,
        c.class_id,
        c.certificate_type,
        c.certificate_name,
        c.certificate_url,
        c.issue_date,
        s.student_name,
        cl.class_code,
        cl.class_name
    FROM certificate c
    INNER JOIN student s ON s.student_id = c.student_id
    INNER JOIN classes cl ON cl.class_id = c.class_id
    ORDER BY c.certificate_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$reports = $pdo->query("
    SELECT
        r.*,
        s.student_name,
        cl.class_code,
        cl.class_name
    FROM report_card r
    INNER JOIN student s ON s.student_id = r.student_id
    INNER JOIN classes cl ON cl.class_id = r.class_id
    ORDER BY r.reportcard_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$totalCertificates = count($certificates);
$totalReports = count($reports);
$totalStudents = count($students);
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Achievements | JAZRO Admin Portal</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">

<style>
:root {
    --blue:#2878f5;
    --blue-dark:#1758dc;
    --navy:#163664;
    --muted:#7c8eaa;
    --line:#e4edfa;
    --bg:#f5f9ff;
}

* { box-sizing:border-box; }

body {
    margin:0;
    background:var(--bg);
    color:var(--navy);
    font-family:Inter,Arial,sans-serif;
    font-size:13px;
}

button,input,select,textarea { font:inherit; }
button { cursor:pointer; }
a { text-decoration:none; }

/* SIDEBAR */

.admin-sidebar {
    position:fixed;
    inset:0 auto 0 0;
    z-index:50;
    display:flex;
    flex-direction:column;
    width:245px;
    padding:24px 15px 17px;
    border-right:1px solid #e3edfb;
    background:#fff;
}

.admin-brand {
    display:flex;
    align-items:center;
    gap:11px;
    min-height:72px;
    padding:0 7px 20px;
    border-bottom:1px solid var(--line);
}

.admin-brand img {
    width:59px;
    height:59px;
    object-fit:contain;
    flex-shrink:0;
}

.admin-brand span {
    color:var(--blue);
    font-size:10px;
    font-weight:800;
    letter-spacing:1.6px;
}

.nav-caption {
    margin:26px 10px 12px;
    color:#91a2bb;
    font-size:9px;
    font-weight:800;
    letter-spacing:1.6px;
}

/* IMPORTANT:
   Sidebar menu is loaded from learning_activities_menu.php.
   Do not add another menu here.
*/

.admin-nav {
    display:grid;
    gap:6px;
    flex:1 1 auto;
    min-height:0;
    overflow-y:auto;
    overflow-x:hidden;
    align-content:start;
    padding-bottom:15px;
    scrollbar-width:thin;
    scrollbar-color:#dce8f8 transparent;
}

.admin-nav::-webkit-scrollbar { width:5px; }
.admin-nav::-webkit-scrollbar-thumb {
    background:#dce8f8;
    border-radius:10px;
}

.admin-nav a {
    display:flex;
    align-items:center;
    gap:12px;
    min-height:43px;
    padding:11px 13px;
    border-radius:10px;
    color:#60738f;
    font-size:12px;
    font-weight:600;
}

.admin-nav a:hover {
    background:#f0f6ff;
    color:var(--blue);
}

.admin-nav a.active {
    background:#e8f2ff;
    color:var(--blue);
    box-shadow:inset 3px 0 var(--blue);
}

.nav-icon {
    display:grid;
    width:23px;
    height:23px;
    place-items:center;
    flex-shrink:0;
    border-radius:7px;
    background:#f0f6ff;
    font-size:14px;
}

.nav-group {
    display:grid;
    gap:4px;
}

.nav-group-title {
    width:100%;
    display:flex;
    align-items:center;
    gap:12px;
    min-height:43px;
    padding:11px 13px;
    border:0;
    border-radius:10px;
    background:transparent;
    color:#60738f;
    text-align:left;
    font-size:12px;
    font-weight:600;
}

.nav-group-title:hover,
.nav-group-title.active {
    background:#e8f2ff;
    color:var(--blue);
}

.nav-chevron {
    margin-left:auto;
}

.nav-submenu {
    display:grid;
    gap:3px;
    margin:0 0 5px 35px;
    padding-left:12px;
    border-left:1px solid #dce8f8;
}

.nav-submenu[hidden] {
    display:none !important;
}

.nav-submenu a {
    min-height:auto;
    padding:9px 10px;
    font-size:10px;
}

.sidebar-bottom {
    margin-top:auto;
    padding-top:15px;
    border-top:1px solid var(--line);
}

.sidebar-bottom a {
    display:flex;
    align-items:center;
    gap:10px;
    padding:11px 12px;
    color:#60738f;
    font-size:11px;
}

.sidebar-bottom small {
    display:block;
    padding:14px 12px 0;
    color:#9aaac0;
    font-size:9px;
}

/* MAIN */

.main {
    margin-left:245px;
    min-width:0;
    min-height:100vh;
    display:flex;
    flex-direction:column;
}

.topbar {
    position:sticky;
    top:0;
    z-index:40;
    min-height:72px;
    padding:12px 30px;
    background:rgba(255,255,255,.97);
    border-bottom:1px solid var(--line);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
}

.top-title strong {
    display:block;
    font-size:15px;
}

.top-title small {
    display:block;
    margin-top:4px;
    color:var(--muted);
    font-size:10px;
}

.top-right {
    display:flex;
    align-items:center;
    gap:13px;
}

.avatar {
    width:39px;
    height:39px;
    border-radius:50%;
    display:grid;
    place-items:center;
    background:linear-gradient(145deg,#55baff,#2870f4);
    color:#fff;
    font-weight:800;
}

.account strong { display:block;font-size:11px; }
.account small { display:block;margin-top:4px;color:var(--muted);font-size:10px; }

.mobile-menu {
    display:none;
    width:38px;
    height:38px;
    border:1px solid var(--line);
    border-radius:10px;
    background:#fff;
    color:var(--blue);
}

.backdrop { display:none; }

.content {
    width:100%;
    max-width:1700px;
    margin:0 auto;
    padding:24px 30px;
}

.intro {
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    flex-wrap:wrap;
    gap:18px;
    margin-bottom:19px;
}

.eyebrow {
    display:inline-block;
    padding:10px 17px;
    margin-bottom:9px;
    border-radius:25px;
    background:#fff;
    color:var(--blue);
    font-size:9px;
    font-weight:800;
    letter-spacing:1.6px;
}

.intro h1 {
    margin:0;
    font-size:27px;
    letter-spacing:-.8px;
}

.intro p {
    margin:8px 0 0;
    color:var(--muted);
    font-size:12px;
}

/* SUMMARY */

.stats {
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:14px;
    margin-bottom:20px;
}

.stat-card {
    padding:18px;
    border:1px solid var(--line);
    border-radius:14px;
    background:#fff;
}

.stat-top {
    display:flex;
    align-items:center;
    justify-content:space-between;
    color:var(--muted);
    font-size:10px;
    font-weight:700;
}

.stat-icon {
    width:38px;
    height:38px;
    display:grid;
    place-items:center;
    border-radius:11px;
    background:#eaf2ff;
    font-size:18px;
}

.stat-number {
    display:block;
    margin-top:12px;
    font-size:27px;
    font-weight:800;
}

/* TABS */

.tabs {
    display:flex;
    gap:7px;
    padding:6px;
    margin-bottom:18px;
    border:1px solid var(--line);
    border-radius:13px;
    background:#fff;
}

.tab {
    flex:1;
    padding:12px;
    border:0;
    border-radius:9px;
    background:transparent;
    color:#71839d;
    font-size:11px;
    font-weight:700;
}

.tab.active {
    background:#e8f2ff;
    color:var(--blue);
}

.panel { display:none; }
.panel.active { display:block; }

.panel-card {
    padding:24px;
    border:1px solid var(--line);
    border-radius:16px;
    background:#fff;
    box-shadow:0 8px 25px rgba(36,91,160,.035);
}

.panel-heading {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:15px;
}

.panel-heading h2 {
    margin:0;
    font-size:18px;
}

.panel-heading p {
    margin:6px 0 0;
    color:var(--muted);
    font-size:10px;
}

.primary {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:12px 16px;
    border:0;
    border-radius:10px;
    background:var(--blue);
    color:#fff;
    font-size:11px;
    font-weight:700;
}

.primary:hover { background:var(--blue-dark); }

.secondary {
    padding:10px 13px;
    border:1px solid var(--line);
    border-radius:9px;
    background:#fff;
    color:#627590;
    font-size:11px;
    font-weight:600;
}

/* TABLE */

.search {
    width:100%;
    max-width:440px;
    height:43px;
    padding:0 13px;
    margin-bottom:13px;
    border:1px solid var(--line);
    border-radius:10px;
    background:#fff;
    outline:none;
    font-size:11px;
}

.table-wrap {
    overflow-x:auto;
    border:1px solid var(--line);
    border-radius:13px;
    background:#fff;
}

table {
    width:100%;
    min-width:760px;
    border-collapse:collapse;
}

th,td {
    padding:14px 15px;
    text-align:left;
    border-bottom:1px solid #edf2fa;
}

th {
    background:#f8fbff;
    color:#71839d;
    font-size:9px;
    text-transform:uppercase;
    letter-spacing:.7px;
}

td {
    color:#425976;
    font-size:11px;
}

tbody tr:last-child td { border-bottom:0; }

.name {
    color:var(--navy);
    font-weight:700;
}

.subtext {
    display:block;
    margin-top:4px;
    color:var(--muted);
    font-size:10px;
}

.pill {
    display:inline-block;
    padding:6px 9px;
    border-radius:7px;
    background:#eaf2ff;
    color:var(--blue);
    font-size:9px;
    font-weight:800;
}

.action-btn {
    padding:7px 9px;
    border:1px solid var(--line);
    border-radius:7px;
    background:#fff;
    color:#60738f;
    font-size:10px;
}

.delete-btn:hover {
    background:#fff0f1;
    color:#dc5261;
}

.empty {
    padding:40px;
    text-align:center;
    color:var(--muted);
}

/* FORMS */

.form-card {
    display:none;
    margin-bottom:20px;
    padding:22px;
    border:1px solid var(--line);
    border-radius:14px;
    background:#fbfdff;
}

.form-card.show { display:block; }

.form-card h3 {
    margin:0 0 18px;
    font-size:16px;
}

.form-grid {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:15px;
}

.form-group {
    display:flex;
    flex-direction:column;
    gap:7px;
}

.form-group.full { grid-column:1/-1; }

.form-group label {
    color:#425976;
    font-size:10px;
    font-weight:700;
}

.form-control {
    width:100%;
    min-height:41px;
    padding:10px 12px;
    border:1px solid var(--line);
    border-radius:8px;
    background:#fff;
    color:var(--navy);
    outline:none;
    font-size:11px;
}

textarea.form-control {
    min-height:75px;
    resize:vertical;
}

.form-actions {
    display:flex;
    justify-content:flex-end;
    gap:9px;
    margin-top:18px;
}

.notice {
    padding:12px 15px;
    margin-bottom:16px;
    border-radius:9px;
    font-size:11px;
}

.notice.success {
    background:#e5f8ef;
    color:#13885a;
}

.notice.error {
    background:#fff0f1;
    color:#c74655;
}

/* RESPONSIVE */

@media(max-width:760px) {

    .admin-sidebar {
        width:245px;
        height:100dvh;
        transform:translateX(-105%);
        transition:transform .25s;
    }

    .admin-sidebar.open {
        transform:translateX(0);
    }

    .backdrop {
        position:fixed;
        inset:0;
        z-index:45;
        display:block;
        background:rgba(20,45,80,.25);
        opacity:0;
        pointer-events:none;
    }

    .backdrop.show {
        opacity:1;
        pointer-events:auto;
    }

    .main { margin-left:0; }

    .mobile-menu {
        display:grid;
        place-items:center;
        flex-shrink:0;
    }

    .topbar {
        min-height:62px;
        padding:10px 15px;
    }

    .top-title { flex:1; }
    .account { display:none; }

    .content { padding:20px 15px; }

    .intro h1 { font-size:23px; }

    .stats { grid-template-columns:1fr; }

    .form-grid { grid-template-columns:1fr; }

    .form-group.full { grid-column:auto; }

    .panel-card { padding:17px; }
}
</style>
</head>

<body>

<!-- ======================================
     SIDEBAR
     USING SHARED MENU FILE
====================================== -->

<aside class="admin-sidebar" id="sidebar">

    <a class="admin-brand" href="dashboard.php">
        <img src="assets/images/jazro-logo.png" alt="JAZRO Logo">
        <span>ADMIN PORTAL</span>
    </a>

    <p class="nav-caption">MAIN MENU</p>

    <?php include __DIR__ . '/learning_activities_menu.php'; ?>

    <div class="sidebar-bottom">

        <a href="index.php" class="logout-link">
            <span class="nav-icon">↪</span>
            Log Out
        </a>

        <small>JAZRO ROBOTIC ACADEMY · 2026</small>

    </div>

</aside>

<div class="backdrop" id="backdrop"></div>


<!-- MAIN CONTENT -->
<main class="main">

    <header class="topbar">

        <button class="mobile-menu" id="mobileMenu" type="button">
            ☰
        </button>

        <div class="top-title">
            <strong>Student Achievements Management</strong>
            <small>Manage student certificates and report cards</small>
        </div>

        <div class="top-right">

            <div class="avatar">A</div>

            <div class="account">
                <strong>Admin</strong>
                <small>Administrator</small>
            </div>

        </div>

    </header>


    <section class="content">

        <div class="intro">

            <div>
                <div class="eyebrow">ACADEMIC MANAGEMENT</div>

                <h1>Student Achievements</h1>

                <p>
                    Manage student certificates and academic performance records.
                </p>
            </div>

        </div>


        <?php if ($message !== ''): ?>
            <div class="notice <?= h($messageType) ?>">
                <?= h($message) ?>
            </div>
        <?php endif; ?>


        <!-- SUMMARY CARDS -->
        <section class="stats">

            <div class="stat-card">
                <div class="stat-top">
                    Total Certificates
                    <span class="stat-icon">🏆</span>
                </div>

                <span class="stat-number"><?= $totalCertificates ?></span>
            </div>


            <div class="stat-card">
                <div class="stat-top">
                    Total Report Cards
                    <span class="stat-icon">📊</span>
                </div>

                <span class="stat-number"><?= $totalReports ?></span>
            </div>


            <div class="stat-card">
                <div class="stat-top">
                    Registered Students
                    <span class="stat-icon">👥</span>
                </div>

                <span class="stat-number"><?= $totalStudents ?></span>
            </div>

        </section>


        <!-- TABS -->
        <div class="tabs">

            <button type="button"
                    class="tab <?= $activeTab === 'certificates' ? 'active' : '' ?>"
                    onclick="switchTab('certificates', this)">
                🏆 Certificates
            </button>

            <button type="button"
                    class="tab <?= $activeTab === 'reports' ? 'active' : '' ?>"
                    onclick="switchTab('reports', this)">
                📊 Report Cards
            </button>

        </div>


        <!-- =================================
             CERTIFICATES
        ================================== -->

        <section class="panel <?= $activeTab === 'certificates' ? 'active' : '' ?>"
                 id="certificates">

            <div class="panel-card">

                <div class="panel-heading">

                    <div>
                        <h2>Certificates</h2>
                        <p>Manage certificates issued to students.</p>
                    </div>

                    <button type="button" class="primary"
                            onclick="openCertificateForm()">
                        + Add Certificate
                    </button>

                </div>


                <!-- CERTIFICATE FORM -->
                <div class="form-card" id="certificateForm">

                    <h3 id="certificateFormTitle">Add New Certificate</h3>

                    <form method="POST">

                        <input type="hidden" name="action" value="save_certificate">
                        <input type="hidden" name="certificate_id" id="certificate_id">

                        <div class="form-grid">

                            <div class="form-group">
                                <label>Student *</label>

                                <select name="student_id" id="cert_student"
                                        class="form-control" required>

                                    <option value="">Select Student</option>

                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= (int)$student['student_id'] ?>">
                                            <?= h($student['student_name']) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>


                            <div class="form-group">
                                <label>Class *</label>

                                <select name="class_id" id="cert_class"
                                        class="form-control" required>

                                    <option value="">Select Class</option>

                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= (int)$class['class_id'] ?>">
                                            <?= h($class['class_code'] . ' - ' . $class['class_name']) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>


                            <div class="form-group">
                                <label>Certificate Type *</label>

                                <select name="certificate_type" id="cert_type"
                                        class="form-control" required>

                                    <option value="">Select Type</option>
                                    <option value="SB">SB</option>
                                    <option value="SP">SP</option>
                                    <option value="RC">RC</option>

                                </select>
                            </div>


                            <div class="form-group">
                                <label>Issue Date</label>

                                <input type="date" name="issue_date"
                                       id="cert_date" class="form-control">
                            </div>


                            <div class="form-group full">
                                <label>Certificate Name *</label>

                                <input type="text" name="certificate_name"
                                       id="cert_name" class="form-control"
                                       placeholder="Enter certificate name" required>
                            </div>


                            <div class="form-group full">
                                <label>Certificate URL</label>

                                <input type="url" name="certificate_url"
                                       id="cert_url" class="form-control"
                                       placeholder="https://example.com/certificate.pdf">
                            </div>

                        </div>


                        <div class="form-actions">

                            <button type="button" class="secondary"
                                    onclick="closeForm('certificateForm')">
                                Cancel
                            </button>

                            <button type="submit" class="primary">
                                Save Certificate
                            </button>

                        </div>

                    </form>

                </div>


                <input type="search" class="search"
                       placeholder="Search student or certificate..."
                       oninput="filterTable(this, 'certificateTable')">


                <div class="table-wrap">

                    <table id="certificateTable">

                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Certificate</th>
                                <th>Type</th>
                                <th>Issue Date</th>
                                <th>File</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!$certificates): ?>
                            <tr>
                                <td colspan="7" class="empty">
                                    No certificates found.
                                </td>
                            </tr>
                        <?php endif; ?>


                        <?php foreach ($certificates as $item): ?>

                            <tr>

                                <td>
                                    <span class="name">
                                        <?= h($item['student_name']) ?>
                                    </span>
                                </td>

                                <td><?= h($item['class_code']) ?></td>

                                <td><?= h($item['certificate_name']) ?></td>

                                <td>
                                    <span class="pill">
                                        <?= h($item['certificate_type']) ?>
                                    </span>
                                </td>

                                <td><?= h($item['issue_date'] ?: '-') ?></td>

                                <td>
                                    <?php if (!empty($item['certificate_url'])): ?>

                                        <a class="action-btn"
                                           href="<?= h($item['certificate_url']) ?>"
                                           target="_blank" rel="noopener">
                                            View File ↗
                                        </a>

                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>

                                    <div style="display:flex;gap:5px;">

                                        <button type="button" class="action-btn"
                                            onclick='editCertificate(<?= json_encode([
                                                'id' => $item['certificate_id'],
                                                'student_id' => $item['student_id'],
                                                'class_id' => $item['class_id'],
                                                'type' => $item['certificate_type'],
                                                'name' => $item['certificate_name'],
                                                'date' => $item['issue_date'],
                                                'url' => $item['certificate_url']
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'>
                                            Edit
                                        </button>


                                        <form method="POST"
                                              onsubmit="return confirm('Delete this certificate?');">

                                            <input type="hidden" name="action"
                                                   value="delete_certificate">

                                            <input type="hidden" name="certificate_id"
                                                   value="<?= (int)$item['certificate_id'] ?>">

                                            <button type="submit"
                                                    class="action-btn delete-btn">
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>


        <!-- =================================
             REPORT CARDS
        ================================== -->

        <section class="panel <?= $activeTab === 'reports' ? 'active' : '' ?>"
                 id="reports">

            <div class="panel-card">

                <div class="panel-heading">

                    <div>
                        <h2>Report Cards</h2>
                        <p>Manage student performance and academic results.</p>
                    </div>

                    <button type="button" class="primary"
                            onclick="openReportForm()">
                        + Add Report Card
                    </button>

                </div>


                <!-- REPORT FORM -->
                <div class="form-card" id="reportForm">

                    <h3 id="reportFormTitle">Add New Report Card</h3>

                    <form method="POST">

                        <input type="hidden" name="action" value="save_report">
                        <input type="hidden" name="reportcard_id" id="report_id">

                        <div class="form-grid">

                            <div class="form-group">
                                <label>Student *</label>

                                <select name="student_id" id="report_student"
                                        class="form-control" required>

                                    <option value="">Select Student</option>

                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= (int)$student['student_id'] ?>">
                                            <?= h($student['student_name']) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>


                            <div class="form-group">
                                <label>Class *</label>

                                <select name="class_id" id="report_class"
                                        class="form-control" required>

                                    <option value="">Select Class</option>

                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= (int)$class['class_id'] ?>">
                                            <?= h($class['class_code'] . ' - ' . $class['class_name']) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>


                            <div class="form-group">
                                <label>Academic Term</label>

                                <input type="text" name="academic_term"
                                       id="report_term" class="form-control"
                                       placeholder="Example: Term 1">
                            </div>


                            <div class="form-group">
                                <label>Academic Year *</label>

                                <input type="number" name="academic_year"
                                       id="report_year" class="form-control"
                                       min="2000" max="2100"
                                       value="<?= date('Y') ?>" required>
                            </div>


                            <div class="form-group">
                                <label>Attendance Score (%)</label>

                                <input type="number" name="attendance_score"
                                       id="report_attendance" class="form-control"
                                       min="0" max="100" step="0.01" value="0">
                            </div>


                            <div class="form-group">
                                <label>Skills in Class (%)</label>

                                <input type="number" name="skills_score"
                                       id="report_skills" class="form-control"
                                       min="0" max="100" step="0.01" value="0">
                            </div>


                            <div class="form-group">
                                <label>Soft Skills (%)</label>

                                <input type="number" name="softskills_score"
                                       id="report_softskills" class="form-control"
                                       min="0" max="100" step="0.01" value="0">
                            </div>


                            <div class="form-group">
                                <label>Portal Assessment (%)</label>

                                <input type="number" name="portal_assessment_score"
                                       id="report_assessment" class="form-control"
                                       min="0" max="100" step="0.01" value="0">
                            </div>


                            <div class="form-group">
                                <label>Overall Score (%)</label>

                                <input type="number" name="overall_score"
                                       id="report_overall" class="form-control"
                                       min="0" max="100" step="0.01" value="0">
                            </div>


                            <div class="form-group">
                                <label>Grade</label>

                                <input type="text" name="grade"
                                       id="report_grade" class="form-control"
                                       placeholder="Example: A">
                            </div>


                            <div class="form-group">
                                <label>Report Date</label>

                                <input type="date" name="report_date"
                                       id="report_date" class="form-control">
                            </div>


                            <div class="form-group full">
                                <label>Comment / Remarks</label>

                                <textarea name="comment" id="report_comment"
                                          class="form-control"
                                          placeholder="Enter student performance comments..."></textarea>
                            </div>

                        </div>


                        <div class="form-actions">

                            <button type="button" class="secondary"
                                    onclick="closeForm('reportForm')">
                                Cancel
                            </button>

                            <button type="submit" class="primary">
                                Save Report Card
                            </button>

                        </div>

                    </form>

                </div>


                <input type="search" class="search"
                       placeholder="Search student or class..."
                       oninput="filterTable(this, 'reportTable')">


                <div class="table-wrap">

                    <table id="reportTable">

                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Term / Year</th>
                                <th>Attendance</th>
                                <th>Skills</th>
                                <th>Soft Skills</th>
                                <th>Assessment</th>
                                <th>Overall</th>
                                <th>Grade</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!$reports): ?>
                            <tr>
                                <td colspan="10" class="empty">
                                    No report cards found.
                                </td>
                            </tr>
                        <?php endif; ?>


                        <?php foreach ($reports as $item): ?>

                            <tr>

                                <td>
                                    <span class="name">
                                        <?= h($item['student_name']) ?>
                                    </span>
                                </td>

                                <td><?= h($item['class_code']) ?></td>

                                <td>
                                    <?= h($item['academic_term'] ?: '-') ?>
                                    <span class="subtext">
                                        <?= h($item['academic_year']) ?>
                                    </span>
                                </td>

                                <td><?= h($item['attendance_score']) ?>%</td>
                                <td><?= h($item['skills_score']) ?>%</td>
                                <td><?= h($item['softskills_score']) ?>%</td>
                                <td><?= h($item['portal_assessment_score']) ?>%</td>

                                <td>
                                    <strong><?= h($item['overall_score']) ?>%</strong>
                                </td>

                                <td>
                                    <span class="pill">
                                        <?= h($item['grade'] ?: '-') ?>
                                    </span>
                                </td>

                                <td>

                                    <div style="display:flex;gap:5px;">

                                        <button type="button" class="action-btn"
                                            onclick='editReport(<?= json_encode([
                                                'id' => $item['reportcard_id'],
                                                'student_id' => $item['student_id'],
                                                'class_id' => $item['class_id'],
                                                'term' => $item['academic_term'],
                                                'year' => $item['academic_year'],
                                                'attendance' => $item['attendance_score'],
                                                'skills' => $item['skills_score'],
                                                'softskills' => $item['softskills_score'],
                                                'assessment' => $item['portal_assessment_score'],
                                                'overall' => $item['overall_score'],
                                                'grade' => $item['grade'],
                                                'date' => $item['report_date'],
                                                'comment' => $item['comment']
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'>
                                            Edit
                                        </button>


                                        <form method="POST"
                                              onsubmit="return confirm('Delete this report card?');">

                                            <input type="hidden" name="action"
                                                   value="delete_report">

                                            <input type="hidden" name="reportcard_id"
                                                   value="<?= (int)$item['reportcard_id'] ?>">

                                            <button type="submit"
                                                    class="action-btn delete-btn">
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </section>

</main>


<script>

/* MOBILE SIDEBAR */

const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('backdrop');
const mobileMenu = document.getElementById('mobileMenu');

mobileMenu.addEventListener('click', function () {
    sidebar.classList.add('open');
    backdrop.classList.add('show');
});

backdrop.addEventListener('click', function () {
    sidebar.classList.remove('open');
    backdrop.classList.remove('show');
});


/* TAB SWITCHING */

function switchTab(tabName, button) {

    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });

    document.querySelectorAll('.panel').forEach(panel => {
        panel.classList.remove('active');
    });

    button.classList.add('active');
    document.getElementById(tabName).classList.add('active');

    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabName);

    window.history.replaceState({}, '', url);
}


/* FORM HELPERS */

function closeForm(formId) {
    document.getElementById(formId).classList.remove('show');
}


function openCertificateForm() {

    const form = document.getElementById('certificateForm');

    form.classList.add('show');

    document.getElementById('certificateFormTitle').textContent =
        'Add New Certificate';

    document.getElementById('certificate_id').value = '';
    document.getElementById('cert_student').value = '';
    document.getElementById('cert_class').value = '';
    document.getElementById('cert_type').value = '';
    document.getElementById('cert_name').value = '';
    document.getElementById('cert_date').value = '';
    document.getElementById('cert_url').value = '';

    form.scrollIntoView({
        behavior:'smooth',
        block:'start'
    });
}


function openReportForm() {

    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });

    document.querySelectorAll('.panel').forEach(panel => {
        panel.classList.remove('active');
    });

    document.querySelectorAll('.tab')[1].classList.add('active');
    document.getElementById('reports').classList.add('active');

    const form = document.getElementById('reportForm');

    form.classList.add('show');

    document.getElementById('reportFormTitle').textContent =
        'Add New Report Card';

    document.getElementById('report_id').value = '';
    document.getElementById('report_student').value = '';
    document.getElementById('report_class').value = '';
    document.getElementById('report_term').value = '';
    document.getElementById('report_year').value = new Date().getFullYear();

    document.getElementById('report_attendance').value = 0;
    document.getElementById('report_skills').value = 0;
    document.getElementById('report_softskills').value = 0;
    document.getElementById('report_assessment').value = 0;
    document.getElementById('report_overall').value = 0;

    document.getElementById('report_grade').value = '';
    document.getElementById('report_date').value = '';
    document.getElementById('report_comment').value = '';

    form.scrollIntoView({
        behavior:'smooth',
        block:'start'
    });
}


/* EDIT CERTIFICATE */

function editCertificate(data) {

    const form = document.getElementById('certificateForm');

    form.classList.add('show');

    document.getElementById('certificateFormTitle').textContent =
        'Edit Certificate';

    document.getElementById('certificate_id').value = data.id;
    document.getElementById('cert_student').value = data.student_id;
    document.getElementById('cert_class').value = data.class_id;
    document.getElementById('cert_type').value = data.type;
    document.getElementById('cert_name').value = data.name;
    document.getElementById('cert_date').value = data.date || '';
    document.getElementById('cert_url').value = data.url || '';

    form.scrollIntoView({
        behavior:'smooth',
        block:'start'
    });
}


/* EDIT REPORT CARD */

function editReport(data) {

    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });

    document.querySelectorAll('.panel').forEach(panel => {
        panel.classList.remove('active');
    });

    document.querySelectorAll('.tab')[1].classList.add('active');
    document.getElementById('reports').classList.add('active');

    const form = document.getElementById('reportForm');

    form.classList.add('show');

    document.getElementById('reportFormTitle').textContent =
        'Edit Report Card';

    document.getElementById('report_id').value = data.id;
    document.getElementById('report_student').value = data.student_id;
    document.getElementById('report_class').value = data.class_id;
    document.getElementById('report_term').value = data.term || '';
    document.getElementById('report_year').value = data.year;

    document.getElementById('report_attendance').value = data.attendance ?? 0;
    document.getElementById('report_skills').value = data.skills ?? 0;
    document.getElementById('report_softskills').value = data.softskills ?? 0;
    document.getElementById('report_assessment').value = data.assessment ?? 0;
    document.getElementById('report_overall').value = data.overall ?? 0;

    document.getElementById('report_grade').value = data.grade || '';
    document.getElementById('report_date').value = data.date || '';
    document.getElementById('report_comment').value = data.comment || '';

    form.scrollIntoView({
        behavior:'smooth',
        block:'start'
    });
}


/* TABLE SEARCH */

function filterTable(input, tableId) {

    const filter = input.value.toLowerCase();

    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {

        row.style.display = row.textContent.toLowerCase().includes(filter)
            ? ''
            : 'none';

    });
}

</script>

</body>
</html>
