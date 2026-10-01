<?php
// Function to get the access token using email and password
function getAccessToken($email, $password) {
    $url = 'https://developers.cjdropshipping.com/api2.0/v1/authentication/getAccessToken';

    $data = json_encode([
        'email' => $email,
        'password' => $password,
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data),
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// Function to refresh the access token using the refresh token
function refreshAccessToken($refreshToken) {
    $url = 'https://developers.cjdropshipping.com/api2.0/v1/authentication/refreshAccessToken';

    $data = json_encode([
        'refreshToken' => $refreshToken,
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data),
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// Example usage: replace these with your credentials
$email = 'phemcodejay@gmail.com';
$password = '42667d2d1d1a4dd7bb1f563b8eb7fc8c'; // Use your actual password
$token = getAccessToken($email, $password);

if (isset($token['access_token'])) {
    $accessToken = $token['access_token'];
    $refreshToken = $token['refresh_token']; // Assuming the response contains a refresh_token
    echo "Access Token: " . $accessToken . "\n";
    echo "Refresh Token: " . $refreshToken . "\n";

    // You may want to store the access token and refresh token for future use
    // Example: save them to a session or database
} else {
    // Handle error
    echo "Error retrieving access token: " . json_encode($token) . "\n";
}

// Example of refreshing the token (only needed when the access token expires)
if (isset($refreshToken)) {
    $newToken = refreshAccessToken($refreshToken);
    if (isset($newToken['access_token'])) {
        $accessToken = $newToken['access_token'];
        echo "New Access Token: " . $accessToken . "\n";
        // Use the new access token as needed
    } else {
        // Handle error
        echo "Error refreshing access token: " . json_encode($newToken) . "\n";
    }
}

// Function to create a new order
function createOrder($orderData, $accessToken) {
    $url = 'https://developers.cjdropshipping.com/api2.0/v1/shopping/order/createOrderV2'; // Updated URL

    $data = json_encode($orderData);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "CJ-Access-Token: $accessToken", // Updated header
        "Content-Type: application/json",
        'Content-Length: ' . strlen($data),
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $result = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
    }

    curl_close($ch);

    return json_decode($result, true);
}

// Usage for createOrder
$orderData = [
    "orderNumber" => "1234", // Example order number
    "shippingZip" => "12345",
    "shippingCountry" => "USA",
    "shippingCountryCode" => "US",
    "shippingProvince" => "CA",
    "shippingCity" => "Los Angeles",
    "shippingCounty" => "",
    "shippingPhone" => "1234567890",
    "shippingCustomerName" => "John Doe",
    "shippingAddress" => "123 Main St",
    "shippingAddress2" => "",
    "taxId" => "123-45-6789",
    "remark" => "Urgent order",
    "products" => [
        [
            "vid" => "92511400-C758-4474-93CA-66D442F5F787", // Example product ID
            "quantity" => 1
        ]
    ]
];

$orderResponse = createOrder($orderData, $accessToken);

if (isset($orderResponse['code']) && $orderResponse['code'] == 200) {
    echo "Order ID: {$orderResponse['data']['orderId']}, Status: {$orderResponse['data']['status']}\n";
} else {
    // Handle error
    echo "Error creating order: " . json_encode($orderResponse);
}

// Function to get order status
function getOrderStatus($orderId, $accessToken) {
    $url = "https://developers.cjdropshipping.com/api2.0/v1/shopping/order/getOrderDetail?orderId=$orderId"; // Updated URL

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "CJ-Access-Token: $accessToken", // Updated header
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

    $result = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
    }

    curl_close($ch);

    return json_decode($result, true);
}

// Usage for getOrderStatus
$orderStatusResponse = getOrderStatus('210711100018043276', $accessToken); // Example order ID

if (isset($orderStatusResponse['data'])) {
    echo "Order Status: {$orderStatusResponse['data']['status']}\n";
} else {
    // Handle error
    echo "Error retrieving order status: " . json_encode($orderStatusResponse);
}

// Function to track an order
function trackOrder($trackingNumber, $accessToken) {
    $url = "https://developers.cjdropshipping.com/api2.0/v1/shopping/order/track/$trackingNumber"; // Updated URL

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "CJ-Access-Token: $accessToken", // Updated header
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

    $result = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
    }

    curl_close($ch);

    return json_decode($result, true);
}

// Usage for trackOrder
$trackingInfo = trackOrder('tracking_number_here', $accessToken); // Replace with a valid tracking number

if (isset($trackingInfo['data'])) {
    echo "Tracking Status: {$trackingInfo['data']['status']}, Estimated Delivery: {$trackingInfo['data']['estimatedDelivery']}\n";
} else {
    // Handle error
    echo "Error retrieving tracking information: " . json_encode($trackingInfo);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            margin-bottom: 20px;
        }
        input, select {
            padding: 10px;
            margin: 5px;
            width: 300px;
        }
        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .response {
            margin-top: 20px;
            padding: 10px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>

<h1>Order Management</h1>

<!-- Create Order Form -->
<h2>Create Order</h2>
<form action="create_order.php" method="POST">
    <label for="orderNumber">Order Number:</label>
    <input type="text" id="orderNumber" name="orderNumber" required><br>
    
    <label for="shippingZip">Shipping Zip Code:</label>
    <input type="text" id="shippingZip" name="shippingZip" required><br>
    
    <label for="shippingCountry">Shipping Country:</label>
    <input type="text" id="shippingCountry" name="shippingCountry" required><br>
    
    <label for="shippingCity">Shipping City:</label>
    <input type="text" id="shippingCity" name="shippingCity" required><br>

    <label for="productVid">Product ID (vid):</label>
    <input type="text" id="productVid" name="productVid" required><br>

    <label for="quantity">Quantity:</label>
    <input type="number" id="quantity" name="quantity" required><br>
    
    <button type="submit">Create Order</button>
</form>

<!-- Check Order Status Form -->
<h2>Check Order Status</h2>
<form action="order_status.php" method="GET">
    <label for="orderId">Order ID:</label>
    <input type="text" id="orderId" name="orderId" required><br>
    
    <button type="submit">Check Status</button>
</form>

<!-- Track Order Form -->
<h2>Track Order</h2>
<form action="track_order.php" method="GET">
    <label for="trackingNumber">Tracking Number:</label>
    <input type="text" id="trackingNumber" name="trackingNumber" required><br>
    
    <button type="submit">Track Order</button>
</form>

<div class="response">
    <h3>Response:</h3>
    <p id="responseText">Your response will appear here.</p>
</div>

</body>
</html>
