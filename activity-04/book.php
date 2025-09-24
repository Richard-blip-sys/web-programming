<?php
require_once "database.php";

class Book {
    public $id = "";
    public $title = "";
    public $author = "";
    public $genre = "";
    public $publication_year = "";
    public $publisher = "";
    public $copies = "";

    protected $db;

    public function __construct() {
        $this->db = new Database();
    }

  
    public function addBook() {
        $sql = "INSERT INTO book(title, author, genre, publication_year, publisher, copies) 
                VALUES (:title, :author, :genre, :publication_year, :publisher, :copies)";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(":title", $this->title);
        $query->bindParam(":author", $this->author);
        $query->bindParam(":genre", $this->genre);
        $query->bindParam(":publication_year", $this->publication_year);
        $query->bindParam(":publisher", $this->publisher);
        $query->bindParam(":copies", $this->copies);

        return $query->execute();
    }

 
    public function viewBooks() {
        $sql = "SELECT * FROM book";
        $query = $this->db->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

  
    public function searchBooks($keyword = "", $genre = "") {
        $sql = "SELECT * FROM book WHERE 1"; 

        if (!empty($keyword)) {
            $sql .= " AND (title LIKE :keyword OR author LIKE :keyword OR publisher LIKE :keyword)";
        }

        if (!empty($genre)) {
            $sql .= " AND genre = :genre";
        }

        $query = $this->db->connect()->prepare($sql);

        if (!empty($keyword)) {
            $keyword = "%" . $keyword . "%";
            $query->bindParam(":keyword", $keyword);
        }

        if (!empty($genre)) {
            $query->bindParam(":genre", $genre);
        }

        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
