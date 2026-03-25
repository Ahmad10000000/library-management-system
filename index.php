<?php include "header.php"; ?>

<!DOCTYPE html>
<html>
<head>
  <title>Library System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

  <div class="topbar">
    <div class="brand">
      <div class="logo"></div>
      <div>
        <h1>Library Management</h1>
        <p>PHP + MySQL CRUD Project</p>
      </div>
    </div>

    <div class="actions">
      <a class="btn btn-primary" href="add_book.php">+ Add Book</a>
      <a class="btn btn-ghost" href="view_books.php">View Books</a>
    </div>
  </div>

  <div class="card">
    <div class="h2">Welcome 👋</div>
    <p class="sub">
      Manage your books easily: add, view, edit, and delete — all saved in the database.
    </p>

    <div class="actions" style="justify-content:flex-start;">
      <a class="btn btn-primary" href="add_book.php">Start Adding</a>
      <a class="btn btn-ghost" href="view_books.php">Open Library</a>
    </div>
  </div>

</div>

</body>
</html>
