<?php
include "db.php";
include "auth.php";
requireAdmin();

$id = $_GET["id"] ?? "";
if (!ctype_digit($id)) {
  die("Invalid book id");
}
$bookId = (int)$id;

$errors = [];
$title = "";
$author = "";
$year = "";
$isbn = "";
$olid = "";
$oldCover = "";

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

$stmt = mysqli_prepare($conn, "SELECT id, title, author, year, isbn, olid, cover_file FROM books WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $bookId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$book) {
  die("Book not found");
}

$title  = $book["title"];
$author = $book["author"];
$year   = (string)$book["year"];
$isbn   = (string)($book["isbn"] ?? "");
$olid   = (string)($book["olid"] ?? "");
$oldCover = (string)($book["cover_file"] ?? "");

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

  $newCover = uploadCover($errors);

  if (!$errors) {
    $finalCover = $newCover ? $newCover : $oldCover;

    $stmt = mysqli_prepare($conn, "UPDATE books SET title=?, author=?, year=?, isbn=?, olid=?, cover_file=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssisssi", $title, $author, $y, $isbn, $olid, $finalCover, $bookId);

    if (mysqli_stmt_execute($stmt)) {
      mysqli_stmt_close($stmt);

      if ($newCover && $oldCover && file_exists(__DIR__ . "/uploads/" . $oldCover)) {
        @unlink(__DIR__ . "/uploads/" . $oldCover);
      }

      header("Location: view_books.php?updated=1");
      exit;
    } else {
      $errors[] = "Database error: " . mysqli_error($conn);
      mysqli_stmt_close($stmt);

      if ($newCover && file_exists(__DIR__ . "/uploads/" . $newCover)) {
        @unlink(__DIR__ . "/uploads/" . $newCover);
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Book</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "header.php"; ?>

<div class="container">
  <div class="card">
    <div class="h2">Edit Book</div>
    <p class="sub">Admin only. Update details and optional cover.</p>

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
        <input class="input" type="text" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>">
      </div>

      <div class="field">
        <div class="label">OpenLibrary OLID (optional)</div>
        <input class="input" type="text" name="olid" value="<?php echo htmlspecialchars($olid); ?>">
      </div>

      <div class="field" style="grid-column: 1 / -1;">
        <div class="label">Cover Image (JPG/PNG, max 2MB)</div>
        <input class="input" type="file" name="cover" accept="image/png,image/jpeg">
        <?php if ($oldCover): ?>
          <p class="sub" style="margin-top:8px;">Current cover: <b><?php echo htmlspecialchars($oldCover); ?></b></p>
        <?php endif; ?>
      </div>

      <div class="actions" style="grid-column: 1 / -1; justify-content:flex-start;">
        <button class="btn btn-primary" type="submit">Update Book</button>
        <a class="btn btn-ghost" href="view_books.php">Cancel</a>
      </div>
    </form>
  </div>
</div>

</body>
</html>
