<?php
include "db.php";
include "auth.php";
requireLogin();

$id = $_GET["id"] ?? "";
if (!ctype_digit($id)) die("Invalid id");

$uid = (int)$_SESSION["user_id"];
$bid = (int)$id;

$check = mysqli_query($conn, "SELECT 1 FROM favorites WHERE user_id=$uid AND book_id=$bid LIMIT 1");
if ($check && mysqli_num_rows($check) > 0) {
  mysqli_query($conn, "DELETE FROM favorites WHERE user_id=$uid AND book_id=$bid");
} else {
  mysqli_query($conn, "INSERT IGNORE INTO favorites(user_id, book_id) VALUES($uid,$bid)");
}

$back = $_GET["back"] ?? "view_books.php";
header("Location: " . $back);
exit;
