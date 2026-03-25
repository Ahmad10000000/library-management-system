<?php
include "db.php";
include "auth.php";

$errors = [];
$name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["full_name"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $pass = $_POST["password"] ?? "";
  $pass2 = $_POST["confirm_password"] ?? "";

  if ($name === "") $errors[] = "Full name is required";
  if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
  if (strlen($pass) < 6) $errors[] = "Password must be at least 6 characters";
  if ($pass !== $pass2) $errors[] = "Passwords do not match";

  if (!$errors) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hash);

    if (mysqli_stmt_execute($stmt)) {
      header("Location: login.php?registered=1");
      exit;
    } else {
      $errors[] = "Email already exists (or DB error).";
    }
    mysqli_stmt_close($stmt);
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

  <div class="topbar">
    <div class="brand">
      <div class="logo"></div>
      <div>
        <h1>Create account</h1>
        <p>Register to access the library</p>
      </div>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="login.php">Login</a>
      <a class="btn btn-ghost" href="index.php">Home</a>
    </div>
  </div>

  <div class="card">
    <?php if ($errors): ?>
      <div class="alert alert-warn">
        <b>Fix these errors:</b>
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?php echo htmlspecialchars($e); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form class="form" method="POST">
      <div class="field" style="grid-column: 1 / -1;">
        <div class="label">Full name</div>
        <input class="input" type="text" name="full_name" value="<?php echo htmlspecialchars($name); ?>" required>
      </div>

      <div class="field" style="grid-column: 1 / -1;">
        <div class="label">Email</div>
        <input class="input" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
      </div>

      <div class="field">
        <div class="label">Password</div>
        <input class="input" type="password" name="password" required>
      </div>

      <div class="field">
        <div class="label">Confirm password</div>
        <input class="input" type="password" name="confirm_password" required>
      </div>

      <div class="actions" style="grid-column: 1 / -1; justify-content:flex-start;">
        <button class="btn btn-primary" type="submit">Register</button>
        <a class="btn btn-ghost" href="login.php">I have an account</a>
      </div>
    </form>
  </div>

</div>
</body>
</html>
