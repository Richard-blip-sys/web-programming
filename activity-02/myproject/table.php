<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Table</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php 
   $products = array (
    array("name"=>"Product A", "price"=>10.50, "stock"=>12),
    array("name"=>"Product B", "price"=>5.60, "stock"=>7),
    array("name"=>"Product C", "price"=>7.00, "stock"=>5),
    array("name"=>"Product D", "price"=>15.00, "stock"=>20),
    array("name"=>"Product E", "price"=>25.00, "stock"=>15),
    array("name"=>"Product F", "price"=>30.00, "stock"=>10)
);
?>
    <div class="container">
        <h2>Dynamic Table</h2>
        <table border=1>
            <tr>
                <th>No.</th>
                <th>Product Name</th>
                <th>Price</th>
                <th>Stock</th>
            </tr>
            <?php 
            $counter = 1;
            foreach($products as $p) {
            ?>
            <tr class="<?= ($p['stock'] < 10) ? 'low-stock' : '' ?>">
                <td><?= $counter?></td>
                <td><?= $p["name"]?></td>
                <td><?= $p["price"]?></td>
                <td><?= $p["stock"]?></td>
            </tr>
            <?php
                $counter++;
            }
            ?>

        </table>
    </div>
</body>

</html>