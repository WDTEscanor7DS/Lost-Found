<?php
session_start();
include "misc/connect.php";

if (isset($_POST['username']) && isset($_POST['password'])) {

  $username = $_POST['username'];
  $password = $_POST['password'];

  $sql = "SELECT * FROM users WHERE username = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    if (password_verify($password, $row['password'])) {

      session_regenerate_id(true);
      $_SESSION['id'] = $row['id'];
      $_SESSION['username'] = $row['username'];
      $_SESSION['role'] = $row['role'];

      if ($row['role'] === 'admin') {
        header("Location: admin/index.php");
      } else {
        header("Location: user/index.php");
      }
      exit();
    } else {
      echo "<script>alert('Invalid username or password');</script>";
    }
  } else {
    echo "<script>alert('Invalid username or password');</script>";
  }
}

?>

<!doctype html>

<html
  lang="en"
  class="layout-wide customizer-hide"
  data-assets-path="assets/"
  data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />

  <title>LJH | Login</title>

  <meta name="description" content="" />

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico" />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
    rel="stylesheet" />

  <link rel="stylesheet" href="assets/vendor/fonts/iconify-icons.css" />

  <!-- Core CSS -->
  <!-- build:css assets/vendor/css/theme.css -->

  <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />

  <link rel="stylesheet" href="assets/vendor/css/core.css" />
  <link rel="stylesheet" href="assets/css/demo.css" />

  <!-- Vendors CSS -->

  <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

  <!-- endbuild -->

  <!-- Page CSS -->
  <!-- Page -->
  <link rel="stylesheet" href="assets/vendor/css/pages/page-auth.css" />

  <!-- Helpers -->
  <script src="assets/vendor/js/helpers.js"></script>
  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

  <!--? Config: Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file. -->

  <script src="assets/js/config.js"></script>
</head>

<body>
  <!-- Content -->

  <div class="position-relative">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner py-6 mx-4">
        <!-- Login -->
        <div class="card p-sm-7 p-2">

          <div class="card-body mt-1">
            <h4 class="mb-1">Welcome to Local Joy Holdings!</h4>
            <p class="mb-5">Please sign-in to view </p>

            <form id="formAuthentication" class="mb-5" action="index.php" method="POST">
              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <input
                  type="text"
                  class="form-control"
                  id="username"
                  name="username"
                  placeholder="Enter your username"
                  autofocus />
                <label for="username">Username</label>
              </div>
              <div class="mb-5">
                <div class="form-password-toggle form-control-validation">
                  <div class="input-group input-group-merge">
                    <div class="form-floating form-floating-outline">
                      <input
                        type="password"
                        id="password"
                        class="form-control"
                        name="password"
                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                        aria-describedby="password" />
                      <label for="password">Password</label>
                    </div>
                    <span class="input-group-text cursor-pointer"><i class="icon-base ri ri-eye-off-line icon-20px"></i></span>
                  </div>
                </div>
              </div>
              <div class="mb-5 pb-2 d-flex justify-content-between pt-2 align-items-center">
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" id="remember-me" />
                  <label class="form-check-label" for="remember-me"> Remember Me </label>
                </div>
                <a href="forgot-password.php" class="float-end mb-1">
                  <span>Forgot Password?</span>
                </a>
              </div>
              <div class="mb-5">
                <button class="btn btn-primary d-grid w-100" type="submit">login</button>
              </div>
            </form>

            <p class="text-center mb-5">
              <span>New on our platform?</span>
              <a href="register.php">
                <span>Create an account</span>
              </a>
            </p>
          </div>
        </div>
        <!-- /Login -->
        <img
          src="assets/img/illustrations/tree-3.png"
          alt="auth-tree"
          class="authentication-image-object-left d-none d-lg-block" />
        <img
          src="assets/img/illustrations/auth-basic-mask-light.png"
          class="authentication-image d-none d-lg-block scaleX-n1-rtl"
          height="172"
          alt="triangle-bg" />
        <img
          src="assets/img/illustrations/tree.png"
          alt="auth-tree"
          class="authentication-image-object-right d-none d-lg-block" />
      </div>
    </div>
  </div>


  <!-- Core JS -->

  <script src="assets/vendor/libs/jquery/jquery.js"></script>

  <script src="assets/vendor/libs/popper/popper.js"></script>
  <script src="assets/vendor/js/bootstrap.js"></script>
  <script src="assets/vendor/libs/node-waves/node-waves.js"></script>

  <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

  <script src="assets/vendor/js/menu.js"></script>

  <!-- endbuild -->

  <!-- Vendors JS -->

  <!-- Main JS -->

  <script src="assets/js/main.js"></script>

  <!-- Page JS -->

  <!-- Place this tag before closing body tag for github widget button. -->
  <script async="async" defer="defer" src="https://buttons.github.io/buttons.js"></script>
</body>

</html>