<?php require_once('header.php'); ?>
<!-- fetching row banner login -->
<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_login = $row['banner_login'];
}
?>
<!-- login form -->
<?php
if(isset($_POST['form1'])) {
        
    if(empty($_POST['cust_email']) || empty($_POST['cust_password'])) {
        $error_message = LANG_VALUE_132.'<br>';
    } else {
        
        $cust_email = trim((string)$_POST['cust_email']);
        $cust_password = (string)$_POST['cust_password']; // never strip_tags a password

        $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email=?");
        $statement->execute(array($cust_email));
        $total = $statement->rowCount();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        if($total==0) {
            $error_message .= LANG_VALUE_134.'<br>';
        } else {
            $row = $result[0];
            $row_password = $row['cust_password'];
            $cust_status  = (int)$row['cust_status'];

            $password_ok = false;
            // a$ / b$ / x$ / y$ (bcrypt) or $argon2 - MD5 hashes are hex only,
            // so they can never start with a '$'.
            $is_modern_hash = (strpos($row_password, '$2') === 0 || strpos($row_password, '$argon') === 0);

            if ($is_modern_hash) {
                // password_hash() format
                $password_ok = password_verify($cust_password, $row_password);
            } else {
                // Legacy MD5 format (existing customers)
                $password_ok = ($row_password === md5($cust_password));
            }

            // Transparent upgrade: rehash legacy MD5 into a modern hash after a
            // successful login so the stored format improves over time.
            if ($password_ok && !$is_modern_hash) {
                $up = $pdo->prepare("UPDATE tbl_customer SET cust_password=? WHERE cust_id=?");
                $up->execute(array(password_hash($cust_password, PASSWORD_DEFAULT), $row['cust_id']));
            }

            if(!$password_ok) {
                $error_message .= LANG_VALUE_132.'<br>';
            } else {
                if($cust_status != 1) {
                    $error_message .= LANG_VALUE_148.'<br>';
                } else {
                    $_SESSION['customer'] = $row;
                    header("location: ".BASE_URL."dashboard.php");
                    exit;
                }
            }
        }
    }
}
?>

<div class="page-banner" style="background-color:#444;background-image: url(assets/uploads/<?php echo $banner_login; ?>);">
    <div class="inner">
        <h1><?php echo LANG_VALUE_10; ?></h1>
    </div>
</div>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="user-content">

                    
                    <form action="" method="post">
                        <?php $csrf->echoInputField(); ?>                  
                        <div class="row">
                            <div class="col-md-4"></div>
                            <div class="col-md-4">
                                <?php
                                if($error_message != '') {
                                    echo "<div class='error' style='padding: 10px;background:#f1f1f1;margin-bottom:20px;'>".$error_message."</div>";
                                }
                                if($success_message != '') {
                                    echo "<div class='success' style='padding: 10px;background:#f1f1f1;margin-bottom:20px;'>".$success_message."</div>";
                                }
                                ?>
                                <div class="form-group">
                                    <label for=""><?php echo LANG_VALUE_94; ?> *</label>
                                    <input type="email" class="form-control" name="cust_email">
                                </div>
                                <div class="form-group">
                                    <label for=""><?php echo LANG_VALUE_96; ?> *</label>
                                    <input type="password" class="form-control" name="cust_password">
                                </div>
                                <div class="form-group">
                                    <label for=""></label>
                                    <input type="submit" class="btn btn-success" value="<?php echo LANG_VALUE_4; ?>" name="form1">
                                </div>
                                <a href="forget-password.php" style="color:#e4144d;"><?php echo LANG_VALUE_97; ?>?</a>
                            </div>
                        </div>                        
                    </form>
                </div>                
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>