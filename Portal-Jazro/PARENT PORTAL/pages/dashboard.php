<?php
session_start();

// Make sure only an authenticated parent can access this page.
if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "PARENT" ||
    !isset($_SESSION["parent_id"])
) {
    header("Location: ../index.html");
    exit;
}

require_once __DIR__ . "/../config/db.php";

$parentId = (int) $_SESSION["parent_id"];
$registeredChildren = 0;

// Count only students linked to the currently logged-in parent.
$sql = "SELECT COUNT(*) AS total_children FROM student WHERE parent_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    exit("Unable to load dashboard information.");
}

$stmt->bind_param("i", $parentId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$registeredChildren = (int) ($row["total_children"] ?? 0);
$stmt->close();

/*
 * As requested, Active Classes displays the same count as Registered Children
 * so parents can easily understand the overview (one count per child).
 */
$activeClasses = $registeredChildren;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

  <title>Dashboard | JAZRO Parent Portal</title>

  <!-- FONT -->
  <link rel="preconnect"
        href="https://fonts.googleapis.com">

  <link rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap"
    rel="stylesheet"
  >

  <!-- SHARED CSS -->
  <link rel="stylesheet" href="../css/global.css">

  <!-- DASHBOARD CSS -->
  <link rel="stylesheet" href="../css/dashboard.css">

  <!-- JAVASCRIPT -->
  <script src="../js/dashboard.js" defer></script>
</head>

<body>

  <div class="app-layout">

    <!-- ===============================
         LEFT SIDEBAR
         =============================== -->

    <aside class="sidebar" id="sidebar">

      <!-- LOGO -->
      
<!-- JAZRO PORTAL LOGO -->
<div class="sidebar-brand">

  <img
    src="../assets/images/logo jazro 1.png"
    alt="JAZRO Learning & Student Progress Portal"
    class="sidebar-logo"
  >

</div>


      <div class="sidebar-divider"></div>

      <!-- NAVIGATION -->
      <nav class="sidebar-nav"
           aria-label="Main navigation">

        <span class="nav-section-label">
          MAIN MENU
        </span>

        <!-- DASHBOARD -->
        <a href="dashboard.php"
           class="nav-item active"
           aria-current="page">

          <span class="nav-icon">
            <img
              src="../assets/icons/dashboard-icon.png"
              alt=""
            >
          </span>

          <span>Dashboard</span>

        </a>

        <!-- MY CHILDREN -->
        <a href="my-children.html"
           class="nav-item">

          <span class="nav-icon">
            <img
              src="../assets/icons/mychildren-icon.png"
              alt=""
            >
          </span>

          <span>My Children</span>

        </a>

      </nav>


      <!-- SIDEBAR BOTTOM -->
      <div class="sidebar-bottom">

        <div class="sidebar-divider"></div>

        <!-- LOG OUT -->
        <button
          type="button"
          class="nav-item logout-item"
          id="sidebarLogout"
        >

          <span class="nav-icon">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M10 17l5-5-5-5"></path>
              <path d="M15 12H3"></path>
              <path d="M12 3h6a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-6"></path>
            </svg>
          </span>

          <span>Log Out</span>

        </button>

        <p class="sidebar-footer">
          JAZRO © <span id="sidebarYear"></span>
        </p>

      </div>

    </aside>


    <!-- ===============================
         RIGHT MAIN AREA
         =============================== -->

    <div class="app-main">

      <!-- HEADER -->
      <header class="top-header">

        <div class="header-left">

          <!-- MOBILE SIDEBAR BUTTON -->
          <button
            type="button"
            class="mobile-menu-btn"
            id="menuToggle"
            aria-label="Open navigation"
            aria-expanded="false"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            >
              <path d="M4 6h16"></path>
              <path d="M4 12h16"></path>
              <path d="M4 18h16"></path>
            </svg>
          </button>

          <div class="header-page-title">
            <h1>Dashboard</h1>
            <span>Parent Overview</span>
          </div>

        </div>


        <!-- HEADER RIGHT -->
        <div class="header-right">

          <!-- NOTIFICATION BUTTON -->
          <div class="notification-wrapper">

            <button
              type="button"
              class="notification-btn"
              id="notificationBtn"
              aria-label="Notifications"
              aria-expanded="false"
              aria-controls="notificationPanel"
            >

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.9"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                <path d="M10 21h4"></path>
              </svg>

            </button>

            <!-- NOTIFICATION PANEL -->
            <div
              class="notification-panel"
              id="notificationPanel"
              hidden
            >

              <div class="notification-heading">
                <strong>Notifications</strong>
              </div>

              <div class="notification-empty">
                <span>🔔</span>
                <p>No notifications yet.</p>
                <small>
                  Your updates will appear here.
                </small>
              </div>

            </div>

          </div>


          <!-- PARENT PROFILE -->
          <div class="header-profile">

            <div class="profile-avatar">
              P
            </div>

            <div class="profile-info">
              <span>Parent Account</span>
              <strong>Parent</strong>
            </div>

          </div>

        </div>

      </header>


      <!-- ===============================
           DASHBOARD CONTENT
           =============================== -->

      <main class="dashboard-content">

        <!-- WELCOME BANNER -->
        <section class="welcome-banner">

          <div class="banner-text">

            <span class="banner-label">
              ✦ PARENT DASHBOARD
            </span>

            <h2>
              Welcome Back,
              <span>Jazarians Parent!</span>
              👋
            </h2>

            <p>
              Here's an overview of your
              children's learning journey.
              Stay connected with their
              progress at JAZRO!
            </p>

          </div>


          <!-- ROBOT DECORATION -->
          <img
            src="../assets/images/points-robot.png"
            alt="Friendly JAZRO robot"
            class="banner-robot"
          >

          <div class="banner-decoration"></div>

        </section>


        <!-- SECTION HEADING -->
        <div class="section-heading">

          <div>
            <h2>Learning Overview</h2>

            <p>
              A quick summary of your
              children's learning information.
            </p>
          </div>

          <span class="overview-tag">
            Overview
          </span>

        </div>


        <!-- OVERVIEW GRID -->
        <section class="overview-grid"
                 aria-label="Learning overview">

          <!-- REGISTERED CHILDREN -->
          <article class="overview-card">

            <div class="stat-icon blue-icon">

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <circle cx="9" cy="8" r="3"></circle>
                <path d="M3 20v-2a6 6 0 0 1 12 0v2"></path>
                <circle cx="17" cy="9" r="2"></circle>
                <path d="M16 15a5 5 0 0 1 5 5"></path>
              </svg>

            </div>

            <div class="stat-details">
              <strong class="stat-number"><?= htmlspecialchars((string) $registeredChildren, ENT_QUOTES, 'UTF-8') ?></strong>
              <h3>Registered Children</h3>
              <p>Total registered students</p>
            </div>

          </article>


          <!-- ACTIVE CLASSES -->
          <article class="overview-card">

            <div class="stat-icon cyan-icon">

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="m2 10 10-5 10 5-10 5-10-5Z"></path>
                <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                <path d="M22 10v6"></path>
              </svg>

            </div>

            <div class="stat-details">
              <strong class="stat-number"><?= htmlspecialchars((string) $activeClasses, ENT_QUOTES, 'UTF-8') ?></strong>
              <h3>Active Classes</h3>
              <p>Enrolled learning classes</p>
            </div>

          </article>


          <!-- ASSESSMENTS -->
          <article class="overview-card">

            <div class="stat-icon lime-icon">

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <rect x="5" y="4" width="14" height="17" rx="2"></rect>
                <path d="M9 4.5V3h6v1.5"></path>
                <path d="m9 11 1.5 1.5L13 10"></path>
                <path d="M9 16h6"></path>
              </svg>

            </div>

            <div class="stat-details">
              <strong class="stat-number pending">—</strong>
              <h3>Assessments</h3>
              <p>Summary not available yet</p>
            </div>

          </article>


          <!-- REPORT CARDS -->
          <article class="overview-card">

            <div class="stat-icon grey-icon">

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"></path>
                <path d="M15 3v5h4"></path>
                <path d="M9 17v-3"></path>
                <path d="M12 17v-6"></path>
                <path d="M15 17v-4"></path>
              </svg>

            </div>

            <div class="stat-details">
              <strong class="stat-number pending">—</strong>
              <h3>Report Cards</h3>
              <p>Reports not available yet</p>
            </div>

          </article>

        </section>


        <!-- MY CHILDREN SHORTCUT -->
        <section class="children-shortcut">

          <div class="shortcut-icon">

            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="9" cy="8" r="3"></circle>
              <path d="M3 20v-2a6 6 0 0 1 12 0v2"></path>
              <circle cx="17" cy="9" r="2"></circle>
              <path d="M16 15a5 5 0 0 1 5 5"></path>
            </svg>

          </div>

          <div class="shortcut-text">
            <h3>My Children</h3>

            <p>
              View your children's profiles,
              class details and learning progress.
            </p>
          </div>

          <a
            href="my-children.html"
            class="shortcut-button"
          >
            View All Children

            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M5 12h14"></path>
              <path d="m13 6 6 6-6 6"></path>
            </svg>

          </a>

        </section>

      </main>


      <!-- FOOTER -->
      <footer class="app-footer">

        <p>
          © <span id="currentYear"></span>
          JAZRO Robotic Academy.
        </p>

        <span>
          Learning & Student Progress Portal
        </span>

      </footer>

    </div>

  </div>


  <!-- MOBILE SIDEBAR BACKDROP -->
  <button
    type="button"
    class="sidebar-backdrop"
    id="sidebarBackdrop"
    aria-label="Close navigation"
    hidden
  ></button>

</body>
</html>
<?php
$conn->close();
?>
