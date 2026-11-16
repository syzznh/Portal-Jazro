<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
require_once __DIR__ . '/db.php';

$adminStmt = $pdo->prepare("SELECT admin_id, admin_name FROM admin WHERE user_id = ? LIMIT 1");
$adminStmt->execute([$_SESSION['user_id']]);
$admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
if (!$admin) { http_response_code(403); exit('Admin account not found. Please log in again.'); }
$adminId = (int)$admin['admin_id'];
$adminName = $admin['admin_name'] ?: ($_SESSION['username'] ?? 'Administrator');
if (empty($_SESSION['csrf_assessments'])) $_SESSION['csrf_assessments'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_assessments'];
$errorMessage = '';
$successMessage = match ($_GET['saved'] ?? '') { 'added' => 'Assessment added successfully.', 'updated' => 'Assessment updated successfully.', 'deleted' => 'Assessment deleted successfully.', default => '' };

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function titleCaseName($v): string { return mb_convert_case(mb_strtolower(trim((string)$v), 'UTF-8'), MB_CASE_TITLE, 'UTF-8'); }

try {
    // Assessment records are linked to modules in the supplied database schema.
    $modules = $pdo->query("SELECT module_id, module_code, module_name, module_category, level FROM module ORDER BY module_category, module_name")->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) throw new RuntimeException('Your session has expired. Please refresh and try again.');
        $action = $_POST['action'] ?? '';
        if ($action === 'save') {
            $assessmentId = (int)($_POST['assessment_id'] ?? 0);
            $moduleId = (int)($_POST['module_id'] ?? 0);
            $title = trim((string)($_POST['title'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $date = trim((string)($_POST['date'] ?? ''));
            $totalMarks = filter_var($_POST['total_marks'] ?? null, FILTER_VALIDATE_INT);
            if ($moduleId < 1 || $title === '' || !$totalMarks || $totalMarks < 1 || $date === '') throw new RuntimeException('Please complete all required fields with valid values.');
            $moduleCheck = $pdo->prepare("SELECT COUNT(*) FROM module WHERE module_id = ?");
            $moduleCheck->execute([$moduleId]);
            if (!(int)$moduleCheck->fetchColumn()) throw new RuntimeException('Please select a valid module.');
            $dateObj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $date) throw new RuntimeException('Please enter a valid assessment date.');
            if ($assessmentId > 0) {
                $ownerCheck = $pdo->prepare("SELECT assessment_id, module_id, total_marks FROM assessment WHERE assessment_id = ? LIMIT 1");
                $ownerCheck->execute([$assessmentId]);
                $existingAssessment = $ownerCheck->fetch(PDO::FETCH_ASSOC);
                if (!$existingAssessment) throw new RuntimeException('Assessment record was not found.');

                // Keep module and maximum marks stable once student results exist.
                $resultCheck = $pdo->prepare("SELECT COUNT(*) FROM result_assessment WHERE assessment_id = ?");
                $resultCheck->execute([$assessmentId]);
                $hasStudentResults = (int)$resultCheck->fetchColumn() > 0;

                if ($hasStudentResults &&
                    ((int)$existingAssessment['module_id'] !== $moduleId ||
                     (int)$existingAssessment['total_marks'] !== $totalMarks)) {
                    throw new RuntimeException('This assessment already has student results. Its module and total marks cannot be changed.');
                }

                $stmt = $pdo->prepare("UPDATE assessment SET module_id = ?, title = ?, description = ?, total_marks = ?, date = ? WHERE assessment_id = ?");
                $stmt->execute([$moduleId, $title, $description !== '' ? $description : null, $totalMarks, $date, $assessmentId]);
                header('Location: assessments.php?saved=updated'); exit;
            } else {
                $stmt = $pdo->prepare("INSERT INTO assessment (admin_id, module_id, title, description, total_marks, date) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$adminId, $moduleId, $title, $description !== '' ? $description : null, $totalMarks, $date]);
                header('Location: assessments.php?saved=added'); exit;
            }
        } elseif ($action === 'delete') {
            $assessmentId = (int)($_POST['assessment_id'] ?? 0);
            $check = $pdo->prepare("SELECT COUNT(*) FROM result_assessment WHERE assessment_id = ?");
            $check->execute([$assessmentId]);
            if ((int)$check->fetchColumn() > 0) {
                throw new RuntimeException('This assessment already has student results, so it cannot be deleted.');
            }
            $delete = $pdo->prepare("DELETE FROM assessment WHERE assessment_id = ?");
            $delete->execute([$assessmentId]);
            if (!$delete->rowCount()) throw new RuntimeException('Assessment record was not found.');
            header('Location: assessments.php?saved=deleted'); exit;
        }
    }

    $search = trim((string)($_GET['search'] ?? ''));
    $categoryFilter = trim((string)($_GET['category'] ?? ''));
    $moduleFilter = (int)($_GET['module_id'] ?? 0);
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(a.title LIKE ? OR a.description LIKE ? OR m.module_name LIKE ? OR m.module_code LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    if ($categoryFilter !== '') { $where[] = 'm.module_category = ?'; $params[] = $categoryFilter; }
    if ($moduleFilter > 0) { $where[] = 'a.module_id = ?'; $params[] = $moduleFilter; }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $listSql = "SELECT a.assessment_id, a.title, a.description, a.total_marks, a.date,
                       m.module_id, m.module_code, m.module_name, m.module_category, m.level,
                       COUNT(ra.result_id) AS total_results,
                       SUM(CASE WHEN ra.status = 'Completed' THEN 1 ELSE 0 END) AS completed_results
                FROM assessment a
                INNER JOIN module m ON m.module_id = a.module_id
                LEFT JOIN result_assessment ra ON ra.assessment_id = a.assessment_id
                $whereSql
                GROUP BY a.assessment_id, a.title, a.description, a.total_marks, a.date,
                         m.module_id, m.module_code, m.module_name, m.module_category, m.level
                ORDER BY a.date DESC, a.assessment_id DESC";
    $listStmt = $pdo->prepare($listSql);
    $listStmt->execute($params);
    $assessments = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = $pdo->query("SELECT COUNT(DISTINCT a.assessment_id) AS total,
                                  COUNT(DISTINCT CASE WHEN ra.result_id IS NOT NULL THEN a.assessment_id END) AS with_results,
                                  COUNT(ra.result_id) AS result_entries,
                                  SUM(CASE WHEN ra.status = 'Completed' THEN 1 ELSE 0 END) AS completed_entries
                           FROM assessment a LEFT JOIN result_assessment ra ON ra.assessment_id = a.assessment_id")->fetch(PDO::FETCH_ASSOC);
    $categories = $pdo->query("SELECT DISTINCT module_category FROM module WHERE module_category IS NOT NULL AND module_category <> '' ORDER BY module_category")->fetchAll(PDO::FETCH_COLUMN);

    $viewId = (int)($_GET['view'] ?? 0);
    $viewAssessment = null;
    $viewResults = [];
    if ($viewId > 0) {
        $viewStmt = $pdo->prepare("SELECT a.*, m.module_code, m.module_name, m.module_category FROM assessment a INNER JOIN module m ON m.module_id = a.module_id WHERE a.assessment_id = ?");
        $viewStmt->execute([$viewId]);
        $viewAssessment = $viewStmt->fetch(PDO::FETCH_ASSOC);
        if ($viewAssessment) {
            $resultStmt = $pdo->prepare("SELECT ra.score, ra.percentage, ra.status, ra.completed_date, s.student_id, s.student_name
                                         FROM result_assessment ra INNER JOIN student s ON s.student_id = ra.student_id
                                         WHERE ra.assessment_id = ? ORDER BY s.student_name");
            $resultStmt->execute([$viewId]);
            $viewResults = $resultStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Throwable $e) {
    if ($e instanceof PDOException) $errorMessage = 'Unable to load or save assessments. Please check the database connection and table structure.';
    else $errorMessage = $e->getMessage();
    $assessments = $assessments ?? [];
    $modules = $modules ?? [];
    $categories = $categories ?? [];
    $stats = $stats ?? ['total'=>0,'with_results'=>0,'result_entries'=>0,'completed_entries'=>0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assessments | JAZRO Admin Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--blue:#2878f5;--blue-dark:#1758dc;--navy:#163664;--muted:#7c8eaa;--line:#e4edfa;--bg:#f5f9ff;--green:#159b69;--orange:#d88922;--red:#dc5262;--purple:#8059dc}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--navy);font-family:Inter,Arial,sans-serif;font-size:13px}a{text-decoration:none;color:inherit}button,input,select,textarea{font:inherit}
.admin-sidebar{position:fixed;inset:0 auto 0 0;z-index:50;display:flex;flex-direction:column;width:245px;padding:24px 15px 17px;border-right:1px solid var(--line);background:#fff}.admin-brand{display:flex;align-items:center;gap:11px;min-height:72px;padding:0 7px 20px;border-bottom:1px solid var(--line)}.admin-brand img{width:59px;height:59px;object-fit:contain;flex-shrink:0}.admin-brand span{color:var(--blue);font-size:10px;font-weight:800;letter-spacing:1.6px}.nav-caption{margin:20px 0 14px;color:#91a2bb;font-size:9px;font-weight:800;letter-spacing:1.6px}.admin-nav{display:grid;gap:4px}.admin-nav a,.logout-link{display:flex;align-items:center;gap:12px;min-height:53px;padding:0 14px;border-radius:13px;color:#60738f;font-size:12px;font-weight:600;transition:.2s}.admin-nav a:hover,.logout-link:hover{background:#f0f6ff;color:var(--blue)}.admin-nav a.active{background:#e8f2ff;color:var(--blue);box-shadow:inset 3px 0 var(--blue)}.nav-icon{display:grid;width:23px;height:23px;place-items:center;border-radius:7px;background:#f0f6ff;font-size:14px}.sidebar-bottom{margin-top:auto;padding-top:15px;border-top:1px solid var(--line)}.sidebar-bottom small{display:block;padding:14px 12px 0;color:#9aaac0;font-size:9px}
.main{margin-left:245px;min-height:100vh;display:flex;flex-direction:column}.topbar{position:sticky;top:0;z-index:40;min-height:72px;padding:12px 30px;background:rgba(255,255,255,.97);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:15px}.top-title strong{display:block;font-size:15px}.top-title small{display:block;margin-top:4px;color:var(--muted);font-size:10px}.top-right{display:flex;align-items:center;gap:12px}.avatar{width:39px;height:39px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#55baff,#2870f4);color:#fff;font-weight:800}.account strong{display:block;font-size:11px}.account small{display:block;margin-top:4px;color:var(--muted);font-size:10px}.mobile-menu{display:none;width:38px;height:38px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--blue)}
.content{width:100%;max-width:1500px;margin:0 auto;padding:27px 30px}.intro{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;flex-wrap:wrap;margin-bottom:21px}.eyebrow{margin:0 0 9px;color:var(--blue);font-size:9px;font-weight:800;letter-spacing:1.6px}h1{margin:0;font-size:27px;letter-spacing:-.8px}.intro p{margin:8px 0 0;color:var(--muted);font-size:12px;line-height:1.7}.primary{height:42px;padding:0 16px;border:0;border-radius:9px;background:var(--blue);color:#fff;font-size:11px;font-weight:700;cursor:pointer}.primary:hover{background:var(--blue-dark)}.secondary{height:40px;padding:0 14px;border:1px solid var(--line);border-radius:9px;background:#fff;color:#526c90;font-size:11px;font-weight:600;cursor:pointer}.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin:18px 0}.stat{display:flex;align-items:center;gap:13px;padding:17px;border:1px solid var(--line);border-radius:13px;background:#fff}.stat-icon{width:40px;height:40px;display:grid;place-items:center;border-radius:11px;background:#eaf2ff;color:var(--blue);font-size:17px}.stat-icon.green{background:#e4f8ef;color:var(--green)}.stat-icon.purple{background:#f0eaff;color:var(--purple)}.stat strong{display:block;font-size:22px;letter-spacing:-.5px}.stat span{display:block;margin-top:4px;color:var(--muted);font-size:10px}
.panel{padding:21px;border:1px solid var(--line);border-radius:15px;background:#fff;box-shadow:0 5px 18px #25569908}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:17px}.panel-head h2{margin:0;font-size:15px}.panel-head p{margin:5px 0 0;color:var(--muted);font-size:10px}.filters{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.control{height:40px;padding:0 11px;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--navy);font-size:11px;outline:none}.control:focus,.field input:focus,.field select:focus,.field textarea:focus{border-color:#8db6ff;box-shadow:0 0 0 3px #2878f510}.search{width:220px}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:780px}th{padding:12px;text-align:left;background:#f5f9ff;color:#71839d;font-size:9px;font-weight:800;letter-spacing:.6px}td{padding:13px 12px;border-bottom:1px solid #edf2fa;font-size:11px;vertical-align:middle}tbody tr:hover{background:#fbfdff}.assessment-title{font-weight:700;color:var(--navy)}.subtext{display:block;margin-top:4px;color:var(--muted);font-size:9px}.module-chip{display:inline-flex;align-items:center;padding:6px 9px;border-radius:7px;background:#edf4ff;color:var(--blue);font-size:9px;font-weight:700}.date{white-space:nowrap;color:#526c90}.result-count{font-weight:700}.action-group{display:flex;gap:5px}.action{width:31px;height:31px;border:1px solid var(--line);border-radius:8px;background:#fff;color:#58769b;cursor:pointer}.action:hover{background:#eaf2ff;color:var(--blue)}.empty{padding:35px 15px;text-align:center;color:var(--muted);font-size:11px}.notice{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:11px}.notice.success{background:#e8f8f0;color:#147b53}.notice.error{background:#fff0f1;color:#b83d4d}
.modal{display:none;position:fixed;inset:0;z-index:100;background:#142d5066;align-items:center;justify-content:center;padding:18px}.modal.show{display:flex}.modal-box{width:100%;max-width:560px;max-height:90vh;overflow:auto;padding:23px;border-radius:16px;background:#fff;box-shadow:0 20px 60px #173b6826}.modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}.modal-head h2{margin:0;font-size:17px}.close{width:32px;height:32px;border:0;border-radius:8px;background:#f1f5fb;color:#526c90;cursor:pointer}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field{display:flex;flex-direction:column;gap:7px}.field.full{grid-column:1/-1}.field label{font-size:10px;font-weight:700;color:#526c90}.field input,.field select,.field textarea{width:100%;min-height:40px;padding:10px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--navy);font-size:11px;outline:none}.field textarea{min-height:85px;resize:vertical}.modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:20px}.result-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin:14px 0}.result-summary div{padding:12px;border-radius:9px;background:#f5f9ff}.result-summary strong{display:block;font-size:18px}.result-summary span{display:block;margin-top:4px;color:var(--muted);font-size:9px}.result-table{min-width:560px}.status{display:inline-flex;padding:5px 8px;border-radius:20px;font-size:9px;font-weight:700}.status.completed{background:#e8f8f0;color:#147b53}.status.progress{background:#fff5e5;color:#a96812}.status.not-started{background:#eef3fa;color:#647791}.backdrop{display:none}
@media(max-width:900px){.stats{grid-template-columns:repeat(3,minmax(0,1fr))}.search{width:100%}}@media(max-width:760px){.admin-sidebar{height:100dvh;transform:translateX(-105%);box-shadow:12px 0 35px #1941781a;transition:transform .2s}.admin-sidebar.open{transform:translateX(0)}.backdrop{position:fixed;inset:0;z-index:45;display:block;background:#142d5040;opacity:0;pointer-events:none}.backdrop.show{opacity:1;pointer-events:auto}.main{margin-left:0}.mobile-menu{display:grid;place-items:center;flex-shrink:0}.topbar{min-height:62px;padding:10px 15px}.top-title{flex:1}.account{display:none}.content{padding:20px 15px}.intro h1{font-size:23px}.stats{grid-template-columns:1fr 1fr}.panel{padding:15px}}@media(max-width:480px){.stats{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}.result-summary{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<aside class="admin-sidebar" id="sidebar">
 <a class="admin-brand" href="dashboard.php"><img src="assets/images/jazro-logo.png" alt="JAZRO Robotic Academy"><span>ADMIN PORTAL</span></a>
 <p class="nav-caption">MAIN MENU</p><nav class="admin-nav">
  <a href="dashboard.php"><span class="nav-icon">⌂</span>Dashboard</a><a href="classes.php"><span class="nav-icon">▦</span>Classes</a><a href="students.php"><span class="nav-icon">♧</span>Students</a><a href="attendance.php"><span class="nav-icon">✓</span>Attendance</a><a class="active" href="assessments.php"><span class="nav-icon">☷</span>Assessments</a><a href="certificates.php"><span class="nav-icon">♜</span>Certificates</a><a href="learning_materials.php"><span class="nav-icon">▤</span>Learning Materials</a><a href="recordings.php"><span class="nav-icon">▷</span>Online Recordings</a><a href="reports.php"><span class="nav-icon">▥</span>Reports</a><a href="settings.php"><span class="nav-icon">⚙</span>Settings</a>
 </nav><div class="sidebar-bottom"><a href="index.php" class="logout-link"><span class="nav-icon">↪</span>Log Out</a><small>JAZRO ROBOTIC ACADEMY · 2026</small></div>
</aside><div class="backdrop" id="backdrop"></div>
<main class="main"><header class="topbar"><button class="mobile-menu" id="mobileMenu" aria-label="Open menu">☰</button><div class="top-title"><strong>Assessment Management</strong><small>Manage assessments and review student results</small></div><div class="top-right"><div class="avatar"><?= h(mb_strtoupper(mb_substr(titleCaseName($adminName),0,1,'UTF-8'),'UTF-8')) ?></div><div class="account"><strong><?= h(titleCaseName($adminName)) ?></strong><small>Administrator</small></div></div></header>
<section class="content"><div class="intro"><div><div class="eyebrow">ACADEMIC MANAGEMENT</div><h1>Assessments</h1><p>Create assessments by module and keep track of student results.</p></div><button class="primary" type="button" onclick="openAddModal()">＋ Add Assessment</button></div>
<?php if ($successMessage): ?><div class="notice success"><?= h($successMessage) ?></div><?php endif; ?><?php if ($errorMessage): ?><div class="notice error"><?= h($errorMessage) ?></div><?php endif; ?>
<div class="stats"><div class="stat"><div class="stat-icon">☷</div><div><strong><?= (int)($stats['total'] ?? 0) ?></strong><span>Total Assessments</span></div></div><div class="stat"><div class="stat-icon green">✓</div><div><strong><?= (int)($stats['with_results'] ?? 0) ?></strong><span>Assessments With Results</span></div></div><div class="stat"><div class="stat-icon purple">▤</div><div><strong><?= (int)($stats['completed_entries'] ?? 0) ?></strong><span>Completed Student Results</span></div></div></div>
<div class="panel"><div class="panel-head"><div><h2>Assessment Directory</h2><p>Search and manage assessment records stored in the database.</p></div><form class="filters" method="get"><input class="control search" type="search" name="search" placeholder="Search title or module..." value="<?= h($search ?? '') ?>"><select class="control" name="category"><option value="">All Categories</option><?php foreach ($categories as $cat): ?><option value="<?= h($cat) ?>" <?= ($categoryFilter ?? '') === $cat ? 'selected' : '' ?>><?= h($cat) ?></option><?php endforeach; ?></select><button class="primary" type="submit">Filter</button><?php if (($search ?? '') !== '' || ($categoryFilter ?? '') !== ''): ?><a class="secondary" href="assessments.php" style="display:inline-flex;align-items:center">Reset</a><?php endif; ?></form></div>
<div class="table-wrap"><table><thead><tr><th>ASSESSMENT</th><th>MODULE</th><th>DATE</th><th>TOTAL MARKS</th><th>STUDENT RESULTS</th><th>ACTIONS</th></tr></thead><tbody>
<?php foreach ($assessments as $a): ?><tr><td><span class="assessment-title"><?= h($a['title']) ?></span><span class="subtext"><?= h($a['description'] ?: 'No description') ?></span></td><td><span class="module-chip"><?= h($a['module_code']) ?></span><span class="subtext"><?= h($a['module_name']) ?><?= $a['module_category'] ? ' · '.h($a['module_category']) : '' ?></span></td><td class="date"><?= $a['date'] ? h(date('d M Y',strtotime($a['date']))) : '—' ?></td><td><strong><?= (int)$a['total_marks'] ?></strong> marks</td><td><span class="result-count"><?= (int)$a['completed_results'] ?> / <?= (int)$a['total_results'] ?></span><span class="subtext">completed</span></td><td><div class="action-group"><a class="action" title="View results" href="assessments.php?view=<?= (int)$a['assessment_id'] ?><?= $search !== '' ? '&search='.urlencode($search) : '' ?><?= $categoryFilter !== '' ? '&category='.urlencode($categoryFilter) : '' ?>">⌕</a><button class="action" title="Edit" type="button" onclick='openEditModal(<?= json_encode(['assessment_id'=>(int)$a['assessment_id'],'title'=>$a['title'],'module_id'=>(int)$a['module_id'],'date'=>$a['date'],'total_marks'=>(int)$a['total_marks'],'description'=>$a['description']], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>)'>✎</button><form method="post" onsubmit="return confirm('Delete this assessment? Assessments with student results cannot be deleted.');"><input type="hidden" name="csrf_token" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="assessment_id" value="<?= (int)$a['assessment_id'] ?>"><button class="action" title="Delete" type="submit">×</button></form></div></td></tr><?php endforeach; ?>
<?php if (!$assessments): ?><tr><td colspan="6"><div class="empty">No assessment records found.</div></td></tr><?php endif; ?>
</tbody></table></div></div>
</section></main>
<div class="modal" id="assessmentModal"><div class="modal-box"><div class="modal-head"><h2 id="modalTitle">Add Assessment</h2><button class="close" type="button" onclick="closeModal()">✕</button></div><form method="post" id="assessmentForm"><input type="hidden" name="csrf_token" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="assessment_id" id="assessmentId" value="0"><div class="form-grid"><div class="field full"><label for="title">Assessment Title *</label><input id="title" name="title" type="text" maxlength="200" placeholder="Enter assessment title" required></div><div class="field full"><label for="moduleId">Module *</label><select id="moduleId" name="module_id" required><option value="">Select module</option><?php foreach ($modules as $m): ?><option value="<?= (int)$m['module_id'] ?>"><?= h($m['module_code'].' — '.$m['module_name'].($m['module_category'] ? ' ('.$m['module_category'].')' : '')) ?></option><?php endforeach; ?></select></div><div class="field"><label for="assessmentDate">Assessment Date *</label><input id="assessmentDate" name="date" type="date" required></div><div class="field"><label for="totalMarks">Total Marks *</label><input id="totalMarks" name="total_marks" type="number" min="1" step="1" placeholder="e.g. 100" required></div><div class="field full"><label for="description">Description</label><textarea id="description" name="description" placeholder="Enter assessment description"></textarea></div></div><div class="modal-actions"><button type="button" class="secondary" onclick="closeModal()">Cancel</button><button type="submit" class="primary">Save Assessment</button></div></form></div></div>
<?php if (!empty($viewAssessment)): ?>
<div class="modal show" id="resultsModal" onclick="if(event.target===this)window.location.href='assessments.php'"><div class="modal-box" style="max-width:760px"><div class="modal-head"><div><h2><?= h($viewAssessment['title']) ?></h2><span class="subtext"><?= h($viewAssessment['module_code'].' · '.$viewAssessment['module_name']) ?></span></div><a class="close" href="assessments.php" style="display:grid;place-items:center">✕</a></div><div class="result-summary"><div><strong><?= (int)$viewAssessment['total_marks'] ?></strong><span>Total Marks</span></div><div><strong><?= count($viewResults) ?></strong><span>Student Results</span></div><div><strong><?= count(array_filter($viewResults,fn($r)=>$r['status']==='Completed')) ?></strong><span>Completed</span></div></div><div class="table-wrap"><table class="result-table"><thead><tr><th>STUDENT</th><th>SCORE</th><th>PERCENTAGE</th><th>STATUS</th><th>COMPLETED DATE</th></tr></thead><tbody><?php foreach ($viewResults as $r): $statusClass=$r['status']==='Completed'?'completed':($r['status']==='In Progress'?'progress':'not-started'); ?><tr><td><strong><?= h(titleCaseName($r['student_name'])) ?></strong><span class="subtext">Student ID: <?= (int)$r['student_id'] ?></span></td><td><?= $r['score'] !== null ? h(rtrim(rtrim(number_format((float)$r['score'],2,'.',''),'0'),'.')).' / '.(int)$viewAssessment['total_marks'] : '—' ?></td><td><?= $r['percentage'] !== null ? h(number_format((float)$r['percentage'],1)).'%' : '—' ?></td><td><span class="status <?= $statusClass ?>"><?= h($r['status']) ?></span></td><td><?= $r['completed_date'] ? h(date('d M Y',strtotime($r['completed_date']))) : '—' ?></td></tr><?php endforeach; ?><?php if (!$viewResults): ?><tr><td colspan="5"><div class="empty">No student results have been recorded for this assessment yet.</div></td></tr><?php endif; ?></tbody></table></div></div></div>
<?php endif; ?>
<script>
const modal=document.getElementById('assessmentModal');
function openAddModal(){document.getElementById('assessmentForm').reset();document.getElementById('assessmentId').value='0';document.getElementById('modalTitle').textContent='Add Assessment';modal.classList.add('show');}
function openEditModal(a){document.getElementById('assessmentId').value=a.assessment_id;document.getElementById('title').value=a.title;document.getElementById('moduleId').value=a.module_id;document.getElementById('assessmentDate').value=a.date||'';document.getElementById('totalMarks').value=a.total_marks;document.getElementById('description').value=a.description||'';document.getElementById('modalTitle').textContent='Edit Assessment';modal.classList.add('show');}
function closeModal(){modal.classList.remove('show');}
modal.addEventListener('click',e=>{if(e.target===modal)closeModal();});
document.getElementById('mobileMenu')?.addEventListener('click',()=>{document.getElementById('sidebar').classList.toggle('open');document.getElementById('backdrop').classList.toggle('show');});
document.getElementById('backdrop')?.addEventListener('click',()=>{document.getElementById('sidebar').classList.remove('open');document.getElementById('backdrop').classList.remove('show');});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal();if(document.getElementById('resultsModal'))window.location.href='assessments.php';}});
</script></body></html>
