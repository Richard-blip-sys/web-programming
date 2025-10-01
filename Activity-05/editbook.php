<?php
require_once "Book.php";
$bookObj = new Book();

if (!isset($_GET['id'])) {
    die("Book ID missing.");
}

$id = $_GET['id'];
$currentBook = $bookObj->getBookById($id);

if (!$currentBook) {
    die("Book not found.");
}

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $author = trim($_POST["author"]);
    $genre = trim($_POST["genre"]);
    $year = trim($_POST["publication_year"]);
    $publisher = trim($_POST["publisher"]);
    $copies = trim($_POST["copies"]);

    // validation (same as addBook)
    if (empty($title)) $errors[] = "Title is required.";
    if (empty($author)) $errors[] = "Author is required.";
    if (empty($genre)) $errors[] = "Genre is required.";
    if (!is_numeric($year) || $year > date("Y")) $errors[] = "Invalid year.";
    if (!is_numeric($copies)) $errors[] = "Copies must be numeric.";

    if (empty($errors)) {
        $bookObj->id = $id;
        $bookObj->title = $title;
        $bookObj->author = $author;
        $bookObj->genre = $genre;
        $bookObj->publication_year = $year;
        $bookObj->publisher = $publisher;
        $bookObj->copies = $copies;

        if ($bookObj->updateBook()) {
            $success = "Book updated successfully!";
            $currentBook = $bookObj->getBookById($id); // refresh data
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
<div class="container">
  <h1>Edit Book</h1>
  <?php if ($errors): ?>
    <ul style="color:red;"><?php foreach($errors as $e) echo "<li>$e</li>"; ?></ul>
  <?php endif; ?>
  <?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>

  <form method="post">
    <label>Title *</label>
    <input type="text" name="title" value="<?= htmlspecialchars($currentBook['title']); ?>">

    <label>Author *</label>
    <input type="text" name="author" value="<?= htmlspecialchars($currentBook['author']); ?>">

    <label>Genre *</label>
    <select name="genre">
      <option value="history" <?= $currentBook['genre']=="history"?"selected":""; ?>>History</option>
      <option value="science" <?= $currentBook['genre']=="science"?"selected":""; ?>>Science</option>
      <option value="fiction" <?= $currentBook['genre']=="fiction"?"selected":""; ?>>Fiction</option>
    </select>

    <label>Publication Year *</label>
    <input type="text" name="publication_year" value="<?= htmlspecialchars($currentBook['publication_year']); ?>">

    <label>Publisher</label>
    <input type="text" name="publisher" value="<?= htmlspecialchars($currentBook['publisher']); ?>">

    <label>Copies *</label>
    <input type="text" name="copies" value="<?= htmlspecialchars($currentBook['copies']); ?>">

    <button type="submit">Update</button>
    <a href="viewBook.php">Back</a>
  </form>
</div>
</body>
</html>
