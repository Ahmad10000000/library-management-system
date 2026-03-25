<?php
include "db.php";
include "auth.php";
requireLogin();

$id = $_GET["id"] ?? "";
if (!ctype_digit($id)) {
  die("Invalid book id");
}
$bookId = (int)$id;

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
$year   = (int)$book["year"];
$isbn   = $book["isbn"] ?? "";
$olid   = $book["olid"] ?? "";
$coverFile = $book["cover_file"] ?? "";

function coverUrl($coverFile, $isbn, $olid) {
  if (!empty($coverFile)) {
    return "uploads/" . rawurlencode($coverFile);
  }
  if (!empty($isbn)) {
    return "https://covers.openlibrary.org/b/isbn/" . rawurlencode($isbn) . "-L.jpg";
  }
  if (!empty($olid)) {
    return "https://covers.openlibrary.org/b/olid/" . rawurlencode($olid) . "-L.jpg";
  }
  return "https://via.placeholder.com/180x260?text=Book";
}

$openLibraryUrl = "https://openlibrary.org/search?q=" . urlencode($title . " " . $author);

$uid = (int)$_SESSION["user_id"];
$isFav = false;
$chk = mysqli_query($conn, "SELECT 1 FROM favorites WHERE user_id=$uid AND book_id=$bookId LIMIT 1");
if ($chk && mysqli_num_rows($chk) > 0) $isFav = true;
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo htmlspecialchars($title); ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "header.php"; ?>

<div class="container">

  <div class="card">
    <div class="h2"><?php echo htmlspecialchars($title); ?></div>
    <p class="sub"><?php echo htmlspecialchars($author); ?> • <?php echo (int)$year; ?></p>

    <div style="display:flex; gap:18px; align-items:flex-start; flex-wrap:wrap; margin-top:14px;">
      <img class="cover" style="width:180px; height:260px;" src="<?php echo htmlspecialchars(coverUrl($coverFile, $isbn, $olid)); ?>" alt="">
      <div style="flex:1; min-width:260px;">
        <div style="display:grid; gap:10px;">
          <div><b>Title:</b> <?php echo htmlspecialchars($title); ?></div>
          <div><b>Author:</b> <?php echo htmlspecialchars($author); ?></div>
          <div><b>Year:</b> <?php echo (int)$year; ?></div>
          <?php if ($isbn): ?><div><b>ISBN:</b> <?php echo htmlspecialchars($isbn); ?></div><?php endif; ?>
          <?php if ($olid): ?><div><b>OLID:</b> <?php echo htmlspecialchars($olid); ?></div><?php endif; ?>
        </div>

        <div class="actions" style="justify-content:flex-start; margin-top:16px;">
          <a class="btn btn-primary" target="_blank" href="<?php echo htmlspecialchars($openLibraryUrl); ?>">
            Read / Preview (Open Library)
          </a>

          <a class="btn btn-ghost"
             href="favorite_toggle.php?id=<?php echo $bookId; ?>&back=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>">
            <?php echo $isFav ? "★ Unfavorite" : "☆ Favorite"; ?>
          </a>

          <?php if (isAdmin()): ?>
            <a class="btn btn-ghost" href="edit_book.php?id=<?php echo $bookId; ?>">Edit</a>
          <?php endif; ?>

          <a class="btn btn-ghost" href="view_books.php">Back</a>
        </div>

        <div class="alert" style="margin-top:16px;">
          ملاحظة: قراءة نص الكتاب كامل داخل موقعك تعتمد على حقوق النشر. الرابط يفتح مصادر قانونية (Preview/Borrow/Public Domain).
        </div>
      </div>
    </div>
  </div>

</div>

</body>
</html>
