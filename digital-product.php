<?php
// Database connection details
include 'admin/inc/config.php';

// Start session if not already started
if (session_id() === '') {
    session_start();
}

// Fetch digital products from tbl_digital_products
try {
    $stmt = $pdo->query("SELECT * FROM tbl_digital_products WHERE is_digital = 1");
    $digital_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $digital_products = [];
    $error_message = "Error loading digital products: " . $e->getMessage();
}

// Handle Add to Cart for digital product
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    
    // Initialize cart arrays if not set
    if (!isset($_SESSION['cart_p_id'])) {
        $_SESSION['cart_p_id'] = [];
        $_SESSION['cart_p_name'] = [];
        $_SESSION['cart_p_current_price'] = [];
        $_SESSION['cart_p_featured_photo'] = [];
        $_SESSION['cart_p_qty'] = [];
        $_SESSION['cart_size_id'] = [];
        $_SESSION['cart_size_name'] = [];
        $_SESSION['cart_color_id'] = [];
        $_SESSION['cart_color_name'] = [];
        $_SESSION['cart_is_digital'] = [];
    }
    
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM tbl_digital_products WHERE id = ?");
    $stmt->execute(array($product_id));
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $_SESSION['cart_p_id'][] = 'digital_' . $product['id'];
        $_SESSION['cart_p_name'][] = $product['name'];
        $_SESSION['cart_p_current_price'][] = $product['price'];
        $_SESSION['cart_p_featured_photo'][] = $product['product_image'] ?? '';
        $_SESSION['cart_p_qty'][] = 1;
        $_SESSION['cart_size_id'][] = 0;
        $_SESSION['cart_size_name'][] = 'N/A';
        $_SESSION['cart_color_id'][] = 0;
        $_SESSION['cart_color_name'][] = 'N/A';
        $_SESSION['cart_is_digital'][] = 1;
        
        header('location: checkout.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Products</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f8fa; font-family: Arial, sans-serif; }
        .product-card { border: none; border-radius: 10px; overflow: hidden; background-color: #ffffff; transition: transform 0.3s ease-in-out; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15); }
        .card-img-top { max-height: 200px; object-fit: cover; border-bottom: 2px solid #007bff; }
        .card-title { font-size: 1.25rem; font-weight: bold; color: #333; }
        .card-text { font-size: 0.9rem; color: #555; }
        .btn-primary { background-color: #28a745; border-color: #28a745; }
        .btn-primary:hover { background-color: #218838; border-color: #1e7e34; }
        .text-success { color: #28a745 !important; }
        .digital-badge { background-color: #6f42c1; color: white; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

<div class="container my-5">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <h1 class="text-center text-primary mb-4">Our Digital Products</h1>
    <div class="row">
        <?php if (!empty($digital_products)): ?>
            <?php foreach ($digital_products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm product-card">
                        <?php if (!empty($product['product_image'])): ?>
                            <img src="<?php echo htmlspecialchars($product['product_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                                <span class="text-muted">No Image</span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body text-center">
                            <span class="badge digital-badge mb-2">Digital Product</span>
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>
                            <h6 class="text-success">$<?php echo number_format($product['price'], 2); ?></h6>

                            <form method="post" action="" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="add_to_cart" class="btn btn-primary w-100">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">No digital products available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

</body>
</html>
