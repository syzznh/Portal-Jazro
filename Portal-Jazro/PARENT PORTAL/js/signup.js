
/* =========================================
   JAZRO PARENT SIGN UP
   FRONTEND DEMO - NO DATABASE
   ========================================= */

document.addEventListener("DOMContentLoaded", function () {

  const signupForm = document.getElementById("signupForm");
  const formMessage = document.getElementById("formMessage");

  const fullName = document.getElementById("fullName");
  const username = document.getElementById("username");
  const email = document.getElementById("email");
  const phone = document.getElementById("phone");

  const password = document.getElementById("password");
  const confirmPassword =
    document.getElementById("confirmPassword");

  const agreement = document.getElementById("agreement");

  // COPYRIGHT YEAR
  document.getElementById("currentYear").textContent =
    new Date().getFullYear();


  // SHOW / HIDE PASSWORD
  document.querySelectorAll(".password-toggle")
    .forEach(function (button) {

      button.addEventListener("click", function () {

        const input = document.getElementById(
          button.dataset.target
        );

        const show = input.type === "password";

        input.type = show ? "text" : "password";
        button.textContent = show ? "Hide" : "Show";

        button.setAttribute(
          "aria-label",
          show ? "Hide password" : "Show password"
        );

        button.setAttribute(
          "aria-pressed",
          String(show)
        );

      });

    });


  // DISPLAY MESSAGE
  function showMessage(text, type) {
    formMessage.textContent = text;
    formMessage.className =
      "form-message show " + type;
  }


  // RESET ERRORS
  function clearErrors() {
    document.querySelectorAll(".field input")
      .forEach(function (input) {
        input.classList.remove("invalid");
      });

    formMessage.textContent = "";
    formMessage.className = "form-message";
  }


  function fail(input, message) {
    if (input) {
      input.classList.add("invalid");
      input.focus();
    }

    showMessage(message, "error");
  }


  // HANDLE SIGN UP
  signupForm.addEventListener("submit", function (event) {

    event.preventDefault();
    clearErrors();

    const nameValue = fullName.value.trim();
    const usernameValue = username.value.trim();
    const emailValue = email.value.trim();
    const phoneValue = phone.value.replace(/[\s-]/g, "");
    const passwordValue = password.value;
    const confirmValue = confirmPassword.value;


    // REQUIRED FIELDS
    if (!nameValue) {
      return fail(fullName, "Please enter your full name.");
    }

    if (!/^[a-zA-Z0-9_]{3,24}$/.test(usernameValue)) {
      return fail(
        username,
        "Username must be 3–24 characters using letters, numbers or underscores."
      );
    }

    // BASIC EMAIL VALIDATION
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
      return fail(
        email,
        "Please enter a valid email address."
      );
    }

    // MALAYSIAN PHONE NUMBER - BASIC CHECK
    if (!/^(?:\+?60|0)1\d{8,9}$/.test(phoneValue)) {
      return fail(
        phone,
        "Please enter a valid Malaysian mobile number."
      );
    }

    // PASSWORD RULE
    if (
      passwordValue.length < 8 ||
      !/[a-zA-Z]/.test(passwordValue) ||
      !/\d/.test(passwordValue)
    ) {
      return fail(
        password,
        "Password must contain at least 8 characters, including a letter and a number."
      );
    }

    // CONFIRM PASSWORD
    if (passwordValue !== confirmValue) {
      return fail(
        confirmPassword,
        "Passwords do not match. Please try again."
      );
    }

    // AGREEMENT
    if (!agreement.checked) {
      return showMessage(
        "Please confirm the registration agreement.",
        "error"
      );
    }

    // FRONTEND DEMO ONLY
    showMessage(
      "Demo validation successful! Account registration will be available after backend integration.",
      "success"
    );

    // Do not save account information or passwords.
    // Real registration needs a secure backend.

  });


  // CLEAR ERRORS WHEN EDITING
  signupForm.querySelectorAll("input")
    .forEach(function (input) {

      input.addEventListener("input", function () {
        input.classList.remove("invalid");

        if (formMessage.classList.contains("error")) {
          formMessage.textContent = "";
          formMessage.className = "form-message";
        }
      });

    });

});
