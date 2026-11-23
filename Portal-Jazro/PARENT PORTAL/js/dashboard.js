/* ========================================
   JAZRO PARENT PORTAL
   DASHBOARD JAVASCRIPT
   ======================================== */

document.addEventListener("DOMContentLoaded", function () {

  // ======================================
  // GET ELEMENTS
  // ======================================

  const notificationBtn =
    document.getElementById("notificationBtn");

  const notificationPanel =
    document.getElementById("notificationPanel");

  const sidebarLogout =
    document.getElementById("sidebarLogout");

  const sidebar =
    document.getElementById("sidebar");

  const menuToggle =
    document.getElementById("menuToggle");

  const sidebarBackdrop =
    document.getElementById("sidebarBackdrop");


  // ======================================
  // SET COPYRIGHT YEAR
  // ======================================

  const year = new Date().getFullYear();

  const currentYear =
    document.getElementById("currentYear");

  const sidebarYear =
    document.getElementById("sidebarYear");

  if (currentYear) {
    currentYear.textContent = year;
  }

  if (sidebarYear) {
    sidebarYear.textContent = year;
  }


  // ======================================
  // NOTIFICATION DROPDOWN
  // ======================================

  function closeNotifications() {
    notificationPanel.hidden = true;
    notificationBtn.setAttribute(
      "aria-expanded",
      "false"
    );
  }

  notificationBtn.addEventListener("click", function (event) {

    event.stopPropagation();

    const isOpen = !notificationPanel.hidden;

    notificationPanel.hidden = isOpen;

    notificationBtn.setAttribute(
      "aria-expanded",
      String(!isOpen)
    );

  });

  // Close when clicking outside
  document.addEventListener("click", function (event) {

    if (
      !notificationPanel.hidden &&
      !notificationPanel.contains(event.target) &&
      !notificationBtn.contains(event.target)
    ) {
      closeNotifications();
    }

  });


  // ======================================
  // MOBILE SIDEBAR
  // ======================================

  function openSidebar() {
    sidebar.classList.add("open");
    sidebarBackdrop.hidden = false;

    menuToggle.setAttribute(
      "aria-expanded",
      "true"
    );

    menuToggle.setAttribute(
      "aria-label",
      "Close navigation"
    );
  }

  function closeSidebar() {
    sidebar.classList.remove("open");
    sidebarBackdrop.hidden = true;

    menuToggle.setAttribute(
      "aria-expanded",
      "false"
    );

    menuToggle.setAttribute(
      "aria-label",
      "Open navigation"
    );
  }

  if (menuToggle && sidebarBackdrop) {

    menuToggle.addEventListener("click", function () {

      if (sidebar.classList.contains("open")) {
        closeSidebar();
      } else {
        openSidebar();
      }

    });

    sidebarBackdrop.addEventListener(
      "click",
      closeSidebar
    );

  }


  // ======================================
  // ESCAPE KEY
  // ======================================

  document.addEventListener("keydown", function (event) {

    if (event.key === "Escape") {
      closeNotifications();
      closeSidebar();
    }

  });


  // ======================================
  // LOG OUT
  // ======================================

  if (sidebarLogout) {
    sidebarLogout.addEventListener("click", function () {
      // End the PHP session before returning to the login page.
      window.location.href = "../logout.php";
    });
  }

});

