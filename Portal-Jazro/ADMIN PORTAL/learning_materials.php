<?php
require_once __DIR__ . '/db.php';

$uploadDir = __DIR__ . '/uploads/learning_materials/';
$uploadUrl = 'uploads/learning_materials/';
$allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
$maxFileSize = 10 * 1024 * 1024; // 10 MB

$curriculum = [
    // JTC is an online class category, separate from the three curriculum stages.
    'Online' => ['name' => 'JTC', 'levels' => [
        'Level 1' => ['JTC'],
        'Level 2' => ['JTC'],
        'Level 3' => ['JTC'],
        'Level 4' => ['JTC'],
        'Level 5' => ['JTC'],
    ]],
    'Stage 1' => ['name' => 'Discoverer', 'levels' => [
        'Level 1' => ['MBOT 1'],
        'Level 2' => ['JAZROBOT'],
        'Level 3' => ['DRONE SIMULATION', 'SPACE DISCOVERER', 'INTRO TO AI', 'INTRO TO IOT'],
    ]],
    'Stage 2' => ['name' => 'Explorer', 'levels' => [
        'Level 1' => ['GAME MAKING', '3D DESIGN', 'ARDUINO', 'MICROBIT', 'VIRTUAL ROBOTIC'],
        'Level 2' => ['DRONE CODING'],
        'Level 3' => ['SPACE EXPLORER', '3D DESIGN', 'AI', 'IOT'],
    ]],
    'Stage 3' => ['name' => 'Innovator', 'levels' => [
        'Level 1' => ['MBOT 2'],
        'Level 2' => ['ROBOMASTER'],
        'Level 3' => ['3D PRINTING', 'GAME MAKING', 'VIRTUAL ROBOTIC', 'ARDUINO', 'SPACE INNOVATOR', 'AI', 'IOT'],
    ]],
];

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function getAdminId(PDO $pdo): int {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!empty($_SESSION['admin_id'])) {
        return (int)$_SESSION['admin_id'];
    }

    // The current Admin Portal pages do not expose a session variable.
    // Use the first existing admin record as a compatibility fallback.
    $id = $pdo->query("SELECT admin_id FROM admin ORDER BY admin_id ASC LIMIT 1")->fetchColumn();
    if (!$id) {
        throw new RuntimeException('No admin record was found. Please check the admin table.');
    }
    return (int)$id;
}

function saveUploadedFile(array $file, string $uploadDir, string $uploadUrl, array $allowedExtensions, int $maxFileSize): string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Please choose a file to upload.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file upload failed. Please try again.');
    }
    if ($file['size'] > $maxFileSize) {
        throw new RuntimeException('The maximum file size is 10 MB.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Only PDF, DOC, DOCX, PPT and PPTX files are allowed.');
    }

    $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $uploadDir . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Unable to save the uploaded file. Check folder permissions.');
    }

    return $uploadUrl . $safeName;
}

$message = '';
$messageType = 'success';

