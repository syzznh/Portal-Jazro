/* =========================================
   JAZRO PARENT PORTAL - CLASS NOTES
   DATABASE VERSION
========================================= */
document.addEventListener("DOMContentLoaded", function () {
  const studentId = sessionStorage.getItem("jazroSelectedStudentId");

  const folderGrid = document.getElementById("folderGrid");
  const folderDetail = document.getElementById("folderDetail");
  const emptyState = document.getElementById("emptyState");
  const loadingState = document.getElementById("loadingState");
  const errorState = document.getElementById("errorState");
  const fileList = document.getElementById("fileList");
  const backButton = document.getElementById("backButton");
  const emptyBackButton = document.getElementById("emptyBackButton");

  const folders = [];
  let isOnlineStudent = false;

  // Copyright year
  const year = new Date().getFullYear();
  const currentYear = document.getElementById("currentYear");
  const sidebarYear = document.getElementById("sidebarYear");
  if (currentYear) currentYear.textContent = year;
  if (sidebarYear) sidebarYear.textContent = year;

  function showOnly(view) {
    folderGrid.hidden = view !== "folders";
    folderDetail.hidden = view !== "detail";
    emptyState.hidden = view !== "empty";
    loadingState.hidden = view !== "loading";
    errorState.hidden = view !== "error";
  }

  function showError(message) {
    errorState.textContent = message;
    showOnly("error");
  }

  function makeElement(tag, className, text) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;
    return element;
  }

  function updateStudentProfile(student, classType) {
    const name = student.student_name || "Student";
    const gender = String(student.gender || "").toLowerCase();
    const isFemale = gender === "female" || gender === "girl";

    const photo = document.getElementById("studentPhoto");
    if (photo) {
      photo.src = isFemale ? "../assets/images/pfp-girl.png" : "../assets/images/pfp-boy.png";
      photo.alt = name;
    }

    const nameElement = document.getElementById("studentName");
    const ageElement = document.getElementById("studentAge");
    const classElement = document.getElementById("studentClass");
    const locationElement = document.getElementById("studentLocation");
    const badge = document.getElementById("studentClassBadge");

    if (nameElement) nameElement.textContent = name;
    if (ageElement) ageElement.textContent = student.age ? `${student.age} Years Old` : "Not available";
    if (classElement) classElement.textContent = student.class_name || "Class not assigned";
    if (locationElement) locationElement.textContent = classType === "online" ? "Online / Virtual Class" : (student.class_category || "Physical Class");

    if (badge) {
      badge.textContent = classType === "online" ? "Online Class" : "Physical Class";
      badge.className = `class-badge ${classType === "online" ? "online" : "physical"}`;
    }

    document.title = `${name} - Class Notes | JAZRO Parent Portal`;
  }

  function buildFolders(materials) {
    const grouped = new Map();

    if (isOnlineStudent) {
      // Keep all five JTC levels visible, even when a level has no uploaded PDFs.
      for (let level = 1; level <= 5; level++) {
        grouped.set(`JTC-${level}`, {
          key: `JTC-${level}`,
          title: `JTC LEVEL ${level}`,
          subtitle: `JTC Level ${level} class learning materials`,
          files: []
        });
      }

      materials.forEach(function (material) {
        const level = Number(material.level);
        if (!Number.isInteger(level) || level < 1 || level > 5) return;
        grouped.get(`JTC-${level}`).files.push(material);
      });
    } else {
      // Physical materials are grouped by their actual module category and level.
      materials.forEach(function (material) {
        const category = String(material.module_category || "Physical Materials").trim();
        const level = material.level !== null && material.level !== "" ? String(material.level) : "";
        const key = `${category}::${level}`;
        if (!grouped.has(key)) {
          grouped.set(key, {
            key,
            title: level ? `${category} - LEVEL ${level}` : category,
            subtitle: "Physical class learning materials",
            files: []
          });
        }
        grouped.get(key).files.push(material);
      });
    }

    return Array.from(grouped.values());
  }

  function renderFolders() {
    folderGrid.innerHTML = "";

    if (folders.length === 0) {
      document.getElementById("emptyTitle").textContent = "No learning materials available yet";
      document.getElementById("emptyMessage").textContent = "The academy has not uploaded learning materials for this class yet.";
      emptyBackButton.hidden = true;
      showOnly("empty");
      return;
    }

    folders.forEach(function (folder) {
      const card = makeElement("button", "folder-card");
      card.type = "button";
      card.dataset.folderKey = folder.key;

      const top = makeElement("div", "folder-card-top");
      top.appendChild(makeElement("span", "folder-icon", "📁"));
      top.appendChild(makeElement("span", "folder-open-arrow", "↗"));

      const title = makeElement("h3", "", folder.title);
      const subtitle = makeElement("p", "", folder.subtitle);
      const status = makeElement("span", `folder-file-status${folder.files.length ? " has-files" : ""}`,
        `${folder.files.length} ${folder.files.length === 1 ? "PDF file" : "PDF files"}`);

      card.append(top, title, subtitle, status);
      card.addEventListener("click", function () { openFolder(folder.key); });
      folderGrid.appendChild(card);
    });

    showOnly("folders");
  }

  function makeFileUrl(fileUrl) {
    if (!fileUrl || !fileUrl.startsWith("uploads/learning_materials/") || fileUrl.includes("..")) {
      return null;
    }
    return "../" + fileUrl.split("/").map(encodeURIComponent).join("/");
  }

  function openFolder(folderKey) {
    const folder = folders.find(item => item.key === folderKey);
    if (!folder) return;

    document.getElementById("openedFolderTitle").textContent = folder.title;
    document.getElementById("openedFolderSubtitle").textContent =
      `${folder.files.length} ${folder.files.length === 1 ? "file" : "files"} available`;
    fileList.innerHTML = "";

    if (folder.files.length === 0) {
      document.getElementById("emptyTitle").textContent = "No notes available yet";
      document.getElementById("emptyMessage").textContent = "Learning materials for this folder will be added soon.";
      emptyBackButton.hidden = false;
      showOnly("empty");
      return;
    }

    folder.files.forEach(function (file) {
      const row = makeElement("div", "file-row");
      row.appendChild(makeElement("div", "pdf-icon", "PDF"));

      const info = makeElement("div", "file-info");
      info.appendChild(makeElement("strong", "", file.material_title || file.module_name || "Learning Material.pdf"));
      const description = file.description || file.module_code || "PDF Document • Class Notes";
      info.appendChild(makeElement("span", "", description));
      row.appendChild(info);

      const actions = makeElement("div", "file-actions");
      const url = makeFileUrl(file.file_url);

      if (url) {
        const view = makeElement("a", "file-button", "View PDF");
        view.href = url;
        view.target = "_blank";
        view.rel = "noopener noreferrer";

        const download = makeElement("a", "file-button download", "Download");
        download.href = url;
        download.download = file.material_title || "learning-material.pdf";
        actions.append(view, download);
      } else {
        actions.appendChild(makeElement("span", "file-unavailable", "File unavailable"));
      }

      row.appendChild(actions);
      fileList.appendChild(row);
    });

    showOnly("detail");
  }

  async function loadClassNotes() {
    if (!studentId) {
      showError("No student selected. Please return to My Children and select a child first.");
      return;
    }

    try {
      const response = await fetch(`get-class-notes.php?student_id=${encodeURIComponent(studentId)}`, {
        method: "GET",
        credentials: "same-origin",
        headers: { "Accept": "application/json" }
      });

      if (response.status === 401) {
        window.location.replace("../index.html");
        return;
      }

      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || "Unable to load class notes.");
      }

      if (!data.class_type) {
        throw new Error(data.message || "This student does not have an active class assigned yet.");
      }

      updateStudentProfile(data.student, data.class_type);
      isOnlineStudent = data.class_type === "online";

      const description = document.getElementById("libraryDescription");
      const count = document.getElementById("moduleCount");
      const toolbarTitle = document.getElementById("toolbarTitle");

      if (isOnlineStudent) {
        if (description) description.textContent = "Select a JTC level to view its available notes.";
        if (count) count.textContent = "5 JTC Levels";
        if (toolbarTitle) toolbarTitle.textContent = "JTC Class Notes Folders";
      } else {
        if (description) description.textContent = "Browse learning materials for your child's physical class.";
        if (count) count.textContent = `${data.materials.length} PDF files`;
        if (toolbarTitle) toolbarTitle.textContent = "Physical Class Notes Folders";
      }

      folders.splice(0, folders.length, ...buildFolders(data.materials || []));
      renderFolders();
    } catch (error) {
      console.error("Class Notes Error:", error);
      showError(error.message || "Unable to load class notes. Please refresh the page.");
    }
  }

  backButton.addEventListener("click", renderFolders);
  emptyBackButton.addEventListener("click", renderFolders);

  // Notification panel
  const notificationBtn = document.getElementById("notificationBtn");
  const notificationPanel = document.getElementById("notificationPanel");
  if (notificationBtn && notificationPanel) {
    notificationBtn.addEventListener("click", function (event) {
      event.stopPropagation();
      notificationPanel.hidden = !notificationPanel.hidden;
      notificationBtn.setAttribute("aria-expanded", String(!notificationPanel.hidden));
    });
    document.addEventListener("click", function (event) {
      if (!notificationPanel.hidden && !notificationPanel.contains(event.target) && !notificationBtn.contains(event.target)) {
        notificationPanel.hidden = true;
        notificationBtn.setAttribute("aria-expanded", "false");
      }
    });
  }

  // Mobile sidebar
  const sidebar = document.getElementById("sidebar");
  const menuToggle = document.getElementById("menuToggle");
  const backdrop = document.getElementById("sidebarBackdrop");
  if (sidebar && menuToggle && backdrop) {
    menuToggle.addEventListener("click", function () {
      const open = !sidebar.classList.contains("open");
      sidebar.classList.toggle("open", open);
      backdrop.hidden = !open;
      menuToggle.setAttribute("aria-expanded", String(open));
    });
    backdrop.addEventListener("click", function () {
      sidebar.classList.remove("open");
      backdrop.hidden = true;
      menuToggle.setAttribute("aria-expanded", "false");
    });
  }

  // Logout
  const logoutButton = document.getElementById("logoutButton");
  if (logoutButton) {
    logoutButton.addEventListener("click", async function () {
      try {
        await fetch("../logout.php", { method: "POST", credentials: "same-origin" });
      } catch (error) {
        console.error("Logout error:", error);
      }
      sessionStorage.removeItem("jazroSelectedStudentId");
      sessionStorage.removeItem("jazroSelectedChild");
      sessionStorage.removeItem("jazroDemoLoggedIn");
      sessionStorage.removeItem("jazroDemoParent");
      window.location.replace("../index.html");
    });
  }

  loadClassNotes();
});
