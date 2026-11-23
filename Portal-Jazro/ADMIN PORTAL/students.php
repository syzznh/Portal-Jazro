<?php
require_once 'db.php';


/* Delete a student without deleting the shared parent account or historical records. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_student') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $studentId = filter_var($_POST['student_id'] ?? '', FILTER_VALIDATE_INT);
        if (!$studentId || $studentId < 1) {
            throw new RuntimeException('Invalid student record.');
        }

        $pdo->beginTransaction();

        $checkStudent = $pdo->prepare("SELECT student_id FROM student WHERE student_id = ? FOR UPDATE");
        $checkStudent->execute([$studentId]);
        if (!$checkStudent->fetchColumn()) {
            throw new RuntimeException('Student record was not found.');
        }

        // Preserve every historical record. Check all known tables that reference
        // student_id before removing even the enrollment links.
        $studentHistoryTables = [
            'attendance' => 'attendance records',
            'certificate' => 'certificates',
            'payment' => 'payment records',
            'report_card' => 'report cards',
            'result_assessment' => 'assessment results',
            'student_blooket' => 'Blooket progress'
        ];

        foreach ($studentHistoryTables as $tableName => $recordLabel) {
            $historyCheck = $pdo->prepare("SELECT COUNT(*) FROM {$tableName} WHERE student_id = ?");
            $historyCheck->execute([$studentId]);
            if ((int)$historyCheck->fetchColumn() > 0) {
                throw new RuntimeException(
                    "This student has existing {$recordLabel}. The student cannot be deleted so that historical records are preserved."
                );
            }
        }

        // No historical records are linked, so it is safe to remove enrollment links.
        $deleteEnrollments = $pdo->prepare("DELETE FROM enrollment WHERE student_id = ?");
        $deleteEnrollments->execute([$studentId]);

        $deleteStudent = $pdo->prepare("DELETE FROM student WHERE student_id = ?");
        $deleteStudent->execute([$studentId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully.']);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $error instanceof RuntimeException
                ? $error->getMessage()
                : 'This student has related records (such as attendance, assessment or certificates) and cannot be deleted. The records were kept unchanged.'
        ]);
    }
    exit;
}

/* Create a student, linking an existing parent or creating a new parent account. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_student') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $studentName = trim($_POST['student_name'] ?? '');
        $ageValue = trim($_POST['age'] ?? '');
        $icNumber = trim($_POST['ic_number'] ?? '');
        $schoolName = trim($_POST['school_name'] ?? '');
        $gender = strtolower(trim($_POST['gender'] ?? ''));
        $parentMode = $_POST['parent_mode'] ?? 'existing';
        $parentId = filter_var($_POST['existing_parent_id'] ?? '', FILTER_VALIDATE_INT);
        $newParentName = trim($_POST['new_parent_name'] ?? '');
        $newParentPhone = trim($_POST['new_parent_phone'] ?? '');
        $newParentEmail = strtolower(trim($_POST['new_parent_email'] ?? ''));
        $classId = trim($_POST['class_id'] ?? '');

        if ($studentName === '' || !in_array($gender, ['boy', 'girl'], true)) {
            throw new RuntimeException('Please enter the student name and select a gender.');
        }
        if ($ageValue !== '' && (!ctype_digit($ageValue) || (int)$ageValue < 1 || (int)$ageValue > 100)) {
            throw new RuntimeException('Please enter a valid age.');
        }
        if (!in_array($parentMode, ['existing', 'new'], true)) {
            throw new RuntimeException('Please select a valid parent option.');
        }
        if ($parentMode === 'existing' && !$parentId) {
            throw new RuntimeException('Please select an existing parent account.');
        }
        if ($parentMode === 'new') {
            if ($newParentName === '' || $newParentEmail === '') {
                throw new RuntimeException('Please enter the new parent name and email address.');
            }
            if (!filter_var($newParentEmail, FILTER_VALIDATE_EMAIL) || strlen($newParentEmail) > 100) {
                throw new RuntimeException('Please enter a valid parent email (maximum 100 characters).');
            }
        }
        if ($classId !== '' && !ctype_digit($classId)) {
            throw new RuntimeException('Please select a valid class.');
        }

        $pdo->beginTransaction();

        if ($parentMode === 'existing') {
            $parentCheck = $pdo->prepare("SELECT parent_id FROM parent WHERE parent_id = ? FOR UPDATE");
            $parentCheck->execute([$parentId]);
            if (!$parentCheck->fetchColumn()) {
                throw new RuntimeException('The selected parent account could not be found.');
            }
            $resolvedParentId = $parentId;
        } else {
            $emailCheck = $pdo->prepare("SELECT user_id FROM user WHERE email = ? LIMIT 1 FOR UPDATE");
            $emailCheck->execute([$newParentEmail]);
            if ($emailCheck->fetchColumn()) {
                throw new RuntimeException('This email already has an account. Please select the existing parent instead.');
            }

            // Follow the existing Parent Portal database convention for default credentials.
            $createUser = $pdo->prepare("
                INSERT INTO user (password, email, role, username)
                VALUES (?, ?, 'PARENT', ?)
            ");
            $createUser->execute(['parents123jazro', $newParentEmail, $newParentEmail]);
            $newUserId = (int)$pdo->lastInsertId();

            $createParent = $pdo->prepare("
                INSERT INTO parent (user_id, phone_number, parent_name)
                VALUES (?, ?, ?)
            ");
            $createParent->execute([
                $newUserId,
                $newParentPhone !== '' ? $newParentPhone : null,
                $newParentName
            ]);
            $resolvedParentId = (int)$pdo->lastInsertId();
        }

        $insertStudent = $pdo->prepare("
            INSERT INTO student
                (parent_id, organization_id, student_name, age, ic_number, school_name, gender)
            VALUES (?, NULL, ?, ?, ?, ?, ?)
        ");
        $insertStudent->execute([
            $resolvedParentId,
            $studentName,
            $ageValue === '' ? null : (int)$ageValue,
            $icNumber !== '' ? $icNumber : null,
            $schoolName !== '' ? $schoolName : null,
            $gender
        ]);
        $newStudentId = (int)$pdo->lastInsertId();

        if ($classId !== '') {
            $classCheck = $pdo->prepare("SELECT class_id FROM classes WHERE class_id = ? FOR UPDATE");
            $classCheck->execute([(int)$classId]);
            if (!$classCheck->fetchColumn()) {
                throw new RuntimeException('The selected class could not be found.');
            }

            $insertEnrollment = $pdo->prepare("
                INSERT INTO enrollment (student_id, class_id, enrollment_date, status)
                VALUES (?, ?, CURDATE(), 'Active')
            ");
            $insertEnrollment->execute([$newStudentId, (int)$classId]);
        }

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Student added successfully.',
            'student_id' => $newStudentId
        ]);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $error instanceof RuntimeException
                ? $error->getMessage()
                : 'Unable to add student. Please check the information and database connection.'
        ]);
    }
    exit;
}

/* Save edits to an existing student. Parent changes update the shared parent account. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_student') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $studentName = trim($_POST['student_name'] ?? '');
        $ageValue = trim($_POST['age'] ?? '');
        $icNumber = trim($_POST['ic_number'] ?? '');
        $schoolName = trim($_POST['school_name'] ?? '');
        $gender = strtolower(trim($_POST['gender'] ?? ''));
        $parentName = trim($_POST['parent_name'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (!$studentId || $studentName === '' || $parentName === '') {
            throw new RuntimeException('Please complete the required fields.');
        }
        if ($ageValue !== '' && (!ctype_digit($ageValue) || (int)$ageValue < 1 || (int)$ageValue > 100)) {
            throw new RuntimeException('Please enter a valid age.');
        }
        if (!in_array($gender, ['boy', 'girl'], true)) {
            throw new RuntimeException('Please select a valid gender.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid email address.');
        }

        $pdo->beginTransaction();

        $lookup = $pdo->prepare("
            SELECT s.parent_id, p.user_id
            FROM student s
            LEFT JOIN parent p ON p.parent_id = s.parent_id
            WHERE s.student_id = ?
            FOR UPDATE
        ");
        $lookup->execute([$studentId]);
        $parent = $lookup->fetch(PDO::FETCH_ASSOC);

        if (!$parent) {
            throw new RuntimeException('Student record was not found.');
        }

        $updateStudent = $pdo->prepare("
            UPDATE student
            SET student_name = ?, age = ?, ic_number = ?, school_name = ?, gender = ?
            WHERE student_id = ?
        ");
        $updateStudent->execute([
            $studentName,
            $ageValue === '' ? null : (int)$ageValue,
            $icNumber === '' ? null : $icNumber,
            $schoolName === '' ? null : $schoolName,
            $gender,
            $studentId
        ]);

        if (!empty($parent['parent_id'])) {
            $updateParent = $pdo->prepare("
                UPDATE parent SET parent_name = ?, phone_number = ?
                WHERE parent_id = ?
            ");
            $updateParent->execute([
                $parentName,
                $phoneNumber === '' ? null : $phoneNumber,
                $parent['parent_id']
            ]);

            if (!empty($parent['user_id']) && $email !== '') {
                $updateEmail = $pdo->prepare("UPDATE user SET email = ? WHERE user_id = ?");
                $updateEmail->execute([$email, $parent['user_id']]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Student details updated successfully.']);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $error instanceof RuntimeException
                ? $error->getMessage()
                : 'Unable to update student details. Please check the database fields.'
        ]);
    }
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT
            s.student_id,
            s.student_name,
            s.age,
            s.ic_number,
            s.school_name,
            s.gender,
            p.parent_name,
            p.phone_number,
            u.email,
            GROUP_CONCAT(DISTINCT c.class_name ORDER BY c.class_name SEPARATOR ', ') AS class_names,
            GROUP_CONCAT(DISTINCT c.class_id ORDER BY c.class_id SEPARATOR ',') AS class_ids,
            GROUP_CONCAT(
                DISTINCT CASE
                    WHEN c.class_type = 'Online' THEN 'Online'
                    ELSE c.class_category
                END
                SEPARATOR ', '
            ) AS class_categories,
            MAX(CASE WHEN e.status = 'Active' THEN 1 ELSE 0 END) AS has_active_enrollment
        FROM student s
        LEFT JOIN parent p ON s.parent_id = p.parent_id
        LEFT JOIN user u ON p.user_id = u.user_id
        LEFT JOIN enrollment e ON s.student_id = e.student_id
        LEFT JOIN classes c ON e.class_id = c.class_id
        GROUP BY s.student_id
        ORDER BY s.student_name ASC
    ");

    $students = $stmt->fetchAll();

    $parentStmt = $pdo->query("
        SELECT p.parent_id, p.parent_name, p.phone_number, u.email
        FROM parent p
        INNER JOIN user u ON p.user_id = u.user_id
        WHERE u.role = 'PARENT'
        ORDER BY p.parent_name ASC
    ");
    $parentOptions = $parentStmt->fetchAll(PDO::FETCH_ASSOC);

    $classStmt = $pdo->query("
        SELECT class_id, class_name, class_code, class_type, class_category
        FROM classes
        WHERE status = 'Active'
        ORDER BY class_category ASC, class_name ASC
    ");
    $dbClassOptions = $classStmt->fetchAll(PDO::FETCH_ASSOC);

    // Include inactive classes in the directory filter so existing enrollments
    // remain searchable even when a class is no longer available for new students.
    $classFilterStmt = $pdo->query("
        SELECT class_id, class_name, class_type, class_category
        FROM classes
        ORDER BY class_category ASC, class_name ASC
    ");
    $classFilterOptions = $classFilterStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Unable to load student records.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students | JAZRO Admin Portal</title>

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
            --red: #e05260;
            --purple: #8b5cf6;
            --orange: #e99a35;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            color: var(--navy);
            background: var(--bg);
        }

        button,
        input,
        select {
            font: inherit;
        }

        button {
            cursor: pointer;
        }

        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 100;
            width: 245px;
            background: #fff;
            border-right: 1px solid var(--line);
            padding: 24px 15px 17px;
            display: flex;
            flex-direction: column;
            transition: transform .25s ease;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            min-height: 72px;
            padding: 0 7px 20px;
            border-bottom: 1px solid var(--line);
            text-decoration: none;
            flex-shrink: 0;
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

        .nav-label {
            margin: 26px 10px 12px;
            color: #91a2bb;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.6px;
        }

        .sidebar-menu {
            display: grid;
            gap: 6px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 43px;
            padding: 11px 13px;
            border-radius: 10px;
            color: #60738f;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: .2s ease;
        }

        .sidebar-menu a:hover {
            background: #f0f6ff;
            color: var(--blue);
        }

        .sidebar-menu a.active {
            background: #e8f2ff;
            color: var(--blue);
            box-shadow: inset 3px 0 var(--blue);
        }

        .nav-icon {
            width: 23px;
            height: 23px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 7px;
            background: #f0f6ff;
            font-size: 14px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 18px;
            border-top: 1px solid var(--line);
        }

        .main-area {
            margin-left: 245px;
            min-height: 100vh;
        }

        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            height: 72px;
            padding: 0 30px;
            background: rgba(255,255,255,.96);
            border-bottom: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(12px);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-page {
            font-size: 12px;
            color: var(--muted);
        }

        .topbar-page strong {
            color: var(--navy);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .icon-button {
            position: relative;
            width: 37px;
            height: 37px;
            border: 1px solid var(--line);
            border-radius: 11px;
            background: #fff;
            color: #71829e;
            font-size: 17px;
        }

        .notification-dot {
            position: absolute;
            width: 7px;
            height: 7px;
            background: #f15d75;
            border-radius: 50%;
            top: 7px;
            right: 7px;
            border: 1px solid #fff;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: #e6efff;
            color: var(--blue);
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 13px;
        }

        .admin-name {
            font-size: 12px;
            font-weight: 700;
        }

        .admin-role {
            font-size: 10px;
            color: var(--muted);
            margin-top: 4px;
        }

        .mobile-menu {
            display: none;
            width: 36px;
            height: 36px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: #fff;
            color: var(--navy);
        }

        .page-content {
            max-width: 1700px;
            padding: 28px 30px 35px;
            margin: 0 auto;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 24px;
        }

        .eyebrow {
            color: var(--blue);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            margin-bottom: 9px;
        }

        .page-heading h1 {
            margin: 0;
            font-size: 27px;
            letter-spacing: -1px;
            font-weight: 800;
        }

        .page-heading p {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
            margin: 9px 0 0;
        }

        .primary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 10px;
            background: var(--blue);
            color: white;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 5px 12px #2878f526;
            transition: .2s;
            white-space: nowrap;
        }

        .primary-btn:hover {
            background: var(--blue-dark);
            transform: translateY(-1px);
        }

        .secondary-btn {
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 10px 13px;
            background: white;
            color: #627590;
            font-size: 11px;
            font-weight: 600;
        }

        .secondary-btn:hover {
            background: #f5f8ff;
        }

        .overview-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 21px;
        }

        .overview-card {
            position: relative;
            overflow: hidden;
            padding: 19px;
            min-height: 142px;
            border: 1px solid var(--line);
            border-radius: 15px;
            background: white;
        }

        .overview-card::after {
            content: "";
            position: absolute;
            width: 95px;
            height: 95px;
            border-radius: 50%;
            right: -32px;
            bottom: -40px;
            background: var(--tint, #eef4ff);
        }

        .overview-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .overview-label {
            color: #7b8ca7;
            font-size: 11px;
            font-weight: 600;
        }

        .overview-icon {
            width: 37px;
            height: 37px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: var(--tint, #eef4ff);
            color: var(--accent, var(--blue));
            font-size: 18px;
        }

        .overview-number {
            margin-top: 12px;
            font-size: 29px;
            font-weight: 800;
            letter-spacing: -1.1px;
        }

        .overview-foot {
            margin-top: 6px;
            color: #94a1b5;
            font-size: 10px;
        }

        .overview-foot strong {
            color: var(--accent, var(--blue));
        }

        .insights-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.65fr) minmax(260px, 1fr);
            gap: 17px;
            margin-bottom: 23px;
        }

        .panel {
            min-width: 0;
            border: 1px solid var(--line);
            border-radius: 15px;
            background: white;
        }

        .panel-header {
            padding: 19px 20px 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .panel-title {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
        }

        .panel-description {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 10.5px;
            line-height: 1.6;
        }

        .text-link {
            border: 0;
            background: none;
            color: var(--blue);
            font-size: 10.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .distribution-body {
            padding: 0 20px 20px;
        }

        .distribution-summary {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 12px;
            margin: 5px 0 17px;
        }

        .distribution-total {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .distribution-caption {
            color: var(--muted);
            font-size: 10px;
            margin-top: 4px;
        }

        .distribution-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 10px;
            color: #7c8eaa;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 3px;
            background: var(--dot);
        }


        /* Dashboard visual refresh: clearer statistic cards and a compact donut chart. */
        .overview-card {
            padding: 20px 21px 18px;
            min-height: 158px;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .overview-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(35, 83, 145, .07);
        }

        .overview-number {
            font-size: 31px;
            margin-top: 15px;
        }

        .overview-foot {
            position: relative;
            z-index: 1;
            margin-top: 5px;
        }

        .overview-progress {
            position: relative;
            z-index: 1;
            height: 5px;
            margin-top: 13px;
            overflow: hidden;
            border-radius: 20px;
            background: #edf2fa;
        }

        .overview-progress span {
            display: block;
            width: var(--progress, 0%);
            height: 100%;
            border-radius: inherit;
            background: var(--accent, var(--blue));
            transition: width .35s ease;
        }

        .overview-foot strong {
            font-weight: 800;
        }

        .distribution-body {
            padding: 0 22px 23px;
        }

        .distribution-summary {
            align-items: center;
            margin: 2px 0 14px;
        }

        .distribution-visual {
            display: grid;
            grid-template-columns: minmax(145px, .8fr) minmax(0, 1.2fr);
            align-items: center;
            gap: 24px;
            padding: 8px 0 3px;
        }

        .distribution-donut-wrap {
            display: grid;
            place-items: center;
            min-height: 190px;
        }

        .distribution-donut {
            width: 166px;
            height: 166px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: conic-gradient(#eaf0f8 0deg 360deg);
            position: relative;
        }

        .distribution-donut::before {
            content: "";
            position: absolute;
            inset: 17px;
            border-radius: 50%;
            background: #fff;
        }

        .donut-center {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .donut-center strong {
            color: var(--navy);
            font-size: 25px;
            letter-spacing: -1px;
        }

        .donut-center span {
            color: var(--muted);
            font-size: 10px;
        }

        .distribution-breakdown {
            display: grid;
            gap: 17px;
        }

        .distribution-category {
            display: grid;
            gap: 7px;
        }

        .distribution-category-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            font-size: 11px;
        }

        .distribution-category-name {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
        }

        .distribution-category-name span {
            width: 9px;
            height: 9px;
            border-radius: 3px;
            background: var(--category-color);
        }

        .distribution-category-value {
            color: var(--muted);
            font-size: 10px;
            white-space: nowrap;
        }

        .distribution-category .bar-track {
            height: 7px;
        }

        /* Clean, restrained dashboard cards and monochrome class distribution */
        .overview-grid {
            gap: 16px;
            margin-bottom: 22px;
        }

        .overview-card {
            display: flex;
            align-items: center;
            gap: 17px;
            min-height: 132px;
            padding: 22px 23px;
            border-radius: 14px;
            border: 1px solid #e3ebf6;
            background: #fff;
            box-shadow: 0 3px 12px rgba(35, 67, 110, .025);
            transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease;
        }

        .overview-card::after { display: none; }
        .overview-card:hover {
            transform: translateY(-2px);
            border-color: #d5e2f3;
            box-shadow: 0 9px 22px rgba(35, 67, 110, .06);
        }

        .overview-icon {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            border-radius: 13px;
            background: #f0f5fc;
            color: #3975c8;
            font-size: 20px;
        }

        .overview-content { min-width: 0; }
        .overview-label {
            color: #71829b;
            font-size: 11px;
            font-weight: 600;
        }
        .overview-number {
            margin-top: 7px;
            color: #193b68;
            font-size: 30px;
            line-height: 1.1;
            letter-spacing: -1px;
        }
        .overview-foot {
            margin-top: 6px;
            color: #94a1b5;
            font-size: 10px;
        }
        .overview-foot span {
            color: #3975c8;
            font-weight: 800;
        }
        .overview-progress { display: none !important; }

        .distribution-body { padding: 0 22px 23px; }
        .distribution-summary {
            align-items: center;
            margin: 2px 0 20px;
        }
        .distribution-total {
            color: #193b68;
            font-size: 27px;
        }
        .distribution-breakdown {
            display: grid;
            gap: 18px;
        }
        .distribution-category { display: grid; gap: 8px; }
        .distribution-category-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-size: 11px;
        }
        .distribution-category-name {
            color: #294766;
            font-weight: 700;
        }
        .distribution-category-name span { display: none; }
        .distribution-category-value {
            color: #7d8da5;
            font-size: 10px;
            white-space: nowrap;
        }
        .distribution-category .bar-track {
            height: 8px;
            background: #edf2f8;
            border-radius: 20px;
        }
        .distribution-category .bar-fill {
            background: #6485ad !important;
            border-radius: 20px;
        }

        @media (max-width: 650px) {
            .distribution-visual {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .distribution-donut-wrap {
                min-height: 175px;
            }

            .distribution-breakdown {
                gap: 13px;
            }
        }

        .distribution-bars {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .bar-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            font-size: 10.5px;
            margin-bottom: 7px;
        }

        .bar-heading strong {
            font-weight: 700;
        }

        .bar-heading span {
            color: var(--muted);
        }

        .bar-track {
            height: 7px;
            background: #edf3fc;
            border-radius: 20px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            width: var(--width, 0%);
            background: var(--bar-color, var(--blue));
            border-radius: inherit;
            transition: width .35s ease;
        }

        .recent-list {
            padding: 0 20px 17px;
        }

        .recent-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 12px 0;
            border-bottom: 1px solid #edf2fa;
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .student-avatar {
            width: 37px;
            height: 37px;
            border-radius: 50%;
            display: block;
            object-fit: cover;
            flex-shrink: 0;
            background: #eaf2ff;
        }

        .recent-details {
            min-width: 0;
            flex: 1;
        }

        .recent-name {
            font-size: 11px;
            font-weight: 700;
        }

        .recent-meta {
            color: var(--muted);
            font-size: 10px;
            margin-top: 5px;
        }

        .directory-panel {
            overflow: hidden;
        }

        .directory-toolbar {
            padding: 18px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid var(--line);
        }

        .directory-toolbar h2 {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
        }

        .directory-toolbar p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 10.5px;
        }

        .directory-filters {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 9px;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 210px;
            padding: 0 11px;
            height: 37px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: #fff;
        }

        .search-box span {
            color: #8c9cb3;
            font-size: 15px;
        }

        .search-box input {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: 0;
            color: var(--navy);
            background: transparent;
            font-size: 11px;
        }

        .filter-select {
            height: 37px;
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 0 10px;
            outline: none;
            color: #627590;
            background: #fff;
            font-size: 11px;
        }

        .table-wrap {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        .student-table {
            width: 100%;
            max-width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            white-space: normal;
        }

        .student-table th,
        .student-table td {
            overflow-wrap: anywhere;
            word-break: normal;
        }

        .student-table th:nth-child(1) { width: 27%; }
        .student-table th:nth-child(2) { width: 18%; }
        .student-table th:nth-child(3) { width: 19%; }
        .student-table th:nth-child(4) { width: 13%; }
        .student-table th:nth-child(5) { width: 11%; }
        .student-table th:nth-child(6) { width: 12%; }

        .student-table td:last-child,
        .student-table th:last-child {
            white-space: nowrap;
        }

        .row-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .row-actions .action-btn {
            flex: 0 0 34px;
        }

        .student-table th {
            padding: 13px 20px;
            text-align: left;
            color: #8292aa;
            background: #f8faff;
            border-bottom: 1px solid var(--line);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .8px;
            text-transform: uppercase;
        }

        .student-table td {
            padding: 14px 20px;
            border-bottom: 1px solid #edf2fa;
            font-size: 11px;
            vertical-align: middle;
        }

        .student-table tbody tr:hover {
            background: #fbfdff;
        }

        .student-table tbody tr:last-child td {
            border-bottom: none;
        }

        .student-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-cell .student-avatar {
            width: 39px;
            height: 39px;
        }

        .student-cell-name {
            font-size: 11px;
            font-weight: 700;
        }

        .student-cell-id {
            color: var(--muted);
            font-size: 10px;
            margin-top: 5px;
        }

        .class-tag {
            display: inline-flex;
            align-items: center;
            padding: 6px 9px;
            border-radius: 7px;
            background: #eef4ff;
            color: #3976d7;
            font-size: 10px;
            font-weight: 700;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
        }

        .status-badge::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-active {
            background: #e7f8f0;
            color: #159b69;
        }

        .status-inactive {
            background: #fff0f1;
            color: #d94a5a;
        }

        .row-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .action-btn {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: #71829e;
            font-size: 13px;
            transition: .2s;
        }

        .action-btn:hover {
            background: #eef4ff;
            color: var(--blue);
            border-color: #d5e4ff;
        }

        .action-btn.delete:hover {
            background: #fff0f1;
            color: var(--red);
            border-color: #ffd8dc;
        }

        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            border-top: 1px solid var(--line);
        }

        .table-info {
            color: var(--muted);
            font-size: 10px;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .page-btn {
            min-width: 30px;
            height: 30px;
            padding: 0 8px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: #71829e;
            font-size: 10px;
        }

        .page-btn.active {
            background: var(--blue);
            border-color: var(--blue);
            color: #fff;
        }

        .page-btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .page-count {
    font-size: 11px;
    color: var(--muted);
    padding: 0 8px;
    white-space: nowrap;
}

        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }

        .empty-state strong {
            display: block;
            margin-bottom: 6px;
            color: var(--navy);
            font-size: 13px;
        }

        /* FIX: hidden modal overlays must stay hidden on initial page load */
        .modal-overlay[hidden] {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        .modal-overlay:not([hidden]) {
            display: flex;
            visibility: visible;
            pointer-events: auto;
        }

        /* Keep the student edit modal centered regardless of shared stylesheet rules. */
        #studentModal.modal-overlay:not([hidden]) {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        #studentModal .modal {
            margin: 0 auto !important;
        }

        #detailsModal.modal-overlay:not([hidden]) {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        #detailsModal .modal {
            margin: 0 auto !important;
        }



        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 500;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(18, 39, 72, .45);
            backdrop-filter: blur(4px);
        }

        .modal-overlay.show {
            display: flex;
        }

        /* Keep every modal hidden until JavaScript explicitly opens it. */
        .modal-overlay[hidden] {
            display: none !important;
        }

        .modal {
            width: 100%;
            max-width: 590px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 20px 60px #102d551f;
            animation: modalIn .2s ease;
        }

        .modal.modal-small {
            max-width: 440px;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: translateY(10px) scale(.99);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding: 21px 23px 17px;
            border-bottom: 1px solid var(--line);
        }

        .modal-header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
        }

        .modal-header p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.6;
        }

        .modal-close {
            width: 31px;
            height: 31px;
            flex-shrink: 0;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: #fff;
            color: #7d8da5;
            font-size: 17px;
        }

        .modal-body {
            padding: 21px 23px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: #647895;
            font-size: 10.5px;
            font-weight: 700;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            min-height: 41px;
            padding: 10px 12px;
            border: 1px solid #dfe8f5;
            border-radius: 9px;
            outline: none;
            color: var(--navy);
            background: #fff;
            font-size: 11px;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-group textarea {
            min-height: 75px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px #2878f515;
        }

        .form-hint {
            color: #94a1b5;
            font-size: 9.5px;
            line-height: 1.5;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            padding: 15px 23px 21px;
            border-top: 1px solid var(--line);
        }

        .details-profile {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 0 0 19px;
            border-bottom: 1px solid var(--line);
            margin-bottom: 18px;
        }

        .details-profile .student-avatar {
            width: 53px;
            height: 53px;
            border-radius: 50%;
            object-fit: cover;
        }

        .details-name {
            font-size: 15px;
            font-weight: 800;
        }

        .details-id {
            margin-top: 6px;
            color: var(--muted);
            font-size: 10.5px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        .detail-label {
            color: var(--muted);
            font-size: 10px;
            margin-bottom: 6px;
        }

        .detail-value {
            color: var(--navy);
            font-size: 11.5px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .toast {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 900;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: calc(100vw - 40px);
            padding: 13px 17px;
            border: 1px solid #d8f0e4;
            border-radius: 11px;
            background: #fff;
            color: #167b55;
            box-shadow: 0 10px 30px #17365c18;
            font-size: 11px;
            font-weight: 700;
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            transition: .25s;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .sidebar-backdrop {
            display: none;
        }

        @media (max-width: 1200px) {
            .student-table th,
            .student-table td {
                padding-left: 10px;
                padding-right: 10px;
                font-size: 10px;
            }

            .row-actions {
                gap: 4px;
            }

            .row-actions .action-btn {
                flex-basis: 29px;
                width: 29px;
                height: 29px;
            }

            .page-content {
                padding: 24px 22px 30px;
            }

            .admin-topbar {
                padding: 0 22px;
            }

            .directory-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .directory-filters {
                width: 100%;
            }

            .search-box {
                flex: 1;
            }
        }

        @media (max-width: 900px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            body.sidebar-open .admin-sidebar {
                transform: translateX(0);
            }

            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                z-index: 90;
                background: rgba(18, 39, 72, .35);
            }

            body.sidebar-open .sidebar-backdrop {
                display: block;
            }

            .main-area {
                margin-left: 0;
            }

            .mobile-menu {
                display: grid;
                place-items: center;
            }

            .insights-grid {
                grid-template-columns: 1fr;
            }

            .admin-topbar {
                height: 66px;
            }
        }

        @media (max-width: 650px) {
            .page-content {
                padding: 21px 14px 28px;
            }

            .admin-topbar {
                padding: 0 14px;
            }

            .page-heading {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .page-heading h1 {
                font-size: 23px;
            }

            .overview-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .overview-card {
                min-height: 120px;
            }

            .directory-filters {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                width: 100%;
                min-height: 38px;
            }

            .filter-select {
                width: 100%;
            }

            .table-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-grid,
            .details-grid {
                grid-template-columns: 1fr;
            }

            .topbar-right {
                gap: 9px;
            }

            .admin-profile > div:last-child {
                display: none;
            }

            .modal {
                max-height: 94vh;
            }
        }

        /* Shared admin shell: aligned with dashboard.html */
        body.app-body { margin: 0; background: var(--bg); color: #29415f; font-family: "Inter", Arial, sans-serif; font-size: 13px; }
        .admin-sidebar { width: 245px; box-sizing: border-box; padding: 24px 15px 17px; }
        .nav-caption { margin: 26px 10px 12px; color: #91a2bb; font-size: 9px; font-weight: 800; letter-spacing: 1.6px; }
        .admin-nav { display: grid; gap: 6px; }
        .admin-nav a, .logout-link { display: flex; align-items: center; gap: 12px; min-height: 43px; padding: 11px 13px; border-radius: 10px; color: #60738f; font-size: 12px; font-weight: 600; text-decoration: none; transition: .2s ease; }
        .admin-nav a:hover, .logout-link:hover { background: #f0f6ff; color: var(--blue); }
        .admin-nav a.active { background: #e8f2ff; color: var(--blue); box-shadow: inset 3px 0 var(--blue); }
        .sidebar-bottom { margin-top: auto; padding-top: 15px; border-top: 1px solid var(--line); }
        .sidebar-bottom small { display: block; padding: 14px 12px 0; color: #9aaac0; font-size: 9px; }
        .main-area { margin-left: 245px; min-height: 100vh; }
        .admin-topbar { height: 72px; min-height: 72px; padding: 12px 30px; box-sizing: border-box; }
        .page-content { width: 100%; max-width: 1700px; padding: 28px 30px 35px; box-sizing: border-box; }
        @media (max-width: 900px) {
          .admin-sidebar { transform: translateX(-105%); }
          body.sidebar-open .admin-sidebar { transform: translateX(0); }
          .main-area { margin-left: 0; }
          .admin-topbar { height: 66px; min-height: 66px; padding: 10px 15px; }
          .page-content { padding: 22px 15px 30px; }
        }

        /* Fix long student and parent names */
.student-table {
    table-layout: fixed !important;
    width: 100% !important;
}

.student-table th,
.student-table td {
    white-space: normal !important;
    overflow-wrap: anywhere !important;
    word-break: normal !important;
    vertical-align: middle;
}

.student-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    width: 100%;
}

.student-cell .student-avatar {
    flex: 0 0 39px;
}

.student-cell > div {
    min-width: 0;
    flex: 1;
}

.student-cell-name {
    white-space: normal !important;
    overflow-wrap: anywhere !important;
    line-height: 1.5;
}

.class-tag {
    white-space: normal !important;
    overflow-wrap: anywhere;
    line-height: 1.4;
}


        /* Brighter JAZRO dashboard palette: lively accents, without rainbow chart bars. */
        :root {
            --bg: #f1f6ff;
            --line: #dce8fb;
            --blue: #2878f5;
            --blue-dark: #1758dc;
        }

        .page-content { background: transparent; }

        .overview-card {
            position: relative;
            border: 1px solid #dce8fb;
            border-top: 3px solid var(--card-accent, #2878f5);
            background: linear-gradient(135deg, #ffffff 0%, var(--card-wash, #f7faff) 100%);
            box-shadow: 0 7px 22px rgba(40, 92, 160, .055);
        }
        .overview-card:nth-child(1) {
            --card-accent: #2878f5;
            --card-wash: #f2f7ff;
        }
        .overview-card:nth-child(2) {
            --card-accent: #13a879;
            --card-wash: #f0fbf7;
        }
        .overview-card:nth-child(3) {
            --card-accent: #8055ed;
            --card-wash: #f7f3ff;
        }
        .overview-icon {
            background: var(--card-icon-bg, #e7f0ff);
            color: var(--card-accent, #2878f5);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.8);
        }
        .overview-card:nth-child(1) .overview-icon { --card-icon-bg: #e3efff; }
        .overview-card:nth-child(2) .overview-icon { --card-icon-bg: #ddf7ec; }
        .overview-card:nth-child(3) .overview-icon { --card-icon-bg: #eee6ff; }
        .overview-label { color: #526b8d; }
        .overview-number { color: #123d70; }
        .overview-foot { color: #7186a5; }
        .overview-foot span { color: var(--card-accent, #2878f5); }

        .panel {
            border-color: #dce8fb;
            box-shadow: 0 7px 24px rgba(40, 92, 160, .045);
        }
        .panel-title { color: #123d70; font-size: 15px; }
        .panel-description { color: #788eae; }
        .distribution-total { color: #123d70; }
        .distribution-category-name { color: #24476f; }
        .distribution-category-value { color: #7186a5; }
        .distribution-category .bar-track {
            height: 9px;
            background: #e8f0fc;
            border: 1px solid #e0eafa;
        }
        .distribution-category .bar-fill {
            background: linear-gradient(90deg, #2878f5 0%, #4c96ff 100%) !important;
            box-shadow: 0 2px 6px rgba(40,120,245,.22);
        }
        .recent-item { border-bottom-color: #e9f0fb; }
        .recent-name { color: #173f70; }
        .recent-meta { color: #7186a5; }

        .primary-btn {
            background: linear-gradient(135deg, #3284ff 0%, #2169ed 100%);
            box-shadow: 0 7px 16px rgba(40,120,245,.22);
        }
        .primary-btn:hover {
            background: linear-gradient(135deg, #2878f5 0%, #1758dc 100%);
            box-shadow: 0 9px 20px rgba(40,120,245,.28);
        }

    </style>
</head>
<body class="app-body">

    <aside class="admin-sidebar" id="adminSidebar">
        <!-- JAZRO LOGO -->
<a class="admin-brand" href="dashboard.php">
    <img
        src="assets/images/jazro-logo.png"
        alt="JAZRO Robotic Academy"
        class="brand-logo"
    >
    <span>ADMIN PORTAL</span>
</a>

        <p class="nav-caption">MAIN MENU</p>

        <?php include __DIR__ . '/learning_activities_menu.php'; ?>

        <div class="sidebar-bottom">
            <a class="logout-link" href="index.php"><span class="nav-icon">↪</span> Log Out</a>
            <small>JAZRO ROBOTIC ACADEMY · 2026</small>
        </div>
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main-area">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-menu" id="mobileMenu" aria-label="Open navigation">☰</button>
                <div class="topbar-page">Main Menu&nbsp; / &nbsp;<strong>Students</strong></div>
            </div>

            <div class="topbar-right">
                <button class="icon-button" aria-label="Notifications">
                    ♧
                    <span class="notification-dot"></span>
                </button>

                <div class="admin-profile">
                    <div class="admin-avatar">AD</div>
                    <div>
                        <div class="admin-name">Admin</div>
                        <div class="admin-role">Administrator</div>
                    </div>
                </div>
            </div>
        </header>

        <main class="page-content">
            <div class="page-heading">
                <div>
                    <div class="eyebrow">STUDENT MANAGEMENT</div>
                    <h1>Students</h1>
                    <p>Manage student profiles, class assignments and student status.</p>
                </div>

                <button class="primary-btn" id="addStudentBtn">
                    <span>＋</span> Add Student
                </button>
            </div>

            <section class="overview-grid">
                <article class="overview-card overview-total">
                    <div class="overview-icon">♙</div>
                    <div class="overview-content">
                        <div class="overview-label">Total Students</div>
                        <div class="overview-number" id="totalStudents">0</div>
                        <div class="overview-foot">All registered students</div>
                    </div>
                </article>

                <article class="overview-card">
                    <div class="overview-icon">✓</div>
                    <div class="overview-content">
                        <div class="overview-label">Active Students</div>
                        <div class="overview-number" id="activeStudents">0</div>
                        <div class="overview-foot"><span id="activeStudentsRate">0%</span> of all registered students</div>
                    </div>
                </article>

                <article class="overview-card">
                    <div class="overview-icon">▤</div>
                    <div class="overview-content">
                        <div class="overview-label">Assigned to Classes</div>
                        <div class="overview-number" id="assignedStudents">0</div>
                        <div class="overview-foot"><span id="assignedStudentsRate">0%</span> of all registered students</div>
                    </div>
                </article>
            </section>

            <section class="insights-grid">
                <article class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Class Distribution</h2>
                            <p class="panel-description">Overview of students assigned to each class.</p>
                        </div>
                    </div>
                    <div class="distribution-body">
                        <div class="distribution-summary">
                            <div>
                                <div class="distribution-total" id="distributionTotal">0</div>
                                <div class="distribution-caption">Students assigned to classes</div>
                            </div>
                            <span class="distribution-caption">By programme category</span>
                        </div>
                        <div class="distribution-breakdown" id="distributionBars"></div>
                    </div>
                </article>

                <article class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Recently Added</h2>
                            <p class="panel-description">Latest student profiles added to the portal.</p>
                        </div>
                    </div>
                    <div class="recent-list" id="recentStudents"></div>
                </article>
            </section>

            <section class="panel directory-panel">
                <div class="directory-toolbar">
                    <div>
                        <h2>Student Directory</h2>
                        <p>View, search, edit and manage registered students.</p>
                    </div>

                    <div class="directory-filters">
                        <label class="search-box">
                            <span>⌕</span>
                            <input type="search" id="studentSearch" placeholder="Search student name or ID..." autocomplete="off">
                        </label>

                        <select class="filter-select" id="classFilter">
                            <option value="">All Classes</option>
                        </select>

                        <select class="filter-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Parent / Guardian</th>
                                <th>Class</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody"></tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <div class="table-info" id="tableInfo">Showing 0 students</div>
                    <div class="pagination" id="pagination"></div>
                </div>
            </section>
        </main>
    </div>

    <div class="modal-overlay" id="studentModal" hidden>
        <div class="modal">
            <div class="modal-header">
                <div>
                    <h2 id="studentModalTitle">Add Student</h2>
                    <p>Fill in the student information below.</p>
                </div>
                <button class="modal-close" type="button" data-close="studentModal" aria-label="Close">×</button>
            </div>

            <form id="studentForm">
                <div class="modal-body">
                    <input type="hidden" id="studentEditId">

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="studentName">Student Name *</label>
                            <input id="studentName" name="student_name" required placeholder="Enter full name">
                        </div>
                        <div class="form-group">
                            <label for="studentAge">Age</label>
                            <input id="studentAge" name="age" type="number" min="1" max="100" placeholder="Enter age">
                        </div>
                        <div class="form-group">
                            <label for="studentGender">Gender *</label>
                            <select id="studentGender" name="gender" required>
                                <option value="">Select gender</option>
                                <option value="boy">Boy</option>
                                <option value="girl">Girl</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="studentIC">IC Number</label>
                            <input id="studentIC" name="ic_number" placeholder="Enter IC number">
                        </div>
                        <div class="form-group">
                            <label for="studentSchool">School Name</label>
                            <input id="studentSchool" name="school_name" placeholder="Enter school name">
                        </div>
                        <div id="editParentFields" class="form-group full-width" style="display:none;">
                            <span class="form-hint" style="display:block;margin-bottom:10px;">Note: Parent details are shared by all children linked to this parent account. Changes here will also appear on their profiles.</span>
                            <label for="parentName">Parent / Guardian *</label>
                            <input id="parentName" name="parent_name" placeholder="Enter parent name">
                        </div>
                        <div id="editParentContactFields" class="form-group" style="display:none;">
                            <label for="studentContact">Parent Contact Number</label>
                            <input id="studentContact" name="phone_number" type="tel" placeholder="e.g. 0123456789">
                        </div>
                        <div id="editParentEmailFields" class="form-group" style="display:none;">
                            <label for="studentEmail">Parent Email Address</label>
                            <input id="studentEmail" name="email" type="email" placeholder="parent@example.com">
                        </div>

                        <div id="addParentFields" class="form-group full-width">
                            <label for="parentMode">Parent / Guardian *</label>
                            <select id="parentMode" name="parent_mode">
                                <option value="existing">Select Existing Parent</option>
                                <option value="new">Add New Parent</option>
                            </select>
                        </div>
                        <div id="existingParentFields" class="form-group full-width">
                            <label for="existingParentId">Existing Parent Account *</label>
                            <select id="existingParentId" name="existing_parent_id">
                                <option value="">Select parent account</option>
                                <?php foreach ($parentOptions as $parentOption): ?>
                                    <option value="<?= (int)$parentOption['parent_id'] ?>">
                                        <?= htmlspecialchars($parentOption['parent_name'] . ' — ' . ($parentOption['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-hint">Choose this if the student already has a parent account or sibling in JAZRO.</span>
                        </div>
                        <div id="newParentFields" class="form-group full-width" style="display:none;">
                            <label for="newParentName">New Parent Name *</label>
                            <input id="newParentName" name="new_parent_name" placeholder="Enter parent name">
                            <label for="newParentPhone" style="margin-top:12px;">Parent Contact Number</label>
                            <input id="newParentPhone" name="new_parent_phone" type="tel" placeholder="e.g. 0123456789">
                            <label for="newParentEmail" style="margin-top:12px;">Parent Email Address *</label>
                            <input id="newParentEmail" name="new_parent_email" type="email" placeholder="parent@example.com">
                            <span class="form-hint">Username and email will use this email address. Default password: parents123jazro.</span>
                        </div>

                        <div class="form-group full-width">
                            <label for="studentClassId">Assign Class (Optional)</label>
                            <select id="studentClassId" name="class_id">
                                <option value="">Not assigned yet</option>
                                <?php foreach ($dbClassOptions as $classOption): ?>
                                    <option value="<?= (int)$classOption['class_id'] ?>">
                                        <?= htmlspecialchars($classOption['class_name'] . ' (' . ($classOption['class_type'] === 'Online' ? 'Online' : $classOption['class_category']) . ')', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="secondary-btn" type="button" data-close="studentModal">Cancel</button>
                    <button class="primary-btn" type="submit" id="saveStudentBtn">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="detailsModal" hidden>
        <div class="modal modal-small">
            <div class="modal-header">
                <div>
                    <h2>Student Details</h2>
                    <p>Student profile information.</p>
                </div>
                <button class="modal-close" type="button" data-close="detailsModal" aria-label="Close">×</button>
            </div>
            <div class="modal-body" id="studentDetailsBody"></div>
            <div class="modal-footer">
                <button class="secondary-btn" type="button" data-close="detailsModal">Close</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast">
        <span>✓</span>
        <span id="toastMessage">Student updated successfully.</span>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        const $ = id => document.getElementById(id);

        // Use the live class records from the database for the directory filter.
        const classOptions = <?= json_encode(array_map(function($class) {
            return [
                'id' => (string)$class['class_id'],
                'name' => $class['class_name'],
                'category' => $class['class_type'] === 'Online' ? 'Online' : $class['class_category']
            ];
        }, $classFilterOptions), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        let students = <?= json_encode(array_map(function($s) {
    return [
        'id' => (string)$s['student_id'],
        'name' => $s['student_name'],
        'parent' => $s['parent_name'] ?? '',
        'contact' => $s['phone_number'] ?? '',
        'email' => $s['email'] ?? '',
        'className' => $s['class_names'] ?? '',
        'classIds' => array_values(array_filter(array_map('strval', explode(',', (string)($s['class_ids'] ?? ''))))),
        'classCategories' => $s['class_categories'] ?? '',
        'status' => ((int)($s['has_active_enrollment'] ?? 0) === 1) ? 'Active' : 'Inactive',
        'age' => $s['age'],
        'gender' => $s['gender'],
        'ic' => $s['ic_number'],
        'school' => $s['school_name']
    ];
}, $students), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        let currentPage = 1;
        const pageSize = 10;
        let sortDirection = "asc";

        function escapeHTML(value) {
            return String(value ?? "").replace(/[&<>"']/g, char => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            })[char]);
        }

        function formatName(name) {
            return String(name || "")
                .toLowerCase()
                .replace(/\b\w/g, char => char.toUpperCase());
        }

        function getStudentAvatar(gender) {
            const value = String(gender || "").toLowerCase().trim();

            if (value === "boy") {
                return "assets/images/avatar-boy.png";
            }

            if (value === "girl") {
                return "assets/images/avatar-girl.png";
            }

            return "assets/images/avatar-boy.png";
        }

        function studentAvatarHTML(student, extraClass = "") {
            const className = extraClass ? `student-avatar ${extraClass}` : "student-avatar";
            return `<img class="${className}" src="${getStudentAvatar(student.gender)}" alt="${escapeHTML(formatName(student.name))} avatar" onerror="this.onerror=null;this.src='assets/images/avatar-boy.png';">`;
        }

        function getInitials(name) {
            return String(name || "")
                .trim()
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map(part => part[0].toUpperCase())
                .join("") || "ST";
        }

        function populateClassOptions() {
            const filter = $("classFilter");
            filter.querySelectorAll("option:not(:first-child)").forEach(option => option.remove());

            classOptions.forEach(item => {
                const option = document.createElement("option");
                option.value = item.id;
                option.textContent = `${item.name} (${item.category})`;
                filter.appendChild(option);
            });
        }

        function showToast(message) {
            $("toastMessage").textContent = message;
            $("toast").classList.add("show");

            clearTimeout(showToast.timer);
            showToast.timer = setTimeout(() => {
                $("toast").classList.remove("show");
            }, 4200);
        }

        function getFilteredStudents() {
            const query = $("studentSearch").value.trim().toLowerCase();
            const classValue = $("classFilter").value;
            const statusValue = $("statusFilter").value;

            return students
                .filter(student => {
                    const matchesSearch = [
                        student.name,
                        student.id,
                        student.parent,
                        student.contact,
                        student.email
                    ].some(value => String(value || "").toLowerCase().includes(query));

                    const matchesClass = !classValue || (student.classIds || []).includes(String(classValue));
                    const matchesStatus = !statusValue || student.status === statusValue;

                    return matchesSearch && matchesClass && matchesStatus;
                })
                .sort((a, b) => sortDirection === "asc"
                    ? a.name.localeCompare(b.name)
                    : b.name.localeCompare(a.name));
        }

        function renderOverview() {
            const totalCount = students.length;
            const activeCount = students.filter(student => student.status === "Active").length;
            const assignedCount = students.filter(student => student.className).length;

            const activeRate = totalCount ? (activeCount / totalCount) * 100 : 0;
            const assignedRate = totalCount ? (assignedCount / totalCount) * 100 : 0;

            $("totalStudents").textContent = totalCount;
            $("activeStudents").textContent = activeCount;
            $("assignedStudents").textContent = assignedCount;
            $("activeStudentsRate").textContent = `${activeRate.toFixed(1)}%`;
            $("assignedStudentsRate").textContent = `${assignedRate.toFixed(1)}%`;
        }

        function renderDistribution() {
            const categories = ["BAU", "B2B", "Online"];
            const assigned = students.filter(student => student.className);
            const counts = categories.map(name => ({
                name,
                count: assigned.filter(student =>
                    String(student.classCategories || "")
                        .split(",")
                        .map(value => value.trim())
                        .includes(name)
                ).length
            }));

            const totalCategoryAssignments = counts.reduce((sum, category) => sum + category.count, 0);
            $("distributionTotal").textContent = assigned.length;

            $("distributionBars").innerHTML = counts.map(category => {
                const percentage = totalCategoryAssignments
                    ? (category.count / totalCategoryAssignments) * 100
                    : 0;

                return `
                    <div class="distribution-category">
                        <div class="distribution-category-top">
                            <div class="distribution-category-name">${category.name}</div>
                            <div class="distribution-category-value">${category.count} students · ${Math.round(percentage)}%</div>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="--width:${percentage}%"></div>
                        </div>
                    </div>
                `;
            }).join("");
        }

        function renderRecentStudents() {
            const recent = [...students]
                .sort((a, b) => Number(b.id) - Number(a.id))
                .slice(0, 4);

            if (!recent.length) {
                $("recentStudents").innerHTML = `
                    <div class="empty-state">
                        <strong>No students yet</strong>
                        Student profiles will appear here.
                    </div>
                `;
                return;
            }

            $("recentStudents").innerHTML = recent.map(student => `
                <div class="recent-item">
                    ${studentAvatarHTML(student)}
                    <div class="recent-details">
                        <div class="recent-name">${escapeHTML(formatName(student.name))}</div>
                        <div class="recent-meta">${escapeHTML(student.id)} · ${escapeHTML(student.className || "Not assigned")}</div>
                    </div>
                    <span class="status-badge ${student.status === "Active" ? "status-active" : "status-inactive"}">
                        ${escapeHTML(student.status)}
                    </span>
                </div>
            `).join("");
        }

        function renderTable() {
            const filtered = getFilteredStudents();
            const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));

            if (currentPage > totalPages) currentPage = totalPages;

            const startIndex = (currentPage - 1) * pageSize;
            const visible = filtered.slice(startIndex, startIndex + pageSize);

            if (!visible.length) {
                $("studentTableBody").innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <strong>No students found</strong>
                                Try changing your search or filter.
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                $("studentTableBody").innerHTML = visible.map(student => `
                    <tr>
                        <td>
                            <div class="student-cell">
                                ${studentAvatarHTML(student)}
                                <div>
                                    <div class="student-cell-name">${escapeHTML(formatName(student.name))}</div>
                                </div>
                            </div>
                        </td>
                        <td>${escapeHTML(student.parent ? formatName(student.parent) : "—")}</td>
                        <td>
                            <span class="class-tag">${escapeHTML(student.className || "Not assigned")}</span>
                        </td>
                        <td>${escapeHTML(student.contact || "—")}</td>
                        <td>
                            <span class="status-badge ${student.status === "Active" ? "status-active" : "status-inactive"}">
                                ${escapeHTML(student.status)}
                            </span>
                        </td>
                        <td>
                            <div class="row-actions">
                                <button class="action-btn" title="View details" data-action="view" data-id="${escapeHTML(student.id)}">⌕</button>
                                <button class="action-btn" title="Edit student" data-action="edit" data-id="${escapeHTML(student.id)}">✎</button>
                                <button class="action-btn delete" title="Delete student" data-action="delete" data-id="${escapeHTML(student.id)}">×</button>
                            </div>
                        </td>
                    </tr>
                `).join("");
            }

            const from = filtered.length ? startIndex + 1 : 0;
            const to = Math.min(startIndex + pageSize, filtered.length);
            $("tableInfo").textContent = `Showing ${from}-${to} of ${filtered.length} students`;

            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
    const container = $("pagination");

    container.innerHTML = `
        <button
            class="page-btn"
            data-page="${currentPage - 1}"
            ${currentPage === 1 ? "disabled" : ""}
            title="Previous page">
            &#10094;
        </button>

        <button class="page-btn active" disabled>
            ${currentPage}
        </button>

        <span class="page-count">
            of ${totalPages}
        </span>

        <button
            class="page-btn"
            data-page="${currentPage + 1}"
            ${currentPage === totalPages ? "disabled" : ""}
            title="Next page">
            &#10095;
        </button>
    `;
}
        function renderAll() {
            renderOverview();
            renderDistribution();
            renderRecentStudents();
            renderTable();
        }

        function setParentMode() {
            const isNew = $("parentMode").value === "new";
            $("existingParentFields").style.display = isNew ? "none" : "block";
            $("newParentFields").style.display = isNew ? "block" : "none";
            $("existingParentId").required = !isNew;
            $("newParentName").required = isNew;
            $("newParentEmail").required = isNew;
        }

        function openStudentModal(student = null) {
            $("studentForm").reset();
            $("studentEditId").value = student ? student.id : "";
            $("studentModalTitle").textContent = student ? "Edit Student" : "Add Student";
            $("saveStudentBtn").textContent = student ? "Save Changes" : "Save Student";

            $("addParentFields").style.display = student ? "none" : "block";
            $("existingParentFields").style.display = student ? "none" : "block";
            $("newParentFields").style.display = "none";
            $("editParentFields").style.display = student ? "block" : "none";
            $("editParentContactFields").style.display = student ? "block" : "none";
            $("editParentEmailFields").style.display = student ? "block" : "none";
            $("studentClassId").closest(".form-group").style.display = student ? "none" : "block";

            if (student) {
                $("parentName").required = true;
                $("studentName").value = formatName(student.name);
                $("studentAge").value = student.age ?? "";
                $("studentGender").value = String(student.gender || "").toLowerCase();
                $("studentIC").value = student.ic || "";
                $("studentSchool").value = student.school || "";
                $("parentName").value = student.parent ? formatName(student.parent) : "";
                $("studentContact").value = student.contact || "";
                $("studentEmail").value = student.email || "";
            } else {
                $("parentName").required = false;
                $("parentMode").value = "existing";
                $("studentClassId").value = "";
                setParentMode();
            }

            $("studentModal").hidden = false;
            $("studentModal").classList.add("show");
            document.body.style.overflow = "hidden";
            setTimeout(() => $("studentName").focus(), 100);
        }

        function closeModal(modalId) {
            $(modalId).classList.remove("show");
            $(modalId).hidden = true;

            if (!document.querySelector(".modal-overlay.show")) {
                document.body.style.overflow = "";
            }
        }

        function viewStudent(student) {
            $("studentDetailsBody").innerHTML = `
                <div class="details-profile">
                    ${studentAvatarHTML(student)}
                    <div>
                        <div class="details-name">${escapeHTML(formatName(student.name))}</div>
                        <div class="details-id">Student ID: ${escapeHTML(student.id)}</div>
                    </div>
                </div>

                <div class="details-grid">
                    <div>
                        <div class="detail-label">Age</div>
                        <div class="detail-value">${escapeHTML(student.age ?? "—")}</div>
                    </div>
                    <div>
                        <div class="detail-label">Gender</div>
                        <div class="detail-value">${escapeHTML(student.gender ? formatName(student.gender) : "—")}</div>
                    </div>
                    <div>
                        <div class="detail-label">IC Number</div>
                        <div class="detail-value">${escapeHTML(student.ic || "—")}</div>
                    </div>
                    <div>
                        <div class="detail-label">School Name</div>
                        <div class="detail-value">${escapeHTML(student.school || "—")}</div>
                    </div>
                    <div>
                        <div class="detail-label">Parent / Guardian</div>
                        <div class="detail-value">${escapeHTML(student.parent ? formatName(student.parent) : "—")}</div>
                    </div>
                    <div>
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value">${escapeHTML(student.contact || "—")}</div>
                    </div>
                    <div>
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value">${escapeHTML(student.email || "—")}</div>
                    </div>
                    <div>
                        <div class="detail-label">Assigned Class</div>
                        <div class="detail-value">${escapeHTML(student.className || "Not assigned")}</div>
                    </div>
                    <div>
                        <div class="detail-label">Status</div>
                        <div class="detail-value">${escapeHTML(student.status || "—")}</div>
                    </div>
                </div>
            `;

            $("detailsModal").hidden = false;
            $("detailsModal").classList.add("show");
            document.body.style.overflow = "hidden";
        }

        async function saveStudent(event) {
            event.preventDefault();

            const editId = $("studentEditId").value;
            const formData = new FormData($("studentForm"));
            formData.append("action", editId ? "update_student" : "add_student");

            if (editId) {
                formData.append("student_id", editId);
            }

            const saveButton = $("saveStudentBtn");
            const originalLabel = editId ? "Save Changes" : "Save Student";
            saveButton.disabled = true;
            saveButton.textContent = "Saving...";

            try {
                const response = await fetch(window.location.pathname, {
                    method: "POST",
                    body: formData,
                    headers: { "X-Requested-With": "XMLHttpRequest" }
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || "Unable to save student details.");
                }

                closeModal("studentModal");
                showToast(result.message || (editId ? "Student updated successfully." : "Student added successfully."));
                setTimeout(() => window.location.reload(), 3000);
            } catch (error) {
                showToast(error.message || "Something went wrong. Please try again.");
            } finally {
                saveButton.disabled = false;
                saveButton.textContent = originalLabel;
            }
        }

        async function deleteStudent(id) {
            const student = students.find(item => item.id === id);
            if (!student) return;

            const confirmed = confirm(
                `Are you sure you want to delete ${formatName(student.name)}?\n\nDeletion is only allowed when the student has no attendance, payment, assessment, certificate, report card or Blooket history. The parent account will remain.`
            );
            if (!confirmed) return;

            try {
                const formData = new FormData();
                formData.append("action", "delete_student");
                formData.append("student_id", id);

                const response = await fetch(window.location.pathname, {
                    method: "POST",
                    body: formData,
                    headers: { "X-Requested-With": "XMLHttpRequest" }
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || "Unable to delete student.");
                }

                showToast(result.message || "Student deleted successfully.");
                setTimeout(() => window.location.reload(), 3000);
            } catch (error) {
                showToast(error.message || "Unable to delete student.");
            }
        }

        $("addStudentBtn").addEventListener("click", () => openStudentModal());

        $("studentForm").addEventListener("submit", saveStudent);
        $("parentMode").addEventListener("change", setParentMode);

        $("studentSearch").addEventListener("input", () => {
            currentPage = 1;
            renderTable();
        });

        $("classFilter").addEventListener("change", () => {
            currentPage = 1;
            renderTable();
        });

        $("statusFilter").addEventListener("change", () => {
            currentPage = 1;
            renderTable();
        });

        $("studentTableBody").addEventListener("click", event => {
            const button = event.target.closest("[data-action]");
            if (!button) return;

            const student = students.find(item => item.id === button.dataset.id);
            if (!student) return;

            if (button.dataset.action === "view") {
                viewStudent(student);
            } else if (button.dataset.action === "edit") {
                openStudentModal(student);
            } else if (button.dataset.action === "delete") {
                deleteStudent(student.id);
            }
        });

        $("pagination").addEventListener("click", event => {
            const button = event.target.closest("[data-page]");
            if (!button || button.disabled) return;

            currentPage = Number(button.dataset.page);
            renderTable();
        });

        document.querySelectorAll("[data-close]").forEach(button => {
            button.addEventListener("click", () => closeModal(button.dataset.close));
        });

        document.querySelectorAll(".modal-overlay").forEach(overlay => {
            overlay.addEventListener("click", event => {
                if (event.target === overlay) closeModal(overlay.id);
            });
        });

        document.addEventListener("keydown", event => {
            if (event.key === "Escape") {
                document.querySelectorAll(".modal-overlay.show").forEach(modal => closeModal(modal.id));
            }
        });

        $("mobileMenu").addEventListener("click", () => {
            document.body.classList.toggle("sidebar-open");
        });

        $("sidebarBackdrop").addEventListener("click", () => {
            document.body.classList.remove("sidebar-open");
        });

        document.querySelectorAll(".admin-nav a, .sidebar-bottom a").forEach(link => {
            link.addEventListener("click", () => {
                document.body.classList.remove("sidebar-open");
            });
        });

        populateClassOptions();
        renderAll();
    </script>
</body>
</html>