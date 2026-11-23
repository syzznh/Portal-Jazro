
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    !in_array(strtoupper($_SESSION['role'] ?? ''), ['ADMIN', 'ADMINISTRATOR'], true)
) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$adminName = $_SESSION['username'] ?? 'Admin User';

try {
    $stmt = $pdo->prepare(
        "SELECT admin_name FROM admin WHERE user_id = ? LIMIT 1"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();

    if (!empty($admin['admin_name'])) {
        $adminName = $admin['admin_name'];
    }

    $totalStudents = (int)$pdo->query(
        "SELECT COUNT(*) FROM student"
    )->fetchColumn();

    $activeClasses = (int)$pdo->query(
        "SELECT COUNT(*) FROM classes WHERE status = 'Active'"
    )->fetchColumn();

    $totalAssessments = (int)$pdo->query(
        "SELECT COUNT(*) FROM assessment"
    )->fetchColumn();

    $totalCertificates = (int)$pdo->query(
        "SELECT COUNT(*) FROM certificate"
    )->fetchColumn();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $totalStudents = $activeClasses = 0;
    $totalAssessments = $totalCertificates = 0;
}

// Monthly enrollment totals for the current year, grouped by class type.
$currentYear = (int)date('Y');
$monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthlyEnrollment = [];
for ($month = 1; $month <= 12; $month++) {
    $monthlyEnrollment[$month] = ['Physical' => 0, 'Online' => 0];
}

try {
    $enrollmentStmt = $pdo->prepare(
        "SELECT MONTH(e.enrollment_date) AS month_number,
                c.class_type,
                COUNT(*) AS total
         FROM enrollment e
         INNER JOIN classes c ON c.class_id = e.class_id
         WHERE e.enrollment_date IS NOT NULL
           AND YEAR(e.enrollment_date) = :year
           AND c.class_type IN ('Physical', 'Online')
         GROUP BY MONTH(e.enrollment_date), c.class_type"
    );
    $enrollmentStmt->execute(['year' => $currentYear]);

    foreach ($enrollmentStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $monthNumber = (int)$row['month_number'];
        $classType = $row['class_type'];
        if (isset($monthlyEnrollment[$monthNumber][$classType])) {
            $monthlyEnrollment[$monthNumber][$classType] = (int)$row['total'];
        }
    }
} catch (PDOException $e) {
    error_log('Dashboard enrollment chart: ' . $e->getMessage());
}

$chartMaxValue = 0;
foreach ($monthlyEnrollment as $monthData) {
    $chartMaxValue = max($chartMaxValue, $monthData['Physical'], $monthData['Online']);
}
$chartMaxValue = max(10, (int)(ceil($chartMaxValue / 10) * 10));
$chartAxisLabels = [
    $chartMaxValue,
    (int)round($chartMaxValue * 0.75),
    (int)round($chartMaxValue * 0.50),
    (int)round($chartMaxValue * 0.25),
    0
];

// Recent activity is based on dated records available in the current database.
// Attendance uses the class session date because the attendance table has no
// created_at/timestamp column.
$recentActivities = [];

try {
    $activitySql = "
        SELECT activity_type, activity_title, activity_detail, activity_date
        FROM (
            SELECT
                'student' AS activity_type,
                'Student enrolled' AS activity_title,
                CONCAT(s.student_name, ' enrolled in ', c.class_name) AS activity_detail,
                e.enrollment_date AS activity_date
            FROM enrollment e
            INNER JOIN student s ON s.student_id = e.student_id
            INNER JOIN classes c ON c.class_id = e.class_id
            WHERE e.enrollment_date IS NOT NULL

            UNION ALL

            SELECT
                'assessment' AS activity_type,
                'Assessment scheduled' AS activity_title,
                a.title AS activity_detail,
                a.date AS activity_date
            FROM assessment a
            WHERE a.date IS NOT NULL

            UNION ALL

            SELECT
                'certificate' AS activity_type,
                'Certificate issued' AS activity_title,
                CONCAT(cert.certificate_name, ' · ', s.student_name) AS activity_detail,
                cert.issue_date AS activity_date
            FROM certificate cert
            INNER JOIN student s ON s.student_id = cert.student_id
            WHERE cert.issue_date IS NOT NULL

            UNION ALL

            SELECT
                'attendance' AS activity_type,
                'Attendance recorded' AS activity_title,
                CONCAT(COUNT(att.attendance_id), ' attendance record(s) · ', c.class_name) AS activity_detail,
                cs.session_date AS activity_date
            FROM attendance att
            INNER JOIN class_session cs ON cs.session_id = att.session_id
            INNER JOIN classes c ON c.class_id = cs.class_id
            GROUP BY cs.session_id, cs.session_date, c.class_name
        ) AS activity_feed
        WHERE activity_date IS NOT NULL
          AND activity_date <= CURDATE()
        ORDER BY activity_date DESC
        LIMIT 6
    ";

    $recentActivities = $pdo->query($activitySql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Dashboard recent activities: ' . $e->getMessage());
    $recentActivities = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | JAZRO Admin Portal</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
    }

    * { box-sizing: border-box; }

    body.app-body {
      margin: 0;
      background: var(--bg);
      color: #29415f;
      font-family: "Inter", Arial, sans-serif;
      font-size: 13px;
    }

    a { text-decoration: none; }

    .admin-layout {
      display: flex;
      min-height: 100vh;
    }

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

    /* MAIN CONTENT */
    .admin-main {
      display: flex;
      flex: 1;
      flex-direction: column;
      min-width: 0;
      min-height: 100vh;
      margin-left: 245px;
    }

    .admin-topbar {
      position: sticky;
      top: 0;
      z-index: 30;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      min-height: 72px;
      padding: 12px 30px;
      border-bottom: 1px solid var(--line);
      background: rgba(255,255,255,0.96);
      backdrop-filter: blur(10px);
    }

    .topbar-title strong {
      display: block;
      color: var(--navy);
      font-size: 15px;
    }

    .topbar-title small {
      display: block;
      margin-top: 4px;
      color: var(--muted);
      font-size: 10px;
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 13px;
    }

    .notification-button {
      position: relative;
      display: grid;
      width: 39px;
      height: 39px;
      place-items: center;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: #fff;
      color: var(--blue);
      font-size: 17px;
      cursor: pointer;
    }

    .notification-dot {
      position: absolute;
      top: 7px;
      right: 7px;
      width: 7px;
      height: 7px;
      border: 1px solid #fff;
      border-radius: 50%;
      background: #ff8292;
    }

    .account-avatar {
      display: grid;
      width: 39px;
      height: 39px;
      place-items: center;
      border-radius: 50%;
      background: linear-gradient(145deg, #55baff, #2870f4);
      color: #fff;
      font-size: 14px;
      font-weight: 800;
    }

    .account-copy strong {
      display: block;
      color: var(--navy);
      font-size: 11px;
    }

    .account-copy small {
      display: block;
      margin-top: 4px;
      color: var(--muted);
      font-size: 10px;
    }

    .mobile-menu-button {
      display: none;
      width: 38px;
      height: 38px;
      border: 1px solid var(--line);
      border-radius: 10px;
      background: #fff;
      color: var(--blue);
      font-size: 19px;
      cursor: pointer;
    }

    /* PAGE */
    .dashboard-content {
      width: 100%;
      max-width: 1700px;
      margin: 0 auto;
      padding: 28px 30px 0;
    }

    .page-intro {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 23px;
    }

    .page-eyebrow {
      margin: 0 0 9px;
      color: var(--blue);
      font-size: 9px;
      font-weight: 800;
      letter-spacing: 1.6px;
    }

    .page-intro h1 {
      margin: 0;
      color: var(--navy);
      font-size: 28px;
      font-weight: 800;
      letter-spacing: -0.8px;
    }

    .page-intro p.description {
      margin: 8px 0 0;
      color: var(--muted);
      font-size: 12px;
      line-height: 1.7;
    }

    .date-chip {
      padding: 11px 14px;
      border: 1px solid #deebff;
      border-radius: 11px;
      background: #fff;
      color: #58769f;
      font-size: 11px;
    }

    /* WELCOME BANNER */
    .welcome-banner {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      min-height: 155px;
      padding: 27px 30px;
      overflow: hidden;
      border-radius: 19px;
      background: linear-gradient(110deg, #2877f5 0%, #479ff7 58%, #8ad8ff 100%);
      box-shadow: 0 12px 25px rgba(43, 119, 236, 0.12);
      color: white;
    }

    .welcome-banner::after {
      position: absolute;
      top: -105px;
      right: 100px;
      width: 230px;
      height: 230px;
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 50%;
      content: "";
    }

    .welcome-copy {
      position: relative;
      z-index: 2;
      max-width: 600px;
    }

    .banner-tag {
      display: inline-block;
      margin-bottom: 13px;
      padding: 6px 10px;
      border: 1px solid rgba(255,255,255,0.3);
      border-radius: 20px;
      background: rgba(255,255,255,0.13);
      font-size: 9px;
      font-weight: 800;
      letter-spacing: 1.3px;
    }

    .welcome-copy h2 {
      margin: 0;
      font-size: clamp(20px, 2.2vw, 27px);
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .welcome-copy p {
      max-width: 520px;
      margin: 10px 0 0;
      color: rgba(255,255,255,0.9);
      font-size: 11px;
      line-height: 1.8;
    }

    .banner-robot {
      position: relative;
      z-index: 2;
      display: grid;
      width: 95px;
      height: 80px;
      flex-shrink: 0;
      place-items: center;
      border: 4px solid rgba(255,255,255,0.8);
      border-radius: 29px;
      background: linear-gradient(145deg, #f7fcff, #b8e1ff);
      box-shadow: 0 8px 22px rgba(17, 66, 161, 0.12);
    }

    .banner-robot::before,
    .banner-robot::after {
      position: absolute;
      top: -15px;
      width: 4px;
      height: 17px;
      border-radius: 4px;
      background: #d8f5ff;
      content: "";
    }

    .banner-robot::before {
      left: 16px;
      transform: rotate(-15deg);
    }

    .banner-robot::after {
      right: 16px;
      transform: rotate(15deg);
    }

    .robot-eyes {
      display: flex;
      gap: 21px;
    }

    .robot-eyes span {
      width: 12px;
      height: 20px;
      border: 3px solid #56caff;
      border-radius: 9px;
      background: #2878f5;
    }

    /* SECTION HEADINGS */
    .section-heading {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin: 28px 0 16px;
    }

    .section-heading h2 {
      margin: 0;
      color: var(--navy);
      font-size: 17px;
      font-weight: 800;
    }

    .section-heading p {
      margin: 6px 0 0;
      color: var(--muted);
      font-size: 11px;
    }

    .demo-label {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 8px 11px;
      border-radius: 20px;
      background: #eaf4ff;
      color: #2871e4;
      font-size: 10px;
      font-weight: 700;
    }

    .demo-label span {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #48b4ff;
    }

    /* STAT CARDS */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
    }

    .stat-card {
      min-width: 0;
      padding: 19px;
      border: 1px solid var(--line);
      border-radius: 15px;
      background: #fff;
      box-shadow: 0 5px 17px rgba(37, 86, 153, 0.035);
      transition: transform .2s, box-shadow .2s;
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 24px rgba(37, 86, 153, 0.08);
    }

    .stat-card-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }

    .stat-icon {
      display: grid;
      width: 40px;
      height: 40px;
      place-items: center;
      border-radius: 12px;
      font-size: 18px;
    }

    .icon-blue { background: #e6f0ff; color: #3678ee; }
    .icon-cyan { background: #e1f8ff; color: #19a4d8; }
    .icon-violet { background: #f0eaff; color: #8c68df; }
    .icon-green { background: #e4f9ef; color: #26a776; }

    .stat-tag {
      padding: 5px 8px;
      border-radius: 15px;
      background: #f2f7ff;
      color: #7d93b2;
      font-size: 9px;
      font-weight: 700;
    }

    .stat-card h3 {
      margin: 18px 0 5px;
      color: var(--navy);
      font-size: 29px;
      font-weight: 800;
    }

    .stat-card .stat-name {
      margin: 0;
      color: #395372;
      font-size: 11px;
      font-weight: 700;
    }

    .stat-card .stat-note {
      display: block;
      margin-top: 8px;
      color: #94a3b9;
      font-size: 10px;
    }

    /* LOWER DASHBOARD PANELS */
    .dashboard-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.25fr) minmax(300px, 0.85fr);
      gap: 18px;
      margin-top: 22px;
      margin-bottom: 30px;
    }

    .dashboard-panel {
      min-width: 0;
      padding: 22px;
      border: 1px solid var(--line);
      border-radius: 16px;
      background: #fff;
      box-shadow: 0 5px 17px rgba(37, 86, 153, 0.035);
    }

    .panel-heading {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 22px;
    }

    .panel-heading h3 {
      margin: 0;
      color: var(--navy);
      font-size: 14px;
      font-weight: 800;
    }

    .panel-heading p {
      margin: 7px 0 0;
      color: var(--muted);
      font-size: 10px;
    }

    .chart-legend {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      color: #7286a2;
      font-size: 9px;
    }

    .chart-legend span {
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .legend-dot {
      width: 7px;
      height: 7px;
      border-radius: 3px;
      background: #3985f7;
    }

    .legend-dot.online {
      background: #75d5ed;
    }

    .chart-area {
      display: grid;
      grid-template-columns: 25px 1fr;
      gap: 10px;
      min-height: 205px;
    }

    .chart-y-labels {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding-bottom: 27px;
      color: #9aaac0;
      font-size: 9px;
      text-align: right;
    }

    .bar-chart {
      position: relative;
      display: flex;
      align-items: stretch;
      justify-content: space-around;
      gap: 8px;
      min-width: 0;
      padding: 0 5px 27px;
      background: repeating-linear-gradient(
        to bottom,
        transparent 0,
        transparent 39px,
        #edf3fb 40px,
        transparent 41px
      );
      border-bottom: 1px solid #e8eff9;
    }

    .bar-group {
      display: flex;
      flex: 1;
      align-items: flex-end;
      justify-content: center;
      gap: 4px;
      min-width: 0;
      height: 178px;
      position: relative;
    }

    .bar-group .physical-bar,
    .bar-group .online-bar {
      display: block;
      width: min(13px, 38%);
      min-height: 5px;
      border-radius: 5px 5px 2px 2px;
    }

    .bar-group .physical-bar {
      background: linear-gradient(180deg, #65aaff, #2878f5);
    }

    .bar-group .online-bar {
      background: linear-gradient(180deg, #a0e8f6, #64cce8);
    }

    .bar-group small {
      position: absolute;
      bottom: -21px;
      color: #8194af;
      font-size: 9px;
    }

    /* RECENT ACTIVITIES */
    .activity-list {
      display: grid;
    }

    .activity-item {
      display: grid;
      grid-template-columns: 37px minmax(0, 1fr) 70px;
      align-items: center;
      gap: 11px;
      width: 100%;
      min-width: 0;
      padding: 14px 0;
      border-bottom: 1px solid #edf2fa;
    }

    .activity-item:first-child {
      padding-top: 14;
    }

    .activity-item:last-child {
      padding-bottom: 14;
      border-bottom: 0;
    }

    .activity-icon {
      display: grid;
      width: 37px;
      height: 37px;
      flex-shrink: 0;
      place-items: center;
      border-radius: 11px;
      background: #eaf3ff;
      color: var(--blue);
      font-size: 15px;
    }

    .activity-icon.cyan { background: #e3f9ff; color: #169fc9; }
    .activity-icon.violet { background: #f0eaff; color: #8967d9; }
    .activity-icon.green { background: #e4f9ef; color: #23a06f; }

    .activity-copy {
      min-width: 0;
      width: 100%;
      overflow: hidden;
    }

    .activity-copy strong {
      display: block;
      color: #344e6d;
      font-size: 10px;
      font-weight: 700;
    }

    .activity-copy small {
      display: block;
      margin-top: 5px;
      overflow: hidden;
      color: #91a0b6;
      font-size: 9px;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .activity-time {
      display: block;
      width: 70px;
      min-width: 0;
      color: #9aa9bd;
      font-size: 9px;
      line-height: 1.4;
      text-align: right;
      white-space: nowrap;
    }

    .panel-link {
      color: var(--blue);
      font-size: 10px;
      font-weight: 700;
    }

    /* QUICK LINKS */
    .quick-links {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
      margin-bottom: 30px;
    }

    .quick-link {
      display: flex;
      align-items: center;
      gap: 13px;
      padding: 17px;
      border: 1px solid var(--line);
      border-radius: 14px;
      background: #fff;
      color: inherit;
      transition: 0.2s ease;
    }

    .quick-link:hover {
      border-color: #bfd9ff;
      background: #fbfdff;
      transform: translateY(-2px);
    }

    .quick-link-icon {
      display: grid;
      width: 40px;
      height: 40px;
      flex-shrink: 0;
      place-items: center;
      border-radius: 12px;
      background: #eaf3ff;
      color: var(--blue);
      font-size: 18px;
    }

    .quick-link strong {
      display: block;
      color: var(--navy);
      font-size: 11px;
    }

    .quick-link small {
      display: block;
      margin-top: 5px;
      color: var(--muted);
      font-size: 9px;
      line-height: 1.5;
    }

    .page-footer {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: auto;
      padding: 19px 30px;
      border-top: 1px solid var(--line);
      background: #fff;
      color: #92a1b8;
      font-size: 10px;
    }

    /* RESPONSIVE */
    @media (max-width: 1200px) {
      .dashboard-content { padding-right: 22px; padding-left: 22px; }
      .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .dashboard-grid { grid-template-columns: 1fr; }
      .welcome-banner { min-height: 140px; }
    }

    @media (max-width: 760px) {
      .admin-sidebar {
        transform: translateX(-105%);
        transition: transform .25s ease;
        box-shadow: 10px 0 35px rgba(27, 72, 135, .12);
      }

      .admin-sidebar.sidebar-open {
        transform: translateX(0);
      }

      .admin-main { margin-left: 0; }

      .admin-topbar {
        min-height: 64px;
        padding: 10px 15px;
      }

      .mobile-menu-button { display: grid; place-items: center; }

      .topbar-title { flex: 1; }

      .account-copy { display: none; }

      .dashboard-content { padding: 22px 15px 0; }

      .page-intro h1 { font-size: 25px; }

      .welcome-banner { padding: 22px; }

      .banner-robot { width: 70px; height: 65px; border-radius: 22px; }

      .robot-eyes { gap: 13px; }

      .robot-eyes span { width: 10px; height: 17px; }

      .quick-links { grid-template-columns: 1fr; }

      .page-footer { padding: 17px 15px; }
    }

    @media (max-width: 460px) {
      .stats-grid { gap: 10px; }
      .stat-card { padding: 14px; }
      .stat-card h3 { font-size: 25px; }
      .stat-card .stat-name { font-size: 10px; }
      .stat-card .stat-note { font-size: 9px; }
      .stat-tag { font-size: 8px; padding: 4px 6px; }
      .welcome-banner { min-height: 135px; padding: 18px; }
      .welcome-copy h2 { font-size: 19px; }
      .welcome-copy p { font-size: 10px; }
      .banner-robot { display: none; }
      .dashboard-panel { padding: 16px; }
      .activity-item { grid-template-columns: 34px minmax(0, 1fr) 62px; gap: 8px; }
      .activity-icon { width: 34px; height: 34px; }
      .activity-time { width: 62px; font-size: 8px; }
      .chart-legend { flex-direction: column; gap: 6px; }
      .date-chip { font-size: 9px; }
    }

    /* Show full Recent Activities information */
.activity-item {
    height: auto !important;
    min-height: 70px;
    align-items: start !important;
    overflow: visible !important;
}

.activity-copy {
    min-width: 0 !important;
    overflow: visible !important;
    height: auto !important;
}

.activity-copy small,
.activity-copy p,
.activity-copy .activity-description {
    display: block !important;
    width: 100% !important;
    max-width: none !important;
    height: auto !important;
    max-height: none !important;
    white-space: normal !important;
    overflow: visible !important;
    text-overflow: clip !important;
    overflow-wrap: anywhere !important;
    word-break: normal !important;
    -webkit-line-clamp: unset !important;
    -webkit-box-orient: initial !important;
    line-height: 1.6 !important;
}
  </style>
</head>

<body class="app-body">
<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar" id="sidebar">
    <a class="admin-brand" href="dashboard.php">
      <img src="assets/images/jazro-logo.png" alt="JAZRO Robotic Academy">
      <span>ADMIN PORTAL</span>
    </a>

    <p class="nav-caption">MAIN MENU</p>

    <?php include __DIR__ . '/learning_activities_menu.php'; ?>

    <div class="sidebar-bottom">
      <a class="logout-link" href="index.php">
        <span class="nav-icon">↪</span> Log Out
      </a>
      <small>JAZRO ROBOTIC ACADEMY · 2026</small>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="admin-main">

    <header class="admin-topbar">
      <button class="mobile-menu-button" id="mobileMenu"
              aria-label="Open navigation menu">☰</button>

      <div class="topbar-title">
        <strong>Dashboard</strong>
        <small>Admin Overview</small>
      </div>

      <div class="topbar-right">
        <button class="notification-button" id="notificationButton"
                type="button" aria-label="Notifications">
          ♧ <span class="notification-dot"></span>
        </button>

        <div class="account-avatar">A</div>

        <div class="account-copy">
          <strong><?= htmlspecialchars($adminName) ?></strong>
          <small>Administrator</small>
        </div>
      </div>
    </header>

    <div class="dashboard-content">

      <!-- PAGE INTRO -->
      <section class="page-intro">
        <div>
          <p class="page-eyebrow">YOUR WORKSPACE</p>
          <h1>Dashboard <span aria-hidden="true">✦</span></h1>
          <p class="description">
            Welcome back! Here's an overview of JAZRO Robotic Academy.
          </p>
        </div>

        <div class="date-chip">
          ▦ &nbsp; <span id="todayDate">26 Oct 2026</span>
        </div>
      </section>

      <!-- WELCOME BANNER -->
      <section class="welcome-banner">
        <div class="welcome-copy">
          <span class="banner-tag">ADMIN OVERVIEW</span>
          <h2>Let's make learning more exciting!</h2>
          <p>
            Here's a quick look at your academy's activity and learning progress.
          </p>
        </div>

        <div class="banner-robot" aria-hidden="true">
          <div class="robot-eyes"><span></span><span></span></div>
        </div>
      </section>

      <!-- STATS -->
      <section class="section-heading">
        <div>
          <h2>Academy Overview</h2>
          <p>A snapshot of key information</p>
        </div>

      </section>

      <section class="stats-grid">
        <article class="stat-card">
          <div class="stat-card-top">
            <span class="stat-icon icon-blue">♧</span>
            
          </div>
          <h3><?= $totalStudents ?></h3>
      <p class="stat-name">Total Students</p>
          <small class="stat-note">Registered students</small>
        </article>

        <article class="stat-card">
          <div class="stat-card-top">
            <span class="stat-icon icon-cyan">▦</span>
            
          </div>
          <h3><?= $activeClasses ?></h3>
          <p class="stat-name">Active Classes</p>
          <small class="stat-note">Currently active classes</small>
        </article>

        <article class="stat-card">
          <div class="stat-card-top">
            <span class="stat-icon icon-violet">☷</span>
            
          </div>
          <h3><?= $totalAssessments ?></h3>
          <p class="stat-name">Assessments</p>
          <small class="stat-note">Assessments in the system</small>
        </article>

        <article class="stat-card">
          <div class="stat-card-top">
            <span class="stat-icon icon-green">♜</span>
            
          </div>
          <h3><?= $totalCertificates ?></h3>
          <p class="stat-name">Certificates</p>
          <small class="stat-note">Certificates issued</small>
        </article>
      </section>

      <!-- CHART AND ACTIVITY -->
      <section class="dashboard-grid">

        <article class="dashboard-panel">
          <div class="panel-heading">
            <div>
              <h3>Enrollment Overview</h3>
              <p>Student enrollments by class type · <?= $currentYear ?></p>
            </div>

            <div class="chart-legend">
              <span><i class="legend-dot"></i> Physical</span>
              <span><i class="legend-dot online"></i> Online</span>
            </div>
          </div>

          <div class="chart-area">
            <div class="chart-y-labels">
              <?php foreach ($chartAxisLabels as $axisValue): ?>
                <span><?= $axisValue ?></span>
              <?php endforeach; ?>
            </div>

            <div class="bar-chart" aria-label="Monthly student enrollments for <?= $currentYear ?>">
              <?php foreach ($monthlyEnrollment as $monthNumber => $monthData): ?>
                <?php
                  $physicalHeight = ($monthData['Physical'] / $chartMaxValue) * 100;
                  $onlineHeight = ($monthData['Online'] / $chartMaxValue) * 100;
                ?>
                <div class="bar-group">
                  <i class="physical-bar"
                     title="Physical: <?= $monthData['Physical'] ?>"
                     style="height: <?= $physicalHeight ?>%;<?= $monthData['Physical'] === 0 ? 'display:none;' : '' ?>"></i>
                  <i class="online-bar"
                     title="Online: <?= $monthData['Online'] ?>"
                     style="height: <?= $onlineHeight ?>%;<?= $monthData['Online'] === 0 ? 'display:none;' : '' ?>"></i>
                  <small><?= $monthLabels[$monthNumber - 1] ?></small>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </article>

        <article class="dashboard-panel">
          <div class="panel-heading">
            <div>
              <h3>Recent Activities</h3>
              <p>Latest recorded academy activity</p>
            </div>

          </div>

          <div class="activity-list">
            <?php if (!empty($recentActivities)): ?>
              <?php foreach ($recentActivities as $activity): ?>
                <?php
                  $activityType = $activity['activity_type'];
                  $iconClass = '';
                  $iconText = '•';

                  if ($activityType === 'assessment') {
                      $iconClass = 'cyan';
                      $iconText = '☷';
                  } elseif ($activityType === 'certificate') {
                      $iconClass = 'violet';
                      $iconText = '♜';
                  } elseif ($activityType === 'attendance') {
                      $iconClass = 'green';
                      $iconText = '✓';
                  } else {
                      $iconText = '♧';
                  }

                  $activityDate = !empty($activity['activity_date'])
                      ? date('d M Y', strtotime($activity['activity_date']))
                      : '';
                ?>
                <div class="activity-item">
                  <span class="activity-icon <?= htmlspecialchars($iconClass) ?>"><?= htmlspecialchars($iconText) ?></span>
                  <div class="activity-copy">
                    <strong><?= htmlspecialchars($activity['activity_title']) ?></strong>
                    <small><?= htmlspecialchars($activity['activity_detail']) ?></small>
                  </div>
                  <time class="activity-time"><?= htmlspecialchars($activityDate) ?></time>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-activity" style="padding:18px 0;color:#91a0b6;font-size:11px;">
                No recent records available yet.
              </div>
            <?php endif; ?>
          </div>
        </article>

      </section>

      <!-- QUICK LINKS -->
      <section class="section-heading">
        <div>
          <h2>Quick Actions</h2>
          <p>Shortcuts to common admin tasks</p>
        </div>
      </section>

      <section class="quick-links">
        <a class="quick-link" href="students.php">
          <span class="quick-link-icon">♧</span>
          <span>
            <strong>Manage Students</strong>
            <small>View and manage student records</small>
          </span>
        </a>

        <a class="quick-link" href="classes.php">
          <span class="quick-link-icon">▦</span>
          <span>
            <strong>Manage Classes</strong>
            <small>View and manage academy classes</small>
          </span>
        </a>

        <a class="quick-link" href="#" data-coming-soon="Reports">
          <span class="quick-link-icon">▤</span>
          <span>
            <strong>View Reports</strong>
            <small>Review student learning progress</small>
          </span>
        </a>
      </section>

    </div>

    <footer class="page-footer">
      <span>© 2026 JAZRO Robotic Academy</span>
      <span>Empowering Young Innovators</span>
    </footer>

  </main>
</div>

<script src="assets/js/app.js"></script>
<script>
  // Dashboard-only interactions
  document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
    const menuButton = document.getElementById("mobileMenu");

    menuButton?.addEventListener("click", () => {
      sidebar?.classList.toggle("sidebar-open");
    });

    document.querySelectorAll("[data-coming-soon]").forEach((link) => {
      link.addEventListener("click", (event) => {
        event.preventDefault();
        alert(link.dataset.comingSoon + " module will be implemented next.");
      });
    });

    document.getElementById("notificationButton")?.addEventListener("click", () => {
      alert("There are no real notifications yet. This dashboard is using demo data.");
    });
  });
</script>
</body>
</html>
