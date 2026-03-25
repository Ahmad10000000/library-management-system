<?php
include "db.php";
include "auth.php";
requireAdmin();

$errors = [];
$title = "";
$author = "";
$year = "";
$isbn = "";
$olid = "";

function uploadCover(&$errors) {
  if (empty($_FILES["cover"]["name"])) return null;

  if (!is_dir(__DIR__ . "/uploads")) {
    $errors[] = "uploads folder not found. Create: library_project/uploads";
    return null;
  }

  $tmp  = $_FILES["cover"]["tmp_name"];
  $size = (int)($_FILES["cover"]["size"] ?? 0);

  if (!is_uploaded_file($tmp)) {
    $errors[] = "Cover upload failed.";
    return null;
  }

  if ($size > 2 * 1024 * 1024) {
    $errors[] = "Cover too large (max 2MB)";
    return null;
  }

  $type = mime_content_type($tmp);
  if (!in_array($type, ["image/jpeg", "image/png"], true)) {
    $errors[] = "Only JPG/PNG allowed";
    return null;
  }

  $ext = ($type === "image/png") ? ".png" : ".jpg";
  $name = "cover_" . time() . "_" . rand(1000, 9999) . $ext;

  $dest = __DIR__ . "/uploads/" . $name;
  if (!move_uploaded_file($tmp, $dest)) {
    $errors[] = "Failed to save cover image.";
    return null;
  }

  return $name;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title  = trim($_POST["title"] ?? "");
  $author = trim($_POST["author"] ?? "");
  $year   = trim($_POST["year"] ?? "");
  $isbn   = trim($_POST["isbn"] ?? "");
  $olid   = trim($_POST["olid"] ?? "");

  if ($title === "")  $errors[] = "Title is required";
  if ($author === "") $errors[] = "Author is required";

  $y = null;
  if ($year === "") {
    $errors[] = "Year is required";
  } elseif (!ctype_digit($year)) {
    $errors[] = "Year must be a number";
  } else {
    $y = (int)$year;
    if ($y < 1000 || $y > 2100) $errors[] = "Year must be between 1000 and 2100";
  }

  $coverFile = uploadCover($errors);

  if (!$errors) {
    $stmt = mysqli_prepare($conn, "INSERT INTO books (title, author, year, isbn, olid, cover_file) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssisss", $title, $author, $y, $isbn, $olid, $coverFile);

    if (mysqli_stmt_execute($stmt)) {
      header("Location: view_books.php?added=1");
      exit;
    } else {
      $errors[] = "Database error: " . mysqli_error($conn);
      if ($coverFile && file_exists(__DIR__ . "/uploads/" . $coverFile)) {
        @unlink(__DIR__ . "/uploads/" . $coverFile);
      }
    }
    mysqli_stmt_close($stmt);
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Add Book</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "header.php"; ?>

<div class="container">
  <div class="card">
    <div class="h2">Add a new book</div>
    <p class="sub">Admin only. Title, author, year + optional cover.</p>

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

    <form class="form" method="POST" enctype="multipart/form-data">

      <div class="field">
        <div class="label">Title</div>
        <input class="input" type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
      </div>

      <div class="field">
        <div class="label">Author</div>
        <input class="input" type="text" name="author" value="<?php echo htmlspecialchars($author); ?>" required>
      </div>

      <div class="field" style="grid-column: 1 / -1;">
        <div class="label">Year</div>
        <input class="input" type="number" name="year" min="1000" max="2100"
               value="<?php echo htmlspecialchars($year); ?>" required>
      </div>

      <div class="field">
        <div class="label">ISBN (optional)</div>
        <input class="input" type="text" name="isbn" placeholder="e.g. 9780743273565"
               value="<?php echo htmlspecialchars($isbn); ?>">
      </div>

      <div class="field">
        <div class="label">OpenLibrary OLID (optional)</div>
        <input class="input" type="text" name="olid" placeholder="e.g. OL7353617M"
               value="<?php echo htmlspecialchars($olid); ?>">
      </div>

      <div class="field" style="grid-column: 1 / -1;">
        <div class="label">Cover Image (JPG/PNG, max 2MB)</div>
        <input class="input" type="file" name="cover" accept="image/png,image/jpeg">
      </div>

      <div class="actions" style="grid-column: 1 / -1; justify-content:flex-start;">
        <button class="btn btn-primary" type="submit">Save Book</button>
        <a class="btn btn-ghost" href="view_books.php">Cancel</a>
      </div>
    </form>
  </div>
</div>

</body>
</html>
