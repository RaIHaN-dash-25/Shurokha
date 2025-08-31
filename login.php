<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – Shurokha</title>
  <link rel="icon" type="image/png" href="logo-transparent.png">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Animate.css for animations -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/styles.css">
</head>

<body>
  <?php
  session_start();
  $error = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db.php';
    $email = trim($_POST['login_email'] ?? '');
    $password = $_POST['login_password'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $user = $result->fetch_assoc()) {
      if ($password === $user['password_hash']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        if ($user['role'] === 'admin') {
          header('Location: admin-dashboard.php');
          exit;
        } elseif ($user['role'] === 'doctor') {
          header('Location: doctor-dashboard.php');
          exit;
        } elseif ($user['role'] === 'mother') {
          header('Location: mother-dashboard.php');
          exit;
        } else {
          $error = 'Unknown user role.';
        }
      } else {
        $error = 'Invalid password.';
      }
    } else {
      $error = 'User not found.';
    }
  }
  ?>
  <section class="login-section">
    <div class="login-card animate__animated animate__fadeInUp">
      <div class="text-center mb-4">
        <div class="mb-3">
          <img src="logo-transparent.png" alt="Shurokha Logo" class="mx-auto" style="width: 120px; height: 120px; object-fit: contain;">
        </div>
        <div class="login-title">Login to Shurokha</div>
      </div>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center animate__animated animate__shakeX"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label for="login-email" class="form-label">Email</label>
          <input type="email" class="form-control" id="login-email" name="login_email" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
          <label for="login-password" class="form-label">Password</label>
          <input type="password" class="form-control" id="login-password" name="login_password" placeholder="Enter your password" required>
        </div>
        <div class="d-grid mb-3">
          <button id="loginBtn" type="submit" class="btn btn-primary btn-lg">Login</button>
        </div>
        <div class="text-center">
          <a href="index.php" class="text-decoration-none text-primary">&larr; Back to Homepage</a>
        </div>
      </form>
    </div>
  </section>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- No JS login, handled by PHP -->
</body>

</html>