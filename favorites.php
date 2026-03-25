<?php
include "db.php";
include "auth.php";
requireLogin();

$uid = (int)$_SESSION["user_id"];

$res = mysqli_query($conn, "
  SELECT b.id, b.title, b.author, b.year, b.isbn, b.olid, b.cover_file
  FROM favorites f
  JOIN books b ON b.id = f.book_id
  WHERE f.user_id = $uid
  ORDER BY f.created_at DESC
");

function coverUrl($row) {
  if (!empty($row["cover_file"])) {
    return "uploads/" . rawurlencode($row["cover_file"]);
  }
  if (!empty($row["isbn"])) {
    return "https://covers.openlibrary.org/b/isbn/" . rawurlencode($row["isbn"]) . "-M.jpg";
  }
  if (!empty($row["olid"])) {
    return "https://covers.openlibrary.org/b/olid/" . rawurlencode($row["olid"]) . "-M.jpg";
  }
  return "https://via.placeholder.com/56x80?text=Book";
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>My Favorites</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "header.php"; ?>

<div class="container">
  <div class="card">
    <div class="h2">My Favorites</div>
    <p class="sub">Your saved books (★).</p>

    <div class="table-wrap">
      <table>
        <tr>
          <th>Cover</th>
          <th>Title</th>
          <th>Author</th>
          <th>Year</th>
          <th>Actions</th>
        </tr>

        <?php if ($res && mysqli_num_rows($res) > 0): ?>
          <?php while($r = mysqli_fetch_assoc($res)): ?>
            <tr>
              <td><img class="cover" src="<?php echo htmlspecialchars(coverUrl($r)); ?>" alt=""></td>
              <td><?php echo htmlspecialchars($r["title"]); ?></td>
              <td><?php echo htmlspecialchars($r["author"]); ?></td>
              <td><?php echo (int)$r["year"]; ?></td>
              <td>
                <div class="t-actions">
                  <a class="btn btn-ghost" href="book.php?id=<?php echo (int)$r["id"]; ?>">View</a>
                  <a class="btn btn-ghost"
                     href="favorite_toggle.php?id=<?php echo (int)$r["id"]; ?>&back=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>">
                    ★ Remove
                  </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="5">No favorites yet</td>
          </tr>
        <?php endif; ?>

      </table>
    </div>

  </div>
</div>

</body>
</html>
