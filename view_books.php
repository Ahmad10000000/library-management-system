<?php
include "db.php";
include "auth.php";
requireLogin();

$q = trim($_GET["q"] ?? "");
$page = max(1, (int)($_GET["page"] ?? 1));
$perPage = 10;

$whereSql = "";
$params = [];
$types = "";

if ($q !== "") {
  $like = "%".$q."%";
  $whereSql = "WHERE title LIKE ? OR author LIKE ? OR CAST(year AS CHAR) LIKE ?";
  $params = [$like, $like, $like];
  $types = "sss";
}

if ($whereSql) {
  $stmtC = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM books $whereSql");
  mysqli_stmt_bind_param($stmtC, $types, ...$params);
  mysqli_stmt_execute($stmtC);
  $countRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtC));
  mysqli_stmt_close($stmtC);
} else {
  $countRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM books"));
}

$total = (int)($countRow["c"] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

if ($whereSql) {
  $sql = "SELECT id, title, author, year, isbn, olid, cover_file
          FROM books $whereSql
          ORDER BY id DESC
          LIMIT ? OFFSET ?";
  $stmt = mysqli_prepare($conn, $sql);
  $types2 = $types . "ii";
  $params2 = array_merge($params, [$perPage, $offset]);
  mysqli_stmt_bind_param($stmt, $types2, ...$params2);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
} else {
  $stmt = mysqli_prepare($conn, "SELECT id, title, author, year, isbn, olid, cover_file
                                 FROM books
                                 ORDER BY id DESC
                                 LIMIT ? OFFSET ?");
  mysqli_stmt_bind_param($stmt, "ii", $perPage, $offset);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
}

$uid = (int)($_SESSION["user_id"] ?? 0);
$fav = [];

$stmtF = mysqli_prepare($conn, "SELECT book_id FROM favorites WHERE user_id=?");
mysqli_stmt_bind_param($stmtF, "i", $uid);
mysqli_stmt_execute($stmtF);
$resFav = mysqli_stmt_get_result($stmtF);
while ($r = mysqli_fetch_assoc($resFav)) {
  $fav[(int)$r["book_id"]] = true;
}
mysqli_stmt_close($stmtF);

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

  $title = trim($row["title"] ?? "");
  if ($title !== "") {
    return "https://covers.openlibrary.org/b/title/" . rawurlencode($title) . "-M.jpg";
  }

  return "no-cover.png";
}

function pageLink($q, $p) {
  $p = (int)$p;
  if ($q === "") return "view_books.php?page=".$p;
  return "view_books.php?q=" . urlencode($q) . "&page=" . $p;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Books</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "header.php"; ?>

<div class="container">

  <div class="card">

    <?php if (isset($_GET["added"])): ?>
      <div class="alert alert-success">Book added successfully ✅</div>
    <?php endif; ?>

    <?php if (isset($_GET["updated"])): ?>
      <div class="alert alert-success">Book updated successfully ✅</div>
    <?php endif; ?>

    <?php if (isset($_GET["deleted"])): ?>
      <div class="alert" style="border-color: rgba(239,68,68,.25); background: rgba(239,68,68,.08);">
        Book deleted successfully ✅
      </div>
    <?php endif; ?>

    <div class="h2">All Books</div>
    <p class="sub">Search by title, author, or year. (Showing <?php echo $perPage; ?> per page)</p>

    <form class="searchbar" method="GET">
      <input class="input" type="text" name="q" placeholder="Search books..."
             value="<?php echo htmlspecialchars($q); ?>">
      <button class="btn btn-primary" type="submit">Search</button>

      <?php if ($q !== ""): ?>
        <a class="btn btn-ghost" href="view_books.php">Clear</a>
      <?php endif; ?>

      <span class="badge">Total: <?php echo $total; ?></span>
    </form>

    <div class="table-wrap">
      <table>
        <tr>
          <th>Cover</th>
          <th>Title</th>
          <th>Author</th>
          <th>Year</th>
          <th>Actions</th>
        </tr>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td>
                <img
                  class="cover"
                  src="<?php echo htmlspecialchars(coverUrl($row)); ?>"
                  alt=""
                  onerror="this.onerror=null;this.src='no-cover.png';"
                >
              </td>
              <td><?php echo htmlspecialchars($row["title"]); ?></td>
              <td><?php echo htmlspecialchars($row["author"]); ?></td>
              <td><?php echo (int)$row["year"]; ?></td>
              <td>
                <div class="t-actions">
                  <a class="btn btn-ghost" href="book.php?id=<?php echo (int)$row["id"]; ?>">View</a>

                  <a class="btn btn-ghost"
                     href="favorite_toggle.php?id=<?php echo (int)$row["id"]; ?>&back=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>">
                    <?php echo !empty($fav[(int)$row["id"]]) ? "★" : "☆"; ?>
                  </a>

                  <?php if (isAdmin()): ?>
                    <a class="btn btn-ghost" href="edit_book.php?id=<?php echo (int)$row["id"]; ?>">Edit</a>
                    <a class="btn btn-danger"
                       href="delete_book.php?id=<?php echo (int)$row["id"]; ?>"
                       onclick="return confirm('Are you sure?');">Delete</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5">No books found</td></tr>
        <?php endif; ?>
      </table>
    </div>

    <div class="pager" style="margin-top:14px; display:flex; gap:10px; align-items:center; justify-content:flex-end; flex-wrap:wrap;">
      <a class="btn btn-ghost" <?php echo ($page<=1?'style="pointer-events:none;opacity:.5"':''); ?>
         href="<?php echo pageLink($q, max(1, $page-1)); ?>">Prev</a>

      <span class="badge">Page <?php echo $page; ?> / <?php echo $totalPages; ?></span>

      <a class="btn btn-ghost" <?php echo ($page>=$totalPages?'style="pointer-events:none;opacity:.5"':''); ?>
         href="<?php echo pageLink($q, min($totalPages, $page+1)); ?>">Next</a>
    </div>

  </div>
</div>

</body>
</html>

<?php
if (isset($stmt) && $stmt) mysqli_stmt_close($stmt);
?>
