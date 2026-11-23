<?php
/**
 * Shared JAZRO Admin Portal sidebar navigation.
 * Included by dashboard.php, classes.php, students.php, attendance.php,
 * and the other Admin Portal pages.
 */

$currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ($_SERVER['PHP_SELF'] ?? ''));

// Normalize names such as dashboard(6).php if a temporary test copy is used.
$currentPage = preg_replace('/\(\d+\)(?=\.php$)/i', '', $currentPage);

$learningActivityPages = [
    'learning_materials.php',
    'material_assignment.php',
    'blooket.php'
];

$isLearningActivities = in_array($currentPage, $learningActivityPages, true);
?>

<nav class="admin-nav" aria-label="Main navigation">

    <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon">⌂</span>
        <span>Dashboard</span>
    </a>

    <a href="classes.php" class="<?= $currentPage === 'classes.php' ? 'active' : '' ?>">
        <span class="nav-icon">▦</span>
        <span>Classes</span>
    </a>

    <a href="students.php" class="<?= $currentPage === 'students.php' ? 'active' : '' ?>">
        <span class="nav-icon">♧</span>
        <span>Students</span>
    </a>

    <a href="attendance.php" class="<?= $currentPage === 'attendance.php' ? 'active' : '' ?>">
        <span class="nav-icon">✓</span>
        <span>Attendance</span>
    </a>

    <a href="assessments.php" class="<?= $currentPage === 'assessments.php' ? 'active' : '' ?>">
        <span class="nav-icon">☷</span>
        <span>Assessments</span>
    </a>

    <div class="jazro-nav-group <?= $isLearningActivities ? 'open' : '' ?>" id="learningActivitiesGroup">
        <button
            type="button"
            class="jazro-nav-group-title <?= $isLearningActivities ? 'active' : '' ?>"
            id="learningActivitiesToggle"
            aria-expanded="<?= $isLearningActivities ? 'true' : 'false' ?>"
            aria-controls="learningActivitiesSubmenu">
            <span class="nav-icon">▤</span>
            <span class="jazro-nav-label">Learning Activities</span>
            <span class="jazro-nav-chevron" aria-hidden="true">⌄</span>
        </button>

        <div
            class="jazro-nav-submenu"
            id="learningActivitiesSubmenu"
            <?= $isLearningActivities ? '' : 'hidden' ?>>

            <a href="learning_materials.php" class="<?= $currentPage === 'learning_materials.php' ? 'sub-active' : '' ?>">
                Learning Materials
            </a>

            <a href="material_assignment.php" class="<?= $currentPage === 'material_assignment.php' ? 'sub-active' : '' ?>">
                Assign to Session
            </a>

            <a href="blooket.php" class="<?= $currentPage === 'blooket.php' ? 'sub-active' : '' ?>">
                Blooket
            </a>
        </div>
    </div>

    <a href="student_achievements.php" class="<?= $currentPage === 'student_achievements.php' ? 'active' : '' ?>">
        <span class="nav-icon">🏆</span>
        <span>Student Achievements</span>
    </a>

    <a href="recordings.php" class="<?= $currentPage === 'recordings.php' ? 'active' : '' ?>">
        <span class="nav-icon">▷</span>
        <span>Online Recordings</span>
    </a>

    <a href="settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>">
        <span class="nav-icon">⚙</span>
        <span>Settings</span>
    </a>

</nav>

<style>
/* FIXED SIDEBAR LAYOUT: header, scrollable menu, pinned footer. */
.admin-sidebar {
    position: fixed !important;
    top: 0 !important;
    bottom: 0 !important;
    height: 100vh !important;
    height: 100dvh !important;
    min-height: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    box-sizing: border-box !important;
}

/* Header and MAIN MENU caption keep their natural height. */
.admin-sidebar > .admin-brand,
.admin-sidebar > .nav-caption {
    flex: 0 0 auto !important;
}

/* Only the menu scrolls. It receives all space between header and footer. */
.admin-sidebar > nav.admin-nav {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) !important;
    align-content: start !important;
    gap: 6px !important;
    flex: 1 1 0% !important;
    min-height: 0 !important;
    height: auto !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    overscroll-behavior: contain;
    scrollbar-width: thin;
    scrollbar-color: #cbdcf5 transparent;
    padding: 0 2px 8px 0 !important;
}

.admin-sidebar > nav.admin-nav::-webkit-scrollbar {
    width: 6px;
}

.admin-sidebar > nav.admin-nav::-webkit-scrollbar-track {
    background: transparent;
}

.admin-sidebar > nav.admin-nav::-webkit-scrollbar-thumb {
    background: #cbdcf5;
    border-radius: 10px;
}

