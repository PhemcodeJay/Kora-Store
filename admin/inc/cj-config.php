<?php
/**
 * CJ Dropshipping API configuration.
 *
 * Enable/disable the entire CJ integration from here.
 * The api_password is the API password generated inside your CJ account
 * (developers.cjdropshipping.com -> Account -> API Settings).
 */
$cj_config = [
    // ---- API credentials ----
    'email'             => 'phemcodejay@gmail.com',
    'api_password'      => '42667d2d1d1a4dd7bb1f563b8eb7fc8c',
    'base_url'          => 'https://developers.cjdropshipping.com/api2.0/v1',

    // ---- Connection status (read-only, updated by the import page) ----
    // 'api_connected'    => true/false  — set by cj-import.php at runtime
    // 'api_last_test'    => timestamp   — last time the API was verified

    // ---- Enable/disable the whole integration ----
    // When false: the CJ import page shows a 'disabled' notice and blocks
    // imports. The sidebar link still appears but the page is locked.
    'enabled'           => true,

    // ---- Import defaults (can be overridden in the admin import screen) ----
    'price_markup'      => 2.5,   // retail price = CJ cost * markup
    'old_price_factor'  => 1.25,  // p_old_price = retail * factor (shows discount)
    'default_qty'       => 50,    // used when CJ reports no stock
    'max_photos'        => 5,     // extra gallery photos stored per product
    'max_auto_import'   => 20,    // upper limit for the auto-import button
    'list_page_size'    => 12,    // products shown per page in the browser
    'top_category'      => 'Dropshipping', // fallback storefront category for imports
    // Make each successful import immediately visible to customers.
    'feature_new_products' => true,
    // CJ supplies full category paths; show their top-level categories in the menu.
    'show_categories_on_menu' => true,

    // ---- Auto-sync (background / scheduled imports) ----
    // Set to false to disable automatic imports via cj-autosync.php
    'auto_sync_enabled' => true,
    // Minimum hours between auto-sync runs (to avoid re-importing the same products)
    'auto_sync_interval_hours' => 24,
    // How many products to import per auto-sync run
    'auto_sync_count'   => 5,
    // CJ sort order for auto-sync: 'createTime' (newest) recommended
    'auto_sync_sort_by' => 'createTime',
    'auto_sync_sort_order' => 'desc',
];

/*
 * Live re-read of the 'enabled' master switch.
 *
 * This flag is toggled at runtime from admin/cj-import.php, which rewrites the
 * line above. Because OPcache may keep serving the previous version of this
 * include (opcache.revalidate_freq defaults to 2 seconds), the in-memory value
 * can lag one request behind the file - the admin screen would then claim the
 * integration is enabled while the file says otherwise. Reading the flag back
 * from this file with runtime I/O (never opcode-cached) keeps the page, the
 * sidebar badge and cj-autosync.php all in agreement with the file on disk.
 */
$__cj_self = (string)@file_get_contents(__FILE__);
if (preg_match("~^\s*'enabled'\s*=>\s*(true|false)~m", $__cj_self, $__cj_m)) {
    $cj_config['enabled'] = ($__cj_m[1] === 'true');
}
unset($__cj_self, $__cj_m);