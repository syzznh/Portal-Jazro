<?php
require_once __DIR__ . '/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Database connection error. Please check db.php and ensure it provides a PDO connection named $pdo.');
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function cleanTime(?string $value): ?string {
    $value = trim((string)$value);
    if ($value === '') return null;
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
        throw new InvalidArgumentException('Please enter a valid start and end time.');
    }
    return $value . ':00';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    try {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            jsonResponse(['success' => false, 'message' => 'Invalid request data.'], 400);
        }

        $action = (string)$_GET['action'];

        if ($action === 'save') {
            $id = isset($payload['id']) ? (int)$payload['id'] : 0;
            $code = trim((string)($payload['code'] ?? ''));
            $name = trim((string)($payload['name'] ?? ''));
            $category = strtoupper(trim((string)($payload['category'] ?? '')));
            $type = trim((string)($payload['type'] ?? ''));
            $status = trim((string)($payload['status'] ?? 'Active'));
            $day = trim((string)($payload['day'] ?? ''));
            $startTime = cleanTime($payload['startTime'] ?? '');
            $endTime = cleanTime($payload['endTime'] ?? '');
            $branchId = ($payload['branchId'] ?? '') !== '' ? (int)$payload['branchId'] : null;
            $organizationId = ($payload['organizationId'] ?? '') !== '' ? (int)$payload['organizationId'] : null;

            if ($code === '' || $name === '') {
                jsonResponse(['success' => false, 'message' => 'Class code and class name are required.'], 422);
            }
            if (strlen($code) > 50 || strlen($name) > 150) {
                jsonResponse(['success' => false, 'message' => 'Class code or class name is too long.'], 422);
            }
            if (!in_array($category, ['BAU', 'B2B'], true)) {
                jsonResponse(['success' => false, 'message' => 'Please select BAU or B2B as the class category.'], 422);
            }
            if (!in_array($type, ['Physical', 'Online'], true)) {
                jsonResponse(['success' => false, 'message' => 'Please select a valid class type.'], 422);
            }
            if (!in_array($status, ['Active', 'Inactive', 'Completed'], true)) {
                jsonResponse(['success' => false, 'message' => 'Please select a valid status.'], 422);
            }
            if ($day !== '' && strlen($day) > 20) {
                jsonResponse(['success' => false, 'message' => 'Day must not exceed 20 characters.'], 422);
            }
            if ($startTime && $endTime && $startTime >= $endTime) {
                jsonResponse(['success' => false, 'message' => 'End time must be later than start time.'], 422);
            }

            // A class can be linked to a branch OR an organisation, not both.
            if ($category === 'BAU') {
                $organizationId = null;
            } else {
                $branchId = null;
            }

            if ($branchId !== null) {
                $check = $pdo->prepare('SELECT COUNT(*) FROM branch WHERE branch_id = ?');
                $check->execute([$branchId]);
                if (!(int)$check->fetchColumn()) {
                    jsonResponse(['success' => false, 'message' => 'Selected branch was not found.'], 422);
                }
            }
            if ($organizationId !== null) {
                $check = $pdo->prepare('SELECT COUNT(*) FROM organization WHERE organization_id = ?');
                $check->execute([$organizationId]);
                if (!(int)$check->fetchColumn()) {
                    jsonResponse(['success' => false, 'message' => 'Selected organisation was not found.'], 422);
                }
            }

            $duplicateSql = 'SELECT COUNT(*) FROM classes WHERE LOWER(class_code) = LOWER(?)' . ($id > 0 ? ' AND class_id <> ?' : '');
            $duplicate = $pdo->prepare($duplicateSql);
            $duplicate->execute($id > 0 ? [$code, $id] : [$code]);
            if ((int)$duplicate->fetchColumn() > 0) {
                jsonResponse(['success' => false, 'message' => 'This class code already exists. Please use a different code.'], 409);
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE classes
                     SET branch_id = ?, organization_id = ?, class_code = ?, class_name = ?,
                         class_type = ?, class_category = ?, status = ?, start_time = ?, end_time = ?, day = ?
                     WHERE class_id = ?'
                );
                $stmt->execute([$branchId, $organizationId, $code, $name, $type, $category, $status, $startTime, $endTime, ($day !== '' ? $day : null), $id]);
                jsonResponse(['success' => true, 'message' => 'Class updated successfully.']);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO classes
                 (branch_id, organization_id, class_code, class_name, class_type, class_category, status, start_time, end_time, day)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$branchId, $organizationId, $code, $name, $type, $category, $status, $startTime, $endTime, ($day !== '' ? $day : null)]);
            jsonResponse(['success' => true, 'message' => 'Class added successfully.']);
        }

        if ($action === 'delete') {
            $id = (int)($payload['id'] ?? 0);
            if ($id <= 0) jsonResponse(['success' => false, 'message' => 'Invalid class selected.'], 422);

            try {
                $stmt = $pdo->prepare('DELETE FROM classes WHERE class_id = ?');
                $stmt->execute([$id]);
                if ($stmt->rowCount() === 0) {
                    jsonResponse(['success' => false, 'message' => 'Class not found.'], 404);
                }
                jsonResponse(['success' => true, 'message' => 'Class deleted successfully.']);
            } catch (PDOException $e) {
                // Related sessions, enrolments, certificates or report cards may refer to this class.
                jsonResponse(['success' => false, 'message' => 'This class cannot be deleted because it is already linked to other records. Change its status to Inactive instead.'], 409);
            }
        }

        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
    } catch (InvalidArgumentException $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'A database error occurred. Please check the class information and try again.'], 500);
    }
}

