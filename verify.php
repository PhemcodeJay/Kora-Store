<?php require_once('header.php'); ?>

<?php
$verified = false;

if (isset($_REQUEST['email']) && isset($_REQUEST['token']) && $_REQUEST['email'] != '' && $_REQUEST['token'] != '')
{
    // check if the token is correct and match with database.
    $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email=?");
    $statement->execute(array(trim((string)$_REQUEST['email'])));
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (count($result) == 0) {
        $error_message = '<p style="color:red;">No account found for this email address.</p>';
    } else {
        $row = $result[0];
        if ((int)$row['cust_status'] == 1) {
            // Already activated before (the token is cleared once it is used).
            // Checked first, otherwise the cleared token would look "expired".
            $verified = true;
            $success_message = '<p style="color:green;">Your email is already verified. You can login to our website.</p><p><a href="'.BASE_URL.'login.php" style="color:#167ac6;font-weight:bold;">Click here to login</a></p>';
        } elseif ((string)$row['cust_token'] === '' || (string)$_REQUEST['token'] !== (string)$row['cust_token']) {
            $error_message = '<p style="color:red;">'.LANG_VALUE_168.'</p>';
        } else {
            // everything is correct. now activate the user and clear the token.
            $statement = $pdo->prepare("UPDATE tbl_customer SET cust_token=?, cust_status=? WHERE cust_email=?");
            $statement->execute(array('', 1, $row['cust_email']));

            $verified = true;
            $success_message = '<p style="color:green;">'.LANG_VALUE_167.'</p><p><a href="'.BASE_URL.'login.php" style="color:#167ac6;font-weight:bold;">Click here to login</a></p>';
        }
    }
}

if (!$verified) {
    if (!isset($error_message) || $error_message == '') {
        $error_message = '<p style="color:red;">Verification link is incomplete. Please use the exact link from your verification email.</p>';
    }
}
?>

<div class="page-banner" style="background-color:#444;">
    <div class="inner">
        <h1>Email Verification</h1>
    </div>
</div>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="user-content">
                    <?php 
                        echo $error_message;
                        echo $success_message;
                    ?>
                </div>                
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>