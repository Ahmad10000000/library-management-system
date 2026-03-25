<?php
include_once "auth.php";
?>
<div class="topbar">
  <div class="brand">
    <div class="logo"></div>
    <div>
      <h1>Library Management</h1>
      <p>PHP + MySQL CRUD Project</p>
    </div>
  </div>

  <div class="actions">
    <?php if (isLoggedIn()): ?>

      <span class="badge">👤 <?php echo htmlspecialchars(currentUserName()); ?></span>

      <a class="btn btn-ghost" href="index.php">Home</a>

      <a class="btn btn-ghost" href="view_books.php">Books</a>

      <a class="btn btn-ghost" href="favorites.php">★ Favorites</a>

      <a class="btn btn-ghost" href="export_csv.php">Export CSV</a>

      <?php if (isAdmin()): ?>
        <a class="btn btn-primary" href="add_book.php">+ Add Book</a>
      <?php endif; ?>

      <a class="btn btn-ghost" href="#" onclick="toggleTheme();return false;">🌓</a>

      <a class="btn btn-danger" href="logout.php">Logout</a>

    <?php else: ?>

      <a class="btn btn-primary" href="login.php">Login</a>

    <?php endif; ?>
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
