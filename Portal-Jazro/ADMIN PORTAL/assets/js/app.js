
document.addEventListener("DOMContentLoaded", () => {
  const $ = (id) => document.getElementById(id);

  // Password visibility: one eye icon only
  const passwordInput = $("password");
  const togglePassword = $("togglePassword");

  togglePassword?.addEventListener("click", () => {
    const showPassword = passwordInput.type === "password";

    passwordInput.type = showPassword ? "text" : "password";
    togglePassword.setAttribute("aria-pressed", String(showPassword));
    togglePassword.setAttribute(
      "aria-label",
      showPassword ? "Hide password" : "Show password"
    );
  });

  // Forgot password modal
  const forgotModal = $("forgotModal");

  const openForgotModal = () => {
    if (forgotModal) forgotModal.hidden = false;
  };

  const closeForgotModal = () => {
    if (forgotModal) forgotModal.hidden = true;
  };

  $("forgotLink")?.addEventListener("click", openForgotModal);
  $("closeForgotModal")?.addEventListener("click", closeForgotModal);
  $("forgotOkay")?.addEventListener("click", closeForgotModal);

  forgotModal?.addEventListener("click", (event) => {
    if (event.target === forgotModal) closeForgotModal();
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      if (forgotModal && !forgotModal.hidden) closeForgotModal();

      document.querySelectorAll(".modal:not([hidden])").forEach((modal) => {
        modal.hidden = true;
      });
    }
  });

  // Display current date
  const dateTarget = $("todayDate");

  if (dateTarget) {
    dateTarget.textContent = new Intl.DateTimeFormat("en-GB", {
      weekday: "long",
      day: "2-digit",
      month: "short",
      year: "numeric"
    }).format(new Date());
  }

  // Mobile sidebar
  $("mobileMenu")?.addEventListener("click", () => {
    $("sidebar")?.classList.toggle("sidebar-open");
  });

  // Table search and filters
  function setupTable({ tableId, searchId, filters, countId, resultId, noun }) {
    const table = $(tableId);
    if (!table) return;

    const rows = Array.from(table.querySelectorAll("tbody tr"));

    const run = () => {
      const query = ($(searchId)?.value || "").trim().toLowerCase();

      const filterValues = filters.map(({ id, attribute }) => ({
        value: $(id)?.value || "",
        attribute
      }));

      let visible = 0;

      rows.forEach((row) => {
        const textMatch = row.innerText.toLowerCase().includes(query);

        const filterMatch = filterValues.every(({ value, attribute }) => {
          return !value || row.dataset[attribute] === value;
        });

        const show = textMatch && filterMatch;
        row.hidden = !show;

        if (show) visible++;
      });

      if ($(countId)) {
        $(countId).textContent =
          `${visible} ${noun}${visible === 1 ? "" : "s"}`;
      }

      if ($(resultId)) {
        $(resultId).textContent =
          `Showing ${visible} sample ${noun}${visible === 1 ? "" : "s"}`;
      }
    };

    $(searchId)?.addEventListener("input", run);
    filters.forEach(({ id }) => $(id)?.addEventListener("change", run));
    run();
  }

  setupTable({
    tableId: "classesTable",
    searchId: "classSearch",
    filters: [
      { id: "typeFilter", attribute: "type" },
      { id: "statusFilter", attribute: "status" }
    ],
    countId: "classCount",
    resultId: "tableResultText",
    noun: "class"
  });

  setupTable({
    tableId: "studentsTable",
    searchId: "studentSearch",
    filters: [
      { id: "studentStatusFilter", attribute: "status" }
    ],
    countId: "studentCount",
    resultId: "studentResultText",
    noun: "student"
  });

  // Reusable demo modal helper
  function setupModal({ modalId, openId, closeIds, formId, onSubmit }) {
    const modal = $(modalId);
    if (!modal) return;

    const open = () => {
      modal.hidden = false;
      modal.querySelector("input, select")?.focus();
    };

    const close = () => {
      modal.hidden = true;
      $(formId)?.reset();
    };

    $(openId)?.addEventListener("click", open);
    closeIds.forEach((id) => $(id)?.addEventListener("click", close));

    modal.addEventListener("click", (event) => {
      if (event.target === modal) close();
    });

    $(formId)?.addEventListener("submit", (event) => {
      event.preventDefault();
      onSubmit();
      close();
    });
  }

  setupModal({
    modalId: "classModal",
    openId: "addClassBtn",
    closeIds: ["closeClassModal", "cancelClass"],
    formId: "classForm",
    onSubmit: () => {
      const code = $("classCode").value.trim();
      const name = $("className").value.trim();
      const category = $("classCategory").value;
      const type = $("classType").value;
      const day = $("classDay").value;
      const time = $("classTime").value || "Time TBC";
      const tbody = $("classesTable")?.querySelector("tbody");

      if (!tbody) return;

      const row = document.createElement("tr");
      row.dataset.type = type;
      row.dataset.status = "Active";

      const values = [
        `<b>${escapeHTML(code)}</b>`,
        `<div class="cell-title">${escapeHTML(name)}</div><small>New demo class</small>`,
        escapeHTML(category),
        `<span class="type-pill ${type === "Online" ? "online-pill" : ""}">${escapeHTML(type)}</span>`,
        `${escapeHTML(day)} · ${escapeHTML(time)}`,
        "0",
        '<span class="status-pill active-status">Active</span>',
        '<button class="more-button" aria-label="More actions">•••</button>'
      ];

      values.forEach((value) => {
        const td = document.createElement("td");
        td.innerHTML = value;
        row.appendChild(td);
      });

      tbody.prepend(row);

      if ($("classSearch")) $("classSearch").value = "";
      if ($("typeFilter")) $("typeFilter").value = "";
      if ($("statusFilter")) $("statusFilter").value = "";
      $("classSearch")?.dispatchEvent(new Event("input"));
    }
  });

  setupModal({
    modalId: "studentModal",
    openId: "addStudentBtn",
    closeIds: ["closeStudentModal", "cancelStudent"],
    formId: "studentForm",
    onSubmit: () => {
      const name = $("studentName").value.trim();
      const id = $("studentId").value.trim();
      const parent = $("studentParent").value.trim() || "—";
      const studentClass = $("studentClass").value.trim() || "—";
      const age = $("studentAge").value || "—";
      const tbody = $("studentsTable")?.querySelector("tbody");

      if (!tbody) return;

      const initials = name.split(/\s+/).slice(0, 2)
        .map((part) => part[0]).join("").toUpperCase();

      const row = document.createElement("tr");
      row.dataset.status = "Active";

      const cells = [
        `<div class="student-cell"><span class="student-avatar avatar-purple">${escapeHTML(initials)}</span><div><b>${escapeHTML(name)}</b><small>New demo record</small></div></div>`,
        `${escapeHTML(id)} (demo)`,
        escapeHTML(parent),
        escapeHTML(studentClass),
        escapeHTML(age),
        '<span class="status-pill active-status">Active</span>',
        '<button class="more-button" aria-label="More actions">•••</button>'
      ];

      cells.forEach((value) => {
        const td = document.createElement("td");
        td.innerHTML = value;
        row.appendChild(td);
      });

      tbody.prepend(row);

      if ($("studentSearch")) $("studentSearch").value = "";
      if ($("studentStatusFilter")) $("studentStatusFilter").value = "";
      $("studentSearch")?.dispatchEvent(new Event("input"));
    }
  });

  // Header search demo
  $("globalSearch")?.addEventListener("keydown", (event) => {
    if (event.key !== "Enter" || !event.currentTarget.value.trim()) return;

    const term = event.currentTarget.value.trim();
    const localSearch = $("classSearch") || $("studentSearch");

    if (localSearch) {
      localSearch.value = term;
      localSearch.dispatchEvent(new Event("input"));
    } else {
      alert("Global search will be connected across modules later.");
    }
  });

  function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, (char) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;"
    })[char]);
  }
});
