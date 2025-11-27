<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Session Expired</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Poppins:300,400,500,600,700" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">

  <style>
    body {
      background: #f5f7fa;
    }
    .error-404 {
      text-align: center;
      padding: 60px 15px;
    }
    .error-404 h1 {
      font-size: 120px;
      font-weight: 700;
      color: #4154f1;
    }
    .error-404 h2 {
      font-size: 24px;
      margin: 20px 0;
      color: #012970;
    }
    .error-404 img {
      max-width: 300px;
      margin: 30px 0;
    }
    .btn-relogin {
      background-color: #4154f1;
      color: #fff;
      padding: 10px 30px;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .btn-relogin:hover {
      background-color: #2e3abf;
      color: #fff;
    }
  </style>

</head>

<body>

  <main>
    <div class="container">

      <section class="error-404 min-vh-100 d-flex flex-column align-items-center justify-content-center">
        <h1><i class="bi bi-exclamation-triangle"></i></h1>
        <h2>Your session has expired</h2>
        <p class="mb-4">For your security, you have been logged out. Please login again to continue.</p>
        <a class="btn-relogin" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
        <img src="assets/img/not-found.svg" class="img-fluid" alt="Session Expired">
      </section>

    </div>
  </main>

</body>

</html>
