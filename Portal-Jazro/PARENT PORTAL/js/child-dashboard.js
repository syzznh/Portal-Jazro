/* JAZRO PARENT PORTAL - STUDENT PROGRESS */
document.addEventListener("DOMContentLoaded", function () {
  loadSelectedStudent();
  setupModuleButtons();
  setupNotifications();
  setupMobileSidebar();
  setupLogout();
  updateYear();
});

async function loadSelectedStudent() {
  const studentId = sessionStorage.getItem("jazroSelectedStudentId");
  if (!studentId) {
    showMessage("No student selected. Please go back to My Children.");
    return;
  }

  try {
    const response = await fetch("../get-children.php", {
      method: "GET",
      credentials: "same-origin"
    });

    if (response.status === 401) {
      window.location.replace("../index.html");
      return;
    }
    if (!response.ok) throw new Error("Unable to connect to the database.");

    const data = await response.json();
    if (!data.success || !Array.isArray(data.children)) {
      throw new Error("Invalid database response.");
    }

    const student = data.children.find(function (child) {
      return String(child.student_id) === String(studentId);
    });

    if (!student) {
      showMessage("Student not found. Please return to My Children and select again.");
      return;
    }
    displayStudent(student);
  } catch (error) {
    console.error("Child Dashboard Error:", error);
    showMessage("Unable to load student information. Please refresh the page.");
  }
}

function displayStudent(student) {
  const name = student.student_name || "Student";
  const classType = String(student.class_type || "").toLowerCase();
  const isOnline = classType === "online";

  const setText = function (id, value) {
    const element = document.getElementById(id);
    if (element) element.textContent = value;
  };

  setText("studentName", name);
  setText("studentAge", student.age || "-");
  setText("studentClass", student.class_name || "Class not assigned");
  setText("studentLocation", student.location || "Location not available");
  setText("headerStudentTitle", name + " Progress");
  setText("progressTitle", name + " Progress");

  const photo = document.getElementById("studentPhoto");
  if (photo) {
    const gender = String(student.gender || "").toLowerCase();
    photo.src = (gender === "female" || gender === "girl")
      ? "../assets/images/pfp-girl.png"
      : "../assets/images/pfp-boy.png";
    photo.alt = name;
  }

  const badge = document.getElementById("studentClassType");
  if (badge) {
    badge.textContent = isOnline ? "Online Class" : "Physical Class";
    badge.className = isOnline ? "class-badge online" : "class-badge physical";
  }

  const recordingCard = document.getElementById("recordingCard");
  const recordingSidebar = document.getElementById("recordingSidebar");
  if (recordingCard) recordingCard.hidden = !isOnline;
  if (recordingSidebar) recordingSidebar.hidden = !isOnline;

  setText("moduleCount", isOnline ? "4 Modules" : "3 Modules");
  document.title = name + " Progress | JAZRO Parent Portal";
}

function setupModuleButtons() {
  document.querySelectorAll("[data-module]").forEach(function (button) {
    button.addEventListener("click", function (event) {
      event.preventDefault();
      const module = button.dataset.module;

      if (module === "notes") {
        window.location.href = "class-notes.html";
      } else if (module === "assessments") {
        window.location.href = "assessments.html";
      } else if (module === "achievements") {
        window.location.href = "achievements.html";
      } else if (module === "recordings") {
        showMessage("Online Class Recordings page is coming soon.");
      }
    });
  });
}

function showMessage(message) {
  const toast = document.getElementById("moduleToast");
  if (!toast) {
    alert(message);
    return;
  }
  toast.textContent = message;
  toast.hidden = false;
  toast.classList.add("show");
  clearTimeout(window.messageTimer);
  window.messageTimer = setTimeout(function () {
    toast.classList.remove("show");
    toast.hidden = true;
  }, 3000);
}

function setupNotifications() {
  const button = document.getElementById("notificationBtn");
  const panel = document.getElementById("notificationPanel");
  if (!button || !panel) return;

  button.addEventListener("click", function (event) {
    event.stopPropagation();
    const opening = panel.hidden;
    panel.hidden = !opening;
    button.setAttribute("aria-expanded", String(opening));
  });

  document.addEventListener("click", function (event) {
    if (!panel.hidden && !panel.contains(event.target) && !button.contains(event.target)) {
      panel.hidden = true;
      button.setAttribute("aria-expanded", "false");
    }
  });
}

function setupMobileSidebar() {
  const sidebar = document.getElementById("sidebar");
  const toggle = document.getElementById("menuToggle");
  const backdrop = document.getElementById("sidebarBackdrop");
  if (!sidebar || !toggle || !backdrop) return;

  toggle.addEventListener("click", function () {
    const opening = !sidebar.classList.contains("open");
    sidebar.classList.toggle("open", opening);
    backdrop.hidden = !opening;
    toggle.setAttribute("aria-expanded", String(opening));
  });

  backdrop.addEventListener("click", function () {
    sidebar.classList.remove("open");
    backdrop.hidden = true;
    toggle.setAttribute("aria-expanded", "false");
  });
}

function setupLogout() {
  const button = document.getElementById("sidebarLogout");
  if (!button) return;

  button.addEventListener("click", async function (event) {
    event.preventDefault();
    try {
      await fetch("../logout.php", { method: "POST", credentials: "same-origin" });
    } catch (error) {
      console.error("Logout Error:", error);
    }
    sessionStorage.removeItem("jazroDemoLoggedIn");
    sessionStorage.removeItem("jazroDemoParent");
    sessionStorage.removeItem("jazroSelectedStudentId");
    sessionStorage.removeItem("jazroSelectedChild");
    window.location.replace("../index.html");
  });
}

function updateYear() {
  const year = new Date().getFullYear();
  const current = document.getElementById("currentYear");
  const sidebar = document.getElementById("sidebarYear");
  if (current) current.textContent = year;
  if (sidebar) sidebar.textContent = year;
}
