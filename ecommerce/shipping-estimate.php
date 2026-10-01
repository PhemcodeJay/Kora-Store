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
function estimateShippingAndTrack($shippingData, $trackingNumbers, $accessToken) {
    // Freight Calculate URL
    $freightUrl = 'https://developers.cjdropshipping.com/api2.0/v1/logistic/freightCalculate'; 

    // Prepare data for the freight calculation request
    $freightData = json_encode([
        'startCountryCode' => $shippingData['startCountryCode'],
        'endCountryCode' => $shippingData['endCountryCode'],
        'products' => $shippingData['products'],
    ]);

    // Initialize cURL for the freight calculation
    $ch = curl_init($freightUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "CJ-Access-Token: $accessToken",
        "Content-Type: application/json",
        'Content-Length: ' . strlen($freightData),
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $freightData);
    
    // Execute freight calculation request
    $freightResult = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'Curl error (Freight Calculation): ' . curl_error($ch);
        curl_close($ch);
        return;
    }

    // Parse freight calculation result
    $freightEstimate = json_decode($freightResult, true);

    // Track info URL
    $trackUrl = 'https://developers.cjdropshipping.com/api2.0/v1/logistic/trackInfo';
    
    // Prepare tracking numbers for the request
    $trackQuery = http_build_query(['trackNumber' => $trackingNumbers]);

    // Initialize cURL for tracking info
    $trackCh = curl_init("{$trackUrl}?$trackQuery");
    curl_setopt($trackCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($trackCh, CURLOPT_HTTPHEADER, [
        "CJ-Access-Token: $accessToken"
    ]);

    // Execute tracking info request
    $trackResult = curl_exec($trackCh);
    
    // Check for cURL errors
    if (curl_errno($trackCh)) {
        echo 'Curl error (Tracking): ' . curl_error($trackCh);
        curl_close($trackCh);
        return;
    }

    // Parse tracking info result
    $trackingInfo = json_decode($trackResult, true);

    // Close cURL handles
    curl_close($ch);
    curl_close($trackCh);

    return [
        'freightEstimate' => $freightEstimate,
        'trackingInfo' => $trackingInfo
    ];
}

// Usage
$shippingData = [
    'startCountryCode' => 'US',
    'endCountryCode' => 'US',
    'products' => [
        ['quantity' => 2, 'vid' => '439FC05B-1311-4349-87FA-1E1EF942C418'],
    ],
];

$trackingNumbers = ['CJPKL7160102171YQ', 'CJPKL7160102171YQ']; // Array of tracking numbers

$accessToken = '42667d2d1d1a4dd7bb1f563b8eb7fc8c'; // Your CJ Dropshipping Access Token

$shippingAndTracking = estimateShippingAndTrack($shippingData, $trackingNumbers, $accessToken);

// Output the results
if (isset($shippingAndTracking['freightEstimate']['cost'])) {
    echo "Estimated Shipping Cost: {$shippingAndTracking['freightEstimate']['cost']}\n";
} else {
    echo "Error estimating shipping: " . json_encode($shippingAndTracking['freightEstimate']);
}

if (isset($shippingAndTracking['trackingInfo'])) {
    echo "Tracking Info: " . json_encode($shippingAndTracking['trackingInfo']);
} else {
    echo "Error retrieving tracking information: " . json_encode($shippingAndTracking['trackingInfo']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping and Tracking</title>
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

<h1>Shipping and Tracking</h1>

<!-- Shipping Estimate Form -->
<h2>Estimate Shipping Cost</h2>
<form action="shipping_and_tracking.php" method="POST">
    <label for="startCountryCode">Start Country Code:</label>
    <input type="text" id="startCountryCode" name="startCountryCode" required><br>
    
    <label for="endCountryCode">End Country Code:</label>
    <input type="text" id="endCountryCode" name="endCountryCode" required><br>

    <label for="productVid">Product ID (VID):</label>
    <input type="text" id="productVid" name="productVid" required><br>

    <label for="quantity">Product Quantity:</label>
    <input type="number" id="quantity" name="quantity" required><br>

    <label for="trackingNumbers">Tracking Numbers (comma separated):</label>
    <input type="text" id="trackingNumbers" name="trackingNumbers" required><br>

    <button type="submit">Estimate Shipping and Track</button>
</form>

<div class="response">
    <h3>Response:</h3>
    <p id="responseText">Your response will appear here.</p>
</div>

</body>
</html>
