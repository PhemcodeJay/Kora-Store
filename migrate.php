<?php
$host = getenv('PGHOST');
$port = getenv('PGPORT');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');

$dsn = "pgsql:host=$host;port=$port;dbname=$db";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Connected to PostgreSQL successfully.\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

$tables = [
    "tbl_color" => "CREATE TABLE tbl_color (
        color_id SERIAL PRIMARY KEY,
        color_name VARCHAR(255) NOT NULL
    )",
    "tbl_country" => "CREATE TABLE tbl_country (
        country_id SERIAL PRIMARY KEY,
        country_name VARCHAR(100) NOT NULL DEFAULT ''
    )",
    "tbl_customer" => "CREATE TABLE tbl_customer (
        cust_id SERIAL PRIMARY KEY,
        cust_name VARCHAR(100) NOT NULL,
        cust_cname VARCHAR(100) NOT NULL,
        cust_email VARCHAR(100) NOT NULL,
        cust_phone VARCHAR(50) NOT NULL,
        cust_country INTEGER NOT NULL,
        cust_address TEXT NOT NULL,
        cust_city VARCHAR(100) NOT NULL,
        cust_state VARCHAR(100) NOT NULL,
        cust_zip VARCHAR(30) NOT NULL,
        cust_b_name VARCHAR(100) NOT NULL,
        cust_b_cname VARCHAR(100) NOT NULL,
        cust_b_phone VARCHAR(50) NOT NULL,
        cust_b_country INTEGER NOT NULL,
        cust_b_address TEXT NOT NULL,
        cust_b_city VARCHAR(100) NOT NULL,
        cust_b_state VARCHAR(100) NOT NULL,
        cust_b_zip VARCHAR(30) NOT NULL,
        cust_s_name VARCHAR(100) NOT NULL,
        cust_s_cname VARCHAR(100) NOT NULL,
        cust_s_phone VARCHAR(50) NOT NULL,
        cust_s_country INTEGER NOT NULL,
        cust_s_address TEXT NOT NULL,
        cust_s_city VARCHAR(100) NOT NULL,
        cust_s_state VARCHAR(100) NOT NULL,
        cust_s_zip VARCHAR(30) NOT NULL,
        cust_password VARCHAR(100) NOT NULL,
        cust_token VARCHAR(255) NOT NULL,
        cust_datetime VARCHAR(100) NOT NULL,
        cust_timestamp VARCHAR(100) NOT NULL,
        cust_status INTEGER NOT NULL
    )",
    "tbl_customer_message" => "CREATE TABLE tbl_customer_message (
        customer_message_id SERIAL PRIMARY KEY,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        order_detail TEXT NOT NULL,
        cust_id INTEGER NOT NULL
    )",
    "tbl_digital_products" => "CREATE TABLE tbl_digital_products (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        price DECIMAL(10,2) NOT NULL,
        file_url VARCHAR(255) NOT NULL,
        is_digital SMALLINT DEFAULT 1,
        product_image VARCHAR(255) DEFAULT NULL
    )",
    "tbl_end_category" => "CREATE TABLE tbl_end_category (
        ecat_id SERIAL PRIMARY KEY,
        ecat_name VARCHAR(255) NOT NULL,
        mcat_id INTEGER NOT NULL
    )",
    "tbl_faq" => "CREATE TABLE tbl_faq (
        faq_id SERIAL PRIMARY KEY,
        faq_title VARCHAR(255) NOT NULL,
        faq_content TEXT NOT NULL
    )",
    "tbl_inventory" => "CREATE TABLE tbl_inventory (
        inventory_id SERIAL PRIMARY KEY,
        p_id INTEGER NOT NULL,
        products_id INTEGER NOT NULL,
        sku VARCHAR(100) NOT NULL,
        stock INTEGER NOT NULL,
        cost_price DECIMAL(10,2) NOT NULL,
        retail_price DECIMAL(10,2) NOT NULL,
        source VARCHAR(50) NOT NULL
    )",
    "tbl_language" => "CREATE TABLE tbl_language (
        lang_id SERIAL PRIMARY KEY,
        lang_name VARCHAR(255) NOT NULL,
        lang_value TEXT NOT NULL
    )",
    "tbl_mid_category" => "CREATE TABLE tbl_mid_category (
        mcat_id SERIAL PRIMARY KEY,
        mcat_name VARCHAR(255) NOT NULL,
        tcat_id INTEGER NOT NULL
    )",
    "tbl_order" => "CREATE TABLE tbl_order (
        id SERIAL PRIMARY KEY,
        product_id INTEGER DEFAULT NULL,
        product_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        size VARCHAR(100) DEFAULT NULL,
        color VARCHAR(100) DEFAULT NULL,
        quantity INTEGER NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        product_type VARCHAR(50) DEFAULT NULL,
        payment_id VARCHAR(255) NOT NULL,
        cj_order_id VARCHAR(100) DEFAULT NULL,
        tracking_number VARCHAR(100) DEFAULT NULL,
        carrier VARCHAR(100) DEFAULT NULL,
        order_status VARCHAR(50) DEFAULT 'Pending',
        shipping_status VARCHAR(50) DEFAULT 'Processing',
        estimated_delivery DATE DEFAULT NULL,
        download_code VARCHAR(32) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )",
    "tbl_page" => "CREATE TABLE tbl_page (
        id SERIAL PRIMARY KEY,
        about_title VARCHAR(255) NOT NULL,
        about_content TEXT NOT NULL,
        about_banner VARCHAR(255) NOT NULL,
        about_meta_title VARCHAR(255) NOT NULL,
        about_meta_keyword TEXT NOT NULL,
        about_meta_description TEXT NOT NULL,
        faq_title VARCHAR(255) NOT NULL,
        faq_banner VARCHAR(255) NOT NULL,
        faq_meta_title VARCHAR(255) NOT NULL,
        faq_meta_keyword TEXT NOT NULL,
        faq_meta_description TEXT NOT NULL,
        blog_title VARCHAR(255) NOT NULL,
        blog_banner VARCHAR(255) NOT NULL,
        blog_meta_title VARCHAR(255) NOT NULL,
        blog_meta_keyword TEXT NOT NULL,
        blog_meta_description TEXT NOT NULL,
        contact_title VARCHAR(255) NOT NULL,
        contact_banner VARCHAR(255) NOT NULL,
        contact_meta_title VARCHAR(255) NOT NULL,
        contact_meta_keyword TEXT NOT NULL,
        contact_meta_description TEXT NOT NULL,
        pgallery_title VARCHAR(255) NOT NULL,
        pgallery_banner VARCHAR(255) NOT NULL,
        pgallery_meta_title VARCHAR(255) NOT NULL,
        pgallery_meta_keyword TEXT NOT NULL,
        pgallery_meta_description TEXT NOT NULL,
        vgallery_title VARCHAR(255) NOT NULL,
        vgallery_banner VARCHAR(255) NOT NULL,
        vgallery_meta_title VARCHAR(255) NOT NULL,
        vgallery_meta_keyword TEXT NOT NULL,
        vgallery_meta_description TEXT NOT NULL
    )",
    "tbl_payment" => "CREATE TABLE tbl_payment (
        id SERIAL PRIMARY KEY,
        customer_id INTEGER NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        payment_date VARCHAR(50) NOT NULL,
        txnid VARCHAR(255) NOT NULL,
        paid_amount INTEGER NOT NULL,
        card_number VARCHAR(50) NOT NULL,
        card_cvv VARCHAR(10) NOT NULL,
        card_month VARCHAR(10) NOT NULL,
        card_year VARCHAR(10) NOT NULL,
        bank_transaction_info TEXT NOT NULL,
        payment_method VARCHAR(20) NOT NULL,
        payment_status VARCHAR(25) NOT NULL,
        shipping_status VARCHAR(20) NOT NULL,
        payment_id VARCHAR(255) NOT NULL,
        payment_proof VARCHAR(255) NOT NULL
    )",
    "tbl_photo" => "CREATE TABLE tbl_photo (
        id SERIAL PRIMARY KEY,
        caption VARCHAR(255) NOT NULL,
        photo VARCHAR(255) NOT NULL
    )",
    "tbl_post" => "CREATE TABLE tbl_post (
        post_id SERIAL PRIMARY KEY,
        post_title VARCHAR(255) NOT NULL,
        post_slug VARCHAR(255) NOT NULL,
        post_content TEXT NOT NULL,
        post_date VARCHAR(255) NOT NULL,
        photo VARCHAR(255) NOT NULL,
        category_id INTEGER NOT NULL,
        total_view INTEGER NOT NULL,
        meta_title VARCHAR(255) NOT NULL,
        meta_keyword TEXT NOT NULL,
        meta_description TEXT NOT NULL
    )",
    "tbl_product" => "CREATE TABLE tbl_product (
        p_id SERIAL PRIMARY KEY,
        p_name VARCHAR(255) NOT NULL,
        p_old_price DECIMAL(10,2) NOT NULL,
        p_current_price DECIMAL(10,2) NOT NULL,
        p_qty INTEGER NOT NULL,
        p_featured_photo VARCHAR(255) NOT NULL,
        p_description TEXT NOT NULL,
        p_short_description TEXT NOT NULL,
        p_feature TEXT NOT NULL,
        p_condition TEXT NOT NULL,
        p_return_policy TEXT NOT NULL,
        p_total_view INTEGER NOT NULL,
        p_is_featured INTEGER NOT NULL,
        p_is_active INTEGER NOT NULL,
        ecat_id INTEGER NOT NULL,
        product_type VARCHAR(50) DEFAULT NULL,
        cj_product_id VARCHAR(100) DEFAULT NULL,
        source_url VARCHAR(255) DEFAULT NULL,
        vendor_name VARCHAR(50) DEFAULT NULL,
        inventory_sync SMALLINT DEFAULT 1
    )",
    "tbl_product_color" => "CREATE TABLE tbl_product_color (
        id SERIAL PRIMARY KEY,
        color_id INTEGER NOT NULL,
        p_id INTEGER NOT NULL
    )",
    "tbl_product_photo" => "CREATE TABLE tbl_product_photo (
        pp_id SERIAL PRIMARY KEY,
        photo VARCHAR(255) NOT NULL,
        p_id INTEGER NOT NULL
    )",
    "tbl_product_size" => "CREATE TABLE tbl_product_size (
        id SERIAL PRIMARY KEY,
        size_id INTEGER NOT NULL,
        p_id INTEGER NOT NULL
    )",
    "tbl_rating" => "CREATE TABLE tbl_rating (
        rt_id SERIAL PRIMARY KEY,
        p_id INTEGER NOT NULL,
        cust_id INTEGER NOT NULL,
        comment TEXT NOT NULL,
        rating INTEGER NOT NULL
    )",
    "tbl_service" => "CREATE TABLE tbl_service (
        id SERIAL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        photo VARCHAR(255) NOT NULL
    )",
    "tbl_settings" => "CREATE TABLE tbl_settings (
        id SERIAL PRIMARY KEY,
        logo TEXT NOT NULL,
        favicon TEXT NOT NULL,
        footer_about TEXT NOT NULL,
        footer_copyright TEXT NOT NULL,
        contact_address TEXT NOT NULL,
        contact_email VARCHAR(255) NOT NULL,
        contact_phone VARCHAR(255) NOT NULL,
        contact_fax VARCHAR(255) NOT NULL,
        contact_map_iframe TEXT NOT NULL,
        receive_email VARCHAR(255) NOT NULL,
        receive_email_subject VARCHAR(255) NOT NULL,
        receive_email_thank_you_message TEXT NOT NULL,
        forget_password_message TEXT NOT NULL,
        total_recent_post_footer INTEGER NOT NULL,
        total_popular_post_footer INTEGER NOT NULL,
        total_recent_post_sidebar INTEGER NOT NULL,
        total_popular_post_sidebar INTEGER NOT NULL,
        total_featured_product_home INTEGER NOT NULL,
        total_latest_product_home INTEGER NOT NULL,
        total_popular_product_home INTEGER NOT NULL,
        meta_title_home TEXT NOT NULL,
        meta_keyword_home TEXT NOT NULL,
        meta_description_home TEXT NOT NULL,
        banner_login TEXT NOT NULL,
        banner_registration TEXT NOT NULL,
        banner_forget_password TEXT NOT NULL,
        banner_reset_password TEXT NOT NULL,
        banner_search TEXT NOT NULL,
        banner_cart TEXT NOT NULL,
        banner_checkout TEXT NOT NULL,
        banner_product_category TEXT NOT NULL,
        banner_blog TEXT NOT NULL,
        cta_title VARCHAR(255) NOT NULL,
        cta_content TEXT NOT NULL,
        cta_read_more_text VARCHAR(255) NOT NULL,
        cta_read_more_url VARCHAR(255) NOT NULL,
        cta_photo TEXT NOT NULL,
        featured_product_title VARCHAR(255) NOT NULL,
        featured_product_subtitle VARCHAR(255) NOT NULL,
        latest_product_title VARCHAR(255) NOT NULL,
        latest_product_subtitle VARCHAR(255) NOT NULL,
        popular_product_title VARCHAR(255) NOT NULL,
        popular_product_subtitle VARCHAR(255) NOT NULL,
        testimonial_title VARCHAR(255) NOT NULL,
        testimonial_subtitle VARCHAR(255) NOT NULL,
        testimonial_photo TEXT NOT NULL,
        blog_title VARCHAR(255) NOT NULL,
        blog_subtitle VARCHAR(255) NOT NULL,
        newsletter_text TEXT NOT NULL,
        paypal_email VARCHAR(255) NOT NULL,
        stripe_public_key TEXT NOT NULL,
        stripe_secret_key TEXT NOT NULL,
        bank_detail TEXT NOT NULL,
        before_head TEXT NOT NULL,
        after_body TEXT NOT NULL,
        before_body TEXT NOT NULL,
        home_service_on_off INTEGER NOT NULL,
        home_welcome_on_off INTEGER NOT NULL,
        home_featured_product_on_off INTEGER NOT NULL,
        home_latest_product_on_off INTEGER NOT NULL,
        home_popular_product_on_off INTEGER NOT NULL,
        home_testimonial_on_off INTEGER NOT NULL,
        home_blog_on_off INTEGER NOT NULL,
        newsletter_on_off INTEGER NOT NULL,
        ads_above_welcome_on_off INTEGER NOT NULL,
        ads_above_featured_product_on_off INTEGER NOT NULL,
        ads_above_latest_product_on_off INTEGER NOT NULL,
        ads_above_popular_product_on_off INTEGER NOT NULL,
        ads_above_testimonial_on_off INTEGER NOT NULL,
        ads_category_sidebar_on_off INTEGER NOT NULL
    )",
    "tbl_shipping_cost" => "CREATE TABLE tbl_shipping_cost (
        shipping_cost_id SERIAL PRIMARY KEY,
        country_id INTEGER NOT NULL,
        amount VARCHAR(20) NOT NULL
    )",
    "tbl_shipping_cost_all" => "CREATE TABLE tbl_shipping_cost_all (
        sca_id INTEGER NOT NULL,
        amount VARCHAR(20) NOT NULL
    )",
    "tbl_shipping_info" => "CREATE TABLE tbl_shipping_info (
        shipping_id SERIAL PRIMARY KEY,
        order_id INTEGER NOT NULL,
        tracking_number VARCHAR(100) NOT NULL,
        carrier VARCHAR(100) NOT NULL,
        status VARCHAR(50) DEFAULT 'In Transit',
        estimated_arrival DATE DEFAULT NULL
    )",
    "tbl_size" => "CREATE TABLE tbl_size (
        size_id INTEGER NOT NULL,
        size_name VARCHAR(255) NOT NULL
    )",
    "tbl_slider" => "CREATE TABLE tbl_slider (
        id SERIAL PRIMARY KEY,
        photo VARCHAR(255) NOT NULL,
        heading VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        button_text VARCHAR(255) NOT NULL,
        button_url VARCHAR(255) NOT NULL,
        position VARCHAR(255) NOT NULL
    )",
    "tbl_social" => "CREATE TABLE tbl_social (
        social_id SERIAL PRIMARY KEY,
        social_name VARCHAR(30) NOT NULL,
        social_url VARCHAR(255) NOT NULL,
        social_icon VARCHAR(30) NOT NULL
    )",
    "tbl_subscriber" => "CREATE TABLE tbl_subscriber (
        subs_id SERIAL PRIMARY KEY,
        subs_email VARCHAR(255) NOT NULL,
        subs_date VARCHAR(100) NOT NULL,
        subs_date_time VARCHAR(100) NOT NULL,
        subs_hash VARCHAR(255) NOT NULL,
        subs_active INTEGER NOT NULL
    )",
    "tbl_top_category" => "CREATE TABLE tbl_top_category (
        tcat_id SERIAL PRIMARY KEY,
        tcat_name VARCHAR(255) NOT NULL,
        show_on_menu INTEGER NOT NULL
    )",
    "tbl_user" => "CREATE TABLE tbl_user (
        id SERIAL PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        photo VARCHAR(255) NOT NULL,
        role VARCHAR(30) NOT NULL,
        status VARCHAR(10) NOT NULL
    )",
    "tbl_video" => "CREATE TABLE tbl_video (
        id SERIAL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        iframe_code TEXT NOT NULL
    )",
];

