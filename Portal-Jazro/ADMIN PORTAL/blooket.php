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

        if ($action === 'save_blooket') {
            $id = (int)($_POST['blooket_id'] ?? 0);
            $name = trim($_POST['blooket_name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $level = trim($_POST['level'] ?? '');
            $url = trim($_POST['blooket_url'] ?? '');
            if ($name === '') throw new RuntimeException('Please enter the Blooket game name.');
            if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) throw new RuntimeException('Please enter a valid URL starting with https://.');

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE blooket SET blooket_name=?, category=?, level=?, blooket_url=? WHERE blooket_id=?');
                $stmt->execute([$name, $category ?: null, $level ?: null, $url ?: null, $id]);
                $message = 'Blooket game updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO blooket (blooket_name, category, level, blooket_url) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $category ?: null, $level ?: null, $url ?: null]);
                $message = 'Blooket game added successfully.';
            }
        } elseif ($action === 'delete_blooket') {
            $id = (int)($_POST['blooket_id'] ?? 0);
            $check = $pdo->prepare('SELECT COUNT(*) FROM student_blooket WHERE blooket_id=?');
            $check->execute([$id]);
            if ((int)$check->fetchColumn() > 0) throw new RuntimeException('This game is assigned to students. Remove its student assignments before deleting it.');
            $stmt = $pdo->prepare('DELETE FROM blooket WHERE blooket_id=?');
            $stmt->execute([$id]);
            $message = $stmt->rowCount() ? 'Blooket game deleted.' : 'Blooket game was not found.';
        } elseif ($action === 'assign_blooket') {
            $studentId = (int)($_POST['student_id'] ?? 0);
            $blooketId = (int)($_POST['blooket_id'] ?? 0);
            if ($studentId < 1 || $blooketId < 1) throw new RuntimeException('Please select a student and a Blooket game.');
            $check = $pdo->prepare('SELECT COUNT(*) FROM student WHERE student_id=?');
            $check->execute([$studentId]);
            if (!(int)$check->fetchColumn()) throw new RuntimeException('Student not found.');
            $check = $pdo->prepare('SELECT COUNT(*) FROM blooket WHERE blooket_id=?');
            $check->execute([$blooketId]);
            if (!(int)$check->fetchColumn()) throw new RuntimeException('Blooket game not found.');
            $check = $pdo->prepare('SELECT COUNT(*) FROM student_blooket WHERE student_id=? AND blooket_id=?');
            $check->execute([$studentId, $blooketId]);
            if ((int)$check->fetchColumn()) throw new RuntimeException('This game is already assigned to this student.');
            $stmt = $pdo->prepare("INSERT INTO student_blooket (student_id, blooket_id, status, date_played) VALUES (?, ?, 'Not Started', NULL)");
            $stmt->execute([$studentId, $blooketId]);
            $message = 'Blooket assigned to student successfully.';
        } elseif ($action === 'update_progress') {
            $assignmentId = (int)($_POST['stu_blooket_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $datePlayed = trim($_POST['date_played'] ?? '');
            if (!in_array($status, ['Not Started', 'In Progress', 'Completed'], true)) throw new RuntimeException('Invalid progress status.');
            if ($datePlayed !== '') {
                $date = DateTime::createFromFormat('Y-m-d', $datePlayed);
                if (!$date || $date->format('Y-m-d') !== $datePlayed) throw new RuntimeException('Invalid date played.');
            }
            if ($status === 'Completed' && $datePlayed === '') $datePlayed = date('Y-m-d');
            if ($status !== 'Completed') $datePlayed = '';
            $stmt = $pdo->prepare('UPDATE student_blooket SET status=?, date_played=? WHERE stu_blooket_id=?');
            $stmt->execute([$status, $datePlayed ?: null, $assignmentId]);
            $message = 'Student Blooket progress updated.';
        }
    }
} catch (Throwable $e) {
    $message = $e->getMessage();
    $messageType = 'error';
}

