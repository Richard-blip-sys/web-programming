<?php
require_once "Book.php";
$bookObj = new Book();

$book = [
    "title" => "",
    "author" => "",
    "genre" => "",
    "publication_year" => "",
    "publisher" => "",
    "copies" => ""
];

$error = [
    "title" => "",
    "author" => "",
    "genre" => "",
    "publication_year" => "",
    "copies" => ""
];

$submit_error = "";
$submit_success = "";
$duplicate_title_found = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $book["title"] = trim(htmlspecialchars($_POST["title"]));
    $book["author"] = trim(htmlspecialchars($_POST["author"]));
    $book["genre"] = trim(htmlspecialchars($_POST["genre"]));
    $book["publication_year"] = trim(htmlspecialchars($_POST["publication_year"]));
    $book["publisher"] = trim(htmlspecialchars($_POST["publisher"]));
    $book["copies"] = trim(htmlspecialchars($_POST["copies"]));

   
    if (empty($book["title"]))
        $error["title"] = "Title is required";

    if (empty($book["author"]))
        $error["author"] = "Author is required";

    if (empty($book["genre"]))
        $error["genre"] = "Genre is required";

    if (empty($book["publication_year"]))
        $error["publication_year"] = "Publication year is required";
    else if (!is_numeric($book["publication_year"]))
        $error["publication_year"] = "Publication year must be a number";
    else if ($book["publication_year"] > date("Y"))
        $error["publication_year"] = "Publication year must not be in the future";

    if (empty($book["copies"]))
        $error["copies"] = "Copies is required";
    else if (!is_numeric($book["copies"]))
        $error["copies"] = "Copies must be a number";

   
    if (empty(array_filter($error))) {
        $viewBook = new Book();
        foreach ($viewBook->viewBooks() as $databook) {
            if ($databook["title"] == $book["title"]) {
                $duplicate_title_found = true;
                break;
            }
        }

        if ($duplicate_title_found) {
            $submit_error = "This title is already in the database";
        } else {
            $bookObj->title = $book["title"];
            $bookObj->author = $book["author"];
            $bookObj->genre = $book["genre"];
            $bookObj->publication_year = $book["publication_year"];
            $bookObj->publisher = $book["publisher"];
            $bookObj->copies = $book["copies"];

            if ($bookObj->addBook()) {
                $submit_success = "Book was added successfully";
            }
        }
    } else {
        $submit_error = "You must fill out the required forms";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="wrapper">
        <div class="container">
            <h1>Book Form</h1>
            <form action="" method="post">
                <label for="title">Title <span></span></label>
                <input type="text" name="title" id="title" value="<?= $book["title"]; ?>">
                <p class="error"><?= $error["title"]; ?></p>

                <label for="genre">Genre <span></span></label>
                <select name="genre" id="genre">
                    <option value="">--Select Genre--</option>
                    <option value="history" <?= $book["genre"] == "history" ? "selected" : "" ?>>History</option>
                    <option value="science" <?= $book["genre"] == "science" ? "selected" : "" ?>>Science</option>
                    <option value="fiction" <?= $book["genre"] == "fiction" ? "selected" : "" ?>>Fiction</option>
                </select>
                <p class="error"><?= $error["genre"]; ?></p>

                <label for="author">Author <span></span></label>
                <input type="text" name="author" id="author" value="<?= $book["author"]; ?>">
                <p class="error"><?= $error["author"]; ?></p>

                <label for="publication_year">Publication Year <span></span></label>
                <input type="text" name="publication_year" id="publication_year" value="<?= $book["publication_year"]; ?>">
                <p class="error"><?= $error["publication_year"]; ?></p>

                <label for="publisher">Publisher</label>
                <input type="text" name="publisher" id="publisher" value="<?= $book["publisher"]; ?>">

                <label for="copies">Copies <span></span></label>
                <input type="text" name="copies" id="copies" value="<?= $book["copies"]; ?>">
                <p class="error"><?= $error["copies"]; ?></p>

                <br>
                <input type="submit" value="Add Book">
                <p class="error"><?= $submit_error; ?></p>
                <p style="color: green; margin: 0;"><?= $submit_success; ?></p>
            </form>
            <br>
            <button><a href="viewBook.php">View Book List</a></button>
        </div>
    </div>
</body>
</html>