echo "Step 1: Creating tables...\n";
foreach ($tables as $name => $sql) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS $name CASCADE");
        $pdo->exec($sql);
        echo "  Created: $name\n";
    } catch (PDOException $e) {
        echo "  ERROR creating $name: " . $e->getMessage() . "\n";
    }
}

echo "\nStep 2: Inserting data from MySQL dump...\n";
$lines = file(dirname(__FILE__) . '/DATABASE FILE/cybertrendhub.sql');
$insertBuffer = '';
$inInsert = false;

foreach ($lines as $line) {
    if (preg_match('/^INSERT INTO `/', $line)) {
        $inInsert = true;
        $insertBuffer = $line;
    } elseif ($inInsert) {
        $insertBuffer .= $line;
    }

    if ($inInsert && preg_match('/;\s*$/', rtrim($line))) {
        $inInsert = false;
        $cleaned = str_replace('`', '', $insertBuffer);
        $cleaned = preg_replace("/\\\\'/", "''", $cleaned);
        $cleaned = str_replace('\\"', '"', $cleaned);

        preg_match('/INSERT INTO (\S+)/', $cleaned, $tblMatch);
        $tblName = $tblMatch[1] ?? 'unknown';

        try {
            $pdo->exec($cleaned);
            echo "  Inserted data into: $tblName\n";
        } catch (PDOException $e) {
            echo "  ERROR inserting into $tblName: " . substr($e->getMessage(), 0, 200) . "\n";
        }
        $insertBuffer = '';
    }
}

