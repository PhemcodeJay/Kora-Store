<?php
// Database connection details
// Include the database configuration file
include 'admin/inc/config.php';

try {
    
    // Fetch digital products from tbl_digital_products
    $stmt = $pdo->query("SELECT * FROM tbl_digital_products WHERE is_digital = 1");
    $digital_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Dummy user ID for demonstration, replace with actual user session ID
    $user_id = 1;

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Products</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Inline CSS for styling */
        body { background-color: #f5f8fa; font-family: Arial, sans-serif; }
        h1 { font-weight: bold; color: #007bff; }
        .product-card {
            border: none; border-radius: 10px; overflow: hidden; background-color: #ffffff; transition: transform 0.3s ease-in-out;
        }
        .product-card:hover {
            transform: translateY(-10px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        .card-img-top {
            max-height: 200px; object-fit: cover; border-bottom: 2px solid #007bff;
        }
        .card-title { font-size: 1.25rem; font-weight: bold; color: #333; }
        .card-text { font-size: 0.9rem; color: #555; }
        .btn-primary { background-color: #28a745; border-color: #28a745; }
        .btn-primary:hover { background-color: #218838; border-color: #1e7e34; }
        .text-success { color: #28a745 !important; }
        .pp-7DFQQUC7LZRSL {
            text-align: center;
            border: none;
            border-radius: 1.5rem;
            min-width: 11.625rem;
            padding: 0 2rem;
            height: 2rem;
            font-weight: bold;
            background-color: #FFD140;
            color: #000000;
            font-family: "Helvetica Neue", Arial, sans-serif;
            font-size: 0.875rem;
            line-height: 1.125rem;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container my-5">
    <h1 class="text-center text-primary mb-4">Our Digital Products</h1>
    <div class="row">
        <?php if(!empty($digital_products)): ?>
            <?php foreach ($digital_products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm product-card">
                        <img src="<?= htmlspecialchars($product['product_image']) ?>" class="card-img-top" alt="Product Image">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($product['description']) ?></p>
                            <h6 class="text-success">$<?= number_format($product['price'], 2) ?></h6>

                            <!-- Payment required button, PayPal button will be shown when clicked -->
                            <button class="btn btn-secondary payment-required-btn" data-product-id="<?= $product['id'] ?>" onclick="showPayPalButton(this)">
                                <i class="fas fa-lock"></i> Payment Required
                            </button>

                            <!-- PayPal payment form, hidden by default -->
                            <div class="paypal-btn-container" id="paypal-btn-container-<?= $product['id'] ?>" style="display:none;">
                                <form action="https://www.paypal.com/ncp/payment/7DFQQUC7LZRSL" method="post" target="_top" style="display:inline-grid;justify-items:center;align-content:start;gap:0.5rem;">
                                    <input class="pp-7DFQQUC7LZRSL" type="submit" value="Pay Now" />
                                    <img src="https://www.paypalobjects.com/images/Debit_Credit.svg" alt="cards" />
                                    <section> Powered by <img src="https://www.paypalobjects.com/paypal-ui/logos/svg/paypal-wordmark-color.svg" alt="paypal" style="height:0.875rem;vertical-align:middle;"/></section>
                                </form>
                            </div>

                            <!-- Download button, initially hidden -->
                            <a href="<?= htmlspecialchars($product['file_url']) ?>" class="btn btn-primary download-btn" id="download-btn-<?= $product['id'] ?>" style="display:none;" download>
                                <i class="fas fa-download"></i> Download
                            </a>

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
<script>
    // Function to show PayPal button and hide "Payment Required" button
    function showPayPalButton(button) {
        const productId = button.getAttribute('data-product-id');
        
        // Show PayPal button and hide the payment required button
        document.getElementById('paypal-btn-container-' + productId).style.display = 'block';
        button.style.display = 'none';

        // After successful payment (simulated), show the download button
        // This would be replaced with actual payment success check
        setTimeout(function() {
            document.getElementById('download-btn-' + productId).style.display = 'inline-block';
        }, 300000);  // Simulate payment success after 300 seconds
    }
</script>

</body>
</html>
