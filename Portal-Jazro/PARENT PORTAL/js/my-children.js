
/* =========================================
   JAZRO PARENT PORTAL - MY CHILDREN
   DATABASE VERSION
   ========================================= */

document.addEventListener("DOMContentLoaded", function () {

  // COPYRIGHT YEAR
  const year = new Date().getFullYear();

  document.getElementById("currentYear").textContent = year;
  document.getElementById("sidebarYear").textContent = year;

  const childrenList = document.getElementById("childrenList");
  const childrenCount = document.getElementById("childrenCount");

  // ESCAPE DATABASE TEXT BEFORE DISPLAYING IT
  function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, function (character) {
      const entities = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
      };

      return entities[character];
    });
  }

  // DISPLAY CHILDREN FROM DATABASE
  function renderChildren(children) {

    childrenCount.textContent = children.length;

    if (children.length === 0) {
      childrenList.innerHTML = `
        <p>No children are registered under this account yet.</p>
      `;
      return;
    }

    childrenList.innerHTML = children.map(function (child, index) {

      const name = escapeHtml(child.student_name || "Unnamed Student");
      const age = child.age !== null && child.age !== ""
        ? escapeHtml(child.age)
        : "Not available";

      const className = child.class_name
        ? escapeHtml(child.class_name)
        : "Class not assigned";

      const classType = String(child.class_type || "").toLowerCase();

      let badgeClass = "";
      let badgeText = "Class not assigned";

      if (classType === "physical") {
        badgeClass = "physical";
        badgeText = "Physical Class";
      } else if (classType === "online") {
        badgeClass = "online";
        badgeText = "Online Class";
      }

      const location = escapeHtml(
        child.location || "Location not available"
      );

      const gender = String(child.gender || "").toLowerCase();

      const image = (
        gender === "female" || gender === "girl"
      )
        ? "../assets/images/pfp-girl.png"
        : "../assets/images/pfp-boy.png";

      const studentNumber = String(index + 1).padStart(2, "0");

      return `
        <article class="child-card">

          <div class="child-photo-frame">
            <img
              src="${image}"
              alt="${name}"
              class="child-photo"
            >
          </div>

          <div class="child-information">

            <div class="child-name-row">

              <div>
                <span class="student-number">
                  STUDENT ${studentNumber}
                </span>

                <h3>${name}</h3>

                <p class="student-age">
                  <span class="detail-key">Age:</span>
                  ${age} Years Old
                </p>
              </div>

              <span class="class-badge ${badgeClass}">
                ${badgeText}
              </span>

            </div>

            <div class="student-detail">

              <div class="student-detail-icon">
                <img
                  src="../assets/icons/class-icon.png"
                  alt=""
                >
              </div>

              <div>
                <span class="detail-label">Class</span>
                <strong>${className}</strong>
              </div>

            </div>

            <div class="student-detail">

              <div class="student-detail-icon">
                <img
                  src="../assets/icons/location-icon.png"
                  alt=""
                >
              </div>

              <div>
                <span class="detail-label">Location</span>
                <strong>${location}</strong>
              </div>

            </div>

          </div>

          <div class="child-action">

            <button
              type="button"
              class="view-progress-btn"
              data-student-id="${escapeHtml(child.student_id)}"
            >

              View Progress

              <svg viewBox="0 0 24 24"
                   fill="none"
                   stroke="currentColor"
                   stroke-width="2"
                   stroke-linecap="round"
                   stroke-linejoin="round">
                <path d="M5 12h14"></path>
                <path d="m13 6 6 6-6 6"></path>
              </svg>

            </button>

          </div>

        </article>
      `;

    }).join("");

    // VIEW PROGRESS BUTTONS
    document.querySelectorAll(".view-progress-btn").forEach(function (button) {

      button.addEventListener("click", function () {

        const studentId = button.dataset.studentId;

        sessionStorage.setItem(
          "jazroSelectedStudentId",
          studentId
        );

        window.location.href = "child-dashboard.html";

      });

    });

  }

  // FETCH CHILDREN FROM PHP DATABASE ENDPOINT
  async function loadChildren() {

    try {

      const response = await fetch("../get-children.php", {
        method: "GET",
        credentials: "same-origin"
      });

      if (response.status === 401) {
        window.location.replace("../index.html");
        return;
      }

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || "Unable to load children.");
      }

      renderChildren(data.children || []);

    } catch (error) {

      console.error("My Children Error:", error);

      childrenList.innerHTML = `
        <p>Unable to load children. Please refresh the page and try again.</p>
      `;

    }

  }

  loadChildren();


  // NOTIFICATION PANEL
  const notificationBtn = document.getElementById("notificationBtn");
  const notificationPanel = document.getElementById("notificationPanel");

  function closeNotifications() {
    notificationPanel.hidden = true;
    notificationBtn.setAttribute("aria-expanded", "false");
  }

  notificationBtn.addEventListener("click", function (event) {

    event.stopPropagation();

    const shouldOpen = notificationPanel.hidden;
    notificationPanel.hidden = !shouldOpen;

    notificationBtn.setAttribute(
      "aria-expanded",
      String(shouldOpen)
    );

  });

  document.addEventListener("click", function (event) {

    if (
      !notificationPanel.hidden &&
      !notificationPanel.contains(event.target) &&
      !notificationBtn.contains(event.target)
    ) {
      closeNotifications();
    }

  });


  // MOBILE SIDEBAR
  const sidebar = document.getElementById("sidebar");
  const menuToggle = document.getElementById("menuToggle");
  const backdrop = document.getElementById("sidebarBackdrop");

  function closeSidebar() {

    sidebar.classList.remove("open");
    backdrop.hidden = true;

    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.setAttribute("aria-label", "Open navigation");

  }

  menuToggle.addEventListener("click", function () {

    const open = !sidebar.classList.contains("open");

    sidebar.classList.toggle("open", open);
    backdrop.hidden = !open;

    menuToggle.setAttribute("aria-expanded", String(open));

    menuToggle.setAttribute(
      "aria-label",
      open ? "Close navigation" : "Open navigation"
    );

  });

  backdrop.addEventListener("click", closeSidebar);

  document.addEventListener("keydown", function (event) {

    if (event.key === "Escape") {
      closeNotifications();
      closeSidebar();
    }

  });


  // LOGOUT
  document.getElementById("sidebarLogout")
    .addEventListener("click", async function () {

      try {
        await fetch("../logout.php", {
          method: "POST",
          credentials: "same-origin"
        });
      } catch (error) {
        console.error("Logout error:", error);
      }

      sessionStorage.clear();
      window.location.replace("../index.html");

    });

});
