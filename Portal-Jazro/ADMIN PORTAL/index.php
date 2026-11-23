
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | JAZRO Robotic Academy</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-body">
  <main class="login-layout">

    <!-- LEFT: WELCOME SECTION -->
    <section class="login-hero">

      <a class="brand brand-large" href="index.html">
        <img
          src="assets/images/jazro-logo.png"
          alt="JAZRO Robotic Academy logo"
          class="brand-logo"
        >
        <span class="brand-label">ADMIN PORTAL</span>
      </a>

      <span class="eyebrow">EMPOWERING YOUNG INNOVATORS</span>

      <h1>
        Welcome to<br>
        <span>JAZRO</span><br>
        Admin Portal
      </h1>

      <p class="hero-copy">
        Manage classes, students, learning content and track progress,
        all in one place.
      </p>

      <div class="hero-highlights">
        <div>
          <span class="highlight-icon">▦</span>
          <b>Manage<br>Classes</b>
        </div>

        <div>
          <span class="highlight-icon">↗</span>
          <b>Track<br>Progress</b>
        </div>

        <div>
          <span class="highlight-icon">♙</span>
          <b>Empower Young<br>Innovators</b>
        </div>
      </div>

      <!-- Robot illustration -->
      <div class="robot-orb" aria-hidden="true">
        <div class="robot-face">
          <span></span>
          <span></span>
        </div>
        <div class="robot-mouth"></div>
      </div>

      <div class="hero-wave"></div>
    </section>


    <!-- RIGHT: LOGIN SECTION -->
    <section class="login-side">

      <form class="login-card" id="loginForm" action="login.php" method="POST">

        <!-- Logo for smaller screens -->
        <div class="mobile-brand brand">
          <img
            src="assets/images/jazro-logo.png"
            alt="JAZRO Robotic Academy logo"
            class="brand-logo"
          >
          <span class="brand-label">ADMIN PORTAL</span>
        </div>

        <h2>Admin Login</h2>

        <p class="muted">
          Sign in to your administrator account to continue managing
          JAZRO Robotic Academy.
        </p>

        <label for="email">Email Address</label>

        <div class="input-wrap">
          <span aria-hidden="true">✉</span>
          <input
            id="email"
            name="email"
            type="email"
            placeholder="admin@jazro.com"
            autocomplete="username"
            required
          >
        </div>

        <label for="password">Password</label>

        <div class="input-wrap">
          <span aria-hidden="true">♙</span>

          <input
            id="password"
            name="password"
            type="password"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
          >

          <!-- One eye icon only -->
          <button
            type="button"
            class="eye-button"
            id="togglePassword"
            aria-label="Show password"
            aria-pressed="false"
          >
            <svg
              viewBox="0 0 24 24"
              width="20"
              height="20"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>

        <div class="login-options">
          <label class="remember">
            <input type="checkbox" id="remember">
            Remember me
          </label>

          <button
            type="button"
            class="forgot-link"
            id="forgotLink"
          >
            Forgot password?
          </button>
        </div>

        <button class="primary-button full-button" type="submit">
          Log In <span>→</span>
        </button>

        <p class="demo-note" id="loginMessage">
          Interface preview only. Authentication will be connected
          to the database later.
        </p>
      </form>

      <footer class="login-footer">
        <span>© 2026 JAZRO Robotic Academy</span>
        <span>Empowering Young Innovators</span>
      </footer>


      <!-- FORGOT PASSWORD POPUP -->
      <div class="notice-backdrop" id="forgotModal" hidden>
        <section
          class="notice-card"
          role="dialog"
          aria-modal="true"
          aria-labelledby="forgotTitle"
        >
          <button
            type="button"
            class="close-modal notice-close"
            id="closeForgotModal"
            aria-label="Close"
          >
            &times;
          </button>

          <div class="notice-icon">✉</div>

          <h2 id="forgotTitle">Forgot your password?</h2>

          <p>
            Password recovery will be available after admin authentication
            is connected to the database. Please contact the JAZRO system
            administrator for assistance.
          </p>

          <button
            type="button"
            class="primary-button full-button"
            id="forgotOkay"
          >
            Okay, got it
          </button>
        </section>
      </div>

    </section>
  </main>

  <script src="assets/js/app.js"></script>
</body>
</html>
