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
?>