echo "\nStep 3: Setting sequence values...\n";
$sequences = [
    ['tbl_color', 'color_id', 30],
    ['tbl_country', 'country_id', 246],
    ['tbl_customer', 'cust_id', 12],
    ['tbl_customer_message', 'customer_message_id', 1],
    ['tbl_digital_products', 'id', 3],
    ['tbl_end_category', 'ecat_id', 80],
    ['tbl_faq', 'faq_id', 6],
    ['tbl_inventory', 'inventory_id', 1],
    ['tbl_language', 'lang_id', 164],
    ['tbl_mid_category', 'mcat_id', 18],
    ['tbl_order', 'id', 6],
    ['tbl_page', 'id', 2],
    ['tbl_payment', 'id', 56],
    ['tbl_photo', 'id', 7],
    ['tbl_post', 'post_id', 12],
    ['tbl_product', 'p_id', 103],
    ['tbl_product_color', 'id', 268],
    ['tbl_product_photo', 'pp_id', 133],
    ['tbl_product_size', 'id', 448],
    ['tbl_rating', 'rt_id', 1],
    ['tbl_service', 'id', 11],
    ['tbl_settings', 'id', 2],
    ['tbl_shipping_cost', 'shipping_cost_id', 5],
    ['tbl_shipping_info', 'shipping_id', 1],
    ['tbl_slider', 'id', 4],
    ['tbl_social', 'social_id', 17],
    ['tbl_subscriber', 'subs_id', 7],
    ['tbl_top_category', 'tcat_id', 6],
    ['tbl_user', 'id', 3],
    ['tbl_video', 'id', 5],
];

foreach ($sequences as [$table, $col, $nextVal]) {
    try {
        $seqName = "{$table}_{$col}_seq";
        $pdo->exec("SELECT setval('{$seqName}', {$nextVal}, false)");
        echo "  Set sequence {$seqName} to {$nextVal}\n";
    } catch (PDOException $e) {
        echo "  WARN setting sequence for {$table}.{$col}: " . $e->getMessage() . "\n";
    }
}

echo "\nStep 4: Verification...\n";
$result = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
$dbTables = $result->fetchAll(PDO::FETCH_COLUMN);
echo "  Tables in database (" . count($dbTables) . "):\n";
foreach ($dbTables as $t) {
    $countResult = $pdo->query("SELECT COUNT(*) FROM {$t}");
    $count = $countResult->fetchColumn();
    echo "    {$t}: {$count} rows\n";
}

echo "\nMigration complete!\n";