$blookets = $students = $assignments = [];
try {
    $blookets = $pdo->query('SELECT blooket_id, blooket_name, category, level, blooket_url FROM blooket ORDER BY blooket_name')->fetchAll(PDO::FETCH_ASSOC);
    $students = $pdo->query('SELECT student_id, student_name FROM student ORDER BY student_name')->fetchAll(PDO::FETCH_ASSOC);
    $assignments = $pdo->query("SELECT sb.stu_blooket_id, sb.status, sb.date_played, s.student_name, b.blooket_name, b.category, b.level, b.blooket_url FROM student_blooket sb INNER JOIN student s ON s.student_id=sb.student_id INNER JOIN blooket b ON b.blooket_id=sb.blooket_id ORDER BY sb.stu_blooket_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $message = 'Unable to load Blooket data. Please check the blooket, student and student_blooket tables.';
    $messageType = 'error';
}

$editItem = null;
if (isset($_GET['edit'])) {
    foreach ($blookets as $game) if ((int)$game['blooket_id'] === (int)$_GET['edit']) $editItem = $game;
}
$totalGames = count($blookets);
$totalAssigned = count($assignments);
$totalCompleted = count(array_filter($assignments, fn($a) => $a['status'] === 'Completed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blooket | JAZRO Admin Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root{--blue:#2878f5;--navy:#163664;--muted:#7c8eaa;--line:#e4edfa;--bg:#f5f9ff}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--navy);font:13px Inter,Arial,sans-serif}button,input,select{font:inherit}a{text-decoration:none}
.admin-sidebar{position:fixed;inset:0 auto 0 0;z-index:5;display:flex;flex-direction:column;width:245px;padding:24px 15px 17px;border-right:1px solid var(--line);background:#fff}.admin-brand{display:flex;align-items:center;gap:11px;min-height:72px;padding:0 7px 20px;border-bottom:1px solid var(--line)}.admin-brand img{width:59px;height:59px;object-fit:contain}.admin-brand span{color:var(--blue);font-size:10px;font-weight:800;letter-spacing:1.6px}.nav-caption{margin:26px 10px 12px;color:#91a2bb;font-size:9px;font-weight:800;letter-spacing:1.6px}.admin-nav{display:grid;gap:6px;flex:1 1 auto;min-height:0;overflow-y:auto;overflow-x:hidden;align-content:start;padding-bottom:15px}.admin-nav a{display:flex;align-items:center;gap:12px;min-height:43px;padding:11px 13px;border-radius:10px;color:#60738f;font-size:12px;font-weight:600}.admin-nav a.active,.admin-nav a:hover{background:#e8f2ff;color:var(--blue)}.nav-icon{display:grid;width:23px;height:23px;place-items:center;border-radius:7px;background:#f0f6ff}.sidebar-bottom{margin-top:auto;padding-top:15px;border-top:1px solid var(--line)}.sidebar-bottom small{display:block;padding:14px 12px 0;color:#9aaac0;font-size:9px}
.main{margin-left:245px;min-height:100vh}.topbar{position:sticky;top:0;z-index:4;min-height:72px;padding:12px 30px;background:#ffffffed;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between}.top-title strong{display:block;font-size:15px}.top-title small{display:block;margin-top:4px;color:var(--muted);font-size:10px}.avatar{width:39px;height:39px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#55baff,#2870f4);color:#fff;font-weight:800}.account{display:flex;align-items:center;gap:10px}.account small{display:block;color:var(--muted);font-size:10px}.content{max-width:1700px;margin:auto;padding:26px 30px}.intro{display:flex;align-items:center;justify-content:space-between;min-height:155px;margin-bottom:20px;padding:27px 32px;border-radius:19px;background:linear-gradient(115deg,#173d78,#286de0 65%,#849ff2);color:white;box-shadow:0 12px 28px #235bb426}.eyebrow{margin-bottom:9px;color:#c9dcff;font-size:9px;font-weight:800;letter-spacing:1.7px}.intro h1{margin:0;font-size:27px}.intro p{max-width:600px;color:#e1ebff;font-size:12px;line-height:1.7}.hero-icon{margin-right:30px;font-size:70px}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px}.stat-card,.panel{border:1px solid var(--line);border-radius:14px;background:#fff}.stat-card{padding:18px}.stat-top{display:flex;justify-content:space-between;color:var(--muted);font-size:10px;font-weight:700}.stat-icon{width:39px;height:39px;display:grid;place-items:center;border-radius:11px;background:#eaf2ff;font-size:18px}.stat-number{display:block;margin-top:12px;font-size:27px;font-weight:800}.stat-note{margin-top:4px;color:var(--muted);font-size:10px}.panel{margin-bottom:18px;padding:21px}.panel-head{display:flex;justify-content:space-between;gap:12px;margin-bottom:17px}.panel-head h2{margin:0;font-size:15px}.panel-head p{margin:5px 0 0;color:var(--muted);font-size:10px}.kicker{display:block;margin-bottom:7px;color:var(--blue);font-size:9px;font-weight:800;letter-spacing:1.3px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.field{display:flex;flex-direction:column;gap:7px}.field label{font-size:10px;font-weight:800;color:#425976}.control{width:100%;min-height:42px;padding:10px 12px;border:1px solid var(--line);border-radius:9px;background:#fff;color:#425976;font-size:11px;outline:none}.control:focus,.search:focus{border-color:#8db6ff;box-shadow:0 0 0 3px #2878f510}.hint{color:var(--muted);font-size:9px}.actions{display:flex;justify-content:flex-end;gap:9px;margin-top:16px}.primary{min-height:40px;padding:0 17px;border:0;border-radius:9px;background:var(--blue);color:white;font-size:11px;font-weight:700}.secondary,.action-link{display:inline-flex;align-items:center;justify-content:center;min-height:30px;padding:0 10px;border:1px solid var(--line);border-radius:8px;background:white;color:#60738f;font-size:10px;font-weight:700}.danger{color:#c74655;border-color:#ffe0e3}.notice{margin-bottom:16px;padding:12px 15px;border-radius:10px;font-size:11px}.success{background:#e5f8ef;color:#13885a}.error{background:#fff0f1;color:#c74655}.toolbar{display:flex;gap:10px;margin-bottom:13px}.search{flex:1;height:41px;padding:0 13px;border:1px solid var(--line);border-radius:9px;font-size:11px;outline:none}.table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:12px}table{width:100%;min-width:760px;border-collapse:collapse}th,td{padding:13px 14px;text-align:left;border-bottom:1px solid #edf2fa}th{background:#f8fbff;color:#71839d;font-size:9px;text-transform:uppercase;letter-spacing:.7px}td{color:#425976;font-size:11px}tbody tr:last-child td{border-bottom:0}.main-name{font-weight:700;color:var(--navy)}.sub{display:block;margin-top:4px;color:var(--muted);font-size:10px}.pill{display:inline-flex;padding:5px 8px;border-radius:20px;background:#f0f3f8;color:#75849a;font-size:9px;font-weight:800}.pill.completed{background:#e5f8ef;color:#13885a}.pill.progress{background:#fff5e8;color:#c47b1e}.inline-form{display:flex;align-items:center;gap:5px;flex-wrap:wrap}.inline-form select,.inline-form input{min-height:29px;max-width:125px;padding:4px 6px;border:1px solid var(--line);border-radius:7px;font-size:9px}.empty{padding:30px;text-align:center;color:var(--muted);font-size:11px}
@media(max-width:760px){.admin-sidebar{position:relative;width:100%;height:auto;padding:12px}.admin-brand{min-height:48px;padding-bottom:10px}.admin-brand img{width:40px;height:40px}.nav-caption,.sidebar-bottom{display:none}.admin-nav{grid-template-columns:repeat(2,1fr)}.main{margin-left:0}.topbar{position:static;padding:12px 15px}.account{display:none}.content{padding:18px 13px}.stats{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.intro{padding:22px}.intro h1{font-size:23px}.hero-icon{font-size:45px;margin:0}}

/* Keep Log Out horizontal, matching the other sidebar links. */
.admin-sidebar .sidebar-bottom > a {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 12px !important;
    width: 100% !important;
    min-height: 43px !important;
    height: 43px !important;
    padding: 10px 13px !important;
    border: 0 !important;
    border-radius: 10px !important;
    background: transparent !important;
    color: #60738f !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    text-decoration: none !important;
}
.admin-sidebar .sidebar-bottom > a:hover {
    background: #f0f6ff !important;
    color: var(--blue) !important;
}
.admin-sidebar .sidebar-bottom > a .nav-icon {
    display: grid !important;
    flex: 0 0 23px !important;
    width: 23px !important;
    height: 23px !important;
    place-items: center !important;
    border-radius: 7px !important;
    background: #f0f6ff !important;
    font-size: 14px !important;
}

</style>
</head>
<body>
<aside class="admin-sidebar" id="sidebar">
  <a class="admin-brand" href="dashboard.php"><img src="assets/images/jazro-logo.png" alt="JAZRO Robotic Academy"><span>ADMIN PORTAL</span></a>
  <p class="nav-caption">MAIN MENU</p>
  <?php include __DIR__ . '/learning_activities_menu.php'; ?>
  <div class="sidebar-bottom"><a href="index.php" class="logout-link"><span class="nav-icon">↪</span><span>Log Out</span></a><small>JAZRO ROBOTIC ACADEMY · 2026</small></div>
</aside>
<main class="main">
<header class="topbar"><div class="top-title"><strong>Learning Activities</strong><small>Manage learning resources and student activities</small></div><div class="account"><div class="avatar">A</div><div><strong>Admin</strong><small>Administrator</small></div></div></header>
<section class="content">
  <div class="intro"><div><div class="eyebrow">ACADEMIC RESOURCES</div><h1>Blooket Management</h1><p>Manage interactive Blooket games, assign activities to students and monitor their progress in one place.</p></div><div class="hero-icon">🎮</div></div>
  <?php if ($message): ?><div class="notice <?= $messageType ?>"><?= h($message) ?></div><?php endif; ?>
  <div class="stats">
    <article class="stat-card"><div class="stat-top"><span>AVAILABLE BLOOKET GAMES</span><span class="stat-icon">🎮</span></div><strong class="stat-number"><?= $totalGames ?></strong><div class="stat-note">Games saved in the library</div></article>
    <article class="stat-card"><div class="stat-top"><span>STUDENT ASSIGNMENTS</span><span class="stat-icon">👥</span></div><strong class="stat-number"><?= $totalAssigned ?></strong><div class="stat-note">Games assigned to students</div></article>
    <article class="stat-card"><div class="stat-top"><span>COMPLETED ACTIVITIES</span><span class="stat-icon">🏆</span></div><strong class="stat-number"><?= $totalCompleted ?></strong><div class="stat-note">Activities marked completed</div></article>
  </div>
  <section class="panel" id="game-form">
    <div class="panel-head"><div><span class="kicker">BLOOKET LIBRARY</span><h2><?= $editItem ? 'Edit Blooket Game' : '＋ Add Blooket Game' ?></h2><p>Save the game title and link so it can be assigned to students.</p></div></div>
    <form method="post"><input type="hidden" name="action" value="save_blooket"><input type="hidden" name="blooket_id" value="<?= (int)($editItem['blooket_id'] ?? 0) ?>">
      <div class="form-grid">
        <div class="field"><label for="blooket_name">BLOOKET GAME NAME *</label><input class="control" id="blooket_name" name="blooket_name" required maxlength="200" placeholder="e.g. Introduction to Robotics" value="<?= h($editItem['blooket_name'] ?? '') ?>"></div>
        <div class="field"><label for="category">CATEGORY</label><input class="control" id="category" name="category" maxlength="100" placeholder="e.g. Robotics, Coding, AI" value="<?= h($editItem['category'] ?? '') ?>"></div>
        <div class="field"><label for="level">LEVEL</label><input class="control" id="level" name="level" maxlength="50" placeholder="e.g. JTC Level 1" value="<?= h($editItem['level'] ?? '') ?>"></div>
        <div class="field"><label for="blooket_url">BLOOKET URL</label><input class="control" id="blooket_url" name="blooket_url" type="url" placeholder="https://..." value="<?= h($editItem['blooket_url'] ?? '') ?>"><span class="hint">Paste the share or play link for the activity.</span></div>
      </div><div class="actions"><?php if ($editItem): ?><a class="secondary" href="blooket.php">Cancel Edit</a><?php endif; ?><button class="primary" type="submit"><?= $editItem ? 'Save Changes' : 'Add Blooket Game' ?></button></div>
    </form>
  </section>
  <section class="panel">
    <div class="panel-head"><div><span class="kicker">STUDENT ACTIVITY</span><h2>Assign Blooket to Student</h2><p>Select an individual student and a saved Blooket game. Initial progress is Not Started.</p></div></div>
    <?php if (!$students || !$blookets): ?><div class="empty"><?= !$blookets ? 'Add a Blooket game to the library before assigning it.' : 'No students are available.' ?></div><?php else: ?>
    <form method="post"><input type="hidden" name="action" value="assign_blooket"><div class="form-grid">
      <div class="field"><label for="student_id">STUDENT</label><select class="control" id="student_id" name="student_id" required><option value="">Select student</option><?php foreach ($students as $student): ?><option value="<?= (int)$student['student_id'] ?>"><?= h($student['student_name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="blooket_id">BLOOKET GAME</label><select class="control" id="blooket_id" name="blooket_id" required><option value="">Select Blooket game</option><?php foreach ($blookets as $game): ?><option value="<?= (int)$game['blooket_id'] ?>"><?= h($game['blooket_name']) ?><?= $game['level'] ? ' · ' . h($game['level']) : '' ?></option><?php endforeach; ?></select></div>
    </div><div class="actions"><button class="primary" type="submit">Assign to Student →</button></div></form><?php endif; ?>
  </section>
  <section class="panel">
    <div class="panel-head"><div><span class="kicker">GAME LIBRARY</span><h2>Available Blooket Games</h2><p>Edit game details or remove games that are no longer used.</p></div></div>
    <div class="toolbar"><input class="search" id="gameSearch" type="search" placeholder="Search game, category or level..."></div>
    <div class="table-wrap"><table><thead><tr><th>Game Name</th><th>Category</th><th>Level</th><th>Game Link</th><th>Action</th></tr></thead><tbody id="gameRows">
      <?php if (!$blookets): ?><tr><td colspan="5" class="empty">No Blooket games added yet. Use the form above to add one.</td></tr><?php endif; ?>
      <?php foreach ($blookets as $game): ?><tr><td><span class="main-name"><?= h($game['blooket_name']) ?></span></td><td><?= h($game['category'] ?: '—') ?></td><td><?= h($game['level'] ?: '—') ?></td><td><?php if ($game['blooket_url']): ?><a class="action-link" href="<?= h($game['blooket_url']) ?>" target="_blank" rel="noopener">Open game ↗</a><?php else: ?>—<?php endif; ?></td><td><div class="inline-form"><a class="action-link" href="blooket.php?edit=<?= (int)$game['blooket_id'] ?>#game-form">Edit</a><form method="post" onsubmit="return confirm('Delete this Blooket game?')"><input type="hidden" name="action" value="delete_blooket"><input type="hidden" name="blooket_id" value="<?= (int)$game['blooket_id'] ?>"><button class="action-link" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
  <section class="panel">
    <div class="panel-head"><div><span class="kicker">PROGRESS TRACKING</span><h2>Student Blooket Progress</h2><p>Update each student's activity status. Completed activities can have a played date.</p></div></div>
    <div class="toolbar"><input class="search" id="assignmentSearch" type="search" placeholder="Search student or Blooket game..."></div>
    <div class="table-wrap"><table><thead><tr><th>Student</th><th>Blooket Game</th><th>Category / Level</th><th>Progress</th><th>Date Played</th><th>Update</th></tr></thead><tbody id="assignmentRows">
      <?php if (!$assignments): ?><tr><td colspan="6" class="empty">No Blooket activities have been assigned yet.</td></tr><?php endif; ?>
      <?php foreach ($assignments as $item): $statusClass = $item['status'] === 'Completed' ? 'completed' : ($item['status'] === 'In Progress' ? 'progress' : ''); ?>
      <tr><td><span class="main-name"><?= h($item['student_name']) ?></span></td><td><span class="main-name"><?= h($item['blooket_name']) ?></span><?php if ($item['blooket_url']): ?><span class="sub"><a href="<?= h($item['blooket_url']) ?>" target="_blank" rel="noopener">Open Blooket ↗</a></span><?php endif; ?></td><td><?= h($item['category'] ?: '—') ?><span class="sub"><?= h($item['level'] ?: '') ?></span></td><td><span class="pill <?= $statusClass ?>"><?= h($item['status']) ?></span></td><td><?= $item['date_played'] ? h(date('d M Y', strtotime($item['date_played']))) : '—' ?></td><td><form class="inline-form" method="post"><input type="hidden" name="action" value="update_progress"><input type="hidden" name="stu_blooket_id" value="<?= (int)$item['stu_blooket_id'] ?>"><select name="status" aria-label="Progress status"><?php foreach (['Not Started','In Progress','Completed'] as $option): ?><option value="<?= h($option) ?>" <?= $item['status'] === $option ? 'selected' : '' ?>><?= h($option) ?></option><?php endforeach; ?></select><input type="date" name="date_played" value="<?= h($item['date_played'] ?? '') ?>" aria-label="Date played"><button class="action-link" type="submit">Save</button></form></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  </section>
</section>
</main>
<script>
function tableSearch(inputId, rowsId) { const input=document.getElementById(inputId); if(!input)return; input.addEventListener('input',()=>{const q=input.value.toLowerCase().trim();document.querySelectorAll('#'+rowsId+' tr').forEach(row=>row.style.display=row.innerText.toLowerCase().includes(q)?'':'none');}); }
tableSearch('gameSearch','gameRows'); tableSearch('assignmentSearch','assignmentRows');
</script>
</body>
</html>
