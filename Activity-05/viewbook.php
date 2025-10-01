<?php
require_once "Book.php";

$bookObj = new Book();


$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : "";
$genre   = isset($_GET['genre']) ? trim($_GET['genre']) : "";


$books = $bookObj->searchBooks($keyword, $genre);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Books</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="container">
        <h1>Book List</h1>

        
        <form method="get" action="">
            <input type="text" name="keyword" placeholder="Search by title, author, publisher" value="<?= htmlspecialchars($keyword) ?>">
            <select name="genre">
                <option value="">--All Genres--</option>
                <option value="history" <?= $genre=="history" ? "selected" : "" ?>>History</option>
                <option value="science" <?= $genre=="science" ? "selected" : "" ?>>Science</option>
                <option value="fiction" <?= $genre=="fiction" ? "selected" : "" ?>>Fiction</option>
            </select>
            <button type="submit">Search</button>
        </form>
        <br>

      
        <?php if ($books): ?>
            <table border="1" cellpadding="10">
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Genre</th>
                    <th>Year</th>
                    <th>Publisher</th>
                    <th>Copies</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($books as $row): ?>
                    <tr>
                        <td><?= $row['id']; ?></td>
                        <td><?= $row['title']; ?></td>
                        <td><?= $row['author']; ?></td>
                        <td><?= $row['genre']; ?></td>
                        <td><?= $row['publication_year']; ?></td>
                        <td><?= $row['publisher']; ?></td>
                        <td><?= $row['copies']; ?></td>
                        <td>
                        <a href="editBook.php?id=<?= $row['id']; ?>">Edit</a> | 
                        <a href="deleteBook.php?id=<?= $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No books found.</p>
        <?php endif; ?>

        <br>
        <button><a href="addBook.php">Add New Book</a></button>
    </div>
</body>
</html>
