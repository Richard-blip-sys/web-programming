<?php
require_once "Book.php";
$bookObj = new Book();

if (!isset($_GET['id'])) {
    die("Book ID missing.");
}

$id = $_GET['id'];
if ($bookObj->deleteBook($id)) {
    header("Location: viewBook.php?msg=Book deleted");
    exit;
} else {
    echo "Failed to delete book.";
}
