<?php
require_once('header.php');

// Function to retrieve and refresh CJ Dropshipping access token
function getCJAccessToken($email, $password) {
    $url = 'https://developers.cjdropshipping.com/api2.0/v1/authentication/getAccessToken';

    $data = json_encode(['email' => $email, 'password' => $password]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

function refreshCJAccessToken($refreshToken) {
    $url = 'https://developers.cjdropshipping.com/api2.0/v1/authentication/refreshAccessToken';

    $data = json_encode(['refreshToken' => $refreshToken]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// Set CJ Dropshipping credentials
$email = 'phemcodejay@gmail.com';
$password = '42667d2d1d1a4dd7bb1f563b8eb7fc8c';

$token = getCJAccessToken($email, $password);
$accessToken = $token['access_token'];
$refreshToken = $token['refresh_token'];

// Check if the access token needs refreshing
if (!$accessToken) {
    $token = refreshCJAccessToken($refreshToken);
    $accessToken = $token['access_token'];
}

// Function to create an order with CJ Dropshipping
function createCJOrder($orderData, $accessToken) {
    $url = 'https://developers.cjdropshipping.com/api2.0/v1/shopping/order/createOrderV2';
    $data = json_encode($orderData);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "CJ-Access-Token: $accessToken",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// Example order data
$orderData = [
    "orderNumber" => "1234",
    "shippingZip" => "12345",
    "shippingCountry" => "USA",
    "shippingCountryCode" => "US",
    "shippingProvince" => "CA",
    "shippingCity" => "Los Angeles",
    "shippingPhone" => "1234567890",
    "shippingCustomerName" => "John Doe",
    "shippingAddress" => "123 Main St",
    "products" => [
        ["vid" => "92511400-C758-4474-93CA-66D442F5F787", "quantity" => 1]
    ]
];

$orderResponse = createCJOrder($orderData, $accessToken);
if ($orderResponse['code'] == 200) {
    echo "Order created successfully. Order ID: " . $orderResponse['data']['orderId'];
} else {
    echo "Failed to create order: " . json_encode($orderResponse);
}

// Fetch orders from the database and display in a table
?>

<section class="content-header">
    <div class="content-header-left">
        <h1>View Orders</h1>
    </div>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-body table-responsive">
                    <table id="example1" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Product Details</th>
                                <th>Payment Information</th>
                                <th>Paid Amount</th>
                                <th>Payment Status</th>
                                <th>Shipping Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 0;
                            $statement = $pdo->prepare("SELECT * FROM tbl_payment ORDER by id DESC");
                            $statement->execute();
                            $result = $statement->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($result as $row) {
                                $i++;
                                ?>
                                <tr class="<?php echo $row['payment_status'] == 'Pending' ? 'bg-r' : 'bg-g'; ?>">
                                    <td><?php echo $i; ?></td>
                                    <td>
                                        <b>ID:</b> <?php echo $row['customer_id']; ?><br>
                                        <b>Name:</b> <?php echo $row['customer_name']; ?><br>
                                        <b>Email:</b> <?php echo $row['customer_email']; ?><br><br>
                                        <a href="#" data-toggle="modal" data-target="#model-<?php echo $i; ?>" class="btn btn-warning btn-xs" style="width:100%;margin-bottom:4px;">Send Message</a>
                                        <div id="model-<?php echo $i; ?>" class="modal fade" role="dialog">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        <h4 class="modal-title" style="font-weight: bold;">Send Message</h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="" method="post">
                                                            <input type="hidden" name="cust_id" value="<?php echo $row['customer_id']; ?>">
                                                            <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                                                            <table class="table table-bordered">
                                                                <tr>
                                                                    <td>Subject</td>
                                                                    <td><input type="text" name="subject_text" class="form-control"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Message</td>
                                                                    <td><textarea name="message_text" class="form-control"></textarea></td>
                                                                </tr>
                                                                <tr>
                                                                    <td></td>
                                                                    <td><input type="submit" value="Send Message" name="form1"></td>
                                                                </tr>
                                                            </table>
                                                        </form>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statement1 = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
                                        $statement1->execute([$row['payment_id']]);
                                        $result1 = $statement1->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($result1 as $row1) {
                                            echo "<b>Product:</b> {$row1['product_name']} (Size: {$row1['size']}, Color: {$row1['color']}) Quantity: {$row1['quantity']} Unit Price: {$row1['unit_price']}<br>";
                                        }
                                        ?>
                                    </td>
                                    <td><b>Method:</b> <?php echo $row['payment_method']; ?><br><b>Transaction ID:</b> <?php echo $row['txnid']; ?></td>
                                    <td><b>$</b><?php echo $row['paid_amount']; ?></td>
                                    <td><?php echo $row['payment_status']; ?></td>
                                    <td><?php echo $row['shipping_status']; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once('footer.php'); ?>
