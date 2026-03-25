<?php
include "db.php";
include "auth.php";
requireAdmin();

$id = $_GET["id"] ?? "";
if (!ctype_digit($id)) {
  die("Invalid book id");
}
$bookId = (int)$id;

$stmtGet = mysqli_prepare($conn, "SELECT cover_file FROM books WHERE id=?");
mysqli_stmt_bind_param($stmtGet, "i", $bookId);
mysqli_stmt_execute($stmtGet);
$res = mysqli_stmt_get_result($stmtGet);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmtGet);

$cover = $row["cover_file"] ?? null;

$stmt = mysqli_prepare($conn, "DELETE FROM books WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $bookId);

if (mysqli_stmt_execute($stmt)) {
  mysqli_stmt_close($stmt);

  if ($cover) {
    $path = __DIR__ . "/uploads/" . $cover;
    if (file_exists($path)) {
      @unlink($path);
    }
  }

  header("Location: view_books.php?deleted=1");
  exit;
}

$err = mysqli_error($conn);
mysqli_stmt_close($stmt);
die("Delete failed: " . htmlspecialchars($err));
