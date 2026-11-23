<?php
require_once __DIR__ . '/db.php';

function h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$message = '';
$messageType = 'success';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'assign') {
            $classId = (int)($_POST['class_id'] ?? 0);
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $materialId = (int)($_POST['material_id'] ?? 0);

            if ($classId < 1 || $sessionId < 1 || $materialId < 1) {
                throw new RuntimeException('Please select a class, class session and learning material.');
            }

            $checkSession = $pdo->prepare("
                SELECT COUNT(*)
                FROM class_session
                WHERE session_id = ? AND class_id = ?
            ");
            $checkSession->execute([$sessionId, $classId]);

            if (!(int)$checkSession->fetchColumn()) {
                throw new RuntimeException('The selected session does not belong to the selected class.');
            }

            $checkMaterial = $pdo->prepare("
                SELECT COUNT(*) FROM learning_material WHERE material_id = ?
            ");
            $checkMaterial->execute([$materialId]);

            if (!(int)$checkMaterial->fetchColumn()) {
                throw new RuntimeException('The selected learning material could not be found.');
            }

            $duplicate = $pdo->prepare("
                SELECT COUNT(*) FROM session_material
                WHERE session_id = ? AND material_id = ?
            ");
            $duplicate->execute([$sessionId, $materialId]);

            if ((int)$duplicate->fetchColumn() > 0) {
                throw new RuntimeException('This material is already assigned to that class session.');
            }

            $insert = $pdo->prepare("
                INSERT INTO session_material (session_id, material_id)
                VALUES (?, ?)
            ");
            $insert->execute([$sessionId, $materialId]);

            $message = 'Learning material assigned successfully.';
        } elseif ($action === 'remove') {
            $materialId = (int)($_POST['material_id'] ?? 0);
            $sessionId = (int)($_POST['session_id'] ?? 0);

            $delete = $pdo->prepare("
                DELETE FROM session_material
                WHERE session_id = ? AND material_id = ?
            ");
            $delete->execute([$sessionId, $materialId]);

            $message = $delete->rowCount()
                ? 'Assignment removed. The learning material remains in the library.'
                : 'Assignment was not found.';
            $messageType = $delete->rowCount() ? 'success' : 'error';
        }
    }
} catch (Throwable $e) {
    $message = $e->getMessage();
    $messageType = 'error';
}

$classes = [];
$materials = [];
$sessions = [];
$assignments = [];

try {
    $classes = $pdo->query("
        SELECT class_id, class_code, class_name, class_type, class_category
        FROM classes
        ORDER BY class_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $materials = $pdo->query("
        SELECT material_id, material_title, file_url,
               stage_name, stage_level, curriculum_module
        FROM learning_material
        ORDER BY curriculum_module, stage_level, material_title
    ")->fetchAll(PDO::FETCH_ASSOC);

    $sessions = $pdo->query("
        SELECT cs.session_id, cs.class_id, cs.session_date, cs.topic, cs.status,
               c.class_code, c.class_name
        FROM class_session cs
        INNER JOIN classes c ON c.class_id = cs.class_id
        ORDER BY cs.session_date DESC, cs.session_id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $assignments = $pdo->query("
        SELECT sm.session_id, sm.material_id,
               lm.material_title, lm.file_url,
               lm.stage_name, lm.stage_level, lm.curriculum_module,
               cs.session_date, cs.topic, cs.status AS session_status,
               c.class_code, c.class_name
        FROM session_material sm
        INNER JOIN learning_material lm ON lm.material_id = sm.material_id
        INNER JOIN class_session cs ON cs.session_id = sm.session_id
        INNER JOIN classes c ON c.class_id = cs.class_id
        ORDER BY cs.session_date DESC, c.class_name, lm.material_title
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $message = 'Unable to load assignment data. Please check the classes, class_session, learning_material and session_material tables.';
    $messageType = 'error';
}

$totalAssignments = count($assignments);
$totalSessions = count(array_unique(array_column($assignments, 'session_id')));
$totalMaterials = count(array_unique(array_column($assignments, 'material_id')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Learning Materials | JAZRO Admin Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root{--blue:#2878f5;--blue-dark:#1758dc;--navy:#163664;--muted:#7c8eaa;--line:#e4edfa;--bg:#f5f9ff;--green:#159b69}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--navy);font-family:Inter,Arial,sans-serif;font-size:13px}
button,input,select{font:inherit}button{cursor:pointer}a{text-decoration:none}
.admin-sidebar{position:fixed;inset:0 auto 0 0;z-index:50;display:flex;flex-direction:column;width:245px;padding:24px 15px 17px;border-right:1px solid var(--line);background:#fff}
.admin-brand{display:flex;align-items:center;gap:11px;min-height:72px;padding:0 7px 20px;border-bottom:1px solid var(--line)}
.admin-brand img{width:59px;height:59px;object-fit:contain;flex-shrink:0}.admin-brand span{color:var(--blue);font-size:10px;font-weight:800;letter-spacing:1.6px;line-height:1.7}
.nav-caption{margin:26px 10px 12px;color:#91a2bb;font-size:9px;font-weight:800;letter-spacing:1.6px}
.admin-nav{display:grid;gap:6px}.admin-nav a,.logout-link{display:flex;align-items:center;gap:12px;min-height:43px;padding:11px 13px;border-radius:10px;color:#60738f;font-size:12px;font-weight:600}
.admin-nav a:hover,.logout-link:hover{background:#f0f6ff;color:var(--blue)}.admin-nav a.active{background:#e8f2ff;color:var(--blue);box-shadow:inset 3px 0 var(--blue)}
.nav-icon{display:grid;width:23px;height:23px;place-items:center;border-radius:7px;background:#f0f6ff;font-size:14px}
.sidebar-bottom{margin-top:auto;padding-top:15px;border-top:1px solid var(--line)}.sidebar-bottom small{display:block;padding:14px 12px 0;color:#9aaac0;font-size:9px}
.main{margin-left:245px;min-width:0;min-height:100vh;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:40;min-height:72px;padding:12px 30px;background:rgba(255,255,255,.97);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:15px}
.top-title strong{display:block;font-size:15px}.top-title small{display:block;margin-top:4px;color:var(--muted);font-size:10px}
.top-right{display:flex;align-items:center;gap:13px}.avatar{width:39px;height:39px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#55baff,#2870f4);color:#fff;font-weight:800}
.account strong{display:block;font-size:11px}.account small{display:block;margin-top:4px;color:var(--muted);font-size:10px}
.content{width:100%;max-width:1700px;margin:0 auto;padding:24px 30px}
.intro{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;flex-wrap:wrap;margin-bottom:19px}
.eyebrow{margin:0 0 9px;color:var(--blue);font-size:9px;font-weight:800;letter-spacing:1.6px}
.intro h1{margin:0;font-size:27px;letter-spacing:-.8px}.intro p{margin:8px 0 0;color:var(--muted);font-size:12px;line-height:1.7}
.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:20px}
.stat-card{padding:18px;border:1px solid var(--line);border-radius:14px;background:#fff}
.stat-top{display:flex;align-items:center;justify-content:space-between;color:var(--muted);font-size:10px;font-weight:700}
.stat-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:11px;background:#eaf2ff;font-size:18px}
.stat-number{display:block;margin-top:12px;font-size:27px;font-weight:800;letter-spacing:-1px}.stat-note{margin-top:4px;color:var(--muted);font-size:10px}
.panel{margin-bottom:18px;padding:20px;border:1px solid var(--line);border-radius:14px;background:#fff}
.panel-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:16px}
.panel-head h2{margin:0;font-size:15px}.panel-head p{margin:5px 0 0;color:var(--muted);font-size:10px;line-height:1.6}
.form-grid{display:grid;grid-template-columns:1.2fr 1.5fr auto;gap:12px;align-items:end}
.field label{display:block;margin-bottom:7px;color:#425976;font-size:10px;font-weight:700}
.control{width:100%;height:43px;padding:0 12px;border:1px solid var(--line);border-radius:9px;background:#fff;color:#425976;font-size:11px;outline:none}
.control:focus{border-color:#8db6ff;box-shadow:0 0 0 3px #2878f510}
.primary{height:43px;padding:0 17px;border:0;border-radius:9px;background:var(--blue);color:#fff;font-size:11px;font-weight:700;box-shadow:0 5px 12px #2878f526}
.primary:hover{background:var(--blue-dark)}
.notice{margin-bottom:16px;padding:12px 15px;border-radius:10px;font-size:11px}.notice.success{background:#e5f8ef;color:#13885a}.notice.error{background:#fff0f1;color:#c74655}
.hint{margin-top:13px;padding:11px 13px;border-radius:9px;background:#f5f8ff;color:#71839d;font-size:10px;line-height:1.7}
.toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:13px}
.toolbar input,.toolbar select{height:40px;padding:0 12px;border:1px solid var(--line);border-radius:9px;background:#fff;color:#60738f;font-size:11px}
.toolbar input{flex:1;min-width:220px}
.table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:12px;background:#fff}
table{width:100%;min-width:900px;border-collapse:collapse}th,td{padding:13px 14px;text-align:left;border-bottom:1px solid #edf2fa}
th{background:#f8fbff;color:#71839d;font-size:9px;text-transform:uppercase;letter-spacing:.7px}
td{color:#425976;font-size:11px}tbody tr:last-child td{border-bottom:0}
.main-name{font-weight:700;color:var(--navy)}.sub{display:block;margin-top:4px;color:var(--muted);font-size:10px}
.tag{display:inline-block;padding:5px 8px;border-radius:20px;background:#eaf2ff;color:#2871e4;font-size:9px;font-weight:800}
.tag.completed{background:#e5f8ef;color:#13885a}.tag.cancelled{background:#fff0f1;color:#c74655}
.remove{padding:7px 10px;border:1px solid #ffe0e3;border-radius:8px;background:#fff7f7;color:#c43d4b;font-size:10px;font-weight:700}
.empty{padding:28px;text-align:center;color:var(--muted);font-size:11px}
@media(max-width:950px){.form-grid{grid-template-columns:1fr 1fr}.form-grid .primary{grid-column:1/-1}}
@media(max-width:760px){.admin-sidebar{position:relative;width:100%;height:auto;padding:12px}.admin-brand{min-height:48px;padding-bottom:10px}.admin-brand img{width:40px;height:40px}.nav-caption,.sidebar-bottom{display:none}.admin-nav{grid-template-columns:repeat(2,1fr)}.main{margin-left:0}.topbar{position:static;padding:12px 15px}.account{display:none}.content{padding:20px 15px}.stats{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.form-grid .primary{grid-column:auto}}

/* Shared Learning Activities sidebar layout */
.admin-sidebar{overflow:hidden}
.admin-nav{display:grid;gap:6px;flex:1 1 auto;min-height:0;overflow-y:auto;overflow-x:hidden;align-content:start;padding:0 2px 15px;scrollbar-width:thin;scrollbar-color:#dce8f8 transparent}
.admin-nav::-webkit-scrollbar{width:5px}
.admin-nav::-webkit-scrollbar-thumb{background:#dce8f8;border-radius:10px}
.nav-group{display:grid;gap:4px}
.nav-group-title{display:flex;align-items:center;gap:12px;min-height:43px;padding:11px 13px;border-radius:10px;color:#60738f;font-size:12px;font-weight:600}
.nav-group-title:hover{background:#f0f6ff;color:var(--blue)}
.nav-group-title.active{background:#e8f2ff;color:var(--blue);box-shadow:inset 3px 0 var(--blue)}
.nav-chevron{margin-left:auto;transition:transform .2s}
.nav-group.open .nav-chevron{transform:rotate(180deg)}
.nav-submenu{display:grid;gap:3px;margin:0 0 5px 35px;padding-left:12px;border-left:1px solid #dce8f8}
.nav-submenu a{display:block;padding:9px 10px;border-radius:8px;color:#71839d;font-size:10px;font-weight:600}
.nav-submenu a:hover,.nav-submenu a.sub-active{background:#f0f6ff;color:var(--blue)}
@media(max-width:760px){.admin-sidebar{position:fixed;width:245px;height:100dvh;transform:translateX(-105%);transition:transform .25s;z-index:100}.admin-sidebar.open{transform:translateX(0)}}

/* ===== MODERN JAZRO ASSIGNMENT PAGE ===== */
.content{max-width:1750px;padding:30px 34px}
.intro{position:relative;overflow:hidden;min-height:178px;padding:30px 36px;margin-bottom:25px;border:0;border-radius:20px;background:linear-gradient(118deg,#173d78 0%,#286de0 62%,#789cf4 100%);align-items:center;box-shadow:0 12px 30px rgba(35,91,180,.15)}
.intro>div:first-child{position:relative;z-index:2}
.intro::after{display:none}
.intro .eyebrow{color:#c9dcff;letter-spacing:2px;margin-bottom:12px}
.intro h1{color:#fff;font-size:30px;letter-spacing:-.8px}
.intro p{color:#e0ebff;max-width:620px;line-height:1.8}
.hero-mark{position:absolute;right:55px;top:50%;transform:translateY(-50%) rotate(-10deg);display:grid;place-items:center;width:126px;height:126px;border:1px solid rgba(255,255,255,.2);border-radius:32px;background:rgba(255,255,255,.12);font-size:68px;box-shadow:0 15px 35px rgba(13,42,91,.12)}
.stats{gap:18px;margin-bottom:25px}
.stat-card{position:relative;overflow:hidden;padding:23px;border:1px solid #e8eef9;border-radius:17px;background:#fff;box-shadow:0 5px 18px rgba(35,70,120,.045);transition:transform .2s ease,box-shadow .2s ease}
.stat-card:hover{transform:translateY(-4px);box-shadow:0 12px 25px rgba(35,70,120,.09)}
.stat-top{color:#7183a1;font-size:10px;letter-spacing:.7px}
.stat-icon{width:44px;height:44px;border-radius:13px;font-size:20px}
.stat-card:nth-child(1) .stat-icon{background:#e9f1ff}
.stat-card:nth-child(2) .stat-icon{background:#eeeaff}
.stat-card:nth-child(3) .stat-icon{background:#e4f8f1}
.stat-number{margin-top:17px;color:#173d78;font-size:32px;font-weight:800}
.stat-note{color:#8493ad;font-size:11px}
.panel{padding:24px;border-radius:17px;box-shadow:0 5px 18px rgba(35,70,120,.035)}
.panel-head{margin-bottom:21px}
.panel-head h2{font-size:16px;letter-spacing:-.2px}
.section-kicker{display:block;margin-bottom:8px;color:#3979df;font-size:9px;font-weight:800;letter-spacing:1.5px}
.steps-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}
.step-field{min-width:0;padding:17px;border:1px solid #e7eef9;border-radius:13px;background:linear-gradient(180deg,#fbfdff,#fff)}
.step-heading{display:flex;align-items:center;gap:9px;margin-bottom:17px;color:#24466f;font-size:12px;font-weight:800}
.step-number{display:grid;width:27px;height:27px;place-items:center;border-radius:8px;background:#e9f1ff;color:#2878f5;font-size:10px}
.step-field label{display:block;margin-bottom:8px;color:#526985;font-size:9px;font-weight:800;letter-spacing:.8px}
.control{height:45px;border-color:#dfe8f6;border-radius:9px;color:#344d70}
.control:disabled{background:#f1f4f9;color:#9aa8bc;cursor:not-allowed}
.field-help{display:block;margin-top:9px;color:#8b9ab0;font-size:10px;line-height:1.5}
.form-footer{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-top:19px}
.assignment-hint{display:flex;align-items:center;gap:9px;max-width:690px;padding:12px 14px;border-radius:10px;background:#f2f6ff;color:#7083a2;font-size:10px;line-height:1.6}
.hint-icon{flex-shrink:0;color:#3479e7;font-size:17px}
.assign-button{display:flex;align-items:center;gap:16px;padding:0 20px;white-space:nowrap}
.assign-button span{font-size:17px}
.toolbar input,.toolbar select{border-color:#e0e9f6;border-radius:10px}
.table-wrap{border-color:#e5edf8;border-radius:12px}
th{background:#f6f9ff;color:#7588a5}
.tag{padding:6px 10px}
.remove{transition:background .2s ease}
.remove:hover{background:#ffe9eb}
@media(max-width:1050px){.steps-grid{grid-template-columns:1fr 1fr}.step-field:last-child{grid-column:1/-1}}
@media(max-width:760px){.content{padding:20px 15px}.intro{min-height:160px;padding:25px}.intro h1{font-size:24px}.hero-mark{right:18px;width:75px;height:75px;border-radius:20px;font-size:39px;opacity:.8}.steps-grid{grid-template-columns:1fr}.step-field:last-child{grid-column:auto}.form-footer{align-items:stretch;flex-direction:column}.assign-button{justify-content:center}.panel{padding:17px}}

.mobile-menu{display:none;width:38px;height:38px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--blue)}
.backdrop{display:none}
@media(max-width:760px){.mobile-menu{display:grid;place-items:center}.backdrop{position:fixed;inset:0;z-index:90;background:rgba(20,45,80,.25);opacity:0;pointer-events:none;transition:opacity .2s}.backdrop.show{display:block;opacity:1;pointer-events:auto}}
</style>
</head>
<body>
<aside class="admin-sidebar">
  <a class="admin-brand" href="dashboard.php"><img src="assets/images/jazro-logo.png" alt="JAZRO Robotic Academy"><span>ADMIN PORTAL</span></a>
  <p class="nav-caption">MAIN MENU</p>
  <?php include __DIR__ . '/learning_activities_menu.php'; ?>
  <div class="sidebar-bottom"><a href="index.php" class="logout-link"><span class="nav-icon">↪</span>Log Out</a><small>JAZRO ROBOTIC ACADEMY · 2026</small></div>
</aside>
<div class="backdrop" id="sidebarBackdrop"></div>
<main class="main">
<header class="topbar"><button class="mobile-menu" id="mobileMenu" type="button" aria-label="Open menu">☰</button><div class="top-title"><strong>Learning Material Assignment</strong><small>Link existing resources to individual class sessions</small></div><div class="top-right"><div class="avatar">A</div><div class="account"><strong>Admin</strong><small>Administrator</small></div></div></header>
<section class="content">
  <div class="intro">
    <div>
      <div class="eyebrow">ACADEMIC MANAGEMENT</div>
      <h1>Assign Learning Materials</h1>
      <p>Organise learning resources by class and session. Make sure students can easily access the right materials for their learning journey.</p>
    </div>
    <div class="hero-mark" aria-hidden="true">📚</div>
  </div>
  <?php if ($message !== ''): ?><div class="notice <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= h($message) ?></div><?php endif; ?>

  <div class="stats">
    <article class="stat-card"><div class="stat-top"><span>TOTAL ASSIGNMENTS</span><span class="stat-icon">🔗</span></div><strong class="stat-number"><?= $totalAssignments ?></strong><div class="stat-note">Material-to-session links</div></article>
    <article class="stat-card"><div class="stat-top"><span>SESSIONS WITH MATERIALS</span><span class="stat-icon">📅</span></div><strong class="stat-number"><?= $totalSessions ?></strong><div class="stat-note">Class sessions with resources</div></article>
    <article class="stat-card"><div class="stat-top"><span>MATERIALS IN USE</span><span class="stat-icon">📚</span></div><strong class="stat-number"><?= $totalMaterials ?></strong><div class="stat-note">Unique library materials assigned</div></article>
  </div>

  <section class="panel assignment-panel">
    <div class="panel-head">
      <div>
        <span class="section-kicker">MATERIAL DISTRIBUTION</span>
        <h2>＋ Assign Existing Material</h2>
        <p>Follow the steps below to link a learning resource to a specific class session.</p>
      </div>
    </div>

    <?php if (!$materials || !$classes || !$sessions): ?>
      <div class="empty">
        <?= !$materials ? 'There are no learning materials in the library yet.' : ((!$classes) ? 'There are no classes available.' : 'There are no class sessions available.') ?>
      </div>
    <?php else: ?>
    <form method="post" id="assignmentForm">
      <input type="hidden" name="action" value="assign">

      <div class="steps-grid">
        <div class="step-field">
          <div class="step-heading"><span class="step-number">01</span><span>Choose Class</span></div>
          <label for="classSelect">CLASS</label>
          <select class="control" id="classSelect" name="class_id" required>
            <option value="">Select a class</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= (int)$c['class_id'] ?>">
                <?= h($c['class_code']) ?> — <?= h($c['class_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="field-help">Select the class that will receive the material.</small>
        </div>

        <div class="step-field">
          <div class="step-heading"><span class="step-number">02</span><span>Choose Session</span></div>
          <label for="sessionSelect">CLASS SESSION</label>
          <select class="control" id="sessionSelect" name="session_id" required disabled>
            <option value="">Select a class first</option>
            <?php foreach ($sessions as $s): ?>
              <option value="<?= (int)$s['session_id'] ?>" data-class-id="<?= (int)$s['class_id'] ?>">
                <?= h(date('d M Y', strtotime($s['session_date']))) ?> · <?= h($s['topic'] ?: 'No topic') ?> (<?= h($s['status']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <small class="field-help">Only sessions belonging to the selected class will appear.</small>
        </div>

        <div class="step-field">
          <div class="step-heading"><span class="step-number">03</span><span>Choose Material</span></div>
          <label for="materialSelect">LEARNING MATERIAL</label>
          <select class="control" id="materialSelect" name="material_id" required>
            <option value="">Select a learning material</option>
            <?php foreach ($materials as $m): ?>
              <option value="<?= (int)$m['material_id'] ?>">
                <?= h($m['material_title']) ?> — <?= h($m['curriculum_module'] ?: 'General') ?> · <?= h($m['stage_level'] ?: 'No level') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="field-help">Choose an existing file from the learning materials library.</small>
        </div>
      </div>

      <div class="form-footer">
        <div class="assignment-hint"><span class="hint-icon">ⓘ</span><span>The original learning material stays in the library. This action only links it to the selected class session.</span></div>
        <button class="primary assign-button" type="submit">Assign Material <span>→</span></button>
      </div>
    </form>
    <?php endif; ?>
  </section>

  <section class="panel">
    <div class="panel-head">
      <div>
        <span class="section-kicker">RESOURCE OVERVIEW</span>
        <h2>Assigned Materials</h2>
        <p>Review and manage resources already linked to class sessions.</p>
      </div>
    </div>
    <div class="toolbar"><input id="assignmentSearch" type="search" placeholder="Search material, curriculum, class or topic..."><select id="statusFilter"><option value="">All session statuses</option><option value="scheduled">Scheduled</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
    <div class="table-wrap"><table><thead><tr><th>Learning Material</th><th>Curriculum / Level</th><th>Class Session</th><th>Session Date</th><th>Status</th><th>Action</th></tr></thead><tbody id="assignmentRows">
    <?php if (!$assignments): ?><tr><td colspan="6" class="empty">No materials have been assigned to a class session yet.</td></tr><?php endif; ?>
    <?php foreach ($assignments as $a): $status = strtolower((string)$a['session_status']); ?>
      <tr data-status="<?= h($status) ?>">
        <td><span class="main-name"><?= h($a['material_title']) ?></span><span class="sub"><?= h($a['file_url']) ?></span></td>
        <td><span class="main-name"><?= h($a['curriculum_module'] ?: 'General') ?></span><span class="sub"><?= h($a['stage_name'] ?: '—') ?><?= !empty($a['stage_level']) ? ' · ' . h($a['stage_level']) : '' ?></span></td>
        <td><span class="main-name"><?= h($a['class_name']) ?></span><span class="sub"><?= h($a['class_code']) ?> · <?= h($a['topic'] ?: 'No topic') ?></span></td>
        <td><?= h(date('d M Y', strtotime($a['session_date']))) ?></td>
        <td><span class="tag <?= h($status) ?>"><?= h($a['session_status']) ?></span></td>
        <td><form method="post" onsubmit="return confirm('Remove this material from the class session? The library material will remain.');"><input type="hidden" name="action" value="remove"><input type="hidden" name="material_id" value="<?= (int)$a['material_id'] ?>"><input type="hidden" name="session_id" value="<?= (int)$a['session_id'] ?>"><button class="remove" type="submit">Remove</button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  </section>
</section>
</main>
<script>
const classSelect = document.getElementById('classSelect');
const sessionSelect = document.getElementById('sessionSelect');

if (classSelect && sessionSelect) {
  const sessionOptions = Array.from(sessionSelect.querySelectorAll('option[data-class-id]'));

  classSelect.addEventListener('change', function () {
    const selectedClass = this.value;
    sessionSelect.value = '';
    sessionSelect.innerHTML = '<option value="">Select a class session</option>';

    sessionOptions.forEach(option => {
      if (option.dataset.classId === selectedClass) {
        sessionSelect.appendChild(option.cloneNode(true));
      }
    });

    sessionSelect.disabled = !selectedClass || sessionSelect.options.length === 1;

    if (selectedClass && sessionSelect.options.length === 1) {
      sessionSelect.innerHTML = '<option value="">No sessions available for this class</option>';
    }
  });
}

const sidebar=document.querySelector('.admin-sidebar');
const backdrop=document.getElementById('sidebarBackdrop');
document.getElementById('mobileMenu')?.addEventListener('click',()=>{sidebar.classList.toggle('open');backdrop.classList.toggle('show')});
backdrop?.addEventListener('click',()=>{sidebar.classList.remove('open');backdrop.classList.remove('show')});
const searchInput=document.getElementById('assignmentSearch');
const statusFilter=document.getElementById('statusFilter');
function filterAssignments(){
  const q=(searchInput.value||'').toLowerCase();
  const status=statusFilter.value;
  document.querySelectorAll('#assignmentRows tr[data-status]').forEach(row=>{
    row.style.display=row.innerText.toLowerCase().includes(q)&&(!status||row.dataset.status===status)?'':'none';
  });
}
searchInput.addEventListener('input',filterAssignments);
statusFilter.addEventListener('change',filterAssignments);
</script>
</body>
</html>