/* Log Out and academy footer stay visible at the bottom. */
.admin-sidebar > .sidebar-bottom {
    flex: 0 0 auto !important;
    min-height: max-content !important;
    margin-top: 0 !important;
    padding-top: 15px !important;
    border-top: 1px solid var(--line, #e4edfa) !important;
    background: #fff !important;
}

/* Learning Activities wrapper */
.admin-sidebar .admin-nav .jazro-nav-group {
    display: grid;
    gap: 4px;
    min-width: 0;
}

/* Reset browser button appearance and match the normal sidebar links. */
.admin-sidebar .admin-nav .jazro-nav-group-title {
    appearance: none;
    -webkit-appearance: none;
    box-sizing: border-box;
    width: 100%;
    min-height: 43px;
    margin: 0;
    padding: 11px 13px;

    display: flex;
    align-items: center;
    gap: 12px;

    border: 0;
    border-radius: 10px;
    background: transparent;
    box-shadow: none;

    color: #60738f;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    line-height: normal;
    text-align: left;

    cursor: pointer;
    transition: background .2s ease, color .2s ease, box-shadow .2s ease;
}

.admin-sidebar .admin-nav .jazro-nav-group-title:hover {
    background: #f0f6ff;
    color: var(--blue, #2878f5);
}

.admin-sidebar .admin-nav .jazro-nav-group-title.active {
    background: #e8f2ff;
    color: var(--blue, #2878f5);
    box-shadow: inset 3px 0 var(--blue, #2878f5);
}

.admin-sidebar .admin-nav .jazro-nav-group-title .nav-icon {
    flex: 0 0 23px;
}

.admin-sidebar .admin-nav .jazro-nav-label {
    flex: 1;
}

.admin-sidebar .admin-nav .jazro-nav-chevron {
    flex: 0 0 auto;
    margin-left: auto;
    font-size: 12px;
    line-height: 1;
    transition: transform .2s ease;
}

.admin-sidebar .admin-nav .jazro-nav-group.open .jazro-nav-chevron {
    transform: rotate(180deg);
}

/* Learning Activities submenu */
.admin-sidebar .admin-nav .jazro-nav-submenu {
    display: grid;
    gap: 3px;
    min-width: 0;
    margin: 0 0 5px 35px;
    padding: 0 0 0 12px;
    border-left: 1px solid #dce8f8;
}

.admin-sidebar .admin-nav .jazro-nav-submenu[hidden] {
    display: none !important;
}

.admin-sidebar .admin-nav .jazro-nav-submenu a {
    box-sizing: border-box;
    display: flex;
    align-items: center;
    min-height: 36px;
    padding: 9px 10px;
    border-radius: 8px;

    color: #71839d;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.3;
    text-decoration: none;
    transition: background .2s ease, color .2s ease;
}

.admin-sidebar .admin-nav .jazro-nav-submenu a:hover,
.admin-sidebar .admin-nav .jazro-nav-submenu a.sub-active {
    background: #f0f6ff;
    color: var(--blue, #2878f5);
}

.admin-sidebar .admin-nav .jazro-nav-submenu a.sub-active {
    font-weight: 800;
}

/* UNIFY ALL SIDEBAR LINKS: normal items, active items and Log Out. */
.admin-sidebar > nav.admin-nav > a,
.admin-sidebar > .sidebar-bottom > a.logout-link {
    box-sizing: border-box !important;
    width: 100% !important;
    min-height: 43px !important;
    height: 43px !important;
    margin: 0 !important;
    padding: 10px 13px !important;

    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 12px !important;

    border: 0 !important;
    border-radius: 10px !important;
    background: transparent;
    box-shadow: none;
    color: #60738f !important;

    font-family: inherit !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    line-height: 1.2 !important;
    text-align: left !important;
    text-decoration: none !important;
    white-space: nowrap !important;
}

.admin-sidebar > nav.admin-nav > a:hover,
.admin-sidebar > .sidebar-bottom > a.logout-link:hover {
    background: #f0f6ff !important;
    color: #2878f5 !important;
}

.admin-sidebar > nav.admin-nav > a.active {
    background: #e8f2ff !important;
    color: #2878f5 !important;
    box-shadow: inset 3px 0 #2878f5 !important;
    font-weight: 700 !important;
}

.admin-sidebar > nav.admin-nav > a > .nav-icon,
.admin-sidebar > .sidebar-bottom > a.logout-link > .nav-icon {
    display: grid !important;
    flex: 0 0 23px !important;
    width: 23px !important;
    min-width: 23px !important;
    height: 23px !important;
    margin: 0 !important;
    padding: 0 !important;
    place-items: center !important;
    border-radius: 7px !important;
    background: #f0f6ff !important;
    color: inherit !important;
    font-size: 14px !important;
    line-height: 1 !important;
}

.admin-sidebar > .sidebar-bottom {
    display: block !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.admin-sidebar > .sidebar-bottom > a.logout-link {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: flex-start !important;
}

.admin-sidebar > .sidebar-bottom > small {
    display: block !important;
    padding: 14px 12px 0 !important;
    color: #9aaac0 !important;
    font-size: 9px !important;
    font-weight: 400 !important;
    line-height: 1.4 !important;
    white-space: nowrap !important;
}

/* Keep Blooket and the other submenu links in the same active style. */
.admin-sidebar .admin-nav .jazro-nav-submenu a.sub-active {
    background: #e8f2ff !important;
    color: #2878f5 !important;
    font-weight: 700 !important;
}

</style>

<script>
(function () {
    const toggle = document.getElementById('learningActivitiesToggle');
    const group = document.getElementById('learningActivitiesGroup');
    const submenu = document.getElementById('learningActivitiesSubmenu');

    if (!toggle || !group || !submenu || toggle.dataset.listenerAttached === 'true') {
        return;
    }

    toggle.dataset.listenerAttached = 'true';

    toggle.addEventListener('click', function () {
        const isOpen = group.classList.toggle('open');
        submenu.hidden = !isOpen;
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
})();
</script>