try {
    $adminId = getAdminId($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $materialId = (int)($_POST['material_id'] ?? 0);
            $title = trim($_POST['material_title'] ?? '');
            $stageName = trim($_POST['stage_name'] ?? '');
            $stageLevel = trim($_POST['stage_level'] ?? '');
            $curriculumModule = trim($_POST['curriculum_module'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($title === '') {
                throw new RuntimeException('Please enter a material title.');
            }
            if (!isset($curriculum[$stageName]['levels'][$stageLevel]) ||
                !in_array($curriculumModule, $curriculum[$stageName]['levels'][$stageLevel], true)) {
                throw new RuntimeException('Please select a valid Stage, Level and Module.');
            }

            if ($materialId > 0) {
                $stmt = $pdo->prepare("SELECT file_url FROM learning_material WHERE material_id = ?");
                $stmt->execute([$materialId]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$existing) {
                    throw new RuntimeException('Learning material not found.');
                }

                $fileUrl = $existing['file_url'];
                if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $newFileUrl = saveUploadedFile($_FILES['material_file'], $uploadDir, $uploadUrl, $allowedExtensions, $maxFileSize);
                    $oldPath = __DIR__ . '/' . $fileUrl;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                    $fileUrl = $newFileUrl;
                }

                $stmt = $pdo->prepare("UPDATE learning_material
                    SET module_id = NULL, stage_name = ?, stage_level = ?, curriculum_module = ?,
                        material_title = ?, file_url = ?, description = ?
                    WHERE material_id = ?");
                $stmt->execute([$stageName, $stageLevel, $curriculumModule, $title, $fileUrl,
                    $description !== '' ? $description : null, $materialId]);
                $message = 'Learning material updated successfully.';
            } else {
                $fileUrl = saveUploadedFile($_FILES['material_file'] ?? [], $uploadDir, $uploadUrl, $allowedExtensions, $maxFileSize);
                $stmt = $pdo->prepare("INSERT INTO learning_material
                    (module_id, admin_id, stage_name, stage_level, curriculum_module,
                     material_title, file_url, description)
                    VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$adminId, $stageName, $stageLevel, $curriculumModule, $title,
                    $fileUrl, $description !== '' ? $description : null]);
                $message = 'Learning material uploaded successfully.';
            }
        } elseif ($action === 'delete') {
            $materialId = (int)($_POST['material_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT file_url FROM learning_material WHERE material_id = ?");
            $stmt->execute([$materialId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                throw new RuntimeException('Learning material not found.');
            }

            $delete = $pdo->prepare("DELETE FROM learning_material WHERE material_id = ?");
            $delete->execute([$materialId]);

            $oldPath = __DIR__ . '/' . $item['file_url'];
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
            $message = 'Learning material deleted successfully.';
        }
    }
} catch (Throwable $e) {
    $message = $e->getMessage();
    $messageType = 'error';
}

$modules = [];
$materials = [];
try {
    $modules = $pdo->query("SELECT module_id, module_code, module_name, module_category, level
                            FROM module ORDER BY module_category, level, module_code")->fetchAll(PDO::FETCH_ASSOC);

    $materials = $pdo->query("SELECT lm.material_id, lm.module_id, lm.stage_name, lm.stage_level,
                                     lm.curriculum_module, lm.material_title, lm.file_url,
                                     lm.description, lm.upload_date, m.module_code, m.module_name,
                                     m.module_category, m.level
                              FROM learning_material lm
                              LEFT JOIN module m ON m.module_id = lm.module_id
                              ORDER BY lm.upload_date DESC, lm.material_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Unable to load learning materials. Please run the curriculum migration SQL file first.';
    $messageType = 'error';
}

$totalMaterials = count($materials);
$totalModules = count(array_unique(array_column($materials, 'module_id')));
$totalFiles = count(array_filter($materials, fn($item) => !empty($item['file_url'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Learning Materials | JAZRO Admin Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root{--blue:#2878f5;--blue-dark:#1758dc;--navy:#163664;--muted:#7c8eaa;--line:#e4edfa;--bg:#f5f9ff;--green:#159b69;--purple:#8b5cf6}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--navy);font-family:Inter,Arial,sans-serif;font-size:13px}
button,input,select,textarea{font:inherit}button{cursor:pointer}a{text-decoration:none}
.admin-sidebar{position:fixed;inset:0 auto 0 0;z-index:50;display:flex;flex-direction:column;width:245px;padding:24px 15px 17px;border-right:1px solid #e3edfb;background:#fff}
.admin-brand{display:flex;align-items:center;gap:11px;min-height:72px;padding:0 7px 20px;border-bottom:1px solid var(--line)}
.admin-brand img{width:59px;height:59px;object-fit:contain;flex-shrink:0}.admin-brand span{color:var(--blue);font-size:10px;font-weight:800;letter-spacing:1.6px;line-height:1.7}
.nav-caption{margin:26px 10px 12px;color:#91a2bb;font-size:9px;font-weight:800;letter-spacing:1.6px}
.admin-nav{display:grid;gap:6px}.admin-nav a,.logout-link{display:flex;align-items:center;gap:12px;min-height:43px;padding:11px 13px;border-radius:10px;color:#60738f;font-size:12px;font-weight:600;transition:.2s}
.admin-nav a:hover,.logout-link:hover{background:#f0f6ff;color:var(--blue)}.admin-nav a.active{background:#e8f2ff;color:var(--blue);box-shadow:inset 3px 0 var(--blue)}
.nav-icon{display:grid;width:23px;height:23px;place-items:center;border-radius:7px;background:#f0f6ff;font-size:14px}
.sidebar-bottom{margin-top:auto;padding-top:15px;border-top:1px solid var(--line)}.sidebar-bottom small{display:block;padding:14px 12px 0;color:#9aaac0;font-size:9px}
.main{margin-left:245px;min-width:0;min-height:100vh;display:flex;flex-direction:column}
.topbar{position:sticky;top:0;z-index:50;min-height:72px;padding:12px 30px;background:rgba(255,255,255,.97);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:15px;backdrop-filter:blur(10px)}
.top-title strong{display:block;font-size:15px}.top-title small{display:block;margin-top:4px;color:var(--muted);font-size:10px}
.top-right{display:flex;align-items:center;gap:13px}.bell{position:relative;width:39px;height:39px;border:1px solid var(--line);border-radius:12px;background:#fff;color:var(--blue)}
.dot{position:absolute;top:7px;right:7px;width:7px;height:7px;background:#ff8292;border-radius:50%}
.avatar{width:39px;height:39px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#55baff,#2870f4);color:#fff;font-weight:800;flex-shrink:0}
.account strong{display:block;font-size:11px}.account small{display:block;margin-top:4px;color:var(--muted);font-size:10px}
.mobile-menu{display:none;width:38px;height:38px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--blue)}
.content{width:100%;max-width:1700px;margin:0 auto;padding:24px 30px}
.intro{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;flex-wrap:wrap;margin-bottom:19px}
.eyebrow{margin:0 0 9px;color:var(--blue);font-size:9px;font-weight:800;letter-spacing:1.6px}
.intro h1{margin:0;font-size:27px;letter-spacing:-.8px}.intro p{margin:8px 0 0;color:var(--muted);font-size:12px;line-height:1.7}
.intro-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.demo{display:inline-flex;align-items:center;gap:7px;padding:9px 12px;border-radius:20px;background:#eaf4ff;color:#2871e4;font-size:10px;font-weight:700}
.demo i{width:6px;height:6px;border-radius:50%;background:#48b4ff}
.primary{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 16px;border:0;border-radius:10px;background:var(--blue);color:white;font-size:11px;font-weight:700;box-shadow:0 5px 12px #2878f526;transition:.2s}
.primary:hover{background:var(--blue-dark)}.secondary{padding:10px 13px;border:1px solid var(--line);border-radius:9px;background:white;color:#627590;font-size:11px;font-weight:600}
.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:20px}
.stat-card{padding:18px;border:1px solid var(--line);border-radius:14px;background:#fff}
.stat-top{display:flex;align-items:center;justify-content:space-between;color:var(--muted);font-size:10px;font-weight:700}
.stat-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:11px;background:#eaf2ff;font-size:18px}
.stat-number{display:block;margin-top:12px;font-size:27px;font-weight:800;letter-spacing:-1px}.stat-note{margin-top:4px;color:var(--muted);font-size:10px}
.toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:13px}
.search{position:relative;flex:1 1 220px}.search span{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#8b9ab1;font-size:16px}
.search input{width:100%;height:43px;padding:0 13px 0 37px;border:1px solid var(--line);border-radius:10px;background:#fff;outline:none;font-size:11px}
.filter{height:43px;min-width:155px;padding:0 28px 0 12px;border:1px solid var(--line);border-radius:10px;background:#fff;color:#627590;font-size:11px;outline:none}
.search input:focus,.filter:focus,.form-control:focus{border-color:#8db6ff;box-shadow:0 0 0 3px #2878f510}
.results-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:18px 0 11px}
.results-head h2{margin:0;font-size:15px}.results-head p{margin:5px 0 0;color:var(--muted);font-size:10px}
.results-count{padding:7px 10px;border-radius:20px;background:#eaf2ff;color:var(--blue);font-size:10px;font-weight:800;white-space:nowrap}
.table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:14px;background:#fff}
table{width:100%;border-collapse:collapse;min-width:760px}th,td{padding:14px 15px;text-align:left;border-bottom:1px solid #edf2fa}
th{background:#f8fbff;color:#71839d;font-size:9px;text-transform:uppercase;letter-spacing:.8px}
td{color:#425976;font-size:11px}tbody tr:last-child td{border-bottom:0}tbody tr:hover{background:#fbfdff}
.material-name{font-weight:700;color:var(--navy)}.subtext{display:block;margin-top:4px;color:var(--muted);font-size:10px}
.file-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 9px;border-radius:8px;background:#fff0ee;color:#d65b4b;font-size:9px;font-weight:800}
.action-group{display:flex;gap:6px}.action{width:30px;height:30px;border:1px solid var(--line);border-radius:8px;background:#fff;color:#60738f;font-size:14px}
.action:hover{background:#eaf2ff;color:var(--blue)}.action.delete:hover{background:#fff0f1;border-color:#ffd3d8;color:#e05262}
.empty{padding:45px 20px;text-align:center;color:var(--muted);font-size:12px;line-height:1.8}.empty strong{display:block;color:var(--navy);font-size:14px}
.modal{position:fixed;inset:0;z-index:900;display:none;place-items:center;padding:20px;background:rgba(22,49,87,.35);backdrop-filter:blur(4px)}
.modal.show{display:grid}.modal-card{width:100%;max-width:580px;max-height:90vh;overflow-y:auto;padding:25px;border:1px solid var(--line);border-radius:18px;background:#fff;box-shadow:0 24px 70px rgba(33,77,140,.18)}
.modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;margin-bottom:22px}.modal-head h2{margin:0;font-size:19px}
.modal-head p{margin:6px 0 0;color:var(--muted);font-size:11px;line-height:1.6}
.close-modal{width:32px;height:32px;border:0;border-radius:9px;background:#f0f6ff;color:#56708f;font-size:20px}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.form-group{display:flex;flex-direction:column;gap:8px}.form-group.full{grid-column:1/-1}
.form-group label{color:#425976;font-size:11px;font-weight:700}
.form-control{width:100%;min-height:42px;padding:10px 12px;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--navy);outline:none;font-size:11px}
textarea.form-control{min-height:85px;resize:vertical}.hint{color:var(--muted);font-size:9px;line-height:1.5}
.form-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:23px}
.notice{margin-bottom:16px;padding:12px 15px;border-radius:10px;font-size:11px}
.notice.success{background:#e5f8ef;color:#13885a}.notice.error{background:#fff0f1;color:#c74655}
.toast{position:fixed;right:25px;bottom:25px;z-index:1200;max-width:calc(100vw - 40px);padding:13px 18px;border-radius:11px;background:#17345e;color:#fff;font-size:11px;box-shadow:0 8px 24px #15346625}
.backdrop{display:none}

/* Extra colour accents for the Resource Library */
.library-overview{
  background:linear-gradient(110deg,#eef6ff 0%,#f5f1ff 52%,#fff7ed 100%);
  border-color:#dce8fb;
}
.overview-icon{background:linear-gradient(145deg,#dcecff,#e9e2ff);color:#356fe5}
.overview-meta b{color:#356fe5}
.category-chip:nth-child(2){--chip:#2878f5;--chip-bg:#edf5ff}
.category-chip:nth-child(3){--chip:#8b5cf6;--chip-bg:#f4efff}
.category-chip:nth-child(4){--chip:#0f9f87;--chip-bg:#e9faf5}
.category-chip:nth-child(5){--chip:#e58a25;--chip-bg:#fff5e8}
.category-chip:nth-child(6){--chip:#df5d79;--chip-bg:#fff0f4}
.category-chip:nth-child(7){--chip:#0b9bb5;--chip-bg:#e9f9fc}
.category-chip.active{background:var(--chip-bg,#edf5ff);border-color:var(--chip,#b8d2ff);color:var(--chip,#2878f5)}
.category-chip.active span{background:#fff;color:var(--chip,#2878f5)}
.resource-card{position:relative;overflow:hidden}
.resource-card:before{content:"";position:absolute;inset:0 0 auto;height:3px;background:linear-gradient(90deg,#2878f5,#8b5cf6,#45b7a4);opacity:.78}
.resource-card:nth-child(6n+2):before{background:linear-gradient(90deg,#8b5cf6,#c084fc)}
.resource-card:nth-child(6n+3):before{background:linear-gradient(90deg,#0f9f87,#72d6b5)}
.resource-card:nth-child(6n+4):before{background:linear-gradient(90deg,#e58a25,#f6c36c)}
.resource-card:nth-child(6n+5):before{background:linear-gradient(90deg,#df5d79,#f3a2b4)}
.resource-card:nth-child(6n+6):before{background:linear-gradient(90deg,#0b9bb5,#80d9e8)}
.file-art.pdf{background:linear-gradient(145deg,#fff0ee,#ffe0dc);color:#df554c}
.file-art.ppt,.file-art.pptx{background:linear-gradient(145deg,#fff5e7,#ffe8c7);color:#d88722}
.file-art.doc,.file-art.docx{background:linear-gradient(145deg,#edf4ff,#dceaff);color:#3976d5}
.resource-category{display:inline-flex;padding:5px 8px;border-radius:6px;background:#edf5ff;color:#2878f5}
.resource-card:nth-child(6n+2) .resource-category{background:#f4efff;color:#8052df}
.resource-card:nth-child(6n+3) .resource-category{background:#e9faf5;color:#0f927c}
.resource-card:nth-child(6n+4) .resource-category{background:#fff5e8;color:#d88722}
.resource-card:nth-child(6n+5) .resource-category{background:#fff0f4;color:#d65372}
.resource-card:nth-child(6n+6) .resource-category{background:#e9f9fc;color:#0b8fa8}
.resource-module{background:#f2f7ff}
.resource-card:nth-child(6n+2) .resource-module{background:#f7f3ff}
.resource-card:nth-child(6n+3) .resource-module{background:#effaf6}
.resource-card:nth-child(6n+4) .resource-module{background:#fff8ef}
.resource-card:nth-child(6n+5) .resource-module{background:#fff4f6}
.resource-card:nth-child(6n+6) .resource-module{background:#effbfc}
.open-resource{display:inline-flex;align-items:center;gap:5px;padding:7px 10px;border-radius:7px;background:#edf5ff}
.resource-card:nth-child(6n+2) .open-resource{background:#f4efff;color:#8052df}
.resource-card:nth-child(6n+3) .open-resource{background:#e9faf5;color:#0f927c}
.resource-card:nth-child(6n+4) .open-resource{background:#fff5e8;color:#d88722}
.resource-card:nth-child(6n+5) .open-resource{background:#fff0f4;color:#d65372}
.resource-card:nth-child(6n+6) .open-resource{background:#e9f9fc;color:#0b8fa8}

/* Resource Library content layout */
.library-overview{display:flex;align-items:center;justify-content:space-between;gap:18px;margin:4px 0 24px;padding:16px 19px;border:1px solid var(--line);border-radius:13px;background:linear-gradient(110deg,#fff,#f8fbff)}
.overview-copy{display:flex;align-items:center;gap:12px}.overview-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:#eaf2ff;color:var(--blue);font-size:21px}
.overview-copy strong{display:block;font-size:13px}.overview-copy small{display:block;margin-top:4px;color:var(--muted);font-size:10px}
.overview-meta{display:flex;align-items:center;gap:14px;color:var(--muted);font-size:10px}.overview-meta b{color:var(--navy);font-size:13px}.overview-divider{width:1px;height:20px;background:var(--line)}
.library-toolbar{margin-bottom:17px}.library-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px}
.library-heading h2{margin:0;font-size:17px}.library-heading p{margin:5px 0 0;color:var(--muted);font-size:10px}
.stage-strip{display:flex;flex-wrap:wrap;gap:9px;margin:2px 0 15px}.stage-chip{border:1px solid var(--line);background:#fff;color:#60738f;border-radius:12px;padding:11px 15px;font-weight:700;font-size:11px}.stage-chip.active{background:#e8f2ff;color:var(--blue);border-color:#b9d3ff}.stage-chip span{margin-left:6px;background:#f0f5fc;padding:3px 6px;border-radius:6px}.category-strip{display:flex;gap:8px;overflow-x:auto;padding:2px 0 13px;scrollbar-width:thin}
.category-chip{display:inline-flex;align-items:center;gap:9px;white-space:nowrap;padding:9px 13px;border:1px solid var(--line);border-radius:9px;background:#fff;color:#60738f;font-size:10px;font-weight:700}
.category-chip span{display:grid;place-items:center;min-width:19px;height:19px;padding:0 5px;border-radius:6px;background:#f0f5fc;color:#71839d;font-size:9px}
.category-chip:hover,.category-chip.active{border-color:#b8d2ff;background:#edf5ff;color:var(--blue)}.category-chip.active span{background:#dceaff;color:var(--blue)}
.library-filters{display:flex;align-items:center;gap:10px;margin-top:3px}.library-filters .search{max-width:520px}
.resource-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.resource-card{min-width:0;padding:17px;border:1px solid var(--line);border-radius:13px;background:#fff;transition:transform .18s,border-color .18s,box-shadow .18s}
.resource-card:hover{transform:translateY(-2px);border-color:#c9dcfb;box-shadow:0 9px 24px rgba(40,90,160,.07)}
.resource-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px}
.file-art{display:grid;place-items:center;width:47px;height:51px;border-radius:10px;background:#fff0ef;color:#df6155;font-size:10px;font-weight:800;position:relative}
.file-art:before{content:"";position:absolute;top:0;right:0;width:13px;height:13px;border-radius:0 9px 0 5px;background:#ffd9d5}
.file-art.ppt,.file-art.pptx{background:#fff5e8;color:#d88b28}.file-art.doc,.file-art.docx{background:#edf4ff;color:#3976d5}
.resource-menu{display:flex;gap:5px}.resource-menu form{margin:0}
.resource-category{color:var(--blue);font-size:9px;font-weight:800;letter-spacing:.8px}
.resource-card h3{margin:8px 0 6px;color:var(--navy);font-size:13px;line-height:1.5}
.resource-description{min-height:32px;margin:0;color:var(--muted);font-size:10px;line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.resource-module{display:flex;align-items:center;gap:7px;margin-top:14px;padding:9px 10px;border-radius:8px;background:#f7faff;color:#5c7190;font-size:9px;overflow:hidden}
.resource-module span:last-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.module-dot{width:6px;height:6px;flex-shrink:0;border-radius:50%;background:#58a4ff}
.resource-card-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px;padding-top:12px;border-top:1px solid #edf2fa}
.resource-date{color:#91a0b5;font-size:9px}.open-resource{color:var(--blue);font-size:10px;font-weight:800}.open-resource span{margin-left:4px}
.library-empty{grid-column:1/-1;padding:45px 20px;border:1px dashed #d7e4f5;border-radius:13px;background:#fff;text-align:center;color:var(--muted);font-size:11px}
.library-empty>span{display:block;margin-bottom:10px;color:#9bb8e7;font-size:25px}.library-empty strong{display:block;color:var(--navy);font-size:14px}.library-empty p{margin:6px 0 0}
@media(max-width:1100px){.resource-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.resource-grid{grid-template-columns:1fr}.library-overview{align-items:flex-start;flex-direction:column}.overview-meta{flex-wrap:wrap}.library-filters{align-items:stretch;flex-direction:column}.library-filters .search{max-width:none}.library-filters .filter{width:100%}.library-heading{align-items:flex-start}}

@media(max-width:1100px){.stats{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:760px){.admin-sidebar{width:245px;height:100dvh;transform:translateX(-105%);box-shadow:12px 0 35px rgba(25,65,120,.1)}.admin-sidebar.open{transform:translateX(0)}
.backdrop{position:fixed;inset:0;z-index:90;display:block;background:rgba(20,45,80,.25);opacity:0;pointer-events:none;transition:opacity .25s}.backdrop.show{opacity:1;pointer-events:auto}
.main{margin-left:0}.mobile-menu{display:inline-grid;place-items:center;flex-shrink:0}.topbar{min-height:62px;padding:10px 15px}.top-title{flex:1}.top-right{gap:8px}.account{display:none}.content{padding:20px 15px}.intro h1{font-size:23px}.stats{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.form-group.full{grid-column:auto}.modal-card{padding:20px}}

/* Extra colour accents for the Resource Library */
.library-overview{
  background:linear-gradient(110deg,#eef6ff 0%,#f5f1ff 52%,#fff7ed 100%);
  border-color:#dce8fb;
}
.overview-icon{background:linear-gradient(145deg,#dcecff,#e9e2ff);color:#356fe5}
.overview-meta b{color:#356fe5}
.category-chip:nth-child(2){--chip:#2878f5;--chip-bg:#edf5ff}
.category-chip:nth-child(3){--chip:#8b5cf6;--chip-bg:#f4efff}
.category-chip:nth-child(4){--chip:#0f9f87;--chip-bg:#e9faf5}
.category-chip:nth-child(5){--chip:#e58a25;--chip-bg:#fff5e8}
.category-chip:nth-child(6){--chip:#df5d79;--chip-bg:#fff0f4}
.category-chip:nth-child(7){--chip:#0b9bb5;--chip-bg:#e9f9fc}
.category-chip.active{background:var(--chip-bg,#edf5ff);border-color:var(--chip,#b8d2ff);color:var(--chip,#2878f5)}
.category-chip.active span{background:#fff;color:var(--chip,#2878f5)}
.resource-card{position:relative;overflow:hidden}
.resource-card:before{content:"";position:absolute;inset:0 0 auto;height:3px;background:linear-gradient(90deg,#2878f5,#8b5cf6,#45b7a4);opacity:.78}
.resource-card:nth-child(6n+2):before{background:linear-gradient(90deg,#8b5cf6,#c084fc)}
.resource-card:nth-child(6n+3):before{background:linear-gradient(90deg,#0f9f87,#72d6b5)}
.resource-card:nth-child(6n+4):before{background:linear-gradient(90deg,#e58a25,#f6c36c)}
.resource-card:nth-child(6n+5):before{background:linear-gradient(90deg,#df5d79,#f3a2b4)}
.resource-card:nth-child(6n+6):before{background:linear-gradient(90deg,#0b9bb5,#80d9e8)}
.file-art.pdf{background:linear-gradient(145deg,#fff0ee,#ffe0dc);color:#df554c}
.file-art.ppt,.file-art.pptx{background:linear-gradient(145deg,#fff5e7,#ffe8c7);color:#d88722}
.file-art.doc,.file-art.docx{background:linear-gradient(145deg,#edf4ff,#dceaff);color:#3976d5}
.resource-category{display:inline-flex;padding:5px 8px;border-radius:6px;background:#edf5ff;color:#2878f5}
.resource-card:nth-child(6n+2) .resource-category{background:#f4efff;color:#8052df}
.resource-card:nth-child(6n+3) .resource-category{background:#e9faf5;color:#0f927c}
.resource-card:nth-child(6n+4) .resource-category{background:#fff5e8;color:#d88722}
.resource-card:nth-child(6n+5) .resource-category{background:#fff0f4;color:#d65372}
.resource-card:nth-child(6n+6) .resource-category{background:#e9f9fc;color:#0b8fa8}
.resource-module{background:#f2f7ff}
.resource-card:nth-child(6n+2) .resource-module{background:#f7f3ff}
.resource-card:nth-child(6n+3) .resource-module{background:#effaf6}
.resource-card:nth-child(6n+4) .resource-module{background:#fff8ef}
.resource-card:nth-child(6n+5) .resource-module{background:#fff4f6}
.resource-card:nth-child(6n+6) .resource-module{background:#effbfc}
.open-resource{display:inline-flex;align-items:center;gap:5px;padding:7px 10px;border-radius:7px;background:#edf5ff}
.resource-card:nth-child(6n+2) .open-resource{background:#f4efff;color:#8052df}
.resource-card:nth-child(6n+3) .open-resource{background:#e9faf5;color:#0f927c}
.resource-card:nth-child(6n+4) .open-resource{background:#fff5e8;color:#d88722}
.resource-card:nth-child(6n+5) .open-resource{background:#fff0f4;color:#d65372}
.resource-card:nth-child(6n+6) .open-resource{background:#e9f9fc;color:#0b8fa8}


/* Learning Materials Hub refresh */
.library-hero{position:relative;isolation:isolate;overflow:hidden;display:flex;align-items:center;justify-content:space-between;min-height:188px;margin:0 0 17px;padding:27px 34px;border-radius:20px;background:linear-gradient(112deg,#173f7d 0%,#2868c8 58%,#829cf2 100%);color:#fff;box-shadow:0 14px 30px rgba(35,91,171,.14)}
.library-hero:before{content:"";position:absolute;z-index:-1;width:390px;height:390px;right:120px;top:-245px;border:1px solid rgba(255,255,255,.16);border-radius:50%;box-shadow:0 0 0 35px rgba(255,255,255,.035),0 0 0 75px rgba(255,255,255,.025)}
.hero-copy{max-width:700px}.hero-kicker{display:inline-flex;padding:7px 12px;border:1px solid rgba(255,255,255,.28);border-radius:20px;background:rgba(255,255,255,.14);font-size:9px;font-weight:800;letter-spacing:1.5px;color:#e5efff}
.hero-copy h2{margin:13px 0 7px;font-size:27px;letter-spacing:-.7px}.hero-copy p{max-width:560px;margin:0;color:#e0ebff;font-size:11px;line-height:1.8}
.hero-pills{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}.hero-pills span{padding:7px 10px;border:1px solid rgba(255,255,255,.2);border-radius:20px;background:rgba(255,255,255,.1);color:#f1f6ff;font-size:9px;font-weight:600}
.hero-art{position:relative;display:grid;place-items:center;width:190px;height:135px;flex-shrink:0;margin-right:35px}.hero-book{z-index:2;display:grid;place-items:center;width:88px;height:88px;border:1px solid rgba(255,255,255,.45);border-radius:25px;background:linear-gradient(145deg,rgba(255,255,255,.28),rgba(255,255,255,.1));box-shadow:0 15px 35px rgba(7,28,76,.2);font-size:42px;transform:rotate(-8deg);backdrop-filter:blur(8px)}
.hero-orbit{position:absolute;border:1px solid rgba(255,255,255,.32);border-radius:50%}.orbit-one{width:145px;height:78px;transform:rotate(28deg)}.orbit-two{width:165px;height:105px;transform:rotate(-28deg);border-style:dashed}.hero-spark{position:absolute;color:#ffdf8b;font-size:22px}.spark-one{top:6px;right:25px}.spark-two{bottom:8px;left:18px;color:#b9eaff}.spark-three{top:20px;left:25px;font-size:14px}
.quick-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin-bottom:19px}.quick-stat{display:flex;align-items:center;gap:13px;min-height:91px;padding:15px 17px;border:1px solid var(--line);border-radius:14px;background:#fff;transition:transform .18s,box-shadow .18s}.quick-stat:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(36,87,150,.07)}.quick-stat-icon{display:grid;place-items:center;width:43px;height:43px;flex-shrink:0;border-radius:13px;font-size:20px}.quick-stat small{display:block;color:var(--muted);font-size:9px;font-weight:800;letter-spacing:.65px}.quick-stat strong{display:block;margin-top:3px;color:var(--navy);font-size:22px;line-height:1.15}.quick-stat em{display:block;margin-top:3px;color:#91a0b5;font-size:9px;font-style:normal}.stat-blue .quick-stat-icon{background:#eaf2ff}.stat-purple .quick-stat-icon{background:#f2edff}.stat-green .quick-stat-icon{background:#e8f8f1}
.library-overview{margin-top:0}.library-heading h2{font-size:18px}.stage-chip{transition:.18s}.stage-chip:hover{transform:translateY(-1px);border-color:#b9d3ff}.resource-card{box-shadow:0 3px 12px rgba(36,87,150,.025)}.resource-card h3{font-size:14px}.resource-card:hover{transform:translateY(-4px);box-shadow:0 13px 28px rgba(40,90,160,.10)}
@media(max-width:850px){.hero-art{margin-right:0;transform:scale(.85)}}
@media(max-width:650px){.library-hero{padding:23px 20px;min-height:0}.hero-art{display:none}.hero-copy h2{font-size:23px}.quick-stats{grid-template-columns:1fr}.quick-stat{min-height:76px}.library-overview{margin-bottom:20px}}

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
  <div class="top-title"><strong>Learning Materials Management</strong><small>Upload and manage learning resources</small></div>
  <div class="top-right"><button class="bell" id="notifications" aria-label="Notifications">♧<span class="dot"></span></button><div class="avatar">A</div><div class="account"><strong>Admin</strong><small>Administrator</small></div></div>
</header>
<section class="content">
  <div class="intro">
    <div><div class="eyebrow">ACADEMIC MANAGEMENT</div><h1>Learning Materials</h1><p>Manage notes, documents and learning resources for JAZRO modules.</p></div>
    <div class="intro-actions"><span class="demo"><i></i>Live database</span><button class="primary" id="addMaterial">＋ Add Material</button></div>
  </div>

  <section class="library-hero">
    <div class="hero-copy">
      <span class="hero-kicker">JAZRO DIGITAL LIBRARY</span>
      <h2>Explore. Learn. Create.</h2>
      <p>Your central space for robotics, coding, AI and interactive learning resources.</p>
      <div class="hero-pills"><span>📚 Learning Resources</span><span>🚀 JAZRO Curriculum</span><span>💡 Student Learning</span></div>
    </div>
    <div class="hero-art" aria-hidden="true"><div class="hero-orbit orbit-one"></div><div class="hero-orbit orbit-two"></div><div class="hero-book">▤</div><span class="hero-spark spark-one">✦</span><span class="hero-spark spark-two">✧</span><span class="hero-spark spark-three">✦</span></div>
  </section>

  <div class="quick-stats">
    <article class="quick-stat stat-blue"><span class="quick-stat-icon">📚</span><div><small>TOTAL MATERIALS</small><strong><?= $totalMaterials ?></strong><em>Resources in the library</em></div></article>
    <article class="quick-stat stat-purple"><span class="quick-stat-icon">🧩</span><div><small>CURRICULUM MODULES</small><strong><?= $totalModules ?></strong><em>Modules represented</em></div></article>
    <article class="quick-stat stat-green"><span class="quick-stat-icon">📁</span><div><small>AVAILABLE FILES</small><strong><?= $totalFiles ?></strong><em>Ready to open or assign</em></div></article>
  </div>

  <?php if ($message !== ''): ?>
    <div class="notice <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= h($message) ?></div>
  <?php endif; ?>

  <div class="library-overview">
    <div class="overview-copy"><span class="overview-icon">▤</span><div><strong>Your learning resource library</strong><small>Organised by JAZRO stage, level and curriculum module</small></div></div>
    <div class="overview-meta"><span><b><?= $totalFiles ?></b> available files</span><span class="overview-divider"></span><span>PDF · DOC · PPT · DOCX · PPTX</span></div>
  </div>

  <div class="library-toolbar">
    <div class="library-heading"><div><h2>Browse learning resources</h2><p>Find the right notes, documents and activities for each JAZRO learning stage.</p></div><span class="results-count" id="resultsCount"><?= $totalMaterials ?> materials</span></div>
    <div class="stage-strip">
      <button class="stage-chip active" type="button" data-stage="">All materials <span><?= $totalMaterials ?></span></button>
      <?php foreach ($curriculum as $stage => $details): ?>
        <button class="stage-chip" type="button" data-stage="<?= h($stage) ?>"><?= $stage === 'Online' ? 'Online' : h($stage . ' · ' . $details['name']) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="library-filters">
      <label class="search"><span>⌕</span><input type="search" id="search" placeholder="Search notes or modules..." autocomplete="off"></label>
      <select class="filter" id="levelFilter"><option value="">All Levels</option>
        <?php foreach (['Level 1','Level 2','Level 3'] as $level): ?><option value="<?= h($level) ?>"><?= h($level) ?></option><?php endforeach; ?>
      </select>
      <select class="filter" id="moduleFilter"><option value="">All Modules</option>
        <?php foreach ($curriculum as $stage => $details): foreach ($details['levels'] as $level => $moduleNames): foreach ($moduleNames as $moduleName): ?>
          <option value="<?= h($moduleName) ?>"><?= h($moduleName) ?> (<?= h($stage) ?> · <?= h($level) ?>)</option>
        <?php endforeach; endforeach; endforeach; ?>
      </select>
      <button class="secondary" id="reset" type="button">Clear filters</button>
    </div>
  </div>

  <div class="resource-grid" id="materialRows">
    <?php if (!$materials): ?>
      <div class="library-empty"><span>▤</span><strong>No learning materials yet</strong><p>Upload your first resource to start building the library.</p></div>
    <?php else: foreach ($materials as $item):
      $ext = strtoupper(pathinfo($item['file_url'], PATHINFO_EXTENSION));
      $date = $item['upload_date'] ? date('d M Y', strtotime($item['upload_date'])) : '—';
    ?>
      <article class="resource-card" data-title="<?= h(strtolower($item['material_title'])) ?>" data-module="<?= h(strtolower($item['curriculum_module'] ?? $item['module_name'] ?? '')) ?>" data-description="<?= h(strtolower($item['description'] ?? '')) ?>" data-stage="<?= h($item['stage_name'] ?? '') ?>" data-level="<?= h($item['stage_level'] ?? '') ?>" data-curriculum-module="<?= h($item['curriculum_module'] ?? '') ?>">
        <div class="resource-card-top">
          <div class="file-art <?= h(strtolower($ext)) ?>"><span><?= $ext === 'PDF' ? 'PDF' : (in_array($ext, ['PPT','PPTX']) ? 'PPT' : 'DOC') ?></span></div>
          <div class="resource-menu">
            <button class="action" type="button" title="Edit" aria-label="Edit" data-edit='<?= h(json_encode($item, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP)) ?>'>✎</button>
            <form method="post" onsubmit="return confirm('Delete this learning material?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="material_id" value="<?= (int)$item['material_id'] ?>"><button class="action delete" type="submit" title="Delete" aria-label="Delete">×</button></form>
          </div>
        </div>
        <div class="resource-category"><?= h($item['stage_name'] ?: ($item['module_category'] ?: 'LEGACY MATERIAL')) ?><?= !empty($item['stage_level']) ? ' · ' . h($item['stage_level']) : '' ?></div>
        <h3><?= h($item['material_title']) ?></h3>
        <p class="resource-description"><?= h($item['description'] ?: 'No description provided for this resource.') ?></p>
        <div class="resource-module"><span class="module-dot"></span><span><?= h($item['curriculum_module'] ?: (($item['module_code'] ?? 'Legacy module') . ' · ' . ($item['module_name'] ?? ''))) ?></span></div>
        <div class="resource-card-footer"><span class="resource-date"><?= h($date) ?></span><a class="open-resource" href="<?= h($item['file_url']) ?>" target="_blank" rel="noopener">Open file <span>↗</span></a></div>
      </article>
    <?php endforeach; endif; ?>
  </div>
  <div class="library-empty" id="noFilteredResults" style="display:none"><span>⌕</span><strong>No matching materials</strong><p>Try changing your search or filters.</p></div>
</section>
</main>

<div class="modal" id="materialModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal-card">
    <div class="modal-head"><div><h2 id="modalTitle">Add Learning Material</h2><p id="modalSubtitle">Enter the details and upload a learning resource.</p></div><button class="close-modal" type="button" data-close aria-label="Close modal">×</button></div>
    <form method="post" enctype="multipart/form-data" id="materialForm">
      <input type="hidden" name="action" value="save"><input type="hidden" name="material_id" id="materialId" value="">
      <div class="form-grid">
        <div class="form-group full"><label for="materialTitle">Material Title *</label><input class="form-control" type="text" id="materialTitle" name="material_title" maxlength="200" required placeholder="e.g. JTC Level 1 - C01 Notes"></div>
        <div class="form-group"><label for="stageName">Stage *</label><select class="form-control" id="stageName" name="stage_name" required><option value="">Select category</option>
          <?php foreach ($curriculum as $stage => $details): ?><option value="<?= h($stage) ?>"><?= h($stage . ' — ' . $details['name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label for="stageLevel">Level *</label><select class="form-control" id="stageLevel" name="stage_level" required disabled><option value="">Select level</option></select></div>
        <div class="form-group full"><label for="curriculumModule">Module *</label><select class="form-control" id="curriculumModule" name="curriculum_module" required disabled><option value="">Select module</option></select></div>
        <div class="form-group full"><label for="description">Description</label><textarea class="form-control" id="description" name="description" placeholder="Briefly describe this learning material..."></textarea></div>
        <div class="form-group full"><label for="materialFile">Upload File <span id="fileRequired">*</span></label><input class="form-control" type="file" id="materialFile" name="material_file" accept=".pdf,.doc,.docx,.ppt,.pptx"><span class="hint" id="fileHint">Accepted formats: PDF, DOC, DOCX, PPT, PPTX. Maximum size: 10 MB.</span></div>
      </div>
      <div class="form-actions"><button class="secondary" type="button" data-close>Cancel</button><button class="primary" type="submit" id="saveMaterial">Upload Material</button></div>
    </form>
  </div>
</div>
<script>
const $ = id => document.getElementById(id);
const modal = $('materialModal');
function openModal(){modal.classList.add('show');document.body.style.overflow='hidden'}
function closeModal(){modal.classList.remove('show');document.body.style.overflow=''}
function resetForm(){
  $('materialForm').reset();$('materialId').value='';$('modalTitle').textContent='Add Learning Material';
  $('modalSubtitle').textContent='Enter the details and upload a learning resource.';
  $('saveMaterial').textContent='Upload Material';$('fileRequired').style.display='inline';
  $('materialFile').required=true;$('fileHint').textContent='Accepted formats: PDF, DOC, DOCX, PPT, PPTX. Maximum size: 10 MB.';
}
$('addMaterial').addEventListener('click',()=>{resetForm();openModal()});
document.querySelectorAll('[data-close]').forEach(button=>button.addEventListener('click',closeModal));
modal.addEventListener('click',event=>{if(event.target===modal)closeModal()});
document.addEventListener('keydown',event=>{if(event.key==='Escape')closeModal()});

document.querySelectorAll('[data-edit]').forEach(button=>{
  button.addEventListener('click',()=>{
    const item=JSON.parse(button.dataset.edit);
    resetForm();$('materialId').value=item.material_id;$('materialTitle').value=item.material_title;
    $('stageName').value=item.stage_name||'';
    populateLevels();
    $('stageLevel').value=item.stage_level||'';
    populateModules();
    $('curriculumModule').value=item.curriculum_module||'';
    $('description').value=item.description||'';
    $('modalTitle').textContent='Edit Learning Material';$('modalSubtitle').textContent='Update the details or choose a replacement file.';
    $('saveMaterial').textContent='Save Changes';$('fileRequired').style.display='none';
    $('materialFile').required=false;$('fileHint').textContent='Leave empty to keep the current file.';
    openModal();
  });
});

const curriculumData = <?= json_encode($curriculum, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
function populateLevels(){
  const stage=$('stageName').value, levelSelect=$('stageLevel');
  levelSelect.innerHTML='<option value="">Select level</option>';
  if(stage && curriculumData[stage]){
    Object.keys(curriculumData[stage].levels).forEach(level=>{
      const option=document.createElement('option');option.value=level;option.textContent=level;levelSelect.appendChild(option);
    });
    levelSelect.disabled=false;
  } else { levelSelect.disabled=true; }
  populateModules();
}
function populateModules(){
  const stage=$('stageName').value, level=$('stageLevel').value, moduleSelect=$('curriculumModule');
  moduleSelect.innerHTML='<option value="">Select module</option>';
  const names=stage && level && curriculumData[stage] ? curriculumData[stage].levels[level]||[] : [];
  names.forEach(name=>{const option=document.createElement('option');option.value=name;option.textContent=name;moduleSelect.appendChild(option)});
  moduleSelect.disabled=names.length===0;
}
$('stageName').addEventListener('change',()=>{ $('stageLevel').value=''; populateLevels(); });
$('stageLevel').addEventListener('change',populateModules);

function filterRows(){
  const query=$('search').value.trim().toLowerCase();
  const stage=document.querySelector('.stage-chip.active')?.dataset.stage||'';
  const level=$('levelFilter').value, module=$('moduleFilter').value;
  let shown=0;
  document.querySelectorAll('#materialRows .resource-card').forEach(card=>{
    const text=[card.dataset.title,card.dataset.module,card.dataset.description].join(' ');
    const match=(!query||text.includes(query))&&(!stage||card.dataset.stage===stage)&&(!level||card.dataset.level===level)&&(!module||card.dataset.curriculumModule===module);
    card.style.display=match?'':'none';if(match)shown++;
  });
  $('resultsCount').textContent=shown+' material'+(shown===1?'':'s');
  $('noFilteredResults').style.display=shown===0&&document.querySelectorAll('#materialRows .resource-card').length>0?'block':'none';
}
document.querySelectorAll('.stage-chip').forEach(chip=>{
  chip.addEventListener('click',()=>{
    document.querySelectorAll('.stage-chip').forEach(item=>item.classList.remove('active'));
    chip.classList.add('active');filterRows();
  });
});
$('search').addEventListener('input',filterRows);
$('levelFilter').addEventListener('change',filterRows);
$('moduleFilter').addEventListener('change',filterRows);
$('reset').addEventListener('click',()=>{
  $('search').value='';$('levelFilter').value='';$('moduleFilter').value='';
  document.querySelectorAll('.stage-chip').forEach(item=>item.classList.toggle('active',item.dataset.stage===''));
  filterRows();
});
$('mobileMenu').addEventListener('click',()=>{$('sidebar').classList.toggle('open');$('backdrop').classList.toggle('show')});
$('backdrop').addEventListener('click',()=>{$('sidebar').classList.remove('open');$('backdrop').classList.remove('show')});
document.querySelectorAll('[data-soon]').forEach(link=>link.addEventListener('click',event=>{event.preventDefault();alert(link.dataset.soon+' page is not connected yet.')}));
$('notifications').addEventListener('click',()=>alert('No new notifications.'));
</script>
</body>
</html>
