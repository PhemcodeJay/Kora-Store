<?php
/**
 * Shared CJ Dropshipping import engine.
 * Used by admin/cj-import.php (web UI) and cj-autosync.php (CLI / scheduled task).
 * Requires: cj-api.php (client + config), admin/inc/functions.php (get_next_id).
 */

// get_next_id() lives in admin/inc/functions.php. CLI entry points that only
// load the CJ stack (e.g. cj-autosync.php) must still be able to import.
if (!function_exists('get_next_id')) {
    require_once __DIR__ . '/functions.php';
}

/** Decode CJ fields that arrive JSON-encoded as arrays (multi-language names, image lists). */
function cj_decode_first($value)
{
    if (is_string($value) && strlen($value) > 1 && $value[0] === '[') {
        $dec = json_decode($value, true);
        if (is_array($dec)) {
            foreach ($dec as $item) {
                if (is_string($item) && trim($item) !== '') return $item;
            }
            return '';
        }
    }
    return (string)$value;
}

/** Download a remote CJ image into assets/uploads, return local filename (or empty on failure).
 *  Gallery images use the 'product_photos' subdirectory because product.php
 *  renders tbl_product_photo entries from assets/uploads/product_photos/.
 */
function cj_download_image($url, $subdirectory = '')
{
    $url = trim((string)$url);
    if ($url === '' || !preg_match('~^https?://~i', $url)) return '';

    $ext = strtolower(pathinfo((string)parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) $ext = 'jpg';
    if ($ext === 'jpeg') $ext = 'jpg';

    $name = 'cj-' . md5($url) . '.' . $ext;
    $uploadRoot = dirname(__DIR__, 2) . '/assets/uploads';
    $uploadDir = $uploadRoot . ($subdirectory !== '' ? '/' . trim($subdirectory, '/') : '');
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return '';
    }

    $dest = $uploadDir . '/' . $name;
    if (is_file($dest) && filesize($dest) > 0) return $name;

    $bin = @file_get_contents($url);
    if ($bin === false) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => 'Mozilla/5.0']);
        $bin = curl_exec($ch);
        curl_close($ch);
    }
    if (!is_string($bin) || $bin === '') return '';

    return @file_put_contents($dest, $bin) === false ? '' : $name;
}

