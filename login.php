<?php
include "db.php";
include "auth.php";

$errors = [];
$email = "";

if (!empty($_SESSION["user_id"])) {
  header("Location: view_books.php");
  exit;
}

$next = $_GET["next"] ?? "view_books.php";
if ($next === "" || strpos($next, "://") !== false) { 
  $next = "view_books.php";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  $pass  = $_POST["password"] ?? "";
  $next  = $_POST["next"] ?? $next;

  if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
  if ($pass === "") $errors[] = "Password is required";

  if (!$errors) {
    $stmt = mysqli_prepare($conn, "SELECT id, full_name, role, password_hash FROM users WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $u = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($u && password_verify($pass, $u["password_hash"])) {
      $_SESSION["user_id"]   = $u["id"];
      $_SESSION["user_name"] = $u["full_name"];
      $_SESSION["user_role"] = $u["role"];

      if ($next === "" || strpos($next, "://") !== false) $next = "view_books.php";
      header("Location: " . $next);
      exit;
    } else {
      $errors[] = "Wrong email or password";
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

  <div class="topbar">
    <div class="brand">
      <div class="logo"></div>
      <div>
        <h1>Login</h1>
        <p>Access your library dashboard</p>
      </div>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="register.php">Register</a>
      <a class="btn btn-ghost" href="index.php">Home</a>
      <a class="btn btn-ghost" href="#" onclick="toggleTheme();return false;">🌓</a>
    </div>
  </div>

  <div class="card">
    <?php if (isset($_GET["registered"])): ?>
      <div class="alert alert-success">Account created ✅ Now login.</div>
    <?php endif; ?>

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
      <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">

      <div class="field" style="grid-column: 1 / -1;">
        <div class="label">Email</div>
        <input class="input" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
      </div>

      <div class="field" style="grid-column: 1 / -1;">
        <div class="label">Password</div>
        <input class="input" type="password" name="password" required>
      </div>

      <div class="actions" style="grid-column: 1 / -1; justify-content:flex-start;">
        <button class="btn btn-primary" type="submit">Login</button>
        <a class="btn btn-ghost" href="register.php">Create account</a>
      </div>
    </form>
  </div>

</div>

<script>
  function applyTheme(){
    const t = localStorage.getItem("theme") || "light";
    document.body.classList.toggle("dark", t === "dark");
  }
  function toggleTheme(){
    const isDark = document.body.classList.contains("dark");
    localStorage.setItem("theme", isDark ? "light" : "dark");
    applyTheme();
  }
  applyTheme();
</script>

</body>
</html>
