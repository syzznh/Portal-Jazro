<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/db.php';

$adminStmt = $pdo->prepare("SELECT admin_id, admin_name FROM admin WHERE user_id = ? LIMIT 1");
$adminStmt->execute([$_SESSION['user_id']]);
$admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    http_response_code(403);
    exit('Admin account not found. Please log in again.');
}

$adminId = (int)$admin['admin_id'];
$adminName = $admin['admin_name'] ?: ($_SESSION['username'] ?? 'Administrator');

$allowedStatuses = ['Present', 'Absent'];
$errorMessage = '';
$successMessage = isset($_GET['saved']) ? 'Attendance has been saved successfully.' : (isset($_GET['session_updated']) ? 'Class session has been updated successfully.' : '');

try {
    $classes = $pdo->query(
        "SELECT class_id, class_code, class_name
         FROM classes
         WHERE status = 'Active'
         ORDER BY class_name"
    )->fetchAll(PDO::FETCH_ASSOC);

    $selectedClassId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
    if (!$selectedClassId && $classes) {
        $selectedClassId = (int)$classes[0]['class_id'];
    }

    $selectedClass = null;
    foreach ($classes as $class) {
        if ((int)$class['class_id'] === $selectedClassId) {
            $selectedClass = $class;
            break;
        }
    }

    if (!$selectedClass && $classes) {
        $selectedClass = $classes[0];
        $selectedClassId = (int)$selectedClass['class_id'];
    }

    $sessions = [];
    $sessionDateCounts = [];
    $selectedSessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
    $selectedSession = null;
    $students = [];

    if ($selectedClass) {
        $sessionStmt = $pdo->prepare(
            "SELECT session_id, session_date, topic, status
             FROM class_session
             WHERE class_id = ?
             ORDER BY session_date DESC, session_id DESC"
        );
        $sessionStmt->execute([$selectedClassId]);
        $sessions = $sessionStmt->fetchAll(PDO::FETCH_ASSOC);

        $dateGroups = [];
        foreach ($sessions as $session) {
            $dateKey = date('Y-m-d', strtotime($session['session_date']));
            if (!isset($dateGroups[$dateKey])) {
                $dateGroups[$dateKey] = [
                    'session_date' => $dateKey,
                    'session_ids' => [],
                    'canonical_session_id' => (int)$session['session_id']
                ];
            }
            $dateGroups[$dateKey]['session_ids'][] = (int)$session['session_id'];
            // Use the smallest session ID as the single attendance reference for this date.
            $dateGroups[$dateKey]['canonical_session_id'] = min(
                $dateGroups[$dateKey]['canonical_session_id'],
                (int)$session['session_id']
            );
        }


        foreach ($dateGroups as $dateKey => $group) {
            if (in_array($selectedSessionId, $group['session_ids'], true)) {
                $selectedSessionId = $group['canonical_session_id'];
                $selectedSession = [
                    'session_id' => $group['canonical_session_id'],
                    'session_date' => $group['session_date'],
                    'session_ids' => $group['session_ids']
                ];
                break;
            }
        }

        if (!$selectedSession && $dateGroups) {
            $firstGroup = reset($dateGroups);
            $selectedSessionId = $firstGroup['canonical_session_id'];
            $selectedSession = [
                'session_id' => $firstGroup['canonical_session_id'],
                'session_date' => $firstGroup['session_date'],
                'session_ids' => $firstGroup['session_ids']
            ];
        }

        if ($selectedSession) {
            $sessionIds = $selectedSession['session_ids'];
            $placeholders = implode(',', array_fill(0, count($sessionIds), '?'));
            $studentSql = "
                SELECT s.student_id, s.student_name, s.gender,
                       (
                           SELECT a.session_id
                           FROM attendance a
                           WHERE a.student_id = s.student_id
                             AND a.session_id IN ($placeholders)
                           ORDER BY a.attendance_id DESC
                           LIMIT 1
                       ) AS attendance_session_id,
                       (
                           SELECT a.attendance_status
                           FROM attendance a
                           WHERE a.student_id = s.student_id
                             AND a.session_id IN ($placeholders)
                           ORDER BY a.attendance_id DESC
                           LIMIT 1
                       ) AS attendance_status,
                       (
                           SELECT a.remark
                           FROM attendance a
                           WHERE a.student_id = s.student_id
                             AND a.session_id IN ($placeholders)
                           ORDER BY a.attendance_id DESC
                           LIMIT 1
                       ) AS remark
                FROM enrollment e
                INNER JOIN student s ON s.student_id = e.student_id
                WHERE e.class_id = ?
                  AND e.status = 'Active'
                  AND e.enrollment_date <= ?
                ORDER BY s.student_name ASC";
            $studentStmt = $pdo->prepare($studentSql);
            $studentStmt->execute(array_merge($sessionIds, $sessionIds, $sessionIds, [$selectedClassId, $selectedSession['session_date']]));
            $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

            // Attendance is recorded once per student for the selected class date.
            // Individual exercise/topic assignments are managed separately in Assign to Session.
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_session_date'])) {
        $postClassId = (int)($_POST['class_id'] ?? 0);
        $postSessionId = (int)($_POST['session_id'] ?? 0);
        $newDate = trim((string)($_POST['new_session_date'] ?? ''));

        $dateObject = DateTime::createFromFormat('!Y-m-d', $newDate);
        if (!$dateObject || $dateObject->format('Y-m-d') !== $newDate) {
            throw new RuntimeException('Please enter a valid session date.');
        }

        $oldDateStmt = $pdo->prepare(
            "SELECT session_date FROM class_session WHERE session_id = ? AND class_id = ? LIMIT 1"
        );
        $oldDateStmt->execute([$postSessionId, $postClassId]);
        $oldDate = $oldDateStmt->fetchColumn();
        if (!$oldDate) {
            throw new RuntimeException('The selected class session could not be found.');
        }

        // Keep all topics belonging to the selected class date together when its date is corrected.
        $updateDateStmt = $pdo->prepare(
            "UPDATE class_session SET session_date = ? WHERE class_id = ? AND session_date = ?"
        );
        $updateDateStmt->execute([$newDate, $postClassId, $oldDate]);

        $canonicalStmt = $pdo->prepare(
            "SELECT MIN(session_id) FROM class_session WHERE class_id = ? AND session_date = ?"
        );
        $canonicalStmt->execute([$postClassId, $newDate]);
        $newCanonicalId = (int)$canonicalStmt->fetchColumn();

        header('Location: attendance.php?class_id=' . $postClassId .
               '&session_id=' . $newCanonicalId . '&session_updated=1');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
        $postClassId = (int)($_POST['class_id'] ?? 0);
        $postSessionId = (int)($_POST['session_id'] ?? 0);

        $verifySession = $pdo->prepare(
            "SELECT session_date FROM class_session
             WHERE session_id = ? AND class_id = ? LIMIT 1"
        );
        $verifySession->execute([$postSessionId, $postClassId]);
        $postDate = $verifySession->fetchColumn();

        if (!$postDate) {
            throw new RuntimeException('Please select a valid class date.');
        }

        $sameDateStmt = $pdo->prepare(
            "SELECT session_id FROM class_session
             WHERE class_id = ? AND session_date = ?
             ORDER BY session_id ASC"
        );
        $sameDateStmt->execute([$postClassId, $postDate]);
        $sameDateSessionIds = array_map('intval', $sameDateStmt->fetchAll(PDO::FETCH_COLUMN));
        if (!$sameDateSessionIds) {
            throw new RuntimeException('No class sessions were found for the selected date.');
        }
        $canonicalSessionId = min($sameDateSessionIds);

        $enrolledStmt = $pdo->prepare(
            "SELECT student_id FROM enrollment
             WHERE class_id = ? AND status = 'Active' AND enrollment_date <= ?"
        );
        $enrolledStmt->execute([$postClassId, $postDate]);
        $enrolledIds = array_map('intval', $enrolledStmt->fetchAll(PDO::FETCH_COLUMN));

        $postedStatuses = $_POST['attendance'] ?? [];
        $postedRemarks = $_POST['remark'] ?? [];

        $sessionPlaceholders = implode(',', array_fill(0, count($sameDateSessionIds), '?'));
        $deleteOldRows = $pdo->prepare(
            "DELETE FROM attendance
             WHERE student_id = ?
               AND session_id IN ($sessionPlaceholders)"
        );
        $saveStmt = $pdo->prepare(
            "INSERT INTO attendance
                (session_id, student_id, admin_id, attendance_status, remark)
             VALUES (?, ?, ?, ?, ?)"
        );

        $pdo->beginTransaction();
        foreach ($enrolledIds as $studentId) {
            $status = $postedStatuses[$studentId] ?? '';
            if (!in_array($status, $allowedStatuses, true)) {
                continue;
            }

            $remark = trim((string)($postedRemarks[$studentId] ?? ''));

            // Save one attendance row per student using the shared class-date reference.
            // Exercise assignments remain separate and are not stored through attendance.
            $deleteOldRows->execute(array_merge([$studentId], $sameDateSessionIds));
            $saveStmt->execute([
                $canonicalSessionId,
                $studentId,
                $adminId,
                $status,
                $remark !== '' ? $remark : null
            ]);
        }
        $completeSessionStmt = $pdo->prepare(
            "UPDATE class_session SET status = 'Completed' WHERE class_id = ? AND session_date = ?"
        );
        $completeSessionStmt->execute([$postClassId, $postDate]);
        $pdo->commit();

        header('Location: attendance.php?class_id=' . $postClassId .
               '&session_id=' . $canonicalSessionId . '&saved=1');
        exit;
    }

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errorMessage = $e instanceof PDOException
        ? 'Unable to load or save attendance. Please check the database connection and table structure.'
        : $e->getMessage();
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Display names consistently in Title Case without changing the database values.
function formatName($value): string {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_convert_case')) {
        return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    return ucwords(strtolower($value));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance | JAZRO Admin Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--blue:#2878f5;--blue-dark:#1758dc;--navy:#163664;--muted:#7c8eaa;--line:#e4edfa;--bg:#f5f9ff;--green:#159b69;--red:#dc5262;--orange:#d88922;--purple:#8059dc}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--navy);font-family:Inter,Arial,sans-serif;font-size:13px}
a{text-decoration:none}
button,input,select{font:inherit}
.admin-sidebar{position:fixed;inset:0 auto 0 0;z-index:50;display:flex;flex-direction:column;width:245px;padding:24px 15px 17px;border-right:1px solid #e3edfb;background:#fff}
.admin-brand{display:flex;align-items:center;gap:11px;min-height:72px;padding:0 7px 20px;border-bottom:1px solid var(--line)}
.admin-brand img{width:59px;height:59px;object-fit:contain;flex-shrink:0}
.admin-brand span{color:var(--blue);font-size:10px;font-weight:800;letter-spacing:1.6px}
.nav-caption{margin:26px 10px 12px;color:#91a2bb;font-size:9px;font-weight:800;letter-spacing:1.6px}
.admin-nav{display:grid;gap:6px}
.admin-nav a,.logout-link{display:flex;align-items:center;gap:12px;min-height:43px;padding:11px 13px;border-radius:10px;color:#60738f;font-size:12px;font-weight:600;transition:.2s}
.admin-nav a:hover,.logout-link:hover{background:#f0f6ff;color:var(--blue)}
.admin-nav a.active{background:#e8f2ff;color:var(--blue);box-shadow:inset 3px 0 var(--blue)}
.nav-icon{display:grid;width:23px;height:23px;place-items:center;border-radius:7px;background:#f0f6ff;font-size:14px}
.sidebar-bottom{margin-top:auto;padding-top:15px;border-top:1px solid var(--line)}
.sidebar-bottom small{display:block;padding:14px 12px 0;color:#9aaac0;font-size:9px}
.main{margin-left:245px;min-height:100vh;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:40;min-height:72px;padding:12px 30px;background:rgba(255,255,255,.97);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:15px}
.top-title strong{display:block;font-size:15px}.top-title small{display:block;margin-top:4px;color:var(--muted);font-size:10px}
.top-right{display:flex;align-items:center;gap:12px}.avatar{width:39px;height:39px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#55baff,#2870f4);color:#fff;font-weight:800}
.account strong{display:block;font-size:11px}.account small{display:block;margin-top:4px;color:var(--muted);font-size:10px}
.mobile-menu{display:none;width:38px;height:38px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--blue)}
.content{width:100%;max-width:1500px;margin:0 auto;padding:27px 30px}
.intro{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;flex-wrap:wrap;margin-bottom:20px}
.eyebrow{margin:0 0 9px;color:var(--blue);font-size:9px;font-weight:800;letter-spacing:1.6px}
h1{margin:0;font-size:27px;letter-spacing:-.8px}.intro p{margin:8px 0 0;color:var(--muted);font-size:12px;line-height:1.7}
.panel{padding:22px;border:1px solid var(--line);border-radius:15px;background:#fff;box-shadow:0 5px 18px #25569908}
.filters{display:grid;grid-template-columns:1fr 1fr auto;gap:13px;align-items:end}
.form-group{display:flex;flex-direction:column;gap:8px}.form-group label{font-size:11px;font-weight:700;color:#425976}
.form-control{width:100%;height:43px;padding:0 12px;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--navy);outline:none;font-size:11px}
.form-control:focus{border-color:#8db6ff;box-shadow:0 0 0 3px #2878f510}
.primary{height:43px;padding:0 17px;border:0;border-radius:9px;background:var(--blue);color:#fff;font-size:11px;font-weight:700;cursor:pointer}
.primary:hover{background:var(--blue-dark)}
.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin:20px 0}
.stat{--stat-color:#5578a8;position:relative;display:flex;align-items:center;gap:18px;min-height:116px;padding:21px 23px;border:1px solid #e8edf4;border-radius:14px;background:linear-gradient(145deg,#fff 0%,#fcfdff 100%);box-shadow:0 3px 12px rgba(29,55,90,.035);transition:border-color .2s ease,box-shadow .2s ease}
.stat:hover{border-color:#d8e2ef;box-shadow:0 8px 22px rgba(29,55,90,.07)}
.stat-icon{display:grid;place-items:center;flex:0 0 43px;width:43px;height:43px;border:1px solid color-mix(in srgb,var(--stat-color) 18%,white);border-radius:12px;background:color-mix(in srgb,var(--stat-color) 7%,white);color:var(--stat-color);font-size:18px;font-weight:700}
.stat-info{min-width:0}
.stat-label{display:block;margin-bottom:8px;color:#8290a4;font-size:10px;font-weight:700;letter-spacing:1.05px;text-transform:uppercase}
.stat-bottom{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap}
.stat strong{display:block;color:#203957;font-size:31px;line-height:1;letter-spacing:-.8px;font-weight:700;font-variant-numeric:tabular-nums}
.stat-note{color:#98a4b5;font-size:10px;font-weight:500}
.stat.enrolled{--stat-color:#6283b5}
.stat.present{--stat-color:#4b9b7b}
.stat.absent{--stat-color:#c87980}
.table-wrap{overflow-x:auto;margin-top:18px}
.table-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:13px}
.table-head h2{margin:0;font-size:15px}.table-head p{margin:5px 0 0;color:var(--muted);font-size:10px}
table{width:100%;border-collapse:collapse;min-width:680px}
th{padding:12px 13px;text-align:left;background:#f5f9ff;color:#71839d;font-size:10px;font-weight:700}
td{padding:12px 13px;border-bottom:1px solid #edf2fa;font-size:11px;vertical-align:middle}
tbody tr:hover{background:#fbfdff}
.student-name{font-weight:700;color:var(--navy)}.student-id{display:block;margin-top:4px;color:var(--muted);font-size:9px}
.session-meta{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-top:7px}
.session-topic{color:#60738f;font-size:10px;font-weight:600}
.session-state{display:inline-flex;align-items:center;padding:4px 8px;border-radius:20px;font-size:9px;font-weight:700}
.session-state.completed{background:#e8f8f0;color:#147b53}
.session-state.scheduled{background:#fff5e5;color:#a96812}
.session-state.other{background:#eef3fa;color:#647791}

.status-select{min-width:125px;height:36px;padding:0 9px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--navy);font-size:10px;font-weight:700;cursor:pointer;transition:background .15s,border-color .15s}
.status-select.status-present{background:#e8f8f0;border-color:#b9e8d0;color:#147b53}
.status-select.status-absent{background:#fff0f1;border-color:#f4c4ca;color:#b83d4d}
.remark-input{width:100%;min-width:150px;height:36px;padding:0 9px;border:1px solid var(--line);border-radius:8px;font-size:10px}
.notice{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:11px}
.notice.success{background:#e8f8f0;color:#147b53}.notice.error{background:#fff0f1;color:#b83d4d}
.empty{padding:35px 15px;text-align:center;color:var(--muted);font-size:11px}

.table-actions{display:flex;justify-content:flex-end;margin-top:16px}
.date-edit-form{display:flex;align-items:end;gap:9px;flex-wrap:wrap;margin-top:13px}
.date-edit-form label{width:100%;color:#71839d;font-size:10px;font-weight:700}
.date-edit-form .form-control{width:175px;height:37px}
.date-edit-form .primary{height:37px}
.backdrop{display:none}
@media(max-width:900px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.filters{grid-template-columns:1fr 1fr}.filters .primary{grid-column:1/-1}}
@media(max-width:760px){.admin-sidebar{width:245px;height:100dvh;transform:translateX(-105%);box-shadow:12px 0 35px #1941781a;transition:transform .2s}.admin-sidebar.open{transform:translateX(0)}.backdrop{position:fixed;inset:0;z-index:45;display:block;background:#142d5040;opacity:0;pointer-events:none}.backdrop.show{opacity:1;pointer-events:auto}.main{margin-left:0}.mobile-menu{display:grid;place-items:center;flex-shrink:0}.topbar{min-height:62px;padding:10px 15px}.top-title{flex:1}.account{display:none}.content{padding:20px 15px}.intro h1{font-size:23px}}
@media(max-width:480px){.filters{grid-template-columns:1fr}.filters .primary{grid-column:auto}.stats{gap:8px}.stat{padding:12px}}

/* Attendance page visual refresh */
.content{padding-top:30px}
.intro{margin-bottom:23px}
.intro h1{font-size:29px;color:#173b6b}
.intro p{font-size:12px;color:#7185a2}
.panel{border:1px solid #e2ebf8;border-radius:17px;box-shadow:0 8px 24px rgba(35,91,158,.055)}
.filters{gap:16px}
.form-group label{color:#3f5b7e;font-size:11px}
.form-control{border-color:#dce7f5;border-radius:10px;background:#fbfdff}
.form-control:focus{background:#fff;border-color:#72a7ff;box-shadow:0 0 0 3px rgba(40,120,245,.10)}
.primary{border-radius:10px;box-shadow:0 5px 12px rgba(40,120,245,.16);transition:background .18s,transform .18s}
.primary:hover{transform:translateY(-1px)}
.stats{gap:17px;margin:20px 0}
.stat{min-height:124px;padding:22px 23px;border:1px solid #e1eaf7;border-radius:16px;background:#fff;box-shadow:0 6px 18px rgba(30,69,119,.055)}
.stat.enrolled{--stat-color:#2878f5;background:linear-gradient(135deg,#fff 55%,#f0f6ff)}
.stat.present{--stat-color:#159b69;background:linear-gradient(135deg,#fff 55%,#effaf5)}
.stat.absent{--stat-color:#e05260;background:linear-gradient(135deg,#fff 55%,#fff3f4)}
.stat-icon{width:48px;height:48px;flex-basis:48px;border:0;border-radius:14px;background:color-mix(in srgb,var(--stat-color) 12%,white);color:var(--stat-color);font-size:20px}
.stat-label{color:#7185a2;font-size:10px;letter-spacing:.8px}
.stat strong{color:#173b6b;font-size:33px}
.stat-note{color:#7f91aa;font-size:10px}
.table-head{margin-bottom:17px}
.table-head h2{color:#173b6b;font-size:16px}
table{border-spacing:0}
th{padding:14px 14px;background:#f2f7ff;color:#6c82a1;font-size:9px;letter-spacing:.65px;text-transform:uppercase}
td{padding:14px;border-bottom:1px solid #edf2fa}
tbody tr:hover{background:#f8fbff}
.status-select{border-radius:9px}
.remark-input{border-color:#e0e9f5;border-radius:9px;outline:none}
.remark-input:focus{border-color:#8db6ff;box-shadow:0 0 0 3px rgba(40,120,245,.08)}
.date-edit-form{padding:13px 14px;border:1px solid #e5eefb;border-radius:12px;background:#f8fbff}
.date-edit-form label{color:#617997}
.session-meta{margin-top:9px}
.session-topic{font-weight:700;color:#36577f}
.notice.success,.notice.error{border:1px solid transparent;border-radius:11px}
@media(max-width:480px){.stat{min-height:105px;padding:13px}.stat-icon{width:38px;height:38px;flex-basis:38px}.stat strong{font-size:27px}}
</style>
</head>
<body>
<aside class="admin-sidebar" id="sidebar">
  <a class="admin-brand" href="dashboard.php"><img src="assets/images/jazro-logo.png" alt="JAZRO Robotic Academy"><span>ADMIN PORTAL</span></a>
  <p class="nav-caption">MAIN MENU</p>
  <?php include __DIR__ . '/learning_activities_menu.php'; ?>
  <div class="sidebar-bottom"><a href="index.php" class="logout-link"><span class="nav-icon">↪</span>Log Out</a><small>JAZRO ROBOTIC ACADEMY · 2026</small></div>
</aside>
<div class="backdrop" id="backdrop"></div>
<main class="main">
<header class="topbar">
  <button class="mobile-menu" id="mobileMenu" aria-label="Open menu">☰</button>
  <div class="top-title"><strong>Attendance Management</strong><small>Manage student attendance by class session</small></div>
  <div class="top-right"><div class="avatar">A</div><div class="account"><strong><?= h(formatName($adminName)) ?></strong><small>Administrator</small></div></div>
</header>
<section class="content">
  <div class="intro"><div><div class="eyebrow">ACADEMIC MANAGEMENT</div><h1>Attendance</h1><p>Select a class and date to view or update student attendance.</p></div></div>

  <?php if ($successMessage): ?><div class="notice success"><?= h($successMessage) ?></div><?php endif; ?>
  <?php if ($errorMessage): ?><div class="notice error"><?= h($errorMessage) ?></div><?php endif; ?>

  <section class="panel">
    <form method="get" class="filters" id="filterForm">
      <div class="form-group"><label for="class_id">Select Class</label>
        <select class="form-control" name="class_id" id="class_id" required>
          <option value="">Choose class</option>
          <?php foreach ($classes as $class): ?>
            <option value="<?= (int)$class['class_id'] ?>" <?= $selectedClassId === (int)$class['class_id'] ? 'selected' : '' ?>>
              <?= h($class['class_code'] . ' - ' . $class['class_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label for="session_id">Select Class Date</label>
        <select class="form-control" name="session_id" id="session_id" <?= !$sessions ? 'disabled' : '' ?>>
          <?php if (!$sessions): ?><option value="">No sessions available</option><?php endif; ?>
          <?php foreach ($dateGroups as $dateKey => $group): ?>
            <option value="<?= (int)$group['canonical_session_id'] ?>" <?= $selectedSessionId === (int)$group['canonical_session_id'] ? 'selected' : '' ?>>
              <?= h(date('d M Y', strtotime($dateKey)) . ' (' . date('l', strtotime($dateKey)) . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="primary">View Attendance</button>
    </form>
  </section>

  <?php if ($selectedClass && $selectedSession): ?>
    <?php
      $presentCount = $absentCount = 0;
      foreach ($students as $student) {
          if (($student['attendance_status'] ?? '') === 'Present') {
              $presentCount++;
          } elseif (($student['attendance_status'] ?? '') === 'Absent') {
              $absentCount++;
          }
      }
      $enrolledCount = count($students);
      $presentPercent = $enrolledCount > 0 ? round(($presentCount / $enrolledCount) * 100) : 0;
      $absentPercent = $enrolledCount > 0 ? round(($absentCount / $enrolledCount) * 100) : 0;
    ?>
    <div class="stats">
      <div class="stat enrolled">
        <div class="stat-icon">♧</div>
        <div class="stat-info">
          <span class="stat-label">Enrolled Students</span>
          <div class="stat-bottom"><strong><?= $enrolledCount ?></strong><span class="stat-note">Total registered</span></div>
        </div>
      </div>
      <div class="stat present">
        <div class="stat-icon">✓</div>
        <div class="stat-info">
          <span class="stat-label">Present</span>
          <div class="stat-bottom"><strong><?= $presentCount ?></strong><span class="stat-note"><?= $presentPercent ?>% attendance</span></div>
        </div>
      </div>
      <div class="stat absent">
        <div class="stat-icon">−</div>
        <div class="stat-info">
          <span class="stat-label">Absent</span>
          <div class="stat-bottom"><strong><?= $absentCount ?></strong><span class="stat-note"><?= $absentPercent ?>% of class</span></div>
        </div>
      </div>
    </div>

    <form method="post" class="panel" id="attendanceForm">
      <input type="hidden" name="class_id" value="<?= (int)$selectedClassId ?>">
      <input type="hidden" name="session_id" value="<?= (int)$selectedSessionId ?>">
      <div class="table-head">
        <div>
            <h2><?= h($selectedClass['class_name']) ?></h2>
            <?php
              $sessionStatusStmt = $pdo->prepare(
                  "SELECT
                      CASE
                          WHEN SUM(status = 'Scheduled') > 0 THEN 'Scheduled'
                          WHEN SUM(status = 'Completed') > 0 THEN 'Completed'
                          WHEN SUM(status = 'Cancelled') = COUNT(*) THEN 'Cancelled'
                          ELSE 'Status not set'
                      END
                   FROM class_session
                   WHERE class_id = ? AND session_date = ?"
              );
              $sessionStatusStmt->execute([$selectedClassId, $selectedSession['session_date']]);
              $sessionStatusLabel = $sessionStatusStmt->fetchColumn() ?: 'Status not set';
              $sessionStatus = strtolower($sessionStatusLabel);
              $sessionStatusClass = $sessionStatus === 'completed' ? 'completed' : ($sessionStatus === 'scheduled' ? 'scheduled' : 'other');
            ?>
            <div class="session-meta">
              <span class="session-topic"><?= h(date('d F Y', strtotime($selectedSession['session_date']))) ?></span>
              <span class="session-state <?= h($sessionStatusClass) ?>"><?= h($sessionStatusLabel) ?></span>
            </div>
            <div class="date-edit-form">
              <label for="new_session_date">Edit session date</label>
              <input class="form-control" type="date" id="new_session_date" name="new_session_date" form="attendanceForm" value="<?= h($selectedSession['session_date']) ?>" required>
              <button type="submit" class="primary" name="update_session_date" value="1" form="attendanceForm" formnovalidate>Update Date</button>
            </div>
          </div>
        <span class="notice" style="margin:0;background:#eaf2ff;color:#2871e4"><?= count($students) ?> students</span>
      </div>
      <?php if ($students): ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th style="width:45%">Student</th><th style="width:25%">Attendance Status</th><th style="width:30%">Remark</th></tr></thead>
          <tbody>
          <?php foreach ($students as $student): ?>
            <tr>
              <td><span class="student-name"><?= h(formatName($student['student_name'])) ?></span><span class="student-id">Student ID: <?= (int)$student['student_id'] ?></span></td>
              <td>
                <select class="status-select" name="attendance[<?= (int)$student['student_id'] ?>]" required>
                  <option value="">Select status</option>
                  <?php foreach ($allowedStatuses as $status): ?>
                    <option value="<?= h($status) ?>" <?= ($student['attendance_status'] ?? '') === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input class="remark-input" type="text" name="remark[<?= (int)$student['student_id'] ?>]" value="<?= h($student['remark'] ?? '') ?>" placeholder="Add remark"></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="table-actions" style="justify-content:space-between;gap:10px;flex-wrap:wrap">
        <button type="button" class="primary" id="markAllPresent" style="background:#e8f2ff;color:#2878f5">Mark All Present</button>
        <button type="submit" name="save_attendance" value="1" class="primary">Save Attendance</button>
      </div>
      <?php else: ?>
        <div class="empty">No active enrolled students found for this class.</div>
      <?php endif; ?>
    </form>

  <?php elseif ($selectedClass): ?>
    <div class="panel empty" style="margin-top:18px">No class sessions are available for this class yet.</div>
  <?php else: ?>
    <div class="panel empty" style="margin-top:18px">No active classes are available.</div>
  <?php endif; ?>
</section>
</main>
<script>
const classSelect=document.getElementById('class_id');
const sessionSelect=document.getElementById('session_id');
function updateAttendanceStatusColor(select){
  select.classList.remove('status-present','status-absent');
  const statusClass = {
    Present: 'status-present',
    Absent: 'status-absent',
  }[select.value];
  if(statusClass) select.classList.add(statusClass);
}
document.querySelectorAll('select[name^="attendance["]').forEach(select=>{
  updateAttendanceStatusColor(select);
  select.addEventListener('change',()=>updateAttendanceStatusColor(select));
});
document.getElementById('markAllPresent')?.addEventListener('click',()=>{
  document.querySelectorAll('select[name^="attendance["]').forEach(select=>{
    select.value='Present';
    updateAttendanceStatusColor(select);
  });
});
classSelect?.addEventListener('change',()=>{const url=new URL(window.location.href);url.searchParams.set('class_id',classSelect.value);url.searchParams.delete('session_id');window.location.href=url.toString();});
document.getElementById('mobileMenu')?.addEventListener('click',()=>{document.getElementById('sidebar').classList.toggle('open');document.getElementById('backdrop').classList.toggle('show');});
document.getElementById('backdrop')?.addEventListener('click',()=>{document.getElementById('sidebar').classList.remove('open');document.getElementById('backdrop').classList.remove('show');});
</script>
</body>
</html>