/** Find or create top/mid/end category chain from a CJ category path; return ecat_id. */
function cj_ensure_category($pdo, $name)
{
    global $cj_config;
    $name = trim(strip_tags((string)$name));
    if ($name === '') $name = 'General';

    // CJ returns full paths like "Home, Garden & Furniture > Home Storage > Home Office Storage"
    $parts = array_values(array_filter(array_map('trim', preg_split('/[>\\/]+/', $name))));
    if (count($parts) >= 3) {
        list($topName, $midName) = $parts;
        $endName = end($parts);
    } elseif (count($parts) === 2) {
        list($topName, $midName) = $parts;
        $endName = $parts[1];
    } else {
        $topName = $cj_config['top_category'];
        $midName = $parts ? $parts[0] : 'General';
        $endName = $midName;
    }
    $topName = mb_substr($topName, 0, 250);
    $midName = mb_substr($midName, 0, 250);
    $endName = mb_substr($endName, 0, 250);

    $showOnMenu = !empty($cj_config['show_categories_on_menu']) ? 1 : 0;
    $st = $pdo->prepare('SELECT tcat_id FROM tbl_top_category WHERE tcat_name = ?');
    $st->execute([$topName]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row) { $tcat_id = $row['tcat_id']; }
    else {
        $pdo->prepare('INSERT INTO tbl_top_category (tcat_name, show_on_menu) VALUES (?, ?)')
            ->execute([$topName, $showOnMenu]);
        $tcat_id = (int)$pdo->lastInsertId();
    }

    $st = $pdo->prepare('SELECT mcat_id FROM tbl_mid_category WHERE mcat_name = ? AND tcat_id = ?');
    $st->execute([$midName, $tcat_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row) { $mcat_id = $row['mcat_id']; }
    else {
        $pdo->prepare('INSERT INTO tbl_mid_category (mcat_name, tcat_id) VALUES (?, ?)')
            ->execute([$midName, $tcat_id]);
        $mcat_id = (int)$pdo->lastInsertId();
    }

    $st = $pdo->prepare('SELECT ecat_id FROM tbl_end_category WHERE ecat_name = ? AND mcat_id = ?');
    $st->execute([$endName, $mcat_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row) { return (int)$row['ecat_id']; }
    $pdo->prepare('INSERT INTO tbl_end_category (ecat_name, mcat_id) VALUES (?, ?)')
        ->execute([$endName, $mcat_id]);
    return (int)$pdo->lastInsertId();
}

/** Import one CJ product by pid. Returns ['status', 'msg'] */
function cj_import_product($pdo, CJApiClient $cj, $pid, $opts)
{
    // The single-import button passes [] for $opts. Merge config defaults so a
    // missing 'markup' can never zero out the retail price ($cost * null = 0).
    global $cj_config;
    $opts += [
        'markup'     => $cj_config['price_markup'] ?? 2.5,
        'old_factor' => $cj_config['old_price_factor'] ?? 1.25,
        'qty'        => $cj_config['default_qty'] ?? 50,
        'max_photos' => $cj_config['max_photos'] ?? 5,
        'featured'   => $cj_config['feature_new_products'] ?? true,
    ];
    // duplicate check
    $st = $pdo->prepare('SELECT p_id FROM tbl_product WHERE cj_product_id = ?');
    $st->execute([(string)$pid]);
    if ($st->fetchColumn()) {
        return ['skipped', 'already in store'];
    }

    $d = $cj->getProduct($pid);

    $name = trim(strip_tags(cj_decode_first(CJApiClient::pick($d, ['productNameEn','productName','name'], ''))));
    if ($name === '') {
        $name = trim(strip_tags(cj_decode_first(CJApiClient::pick($d, ['productName','name'], ''))));
    }
    $name = mb_substr($name, 0, 250);
    if ($name === '') return ['failed', 'no product name'];

    $variants = CJApiClient::pick($d, ['variantList','variants'], []);
    $cost = 0.0; $stock = 0;
    if (is_array($variants)) {
        foreach ($variants as $v) {
            $cost = max($cost, (float)CJApiClient::pick($v, ['sellPrice','variantSellPrice','variantSugSellPrice','price'], 0));
            $stock += (int)CJApiClient::pick($v, ['stock','variantStock','inventoryNum','combineNum','qty'], 0);
        }
    }
    if ($cost <= 0) $cost = (float)CJApiClient::pick($d, ['sellPrice','price'], 0);
    if ($cost <= 0) return ['failed', 'no price returned by CJ'];

    $retail = round($cost * $opts['markup'], 2);
    $old    = round($retail * $opts['old_factor'], 2);
    $qty    = max(1, min(9999, $stock ?: $opts['qty']));

    $ecat_id = cj_ensure_category($pdo, cj_decode_first(CJApiClient::pick($d, ['categoryName','category','c_category_name'], 'General')));

    $featuredUrl = cj_decode_first(CJApiClient::pick($d, ['bigImage','imageUrl','productImage','image_url','image']));
    if (trim($featuredUrl) === '') return ['failed', 'no image returned by CJ'];
    $featured = cj_download_image($featuredUrl);
    if ($featured === '') return ['failed', 'CJ image could not be downloaded'];

    $desc  = (string)CJApiClient::pick($d, ['description','descriptionHtml','detailHtml'], '');
    $short = mb_substr(trim(strip_tags($desc)), 0, 190);

    $p_id = get_next_id($pdo, 'tbl_product');
    $pdo->prepare('INSERT INTO tbl_product
        (p_id, p_name, p_old_price, p_current_price, p_qty, p_featured_photo,
         p_description, p_short_description, p_feature, p_condition, p_return_policy,
         p_total_view, p_is_featured, p_is_active, ecat_id,
         product_type, cj_product_id, source_url, vendor_name, inventory_sync)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([
            $p_id, $name, $old, $retail, $qty, $featured,
            $desc, $short,
            'Imported from CJ Dropshipping.', 'New', '7 days money-back guarantee.',
            0, $opts['featured'] ? 1 : 0, 1, $ecat_id,
            NULL, (string)$pid,
            (string)CJApiClient::pick($d, ['productNameUrl','productUrl','sourceUrl']),
            'CJ', 1,
        ]);

    // extra gallery photos (productImage is a JSON list of URLs)
    $extraUrls = [];
    $rawImages = CJApiClient::pick($d, ['productImage','productImageSet','imageList','productImageList','images'], []);
    if (is_string($rawImages) && $rawImages !== '' && $rawImages[0] === '[') {
        $dec = json_decode($rawImages, true);
        if (is_array($dec)) {
            foreach ($dec as $item) {
                if (is_string($item) && trim($item) !== '') $extraUrls[] = trim($item);
            }
        }
    } elseif (is_array($rawImages)) {
        foreach ($rawImages as $im) {
            $u = is_array($im) ? (string)CJApiClient::pick($im, ['url','normalUrl','big','path']) : trim((string)$im);
            if ($u !== '') $extraUrls[] = $u;
        }
    }
    $seen = 0;
    foreach (array_unique($extraUrls) as $u) {
        if ($u === '' || $u === trim((string)$featuredUrl)) continue;
        $localPhoto = cj_download_image($u, 'product_photos');
        if ($localPhoto === '') continue;
        $pdo->prepare('INSERT INTO tbl_product_photo (photo, p_id) VALUES (?, ?)')
            ->execute([$localPhoto, $p_id]);
        if (++$seen >= $opts['max_photos']) break;
    }

    // inventory row
    $sku = (string)CJApiClient::pick(is_array($variants) ? ($variants[0] ?? []) : [], ['vid','variantId','sku'], 'CJ-' . $pid);
    $pdo->prepare('INSERT INTO tbl_inventory (p_id, products_id, sku, stock, cost_price, retail_price, source)
        VALUES (?,?,?,?,?,?,?)')
        ->execute([$p_id, $p_id, $sku, $qty, $cost, $retail, 'CJ']);

    return ['ok', $name . ' (retail $' . number_format($retail, 2) . ')'];
}

/** Auto-import the newest N CJ products from the catalog (called by the UI's Auto Import button,
 * and re-used by cj-autosync.php for scheduled background sync).
 *
 * @param PDO         $pdo    admin PDO (sqlite)
 * @param CJApiClient $cj    authenticated CJ client
 * @param int         $limit max products to import per run
 * @return array[]           each element: ['status'=>'ok'|'skipped'|'failed', 'pid'=>..., 'msg'=>...]
 */
function cj_auto_import($pdo, CJApiClient $cj, $limit = 3)
{
    // Import defaults come from cj-config.php. Declared global explicitly:
    // functions do not see file-scope variables, and without this the
    // '?? 2.5' fallbacks would silently hide any markup change in the config.
    global $cj_config;

    // Default import options (same defaults used by the single-import button).
    $opts = [
        'markup'     => $cj_config['price_markup'] ?? 2.5,
        'old_factor' => $cj_config['old_price_factor'] ?? 1.25,
        'qty'        => $cj_config['default_qty'] ?? 50,
        'max_photos' => $cj_config['max_photos'] ?? 5,
        'featured'   => $cj_config['feature_new_products'] ?? true,
    ];

    $results = [];

    // CJ's /product/list returns the newest products first and ignores
    // sortBy/sortOrder entirely (verified against the live API), so no sort
    // parameters are sent - 'newest first' is already the default order.
    $list = $cj->getProducts([
        'page'     => 1,
        'pageSize' => $limit,
    ]);

    $products = [];
    if (is_array($list) && isset($list['list']) && is_array($list['list'])) {
        $products = $list['list'];
    } elseif (is_array($list) && isset($list['data']) && is_array($list['data'])) {
        $d = $list['data'];
        $products = $d['list'] ?? $d['items'] ?? $d['content'] ?? [];
    }
    // If the catalog call itself failed, surface that as a single failed entry.
    if (empty($products)) {
        $msg = is_array($list) && isset($list['message'])
            ? $list['message']
            : (is_array($list) ? 'empty catalog response' : 'catalog call returned non-array');
        return [['status' => 'failed', 'msg' => 'Catalog fetch: ' . $msg]];
    }

    foreach ($products as $p) {
        $pid  = $p['pid'] ?? $p['id'] ?? $p['productId'] ?? '';
        $name = CJApiClient::pick($p, ['productNameEn', 'productName', 'name'], '(no name)');
        if ($pid === '') continue;

        try {
            $res = cj_import_product($pdo, $cj, $pid, $opts);
            $results[] = [
                'status' => $res[0],
                'pid'    => $pid,
                'msg'    => $res[1],
            ];
        } catch (Throwable $e) {
            $results[] = [
                'status' => 'failed',
                'pid'    => $pid,
                'msg'    => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    return $results;
}