try {
    $stmt = $pdo->query(
        "SELECT c.class_id, c.class_code, c.class_name, c.class_type, c.class_category, c.status,
                c.start_time, c.end_time, c.day, c.branch_id, c.organization_id,
                b.branch_name, b.branch_code, b.branch_location,
                o.organization_name, o.location AS organization_location
         FROM classes c
         LEFT JOIN branch b ON b.branch_id = c.branch_id
         LEFT JOIN organization o ON o.organization_id = c.organization_id
         ORDER BY c.class_id ASC"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $branchRows = $pdo->query('SELECT branch_id, branch_code, branch_name FROM branch ORDER BY branch_name')->fetchAll(PDO::FETCH_ASSOC);
    $organizationRows = $pdo->query('SELECT organization_id, organization_name FROM organization ORDER BY organization_name')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Unable to load classes. Please check the database tables and connections.');
}

$dbClasses = [];
foreach ($rows as $row) {
    $start = trim((string)($row['start_time'] ?? ''));
    $end = trim((string)($row['end_time'] ?? ''));
    $location = 'Location not assigned';
    if (!empty($row['branch_name'])) {
        $location = $row['branch_name'];
        if (!empty($row['branch_location'])) $location .= ' · ' . $row['branch_location'];
    } elseif (!empty($row['organization_name'])) {
        $location = $row['organization_name'];
        if (!empty($row['organization_location'])) $location .= ' · ' . $row['organization_location'];
    } elseif (($row['class_type'] ?? '') === 'Online') {
        $location = 'Online';
    }

    $dbClasses[] = [
        'id' => (string)$row['class_id'],
        'code' => (string)$row['class_code'],
        'name' => (string)$row['class_name'],
        'category' => (string)$row['class_category'],
        'type' => (string)$row['class_type'],
        'status' => (string)($row['status'] ?? 'Active'),
        'day' => (string)($row['day'] ?? ''),
        'startTime' => $start ? substr($start, 0, 5) : '',
        'endTime' => $end ? substr($end, 0, 5) : '',
        'time' => ($start !== '' && $end !== '') ? date('g:i A', strtotime($start)) . ' - ' . date('g:i A', strtotime($end)) : 'Not set',
        'location' => $location,
        'branchId' => $row['branch_id'] !== null ? (string)$row['branch_id'] : '',
        'organizationId' => $row['organization_id'] !== null ? (string)$row['organization_id'] : ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Classes | JAZRO Admin Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root {
  --blue: #2878f5;
  --blue-dark: #1758dc;
  --navy: #163664;
  --muted: #7c8eaa;
  --line: #e4edfa;
  --bg: #f5f9ff;
  --green: #159b69;
  --purple: #8b5cf6;
}

* { box-sizing: border-box; }

body {
  margin: 0;
  background: var(--bg);
  color: var(--navy);
  font-family: Inter, Arial, sans-serif;
  font-size: 13px;
}

button, input, select { font: inherit; }
button { cursor: pointer; }
a { text-decoration: none; }

/* SIDEBAR */
    .admin-sidebar {
      position: fixed;
      inset: 0 auto 0 0;
      z-index: 50;
      display: flex;
      flex-direction: column;
      width: 245px;
      padding: 24px 15px 17px;
      border-right: 1px solid #e3edfb;
      background: #fff;
    }

    .admin-brand {
      display: flex;
      align-items: center;
      gap: 11px;
      min-height: 72px;
      padding: 0 7px 20px;
      border-bottom: 1px solid var(--line);
    }

    .admin-brand img {
      width: 59px;
      height: 59px;
      object-fit: contain;
      flex-shrink: 0;
    }

    .admin-brand span {
      color: var(--blue);
      font-size: 10px;
      font-weight: 800;
      letter-spacing: 1.6px;
      line-height: 1.7;
    }

    .nav-caption {
      margin: 26px 10px 12px;
      color: #91a2bb;
      font-size: 9px;
      font-weight: 800;
      letter-spacing: 1.6px;
    }

    .admin-nav {
      display: grid;
      gap: 6px;
    }

    .admin-nav a,
    .logout-link {
      display: flex;
      align-items: center;
      gap: 12px;
      min-height: 43px;
      padding: 11px 13px;
      border-radius: 10px;
      color: #60738f;
      font-size: 12px;
      font-weight: 600;
      transition: 0.2s ease;
    }

    .admin-nav a:hover,
    .logout-link:hover {
      background: #f0f6ff;
      color: var(--blue);
    }

    .admin-nav a.active {
      background: #e8f2ff;
      color: var(--blue);
      box-shadow: inset 3px 0 var(--blue);
    }

    .nav-icon {
      display: grid;
      width: 23px;
      height: 23px;
      place-items: center;
      border-radius: 7px;
      background: #f0f6ff;
      font-size: 14px;
    }

    .sidebar-bottom {
      margin-top: auto;
      padding-top: 15px;
      border-top: 1px solid var(--line);
    }

    .sidebar-bottom small {
      display: block;
      padding: 14px 12px 0;
      color: #9aaac0;
      font-size: 9px;
    }

    
/* TOPBAR */
.main {
  margin-left: 245px;
  min-width: 0;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.topbar {
  position: sticky;
  top: 0;
  z-index: 50;
  min-height: 72px;
  padding: 12px 30px;
  background: rgba(255,255,255,.97);
  border-bottom: 1px solid var(--line);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  backdrop-filter: blur(10px);
}

.top-title strong {
  display: block;
  font-size: 15px;
}

.top-title small {
  display: block;
  margin-top: 4px;
  color: var(--muted);
  font-size: 10px;
}

.top-right {
  display: flex;
  align-items: center;
  gap: 13px;
}

.bell {
  position: relative;
  width: 39px;
  height: 39px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fff;
  color: var(--blue);
}

.dot {
  position: absolute;
  top: 7px;
  right: 7px;
  width: 7px;
  height: 7px;
  background: #ff8292;
  border-radius: 50%;
}

.avatar {
  width: 39px;
  height: 39px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: linear-gradient(145deg,#55baff,#2870f4);
  color: #fff;
  font-weight: 800;
  flex-shrink: 0;
}

.account strong {
  display: block;
  font-size: 11px;
}

.account small {
  display: block;
  margin-top: 4px;
  color: var(--muted);
  font-size: 10px;
}

.mobile-menu {
  display: none;
  width: 38px;
  height: 38px;
  border: 1px solid var(--line);
  border-radius: 10px;
  background: #fff;
  color: var(--blue);
}

/* PAGE CONTENT */
.content {
  width: 100%;
  max-width: 1700px;
  margin: 0 auto;
  padding: 24px 30px;
}

.intro {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 18px;
  flex-wrap: wrap;
  margin-bottom: 19px;
}

.eyebrow {
  margin: 0 0 9px;
  color: var(--blue);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 1.6px;
}

.intro h1 {
  margin: 0;
  font-size: 27px;
  letter-spacing: -.8px;
}

.intro p {
  margin: 8px 0 0;
  color: var(--muted);
  font-size: 12px;
  line-height: 1.7;
}

.intro-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.demo {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 12px;
  border-radius: 20px;
  background: #eaf4ff;
  color: #2871e4;
  font-size: 10px;
  font-weight: 700;
}

.demo i {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #48b4ff;
}

.primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 16px;
  border: 0;
  border-radius: 10px;
  background: var(--blue);
  color: white;
  font-size: 11px;
  font-weight: 700;
  box-shadow: 0 5px 12px #2878f526;
  transition: .2s;
}

.primary:hover { background: var(--blue-dark); }

.secondary {
  padding: 10px 13px;
  border: 1px solid var(--line);
  border-radius: 9px;
  background: white;
  color: #627590;
  font-size: 11px;
  font-weight: 600;
}

/* CATEGORY CARDS */
.category-grid {
  display: grid;
  grid-template-columns: repeat(3,minmax(0,1fr));
  gap: 14px;
  margin-bottom: 20px;
}

.category-card {
  position: relative;
  overflow: hidden;
  padding: 17px;
  border: 1px solid var(--line);
  border-radius: 14px;
  background: #fff;
  transition: transform .2s, box-shadow .2s;
}

.category-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 9px 22px #25569912;
}

.category-card::after {
  content: "";
  position: absolute;
  width: 85px;
  height: 85px;
  right: -30px;
  bottom: -43px;
  border-radius: 50%;
  background: var(--tint);
  pointer-events: none;
}

.category-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.category-icon {
  width: 42px;
  height: 42px;
  border-radius: 13px;
  display: grid;
  place-items: center;
  background: var(--tint);
  font-size: 20px;
}

.category-tag {
  padding: 6px 8px;
  border-radius: 20px;
  background: var(--tint);
  color: var(--accent);
  font-size: 9px;
  font-weight: 700;
}

.category-card h2 {
  margin: 14px 0 5px;
  font-size: 14px;
}

.category-card p {
  margin: 0;
  color: var(--muted);
  font-size: 10px;
  line-height: 1.6;
}

.category-bottom {
  display: flex;
  align-items: baseline;
  gap: 7px;
  margin-top: 11px;
}

.category-count {
  font-size: 28px;
  font-weight: 800;
  letter-spacing: -1px;
}

.category-count-label {
  color: var(--muted);
  font-size: 10px;
}

.mini-track {
  height: 5px;
  margin-top: 11px;
  border-radius: 10px;
  background: #eef3fb;
  overflow: hidden;
}

.mini-fill {
  height: 100%;
  border-radius: 10px;
  background: var(--accent);
  width: var(--width);
  transition: width .25s;
}

/* SEARCH AND FILTERS */
.toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 13px;
}

.search {
  position: relative;
  flex: 1 1 220px;
}

.search span {
  position: absolute;
  left: 13px;
  top: 50%;
  transform: translateY(-50%);
  color: #8b9ab1;
  font-size: 16px;
}

.search input {
  width: 100%;
  height: 43px;
  padding: 0 13px 0 37px;
  border: 1px solid var(--line);
  border-radius: 10px;
  background: #fff;
  outline: none;
  font-size: 11px;
}

.search input:focus,
.filter:focus {
  border-color: #8db6ff;
  box-shadow: 0 0 0 3px #2878f510;
}

.filter {
  height: 43px;
  min-width: 135px;
  padding: 0 28px 0 12px;
  border: 1px solid var(--line);
  border-radius: 10px;
  background: #fff;
  color: #627590;
  font-size: 11px;
  outline: none;
}

/* CATEGORY TABS */
.tabs {
  display: flex;
  align-items: center;
  gap: 7px;
  flex-wrap: wrap;
  margin-bottom: 17px;
}

.tab {
  padding: 9px 14px;
  border: 1px solid var(--line);
  border-radius: 9px;
  background: #fff;
  color: #72839d;
  font-size: 10px;
  font-weight: 700;
}

.tab:hover {
  border-color: #bcd4fb;
  color: var(--blue);
}

.tab.active {
  background: var(--blue);
  border-color: var(--blue);
  color: #fff;
  box-shadow: 0 4px 10px #2878f522;
}

.tab-count { margin-left: 5px; opacity: .8; }

/* CLASS CARDS */
.results-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 11px;
}

.results-head h2 { margin: 0; font-size: 15px; }

.results-head p {
  margin: 5px 0 0;
  color: var(--muted);
  font-size: 10px;
}

.results-count {
  padding: 7px 10px;
  border-radius: 20px;
  background: #eaf2ff;
  color: var(--blue);
  font-size: 10px;
  font-weight: 800;
  white-space: nowrap;
}

.class-grid {
  display: grid;
  grid-template-columns: repeat(3,minmax(0,1fr));
  gap: 13px;
}

.class-card {
  display: flex;
  flex-direction: column;
  min-width: 0;
  padding: 15px;
  border: 1px solid var(--line);
  border-radius: 14px;
  background: #fff;
  transition: transform .2s, box-shadow .2s, border-color .2s;
}

.class-card:hover {
  transform: translateY(-2px);
  border-color: #c8dcfb;
  box-shadow: 0 9px 22px #25569910;
}

.card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.class-icon {
  width: 39px;
  height: 39px;
  display: grid;
  place-items: center;
  border-radius: 11px;
  background: var(--tint);
  font-size: 19px;
  flex-shrink: 0;
}

.type-pill {
  padding: 6px 8px;
  border-radius: 20px;
  background: var(--tint);
  color: var(--accent);
  font-size: 9px;
  font-weight: 700;
}

.class-card h3 {
  margin: 14px 0 5px;
  color: var(--navy);
  font-size: 14px;
  overflow-wrap: anywhere;
}

.class-subtitle {
  color: var(--muted);
  font-size: 10px;
  line-height: 1.6;
}

.card-meta {
  display: grid;
  gap: 9px;
  margin: 16px 0;
}

.meta-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  color: #60738f;
  font-size: 10px;
  line-height: 1.6;
}

