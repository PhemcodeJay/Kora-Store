<?php
// Include the database configuration file
include 'inc/config.php';

// Initialize error and success messages
$errorMessage = "";
$successMessage = "";

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $is_digital = 1; // Assuming all uploads are digital

    if (isset($_FILES['file']) && isset($_FILES['product_image'])) {
        $file = $_FILES['file'];
        $image = $_FILES['product_image'];

        if ($file['error'] !== UPLOAD_ERR_OK || $image['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = "Error uploading files. Error codes: File - " . $file['error'] . ", Image - " . $image['error'];
        } else {
            // Create unique file paths using product name
            $targetDir = "uploads/files/digital-product/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $filePath = $targetDir . $name . "_" . basename($file['name']);
            $imagePath = $targetDir . $name . "_" . basename($image['name']);

            // Move uploaded files to the target directory
            if (move_uploaded_file($file['tmp_name'], $filePath) && move_uploaded_file($image['tmp_name'], $imagePath)) {
                try {
                    // Insert product data into the database
                    $stmt = $pdo->prepare("INSERT INTO tbl_digital_products (name, description, price, file_url, product_image, is_digital) VALUES (:name, :description, :price, :file_url, :product_image, :is_digital)");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':price', $price);
                    $stmt->bindParam(':file_url', $filePath);
                    $stmt->bindParam(':product_image', $imagePath);
                    $stmt->bindParam(':is_digital', $is_digital, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        // Redirect to digital-product.php on successful upload
                        header("Location: /digital-product.php");
                        exit; // Ensure no further code is executed after redirection
                    }
                     else {
                        $errorMessage = "Error inserting data into the database.";
                    }
                } catch (PDOException $e) {
                    $errorMessage = "Database error: " . $e->getMessage();
                }
            } else {
                $errorMessage = "Error moving uploaded files.";
            }
        }
    } else {
        $errorMessage = "File or image not uploaded.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Digital Product</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: url('https://example.com/tech-image.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            color: #ffffff;
        }
        .container {
            max-width: 600px;
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            color: #333;
        }
        h1 {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 30px;
            color: #2575fc;
        }
        input[type="text"], input[type="number"], input[type="file"], textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        input[type="text"]:focus, input[type="number"]:focus, input[type="file"]:focus, textarea:focus {
            border-color: #2575fc;
            box-shadow: 0 0 6px rgba(37, 117, 252, 0.4);
            outline: none;
        }
        button {
            background-color: #6a11cb;
            color: #ffffff;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            width: 100%;
            transition: background-color 0.3s, transform 0.3s;
        }
        button:hover {
            background-color: #2575fc;
            transform: scale(1.05);
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin: 15px 0;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Upload Digital Product</h1>

    <?php if ($errorMessage): ?>
        <div class="message error"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="message success"><?php echo $successMessage; ?></div>
        <div>
            <p><strong>Uploaded Product:</strong> <?php echo htmlspecialchars($name); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($description); ?></p>
            <p><strong>Price:</strong> $<?php echo htmlspecialchars($price); ?></p>
            <p><strong>Digital File:</strong> <a href="<?php echo htmlspecialchars($filePath); ?>" download>Download File</a></p>
            <p><strong>Product Image:</strong><br>
                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Product Image" style="width:100%; max-width:300px; border-radius: 8px;">
            </p>
        </div>
    <?php endif; ?>

    <form action="add-digital-product.php" method="post" enctype="multipart/form-data">
        <label for="name">Product Name:</label>
        <input type="text" name="name" required>

        <label for="description">Description:</label>
        <textarea name="description" required></textarea>

        <label for="price">Price:</label>
        <input type="number" name="price" step="0.01" required>

        <label for="file">Upload File:</label>
        <input type="file" name="file" accept=".pdf,.zip" required>

        <label for="product_image">Upload Product Image:</label>
        <input type="file" name="product_image" accept=".jpg,.png" required>

        <button type="submit">Upload Product</button>
    </form>
</div>

</body>
</html>
