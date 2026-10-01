<?php require_once('header.php'); ?>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_reset_password = $row['banner_reset_password'];
}
?>

<?php
if( !isset($_GET['email']) || !isset($_GET['token']) )
{
    header('location: '.BASE_URL.'login.php');
    exit;
}

$statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email=? AND cust_token=?");
$statement->execute(array($_GET['email'],$_GET['token']));
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
$tot = $statement->rowCount();
if($tot == 0)
{
    header('location: '.BASE_URL.'login.php');
    exit;
}
foreach ($result as $row) {
    $saved_time = $row['cust_timestamp'];
}

$error_message2 = '';
if(time() - $saved_time > 86400)
{
    $error_message2 = LANG_VALUE_144;
}

if(isset($_POST['form1'])) {

    $valid = 1;
    
    if( empty($_POST['cust_new_password']) || empty($_POST['cust_re_password']) )
    {
        $valid = 0;
        $error_message .= LANG_VALUE_166.'<br>';   // Please enter a password.
    }
    else
    {
        if(strlen((string)$_POST['cust_new_password']) < 8)
        {
            $valid = 0;
            $error_message .= LANG_VALUE_140.'<br>';   // min. 8 characters
        }
        if($_POST['cust_new_password'] !== $_POST['cust_re_password'])
        {
            $valid = 0;
            $error_message .= LANG_VALUE_139.'<br>';   // passwords do not match
        }
    }   

    if($valid == 1) {

        $cust_new_password = (string)$_POST['cust_new_password']; // never strip_tags a password
        $statement = $pdo->prepare("UPDATE tbl_customer SET cust_password=?, cust_token=?, cust_timestamp=? WHERE cust_email=?");
        $statement->execute(array(password_hash($cust_new_password, PASSWORD_DEFAULT), '', time(), $_GET['email']));
        
        header('location: '.BASE_URL.'reset-password-success.php');
        exit;
    }

    
}
?>

<div class="page-banner" style="background-color:#444;background-image: url(assets/uploads/<?php echo $banner_reset_password; ?>);">
    <div class="inner">
        <h1><?php echo LANG_VALUE_149; ?></h1>
    </div>
</div>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="user-content">
                    <?php
                    if($error_message != '') {
                        echo "<div class='error' style='padding:10px;background:#fdf0f0;border-left:3px solid #e4144d;margin-bottom:20px;'>".$error_message."</div>";
                    }
                    ?>
                    <?php if($error_message2 != ''): ?>
                        <div class="error"><?php echo $error_message2; ?></div>
                    <?php else: ?>
                        <form action="" method="post">
                            <?php $csrf->echoInputField(); ?>
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for=""><?php echo LANG_VALUE_100; ?> *</label>
                                        <input type="password" class="form-control" name="cust_new_password">
                                    </div>
                                    <div class="form-group">
                                        <label for=""><?php echo LANG_VALUE_101; ?> *</label>
                                        <input type="password" class="form-control" name="cust_re_password">
                                    </div>
                                    <div class="form-group">
                                        <label for=""></label>
                                        <input type="submit" class="btn btn-primary" value="<?php echo LANG_VALUE_149; ?>" name="form1">
                                    </div>
                                </div>
                            </div>                        
                        </form>
                    <?php endif; ?>
                    
                </div>                
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>