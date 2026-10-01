<?php require_once('header.php'); ?>

<?php
// Check if Add to Cart form is submitted
if (isset($_POST['add_to_cart'])) {
    foreach ($_POST['product_id'] as $key => $product_id) {
        // Add product details to session arrays
        $_SESSION['cart_p_id'][] = $product_id;
        $_SESSION['cart_p_name'][] = $_POST['product_name'][$key];
        $_SESSION['cart_p_qty'][] = $_POST['quantity'][$key];

        // Get product details like price and photo from the database
        $statement = $pdo->prepare("SELECT p_current_price, p_featured_photo FROM tbl_product WHERE p_id = :product_id");
        $statement->bindParam(':product_id', $product_id);
        $statement->execute();
        $product = $statement->fetch(PDO::FETCH_ASSOC);

        if ($product === false) {
            die('Product not found!');
        }

        $_SESSION['cart_p_current_price'][] = $product['p_current_price'];
        $_SESSION['cart_p_featured_photo'][] = $product['p_featured_photo'];
    }
    // Redirect to the cart to view the updated items
    header('Location: cart.php');
    exit();
}

// Get the product details (e.g., from database)
$product_id = isset($_GET['p_id']) ? $_GET['p_id'] : null;
if ($product_id === null) {
    die('Product ID is missing!');
}

$statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_id = :product_id");
$statement->bindParam(':product_id', $product_id);
$statement->execute();
$product = $statement->fetch(PDO::FETCH_ASSOC);

if ($product === false) {
    die('Product not found!');
}

$banner_cart = isset($banner_cart) ? $banner_cart : 'default-banner.jpg'; // Set default banner
?>

<div class="product-page">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <img src="assets/uploads/<?php echo $product['p_featured_photo']; ?>" alt="" class="product-img">
            </div>
            <div class="col-md-6">
                <h2><?php echo $product['p_name']; ?></h2>
                <p><?php echo $product['p_description']; ?></p>
                <p>Price: <?php echo '$'; ?><?php echo $product['p_current_price']; ?></p>
                
                <!-- Add to Cart Form -->
                <form action="cart.php" method="post">
                    <input type="hidden" name="product_id[]" value="<?php echo $product['p_id']; ?>">
                    <input type="hidden" name="product_name[]" value="<?php echo $product['p_name']; ?>">
                    <input type="number" name="quantity[]" value="1" min="1" max="<?php echo $product['p_qty']; ?>" required>
                    <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>

<div class="page-banner" style="background-image: url(assets/uploads/<?php echo $banner_cart; ?>)">
    <div class="overlay"></div>
    <div class="page-banner-inner">
        <h1><?php echo LANG_VALUE_18; ?></h1>
    </div>
</div>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <?php if(!isset($_SESSION['cart_p_id'])): ?>
                    <h2 class="text-center">Cart is Empty!!</h2>
                    <h4 class="text-center">Add products to the cart in order to view it here.</h4>
                <?php else: ?>
                    <form action="" method="post">
                        <div class="cart">
                            <table class="table table-responsive table-hover table-bordered">
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                                <?php
                                $total_price = 0;
                                for ($i = 0; $i < count($_SESSION['cart_p_id']); $i++) {
                                    $product_id = $_SESSION['cart_p_id'][$i];
                                    $product_name = $_SESSION['cart_p_name'][$i];
                                    $product_qty = $_SESSION['cart_p_qty'][$i];
                                    $product_price = $_SESSION['cart_p_current_price'][$i];
                                    $product_photo = $_SESSION['cart_p_featured_photo'][$i];

                                    $total = $product_qty * $product_price;
                                    $total_price += $total;
                                ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><img src="assets/uploads/<?php echo $product_photo; ?>" alt="" style="width: 50px;"> <?php echo $product_name; ?></td>
                                    <td><input type="number" name="quantity[]" value="<?php echo $product_qty; ?>" min="1" max="10" required></td>
                                    <td><?php echo $product_price; ?></td>
                                    <td><?php echo $total; ?></td>
                                    <td><a href="cart-item-delete.php?id=<?php echo $product_id; ?>" class="btn btn-danger">Remove</a></td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <th colspan="4">Total</th>
                                    <th><?php echo $total_price; ?></th>
                                    <th></th>
                                </tr>
                            </table>
                        </div>

                        <div class="cart-buttons">
                            <ul>
                                <li><input type="submit" value="Update Cart" class="btn btn-primary" name="form1"></li>
                                <li><a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a></li>
                            </ul>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>
