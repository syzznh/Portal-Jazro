
/* =========================================
   JAZRO PARENT LOGIN
   DATABASE CONNECTED VERSION
   ========================================= */

document.addEventListener("DOMContentLoaded", function () {

    // GET ELEMENTS
    const loginForm = document.getElementById("loginForm");
    const usernameInput = document.getElementById("username");
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");
    const formMessage = document.getElementById("formMessage");
    const currentYear = document.getElementById("currentYear");

    // CHANGE USERNAME FIELD TO EMAIL
    const emailLabel = usernameInput
        .closest(".form-group")
        .querySelector("label");

    emailLabel.textContent = "Email Address";

    usernameInput.type = "email";
    usernameInput.name = "email";
    usernameInput.placeholder = "Enter your email address";
    usernameInput.autocomplete = "email";

    // CURRENT YEAR
    if (currentYear) {
        currentYear.textContent = new Date().getFullYear();
    }

    // SHOW / HIDE PASSWORD
    if (togglePassword) {

        togglePassword.addEventListener("click", function () {

            const isHidden = passwordInput.type === "password";

            passwordInput.type = isHidden ? "text" : "password";

            togglePassword.setAttribute(
                "aria-label",
                isHidden ? "Hide password" : "Show password"
            );

            togglePassword.setAttribute(
                "aria-pressed",
                String(isHidden)
            );

        });

    }

    // SHOW MESSAGE
    function showMessage(message) {
        formMessage.textContent = message;
        formMessage.className = "form-message show error";
    }

    // CLEAR ERRORS
    function clearErrors() {

        usernameInput.classList.remove("input-error");
        passwordInput.classList.remove("input-error");

        formMessage.textContent = "";
        formMessage.className = "form-message";

    }

    usernameInput.addEventListener("input", clearErrors);
    passwordInput.addEventListener("input", clearErrors);

    // LOGIN USING DATABASE
    loginForm.addEventListener("submit", async function (event) {

        event.preventDefault();

        clearErrors();

        const email = usernameInput.value.trim();
        const password = passwordInput.value;

        // CHECK EMPTY EMAIL
        if (email === "") {

            usernameInput.classList.add("input-error");
            showMessage("Please enter your email address.");
            usernameInput.focus();

            return;
        }

        // CHECK EMPTY PASSWORD
        if (password.trim() === "") {

            passwordInput.classList.add("input-error");
            showMessage("Please enter your password.");
            passwordInput.focus();

            return;
        }

        // LOADING STATE
        const loginButton = loginForm.querySelector(".login-button");
        const loginButtonText = loginButton.querySelector("span");

        loginButton.disabled = true;
        loginButtonText.textContent = "Logging in...";

        try {

            const formData = new FormData();

            formData.append("email", email);
            formData.append("password", password);

            const response = await fetch("login.php", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            });

            const data = await response.json();

            if (!response.ok || !data.success) {

                usernameInput.classList.add("input-error");
                passwordInput.classList.add("input-error");

                showMessage(
                    data.message || "Login failed. Please try again."
                );

                return;
            }

            // KEEP TEMPORARY FRONTEND STATE
            // Existing dashboard JavaScript still checks this.
            sessionStorage.setItem(
                "jazroDemoLoggedIn",
                "true"
            );

            sessionStorage.setItem(
                "jazroDemoParent",
                data.parentName || "Parent"
            );

            sessionStorage.removeItem("jazroSelectedChild");

            // GO TO DASHBOARD
            window.location.href = "pages/dashboard.php";

        } catch (error) {

            console.error("Login error:", error);

            showMessage(
                "Unable to connect to the server. Please try again."
            );

        } finally {

            loginButton.disabled = false;
            loginButtonText.textContent = "Log In";

        }

    });

});

 
