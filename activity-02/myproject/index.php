<html>
    <body>
        <h1>Hello, from "Richard Banquerigo"!</h1>
    </body>
</html>

<?php 
    echo "Hello World!";
?>
<br>

<?php 
    $x = 15;
    $y = 3;
    $sum = $x + $y;

    echo "The sum is: $sum. ";
?>
<br>
<?php
    if($x % $y == 0){
        echo "$y is a factor of $x";
    } else {
        echo "$y is not a factor of $x";
    }
?>
<br>
<?php
    for($i = 1; $i <= 10; $i++) {
        if($i % 3 == 0 || $i % 5 == 0) {
            echo "$i <br>";
        }
        
    }
?>
<br>

<?php
    $products = array("Product A", "Product B", "Product C",);
    var_dump($products);
?>
<br>

<?php
    $products = array("Product A", "Product B", "Product C");
    echo $products[0];
?>
<br>

<?php
    $products = array("Product A", "Product B", "Product C");
    echo $products[1] = "Product D";
    var_dump($products);
?>
<br>

<?php 
     $products = array("Product A", "Product B", "Product C");
     foreach($products as $p) {
        echo "$p <br>";
    }
?>
<br>

<?php 
    $products = array ("name"=>"Product A", "price"=>10.50, "stock"=>12);
    echo $products[name];
?>
<br>

<h3>Step #3: My First PHP Program</h3>
<?php 
    $products = array (
        array("name"=>"Product A", "price"=>10.50, "stock"=>12),
        array("name"=>"Product B", "price"=>5.60, "stock"=>7),
        array("name"=>"Product C", "price"=>7.00, "stock"=>5)
    );

    foreach($products as $p){
        echo $p["name"] . " is " . $p["price"] . "pesos <br>" ;
    }
?>