.meta-icon {
  width: 18px;
  color: var(--blue);
  text-align: center;
  flex-shrink: 0;
}

.card-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: auto;
  padding-top: 12px;
  border-top: 1px solid #edf2fa;
}

.status {
  padding: 6px 9px;
  border-radius: 20px;
  font-size: 9px;
  font-weight: 700;
}

.status-active { background: #e3f8ef; color: #13885a; }
.status-inactive { background: #eef1f6; color: #718096; }
.status-completed { background: #eee8ff; color: #7350d8; }

.card-actions {
  display: flex;
  gap: 6px;
}

.action {
  width: 30px;
  height: 30px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
  color: #60738f;
  font-size: 15px;
}

.action:hover {
  background: #eaf2ff;
  color: var(--blue);
}

.action.delete:hover {
  background: #fff0f1;
  border-color: #ffd3d8;
  color: #e05262;
}

.empty {
  grid-column: 1 / -1;
  padding: 45px 20px;
  border: 1px dashed #cad9ee;
  border-radius: 14px;
  background: #fff;
  text-align: center;
  color: var(--muted);
  font-size: 12px;
  line-height: 1.8;
}

.empty strong {
  display: block;
  color: var(--navy);
  font-size: 14px;
}

/* MODAL */
.modal {
  position: fixed;
  inset: 0;
  z-index: 900;
  display: none;
  place-items: center;
  padding: 20px;
  background: rgba(22,49,87,.35);
  backdrop-filter: blur(4px);
}

.modal.show { display: grid; }

.modal-card {
  width: 100%;
  max-width: 580px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 25px;
  border: 1px solid var(--line);
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 24px 70px rgba(33,77,140,.18);
}

.modal-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 22px;
}

.modal-head h2 { margin: 0; font-size: 19px; }

.modal-head p {
  margin: 6px 0 0;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.6;
}

.close-modal {
  width: 32px;
  height: 32px;
  border: 0;
  border-radius: 9px;
  background: #f0f6ff;
  color: #56708f;
  font-size: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2,minmax(0,1fr));
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group.full-width { grid-column: 1 / -1; }

.form-group label {
  color: #425976;
  font-size: 11px;
  font-weight: 700;
}

.form-control {
  width: 100%;
  min-height: 42px;
  padding: 10px 12px;
  border: 1px solid var(--line);
  border-radius: 9px;
  background: #fff;
  color: var(--navy);
  outline: none;
  font-size: 11px;
}

.form-control:focus {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px #2878f510;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 23px;
}

.toast {
  position: fixed;
  right: 25px;
  bottom: 25px;
  z-index: 1200;
  max-width: calc(100vw - 40px);
  padding: 13px 18px;
  border-radius: 11px;
  background: #17345e;
  color: #fff;
  font-size: 11px;
  box-shadow: 0 8px 24px #15346625;
  opacity: 0;
  pointer-events: none;
  transform: translateY(8px);
  transition: .25s;
}

.toast.show {
  opacity: 1;
  transform: translateY(0);
}

.backdrop { display: none; }

/* RESPONSIVE */
@media (max-width: 1100px) {
  .category-grid { grid-template-columns: repeat(3,minmax(0,1fr)); }
  .class-grid { grid-template-columns: repeat(2,minmax(0,1fr)); }
}

@media (max-width: 760px) {
  .admin-sidebar {
    width: 245px;
    height: 100dvh;
    transform: translateX(-105%);
    box-shadow: 12px 0 35px rgba(25,65,120,.1);
  }

  .admin-sidebar.open { transform: translateX(0); }

  .backdrop {
    position: fixed;
    inset: 0;
    z-index: 90;
    display: block;
    background: rgba(20,45,80,.25);
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s;
  }

  .backdrop.show {
    opacity: 1;
    pointer-events: auto;
  }

  .main { margin-left: 0; }

  .mobile-menu {
    display: inline-grid;
    place-items: center;
    flex-shrink: 0;
  }

  .topbar {
    min-height: 62px;
    padding: 10px 15px;
  }

  .top-title { flex: 1; }
  .top-right { gap: 8px; }
  .account { display: none; }
  .content { padding: 20px 15px; }
  .intro h1 { font-size: 23px; }
  .category-grid, .class-grid { grid-template-columns: 1fr; }
  .form-grid { grid-template-columns: 1fr; }
  .form-group.full-width { grid-column: auto; }
  .modal-card { padding: 20px; }
}

@media (max-width: 420px) {
  .intro-actions { width: 100%; }
  .intro-actions .primary { flex: 1; }
  .results-head h2 { font-size: 14px; }
  .modal { padding: 10px; }
}

.form-hint{font-size:10px;color:var(--muted);line-height:1.5;margin-top:-2px}
.location-fields{display:contents}

.class-filters{
  display:flex;align-items:flex-start;justify-content:flex-start;gap:52px;
  flex-wrap:wrap;margin:2px 0 18px;padding:14px 16px;
  border:1px solid var(--line);border-radius:13px;background:#fff;
}
.filter-group{display:flex;flex-direction:column;gap:9px}
.filter-label{font-size:9px;font-weight:800;letter-spacing:1.3px;color:#91a2bb}
.filter-options{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.filter-options .tab{margin:0}
@media(max-width:760px){.class-filters{gap:18px}.filter-group{width:100%}}
</style>
</head>
<body>
<aside class="admin-sidebar" id="sidebar">
  <a class="admin-brand" href="dashboard.php">
    <img src="assets/images/jazro-logo.png" alt="JAZRO Robotic Academy">
    <span>ADMIN PORTAL</span>
  </a>
  <p class="nav-caption">MAIN MENU</p>
  <?php include __DIR__ . '/learning_activities_menu.php'; ?>
  <div class="sidebar-bottom">
    <a href="index.php" class="logout-link" id="logout"><span class="nav-icon">↪</span> Log Out</a>
    <small>JAZRO ROBOTIC ACADEMY · 2026</small>
  </div>
</aside>
<div class="backdrop" id="backdrop"></div>

<main class="main">
  <header class="topbar">
    <button class="mobile-menu" id="mobileMenu" aria-label="Open menu">☰</button>
    <div class="top-title">
      <strong>Classes Management</strong>
      <small>Manage learning programmes and class information</small>
    </div>
    <div class="top-right">
      <button class="bell" id="notifications" aria-label="Notifications">♧<span class="dot"></span></button>
      <div class="avatar">A</div>
      <div class="account"><strong>Admin</strong><small>Administrator</small></div>
    </div>
  </header>

  <section class="content">
    <div class="intro">
      <div>
        <div class="eyebrow">ACADEMIC MANAGEMENT</div>
        <h1>Classes</h1>
        <p>View and manage JAZRO classes, school classes and online learning groups.</p>
      </div>
      <div class="intro-actions">
        <span class="demo"><i></i>Live database</span>
        <button class="primary" id="addClass">＋ Add Class</button>
      </div>
    </div>

    <div class="category-grid">
      <article class="category-card" style="--tint:#eaf2ff;--accent:#2878f5">
        <div class="category-top"><div class="category-icon">🎓</div><span class="category-tag">CATEGORY</span></div>
        <h2>BAU Classes</h2><p>Regular JAZRO programmes.</p>
        <div class="category-bottom"><strong class="category-count" id="bauCount">0</strong><span class="category-count-label">classes</span></div>
        <div class="mini-track"><div class="mini-fill" id="bauFill" style="--width:0%"></div></div>
      </article>
      <article class="category-card" style="--tint:#f1eaff;--accent:#8b5cf6">
        <div class="category-top"><div class="category-icon">🏫</div><span class="category-tag">CATEGORY</span></div>
        <h2>B2B Classes</h2><p>School and organisation programmes.</p>
        <div class="category-bottom"><strong class="category-count" id="b2bCount">0</strong><span class="category-count-label">classes</span></div>
        <div class="mini-track"><div class="mini-fill" id="b2bFill" style="--width:0%"></div></div>
      </article>
      <article class="category-card" style="--tint:#e3f8ef;--accent:#159b69">
        <div class="category-top"><div class="category-icon">🤖</div><span class="category-tag">CLASS TYPE</span></div>
        <h2>Online Classes</h2><p>Classes delivered through online learning.</p>
        <div class="category-bottom"><strong class="category-count" id="onlineCount">0</strong><span class="category-count-label">classes</span></div>
        <div class="mini-track"><div class="mini-fill" id="onlineFill" style="--width:0%"></div></div>
      </article>
    </div>

    <div class="toolbar">
      <label class="search"><span>⌕</span><input type="search" id="search" placeholder="Search class name, code or location..." autocomplete="off"></label>
      <select class="filter" id="statusFilter" aria-label="Filter by status">
        <option value="">All Statuses</option><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="Completed">Completed</option>
      </select>
      <button class="secondary" id="reset">Reset Filters</button>
    </div>

    <div class="class-filters">
      <div class="filter-group">
        <span class="filter-label">CATEGORY</span>
        <div class="filter-options" role="group" aria-label="Filter by category">
          <button class="tab active" data-category="All" type="button">All <span class="tab-count" id="allTabCount">0</span></button>
          <button class="tab" data-category="BAU" type="button">BAU <span class="tab-count" id="bauTabCount">0</span></button>
          <button class="tab" data-category="B2B" type="button">B2B <span class="tab-count" id="b2bTabCount">0</span></button>
        </div>
      </div>
      <div class="filter-group">
        <span class="filter-label">CLASS TYPE</span>
        <div class="filter-options" role="group" aria-label="Filter by class type">
          <button class="tab type-tab active" data-type="All" type="button">All</button>
          <button class="tab type-tab" data-type="Physical" type="button">Physical</button>
          <button class="tab type-tab" data-type="Online" type="button">Online <span class="tab-count" id="onlineTabCount">0</span></button>
        </div>
      </div>
    </div>

    <div class="results-head">
      <div><h2 id="resultsTitle">All Classes</h2><p id="resultsSubtitle">Browse all JAZRO learning programmes.</p></div>
      <span class="results-count" id="resultsCount">0 classes</span>
    </div>
    <div class="class-grid" id="classGrid"></div>
  </section>
</main>

<div class="modal" id="classModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal-card">
    <div class="modal-head">
      <div><h2 id="modalTitle">Add Class</h2><p id="modalSubtitle">Enter the class details below.</p></div>
      <button class="close-modal" type="button" data-close aria-label="Close modal">×</button>
    </div>
    <form id="classForm">
      <div class="form-grid">
        <div class="form-group">
          <label for="classCode">Class Code *</label>
          <input class="form-control" id="classCode" required maxlength="50" placeholder="e.g. KTG#02">
        </div>
        <div class="form-group">
          <label for="classCategory">Class Category *</label>
          <select class="form-control" id="classCategory" required>
            <option value="BAU">BAU</option><option value="B2B">B2B</option>
          </select>
        </div>
        <div class="form-group full-width">
          <label for="className">Class Name *</label>
          <input class="form-control" id="className" required maxlength="150" placeholder="Enter class name">
        </div>
        <div class="form-group">
          <label for="classType">Class Type *</label>
          <select class="form-control" id="classType" required><option value="Physical">Physical</option><option value="Online">Online</option></select>
        </div>
        <div class="form-group">
          <label for="classStatus">Status</label>
          <select class="form-control" id="classStatus"><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="Completed">Completed</option></select>
        </div>
        <div class="form-group" id="branchGroup">
          <label for="classBranch">Branch</label>
          <select class="form-control" id="classBranch">
            <option value="">No branch assigned</option>
            <?php foreach ($branchRows as $branch): ?>
              <option value="<?= (int)$branch['branch_id'] ?>"><?= htmlspecialchars($branch['branch_name'] . ' (' . $branch['branch_code'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" id="organizationGroup">
          <label for="classOrganization">Organisation</label>
          <select class="form-control" id="classOrganization">
            <option value="">No organisation assigned</option>
            <?php foreach ($organizationRows as $organization): ?>
              <option value="<?= (int)$organization['organization_id'] ?>"><?= htmlspecialchars($organization['organization_name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="classDay">Day</label>
          <select class="form-control" id="classDay">
            <option value="">Not set</option>
            <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $dayOption): ?>
              <option value="<?= $dayOption ?>"><?= $dayOption ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="startTime">Start Time</label>
          <input class="form-control" type="time" id="startTime">
        </div>
        <div class="form-group">
          <label for="endTime">End Time</label>
          <input class="form-control" type="time" id="endTime">
        </div>
        <div class="form-group full-width"><p class="form-hint">Category and Class Type follow the database fields. BAU classes can be linked to a branch, while B2B classes can be linked to an organisation. These links are optional.</p></div>
      </div>
      <div class="form-actions">
        <button class="secondary" type="button" data-close>Cancel</button>
        <button class="primary" type="submit" id="saveClass">Save Class</button>
      </div>
    </form>
  </div>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script>
let classes = <?php echo json_encode($dbClasses, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const $ = id => document.getElementById(id);
let activeCategory = "All";
let activeType = "All";
let editingId = null;

const palette = {
  BAU:{tint:"#eaf2ff",accent:"#2878f5",icon:"🎓",label:"BAU Programme"},
  B2B:{tint:"#f1eaff",accent:"#8b5cf6",icon:"🏫",label:"B2B Programme"},
  Online:{tint:"#e3f8ef",accent:"#159b69",icon:"🤖",label:"Online Class"}
};

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g,char => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[char]));
}
function toast(message) {
  $("toast").textContent = message;
  $("toast").classList.add("show");
  clearTimeout(toast.timer);
  toast.timer = setTimeout(()=>$("toast").classList.remove("show"),3000);
}
function count(filter) {
  if (filter === "Online") return classes.filter(item=>item.type==="Online").length;
  return classes.filter(item=>item.category===filter).length;
}
function updateCounts() {
  const total = Math.max(classes.length,1);
  [["BAU","bauCount","bauFill"],["B2B","b2bCount","b2bFill"],["Online","onlineCount","onlineFill"]].forEach(([category,countId,fillId])=>{
    $(countId).textContent = count(category);
    $(fillId).style.setProperty("--width",(count(category)/total*100)+"%");
  });
  $("allTabCount").textContent = classes.length;
  $("bauTabCount").textContent = count("BAU");
  $("b2bTabCount").textContent = count("B2B");
  $("onlineTabCount").textContent = count("Online");
}
function matchesTab(item) {
  const categoryMatch = activeCategory === "All" || item.category === activeCategory;
  const typeMatch = activeType === "All" || item.type === activeType;
  return categoryMatch && typeMatch;
}
function filteredClasses() {
  const query = $("search").value.trim().toLowerCase();
  const status = $("statusFilter").value;
  return classes.filter(item =>
    matchesTab(item) &&
    (!status || item.status===status) &&
    (!query || [item.code,item.name,item.location,item.category,item.type].join(" ").toLowerCase().includes(query))
  );
}
function renderClasses() {
  updateCounts();
  const rows = filteredClasses();
  const titleParts = [];
  if (activeCategory !== "All") titleParts.push(activeCategory);
  if (activeType !== "All") titleParts.push(activeType);
  $("resultsTitle").textContent = titleParts.length ? titleParts.join(" · ") + " Classes" : "All Classes";
  $("resultsSubtitle").textContent =
    (activeCategory === "All" && activeType === "All") ? "Browse all JAZRO learning programmes." :
    "Showing classes that match the selected category and class type.";
  $("resultsCount").textContent = rows.length+" class"+(rows.length===1?"":"es");
  if (!rows.length) {
    $("classGrid").innerHTML = `<div class="empty"><strong>No classes found</strong>Try another search term or change your filters.</div>`;
    return;
  }
  $("classGrid").innerHTML = rows.map(item => {
    const style = palette[item.category] || palette.BAU;
    const statusClass = item.status==="Active" ? "status-active" : item.status==="Completed" ? "status-completed" : "status-inactive";
    const subtitle = `${item.category} · ${item.type}`;
    return `
      <article class="class-card" style="--tint:${style.tint};--accent:${style.accent}">
        <div class="card-top"><div class="class-icon">${style.icon}</div><span class="type-pill">${esc(subtitle)}</span></div>
        <h3>${esc(item.name)}</h3>
        <div class="class-subtitle">Class Code: ${esc(item.code)}</div>
        <div class="card-meta">
          <div class="meta-row"><span class="meta-icon">⌖</span><span>${esc(item.location||"Location not assigned")}</span></div>
          <div class="meta-row"><span class="meta-icon">◷</span><span>${esc(item.day||"Not set")} · ${esc(item.time||"Not set")}</span></div>
        </div>
        <div class="card-bottom">
          <span class="status ${statusClass}">${esc(item.status)}</span>
          <div class="card-actions">
            <button class="action" title="Edit class" aria-label="Edit class" data-action="edit" data-id="${esc(item.id)}">✎</button>
            <button class="action delete" title="Delete class" aria-label="Delete class" data-action="delete" data-id="${esc(item.id)}">×</button>
          </div>
        </div>
      </article>`;
  }).join("");
}
function openModal() {
  $("classModal").classList.add("show");
  document.body.style.overflow = "hidden";
}
function closeModal() {
  $("classModal").classList.remove("show");
  document.body.style.overflow = "";
}
function updateLocationFields() {
  const isB2B = $("classCategory").value === "B2B";
  $("branchGroup").style.display = isB2B ? "none" : "flex";
  $("organizationGroup").style.display = isB2B ? "flex" : "none";
  if (isB2B) $("classBranch").value = "";
  else $("classOrganization").value = "";
}
function addClass() {
  editingId = null;
  $("classForm").reset();
  $("classCode").readOnly = false;
  $("modalTitle").textContent = "Add Class";
  $("modalSubtitle").textContent = "Enter the class details below.";
  $("saveClass").textContent = "Save Class";
  updateLocationFields();
  openModal();
}
function editClass(id) {
  const item = classes.find(row=>row.id===id);
  if (!item) return;
  editingId = id;
  $("classCode").value = item.code;
  $("classCode").readOnly = false;
  $("classCategory").value = item.category;
  $("className").value = item.name;
  $("classType").value = item.type;
  $("classStatus").value = item.status;
  $("classDay").value = item.day || "";
  $("startTime").value = item.startTime || "";
  $("endTime").value = item.endTime || "";
  $("classBranch").value = item.branchId || "";
  $("classOrganization").value = item.organizationId || "";
  $("modalTitle").textContent = "Edit Class";
  $("modalSubtitle").textContent = "Update class details.";
  $("saveClass").textContent = "Save Changes";
  updateLocationFields();
  openModal();
}
async function sendAction(action, payload) {
  const response = await fetch(`classes.php?action=${encodeURIComponent(action)}`, {
    method:"POST",
    headers:{"Content-Type":"application/json"},
    body:JSON.stringify(payload)
  });
  const result = await response.json();
  if (!response.ok || !result.success) throw new Error(result.message || "The request could not be completed.");
  return result;
}

$("addClass").addEventListener("click",addClass);
$("classCategory").addEventListener("change",updateLocationFields);
$("classGrid").addEventListener("click",async event=>{
  const button = event.target.closest("[data-action]");
  if (!button) return;
  const id = button.dataset.id;
  if (button.dataset.action==="edit") {
    editClass(id);
    return;
  }
  if (button.dataset.action==="delete") {
    const item = classes.find(row=>row.id===id);
    if (!item || !confirm(`Delete this class: ${item.name}? This may be blocked if the class is linked to sessions, students or records.`)) return;
    button.disabled = true;
    try {
      const result = await sendAction("delete",{id});
      classes = classes.filter(row=>row.id!==id);
      renderClasses();
      toast(result.message);
    } catch (error) {
      toast(error.message);
      button.disabled = false;
    }
  }
});
$("classForm").addEventListener("submit",async event=>{
  event.preventDefault();
  const payload = {
    id:editingId,
    code:$("classCode").value.trim(),
    name:$("className").value.trim(),
    category:$("classCategory").value,
    type:$("classType").value,
    status:$("classStatus").value,
    day:$("classDay").value,
    startTime:$("startTime").value,
    endTime:$("endTime").value,
    branchId:$("classBranch").value,
    organizationId:$("classOrganization").value
  };
  if (!payload.code || !payload.name) {
    toast("Please enter a class code and name.");
    return;
  }
  $("saveClass").disabled = true;
  $("saveClass").textContent = "Saving...";
  try {
    const result = await sendAction("save",payload);
    closeModal();
    toast(result.message);
    window.location.reload();
  } catch (error) {
    toast(error.message);
  } finally {
    $("saveClass").disabled = false;
    $("saveClass").textContent = editingId ? "Save Changes" : "Save Class";
  }
});
document.querySelectorAll("[data-close]").forEach(button=>button.addEventListener("click",closeModal));
$("classModal").addEventListener("click",event=>{if(event.target===$("classModal")) closeModal();});
document.addEventListener("keydown",event=>{if(event.key==="Escape" && $("classModal").classList.contains("show")) closeModal();});
$("search").addEventListener("input",renderClasses);
$("statusFilter").addEventListener("change",renderClasses);
document.querySelectorAll("[data-category]").forEach(button=>button.addEventListener("click",()=>{
  activeCategory=button.dataset.category;
  document.querySelectorAll("[data-category]").forEach(tab=>tab.classList.toggle("active",tab===button));
  renderClasses();
}));
document.querySelectorAll("[data-type]").forEach(button=>button.addEventListener("click",()=>{
  activeType=button.dataset.type;
  document.querySelectorAll("[data-type]").forEach(tab=>tab.classList.toggle("active",tab===button));
  renderClasses();
}));
$("reset").addEventListener("click",()=>{
  $("search").value="";
  $("statusFilter").value="";
  activeCategory="All";
  activeType="All";
  document.querySelectorAll("[data-category]").forEach(tab=>tab.classList.toggle("active",tab.dataset.category==="All"));
  document.querySelectorAll("[data-type]").forEach(tab=>tab.classList.toggle("active",tab.dataset.type==="All"));
  renderClasses();
  toast("Filters reset.");
});
$("mobileMenu").addEventListener("click",()=>{
  $("sidebar").classList.toggle("open");
  $("backdrop").classList.toggle("show");
});
$("backdrop").addEventListener("click",()=>{
  $("sidebar").classList.remove("open");
  $("backdrop").classList.remove("show");
});
$("notifications").addEventListener("click",()=>toast("No new notifications."));
renderClasses();
</script>
</body>
</html>
