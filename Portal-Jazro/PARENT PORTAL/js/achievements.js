/* JAZRO ACHIEVEMENTS - FRONTEND UI PREVIEW */
const reportSamples = {
  term3: {
    label: "Term 3 · 2026", score: "88%", grade: "Excellent progress",
    rows: [
      ["Robotics Knowledge", "90%", "Excellent", "excellent"],
      ["Coding Skills", "85%", "Very Good", "good"],
      ["Problem Solving", "88%", "Excellent", "excellent"]
    ],
    remark: "You have shown good understanding of the topics covered this term. Keep practising your coding skills and continue asking questions. Well done!"
  },
  term2: {
    label: "Term 2 · 2026", score: "84%", grade: "Very good progress",
    rows: [
      ["Robotics Knowledge", "86%", "Very Good", "good"],
      ["Coding Skills", "82%", "Very Good", "good"],
      ["Problem Solving", "84%", "Very Good", "good"]
    ],
    remark: "You are making steady progress and becoming more confident during class activities. Keep up the consistent effort!"
  },
  term1: {
    label: "Term 1 · 2026", score: "79%", grade: "Good progress",
    rows: [
      ["Robotics Knowledge", "80%", "Very Good", "good"],
      ["Coding Skills", "76%", "Good", "good"],
      ["Problem Solving", "81%", "Very Good", "good"]
    ],
    remark: "You have made a good start this term. Continue building your confidence by practising the concepts learned in class."
  }
};

document.addEventListener("DOMContentLoaded", function () {
  loadStudentPreview();
  setupTabs();
  setupTermSelector();
  setupCertificatePreview();
  setupPrintButtons();
  setupNavigation();
  setupNotifications();
  setupMobileSidebar();
  setupLogout();
  updateYear();
});

async function loadStudentPreview() {
  const selectedId = sessionStorage.getItem("jazroSelectedStudentId");
  if (!selectedId) {
    setStudentPreview("Selected Student", "Class details unavailable", "../assets/images/pfp-boy.png");
    return;
  }

  try {
    const response = await fetch("../get-children.php", { credentials: "same-origin" });
    if (response.status === 401) {
      window.location.replace("../index.html");
      return;
    }
    if (!response.ok) throw new Error("Unable to load student.");
    const data = await response.json();
    const student = Array.isArray(data.children)
      ? data.children.find(item => String(item.student_id) === String(selectedId))
      : null;

    if (!data.success || !student) {
      setStudentPreview("Selected Student", "Student information unavailable", "../assets/images/pfp-boy.png");
      return;
    }

    const gender = String(student.gender || "").toLowerCase();
    const photo = (gender === "female" || gender === "girl")
      ? "../assets/images/pfp-girl.png"
      : "../assets/images/pfp-boy.png";

    setStudentPreview(
      student.student_name || "Student",
      student.class_name || "Class not assigned",
      photo
    );
  } catch (error) {
    console.error("Achievements student preview:", error);
    setStudentPreview("Selected Student", "Unable to load class details", "../assets/images/pfp-boy.png");
  }
}

function setStudentPreview(name, className, photoPath) {
  const nameEl = document.getElementById("studentName");
  const classEl = document.getElementById("studentClass");
  const photoEl = document.getElementById("studentPhoto");
  const headerEl = document.getElementById("headerStudentTitle");
  const reportName = document.getElementById("reportStudentName");
  const reportClass = document.getElementById("reportStudentClass");
  const modalName = document.getElementById("modalStudentName");

  if (nameEl) nameEl.textContent = name;
  if (classEl) classEl.textContent = className;
  if (photoEl) { photoEl.src = photoPath; photoEl.alt = name; }
  if (headerEl) headerEl.textContent = name + " · Achievements";
  if (reportName) reportName.textContent = name;
  if (reportClass) reportClass.textContent = className;
  if (modalName) modalName.textContent = name;

  document.title = name + " Achievements | JAZRO Parent Portal";
}

function setupTabs() {
  const certTab = document.getElementById("certificatesTab");
  const reportTab = document.getElementById("reportsTab");
  const certPanel = document.getElementById("certificatesPanel");
  const reportPanel = document.getElementById("reportsPanel");

  function activate(which) {
    const certificates = which === "certificates";
    certTab.classList.toggle("active", certificates);
    reportTab.classList.toggle("active", !certificates);
    certTab.setAttribute("aria-selected", String(certificates));
    reportTab.setAttribute("aria-selected", String(!certificates));
    certPanel.hidden = !certificates;
    reportPanel.hidden = certificates;
  }
  certTab.addEventListener("click", () => activate("certificates"));
  reportTab.addEventListener("click", () => activate("reports"));
}

function setupTermSelector() {
  const selector = document.getElementById("termSelect");
  if (!selector) return;
  selector.addEventListener("change", function () {
    const sample = reportSamples[selector.value];
    if (!sample) return;
    document.getElementById("reportTermLabel").textContent = sample.label;
    document.getElementById("overallScore").textContent = sample.score;
    document.getElementById("overallGrade").textContent = sample.grade;
    document.getElementById("tutorRemark").textContent = sample.remark;
    document.getElementById("performanceRows").innerHTML = sample.rows.map(row =>
      `<tr><td>${row[0]}</td><td>${row[1]}</td><td><span class="performance-pill ${row[3]}">${row[2]}</span></td></tr>`
    ).join("");
  });
}

function setupCertificatePreview() {
  const modal = document.getElementById("certificateModal");
  const close = document.getElementById("closeCertificateModal");
  document.querySelectorAll(".preview-certificate").forEach(function (button) {
    button.addEventListener("click", function () {
      const card = button.closest(".certificate-card");
      const title = card.querySelector("h4").textContent;
      document.getElementById("modalTitle").textContent = title.toUpperCase();
      modal.hidden = false;
    });
  });
  close.addEventListener("click", () => modal.hidden = true);
  modal.addEventListener("click", event => {
    if (event.target === modal) modal.hidden = true;
  });
  document.addEventListener("keydown", event => {
    if (event.key === "Escape") modal.hidden = true;
  });
}

function printCurrentContent() {
  window.print();
}

function setupPrintButtons() {
  document.getElementById("printReport").addEventListener("click", printCurrentContent);
  document.getElementById("printCertificate").addEventListener("click", printCurrentContent);
}

function setupNavigation() {
  document.querySelectorAll("[data-go]").forEach(button => {
    button.addEventListener("click", () => {
      if (button.dataset.go !== "#") window.location.href = button.dataset.go;
    });
  });
}

function setupNotifications() {
  const button = document.getElementById("notificationBtn");
  const panel = document.getElementById("notificationPanel");
  if (!button || !panel) return;
  button.addEventListener("click", event => {
    event.stopPropagation();
    const opening = panel.hidden;
    panel.hidden = !opening;
    button.setAttribute("aria-expanded", String(opening));
  });
  document.addEventListener("click", event => {
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
  toggle.addEventListener("click", () => {
    const opening = !sidebar.classList.contains("open");
    sidebar.classList.toggle("open", opening);
    backdrop.hidden = !opening;
    toggle.setAttribute("aria-expanded", String(opening));
  });
  backdrop.addEventListener("click", () => {
    sidebar.classList.remove("open");
    backdrop.hidden = true;
    toggle.setAttribute("aria-expanded", "false");
  });
}

function setupLogout() {
  const button = document.getElementById("sidebarLogout");
  if (!button) return;
  button.addEventListener("click", async function () {
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
