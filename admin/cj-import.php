<?php
/* ============================================================
   admin/cj-import.php
   CJ Dropshipping product import page.
   - Browse / search the CJ catalog
   - Import a single product by pid
   - Auto-import the newest N products
   - Test API connection / enable-disable the integration
   ============================================================ */

// Buffer output so header() redirects keep working even if an
// included file emits stray bytes (e.g. a UTF-8 BOM).
ob_start();

// ---- bootstrap (order matters: DB/session/functions BEFORE any POST logic) ----
require_once __DIR__ . '/inc/config.php';        // $pdo + session
require_once __DIR__ . '/inc/functions.php';     // get_next_id() used by cj-helpers
require_once __DIR__ . '/inc/cj-api.php';        // CJApiClient + $cj_config
require_once __DIR__ . '/inc/cj-helpers.php';    // cj_import_product / cj_auto_import
require_once __DIR__ . '/inc/CSRF_Protect.php';
$csrf = new CSRF_Protect();

// Access control: only logged-in admins may use the CJ import page.
if (!isset($_SESSION['user'])) {
    header('location: login.php');
    exit;
}

$cj   = new CJApiClient();
$conf = &$GLOBALS['cj_config'];

$message     = '';
$messageType = 'info';
$api_error   = '';

// ---- POST/Redirect/GET flash ---------------------------------------------
// Results are shown after a redirect, so the message has to survive that
// redirect - otherwise the user never sees the outcome of an import.
function cj_redirect_with_flash($msg, $type, $anchor = '')
{
    $_SESSION['cj_flash'] = ['msg' => $msg, 'type' => $type];
    $keep = [];
    foreach (['keyword','sku','sortBy','sortOrder','categoryId'] as $k) {
        if (!empty($_GET[$k])) $keep[$k] = $_GET[$k];
    }
    $qs = $keep ? ('?' . http_build_query($keep)) : '';
    header('Location: cj-import.php' . $qs . $anchor);
    exit;
}

if (!empty($_SESSION['cj_flash'])) {
    $message     = $_SESSION['cj_flash']['msg'];
    $messageType = $_SESSION['cj_flash']['type'];
    unset($_SESSION['cj_flash']);
}

// Restore the last known connection state. It is kept in the session so the
// status badge stays accurate after the status-check redirect.
if (isset($_SESSION['cj_api_connected'])) {
    $conf['api_connected'] = (bool)$_SESSION['cj_api_connected'];
    $conf['api_last_test'] = (int)($_SESSION['cj_api_last_test'] ?? 0);
}

// ---- POST: import one product ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['do_import'])) {
    if (empty($_POST['_csrf']) || !is_string($_POST['_csrf']) || !$csrf->isTokenValid($_POST['_csrf'])) {
        $message = 'Invalid request.'; $messageType = 'danger';
    } elseif (empty($conf['enabled'])) {
        // A stale page could still POST here after the integration was turned
        // off, so the master switch is enforced server-side - not just by
        // hiding the button in the UI.
        $message = 'CJ import is disabled.'; $messageType = 'warning';
    } else {
        $pid = (string)($_POST['pid'] ?? '');
        if ($pid !== '') {
            $res = cj_import_product($pdo, $cj, $pid, []);
            $message = 'pid ' . htmlspecialchars($pid) . ': ' . htmlspecialchars($res[1]);
            $messageType = (in_array($res[0], ['ok', 'imported']))
                ? 'success' : ($res[0] === 'skipped' ? 'info' : 'danger');
        } else {
            $message = 'No product ID given.'; $messageType = 'danger';
        }
    }
    cj_redirect_with_flash($message, $messageType);
}

// ---- POST: auto-import N newest ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['do_auto_import'])) {
    if (empty($_POST['_csrf']) || !is_string($_POST['_csrf']) || !$csrf->isTokenValid($_POST['_csrf'])) {
        $message = 'Invalid request.'; $messageType = 'danger';
    } elseif (empty($conf['enabled'])) {
        $message = 'CJ import is disabled.'; $messageType = 'warning';
    } else {
        $limit  = max(1, min((int)$conf['max_auto_import'], (int)($_POST['auto_count'] ?? 3)));
        $results = cj_auto_import($pdo, $cj, $limit);
        $ok   = count(array_filter($results, fn($r) => $r['status'] === 'ok'));
        $skip = count(array_filter($results, fn($r) => $r['status'] === 'skipped'));
        $fail = count(array_filter($results, fn($r) => $r['status'] === 'failed'));
        $parts = [];
        if ($ok)   $parts[] = '<b>' . $ok . ' imported</b>';
        if ($skip) $parts[] = $skip . ' skipped (already in store)';
        if ($fail) $parts[] = '<span class="text-danger">' . $fail . ' failed</span>';
        $message = implode(' &middot; ', $parts) . ' &mdash; details:';
        $message .= '<ul class="mt-2 list-unstyled small">'
                  . implode('', array_map(fn($r) => '<li>' . htmlspecialchars($r['msg']) . '</li>', $results))
                  . '</ul>';
        $messageType = $fail > 0 ? 'warning' : 'success';
    }
    // Redirect so that a browser refresh cannot re-trigger the import.
    cj_redirect_with_flash($message, $messageType, '#autoimport');
}

// ---- POST: toggle enable/disable ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['toggle_enable'])) {
    if (!empty($_POST['_csrf']) && is_string($_POST['_csrf']) && $csrf->isTokenValid($_POST['_csrf'])) {
        $cfgPath = __DIR__ . '/inc/cj-config.php';
        clearstatcache(true, $cfgPath);

        // Read the flag from the FILE, not from the already-loaded $conf.
        // OPcache may still be serving a previous version of that include
        // (revalidate_freq defaults to 2s), which made a second toggle in quick
        // succession write the same value twice and appear to do nothing.
        // ^\s* + /m keeps the match on the real 'enabled' key and never on
        // 'auto_sync_enabled'.
        $cfgText    = (string)file_get_contents($cfgPath);
        $curEnabled = (bool)preg_match("~^\s*'enabled'\s*=>\s*true~m", $cfgText);
        $enableVal  = $curEnabled ? 'false' : 'true';

        $cfgText = preg_replace(
            "~^(\s*'enabled'\s*=>\s*)(true|false)~m",
            '${1}' . $enableVal,
            $cfgText,
            1
        );
        file_put_contents($cfgPath, $cfgText);

        // Make the new value visible to the very next request immediately.
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($cfgPath, true);
        }
        clearstatcache(true, $cfgPath);

        $conf['enabled'] = ($enableVal === 'true');
        $message = $conf['enabled']
            ? 'CJ integration enabled.'
            : 'CJ integration disabled.';
        $messageType = 'success';
    } else {
        $message = 'Invalid request.'; $messageType = 'danger';
    }
    // Redirect so that a browser refresh cannot toggle the setting again.
    cj_redirect_with_flash($message, $messageType, '#status');
}

// ---- GET: check API connection status ----
if (isset($_GET['check_status'])) {
    if (empty($_REQUEST['_csrf']) || !is_string($_REQUEST['_csrf']) || !$csrf->isTokenValid($_REQUEST['_csrf'])) {
        cj_redirect_with_flash('Security token expired. Please try again.', 'danger', '#status');
    }
    $status = $cj->checkConnection();
    $conf['api_connected'] = $status['connected'];
    $conf['api_last_test'] = isset($status['last_test']) ? $status['last_test'] : time();
    // Remember the result so the badge is still correct after the redirect.
    $_SESSION['cj_api_connected'] = $status['connected'];
    $_SESSION['cj_api_last_test'] = $conf['api_last_test'];
    cj_redirect_with_flash(
        htmlspecialchars($status['message']),
        $status['connected'] ? 'success' : 'danger',
        '#status'
    );
}

// ---- GET: search / list products ----
// IMPORTANT: CJ's /product/list only honours a few parameters. Verified live
// against the real API (Sept 2026):
//     productNameEn=<word> -> filters by English product name  (works)
//     productSku=<sku>      -> exact/partial SKU match         (works)
//     keyWord / keywords / keyword / name / searchKey / sku /
//     sortBy / sortOrder -> silently IGNORED (total unchanged vs control)
// So the name + SKU terms are sent to CJ; the sort dropdown is applied locally
// to the rows on screen (the UI says so explicitly). Category is NOT sent to
// CJ (unsupported by /product/list); the category dropdown filters the rows
// currently fetched, client-side.
$keyword    = isset($_GET['keyword'])    ? trim((string)$_GET['keyword'])    : '';
$sku        = isset($_GET['sku'])        ? trim((string)$_GET['sku'])        : '';
$sortBy     = isset($_GET['sortBy'])     ? trim((string)$_GET['sortBy'])     : 'createTime';
$sortOrder  = isset($_GET['sortOrder'])  ? trim((string)$_GET['sortOrder'])  : 'desc';
$categoryId = isset($_GET['categoryId']) ? trim((string)$_GET['categoryId']) : '';
$pageSize   = (int)($conf['list_page_size'] ?? 12);
$currentPage= isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if (!in_array($sortBy, ['createTime', 'sellPrice', 'productName'], true)) { $sortBy = 'createTime'; }
if (!in_array($sortOrder, ['asc', 'desc'], true))                         { $sortOrder = 'desc'; }

/** Sort the rows of the CURRENT page (CJ ignores its own sort parameters). */
function cj_sort_rows(array $rows, $sortBy, $sortOrder)
{
    $dir = ($sortOrder === 'asc') ? 1 : -1;
    usort($rows, function ($a, $b) use ($sortBy, $dir) {
        if ($sortBy === 'sellPrice') {
            $av = (float)($a['sellPrice'] ?? 0);
            $bv = (float)($b['sellPrice'] ?? 0);
        } elseif ($sortBy === 'productName') {
            $av = strtolower((string)CJApiClient::pick($a, ['productNameEn', 'productName'], ''));
            $bv = strtolower((string)CJApiClient::pick($b, ['productNameEn', 'productName'], ''));
        } else { // createTime - CJ returns the newest products first
            $av = (float)($a['createTime'] ?? 0);
            $bv = (float)($b['createTime'] ?? 0);
        }
        if ($av == $bv) { return 0; }
        return ($av < $bv ? -1 : 1) * $dir;
    });
    return $rows;
}

try {
    $params = ['page' => $currentPage, 'pageSize' => $pageSize];
    if ($keyword !== '')    { $params['productNameEn'] = $keyword; }
    if ($sku !== '')        { $params['productSku']    = $sku; }
    if ($categoryId !== '') { $params['categoryId']    = $categoryId; }

    $list = $cj->getProducts($params);

    // getProducts() returns the normalized shape ['pageNum','pageSize','total','list']
    // on success, or the raw response (with 'code'/'message') on failure.
    $listHasResults = is_array($list) && isset($list['list']) && is_array($list['list']);
    if (!$listHasResults) {
        $api_error = 'CJ API error: '
                   . ((is_array($list) && isset($list['message'])) ? $list['message'] : 'unknown');
        $products = []; $total = 0; $pages = 1;
    } else {
        $products = $list['list'];
        $total    = (int)($list['total'] ?? 0);
        $pages    = max(1, (int)ceil($total / $pageSize));
        $currentPage = min($currentPage, max(1, $pages));

        // Apply the on-page sort (CJ ignores sortBy/sortOrder on /product/list).
        $products = cj_sort_rows($products, $sortBy, $sortOrder);
    }
} catch (Throwable $t) {
    $api_error = 'Connection error: ' . $t->getMessage();
    $products = []; $total = 0; $pages = 1;
}

// build a search URL keeping current params except page
function cjUrl($page, $keep = []) {
    $out = [];
    if ($page !== null) $out['page'] = $page;
    foreach (['keyword','sku','sortBy','sortOrder','categoryId'] as $k) {
        if (!empty($keep[$k])) $out[$k] = $keep[$k];
    }
    return $_SERVER['PHP_SELF'] . '?' . http_build_query($out);
}
?>
<?php require_once __DIR__ . '/header.php'; ?>
<style>
.cj-table img { width:64px; height:64px; object-fit:cover; border-radius:6px; background:#eee; }
.cj-badge { font-size:.75rem; font-weight:600; padding:.15rem .5rem; border-radius:999px; white-space:nowrap; }
.badge-listed { background:#e6f4ea; color:#22863a; }
.badge-not { background:#f7f7f7; color:#666; }
</style>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-truck"></i> CJ Dropshipping &mdash; Import Products</h4>
    <a href="https://developers.cjdropshipping.com" target="_blank" class="btn btn-sm btn-outline-secondary">
      <i class="fab fa-crowdflower"></i> CJ Portal
    </a>
  </div>

  <!-- ====== status panel ====== -->
  <?php
    $isEnabled  = (bool)($conf['enabled'] ?? true);
    $isConnected= (bool)($conf['api_connected'] ?? false);
    $lastTest   = isset($conf['api_last_test']) ? (int)$conf['api_last_test'] : 0;
  ?>
  <div id="status" class="card mb-3">
    <div class="card-body">
      <div class="row align-items-center g-2">
        <div class="col-auto">
          <span class="h5 mb-0 <?php echo $isEnabled && $isConnected
                ? 'text-success' : ($isEnabled ? 'text-warning' : 'text-secondary'); ?>">
            <?php if ($isEnabled && $isConnected): ?>
              <i class="fas fa-circle"></i> ACTIVE
            <?php elseif ($isEnabled): ?>
              <i class="fas fa-circle-notch fa-spin"></i> NOT CONNECTED
            <?php else: ?>
              <i class="fas fa-circle"></i> DISABLED
            <?php endif; ?>
          </span>
        </div>
        <div class="col">
          <small class="text-muted">
            <?php if ($isEnabled): ?>
              <?php if ($isConnected): ?>
                API reachable.
              <?php else: ?>
                API not reachable &mdash; check credentials in
                <code class="text-muted">admin/inc/cj-config.php</code>.
              <?php endif; ?>
            <?php else: ?>
              Integration is turned off. Toggle below to re-enable.
            <?php endif; ?>
            <?php if ($lastTest > 0): ?>
              Last checked: <?php echo date('M j, Y g:i A', $lastTest); ?>
            <?php endif; ?>
          </small>
        </div>
        <div class="col-auto">
          <form method="post" class="d-inline">
            <?php echo $csrf->echoInputField(); ?>
            <button type="submit" name="toggle_enable" value="1"
                    class="btn btn-sm <?php echo $isEnabled ? 'btn-outline-warning' : 'btn-outline-success'; ?>">
              <?php echo $isEnabled ? 'Disable' : 'Enable'; ?>
            </button>
          </form>
          <form method="get" class="d-inline">
            <?php echo $csrf->echoInputField(); ?>
            <button type="submit" name="check_status" value="1"
                    class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-sync-alt"></i> Refresh status
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
      <?php echo $message; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>
  <?php if (!empty($api_error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $api_error; ?></div>
  <?php endif; ?>

  <?php if (!$isEnabled): ?>
    <div class="alert alert-warning">
      <i class="fas fa-ban"></i> CJ import is disabled. Click <b>Enable</b> above to start importing.
    </div>
  <?php endif; ?>

  <!-- ====== search form ====== -->
  <!-- CJ's /product/list accepts a name filter (productNameEn) and an exact
       SKU filter (productSku). It deliberately ignores sort parameters, so the
       sort select re-orders the rows currently on screen. -->
  <form method="get" class="form-inline flex-wrap gap-2 mb-3">
    <input type="text" name="keyword" class="form-control" placeholder="Search by English product name..."
           value="<?php echo htmlspecialchars($keyword); ?>" style="width:260px;">
    <input type="text" name="sku" class="form-control" placeholder="Exact CJ SKU (e.g. CJYD3184874)"
           value="<?php echo htmlspecialchars($sku); ?>" style="width:230px;">
    <select name="sortBy" class="form-control" style="width:auto;"
            title="Sorts the rows on this page">
      <option value="createTime"  <?php echo $sortBy==='createTime'?'selected':''; ?>>Newest first</option>
      <option value="sellPrice"   <?php echo $sortBy==='sellPrice'?'selected':''; ?>>Price (cost)</option>
      <option value="productName" <?php echo $sortBy==='productName'?'selected':''; ?>>Name</option>
    </select>
    <select name="sortOrder" class="form-control" style="width:auto;">
      <option value="asc"  <?php echo $sortOrder==='asc'?'selected':''; ?>>Ascending</option>
      <option value="desc" <?php echo $sortOrder==='desc'?'selected':''; ?>>Descending</option>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
    <a href="cj-import.php" class="btn btn-outline-secondary">Reset</a>
    <small class="text-muted w-100">
      Name / SKU search the whole CJ catalog. The sort applies to the rows shown on this page
      (the CJ API does not support server-side sorting).
    </small>
  </form>

  <!-- ====== auto-import card ====== -->
  <div id="autoimport" class="card mb-3 <?php echo $isEnabled ? '' : 'opacity-50'; ?>">
    <div class="card-body">
      <form method="post">
        <?php echo $csrf->echoInputField(); ?>
        <div class="row align-items-center g-2">
          <div class="col-auto">
            <label>Auto-import the <b>newest</b> CJ products into the store:</label>
          </div>
          <div class="col-auto">
            <input type="number" name="auto_count" value="3" min="1"
                   max="<?php echo (int)$conf['max_auto_import']; ?>"
                   class="form-control" style="width:80px;">
          </div>
          <div class="col-auto">
            <button type="submit" name="do_auto_import" value="1"
                    class="btn btn-success <?php echo $isEnabled ? '' : 'disabled'; ?>">
              <i class="fas fa-cloud-upload-alt"></i> Auto Import
            </button>
          </div>
        </div>
        <div class="mt-2">
          <small class="text-muted">
            Imports each product's full detail, images (downloaded locally),
            categories (created if missing), and inventory. Already-imported
            products are skipped. Max per run: <?php echo (int)$conf['max_auto_import']; ?>.
          </small>
        </div>
      </form>
    </div>
  </div>

  <!-- ====== product table ====== -->
  <?php if (empty($api_error) && is_array($products) && count($products)): ?>
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0 cj-table">
            <thead>
              <tr>
                <th style="width:80px;">Image</th>
                <th>Product</th>
                <th style="width:110px;">CJ SKU</th>
                <th style="width:90px;">Cost</th>
                <th style="width:130px;">Category</th>
                <th style="width:80px;">Listed</th>
                <th style="width:130px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p):
                $pid    = $p['pid'] ?? $p['id'] ?? $p['productId'] ?? '';
                $name   = CJApiClient::pick($p, ['productNameEn','productName','name'], '(no name)');
                $sku    = $p['productSku'] ?? '';
                $cost   = $p['sellPrice'] ?? '';
                $cat    = $p['categoryName'] ?? '';
                $listed = (int)($p['listingCount'] ?? $p['listedNum'] ?? 0);
              ?>
              <tr>
                <td>
                  <img src="<?php echo htmlspecialchars(CJApiClient::pick($p,
                          ['productImage','imageUrl','productImageUrl','image'], '')); ?>"
                       onerror="this.src='assets/img/no-thumb.jpg'" alt="">
                </td>
                <td><?php echo htmlspecialchars($name); ?></td>
                <td><?php echo htmlspecialchars($sku); ?></td>
                <td>$<?php echo htmlspecialchars($cost); ?></td>
                <td><?php echo htmlspecialchars($cat); ?></td>
                <td>
                  <?php if ($listed > 0): ?>
                    <span class="cj-badge badge-listed">Listed</span>
                  <?php else: ?>
                    <span class="cj-badge badge-not">No</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($pid !== '' && $isEnabled): ?>
                    <form method="post" class="d-inline">
                      <?php echo $csrf->echoInputField(); ?>
                      <input type="hidden" name="pid" value="<?php echo htmlspecialchars($pid); ?>">
                      <button type="submit" name="do_import" value="1"
                              class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download"></i> Import
                      </button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php if ($pages > 1): ?>
        <div class="card-footer border-top d-flex justify-content-between align-items-center bg-white">
          <span class="text-muted">
            Page <?php echo $currentPage; ?> of <?php echo $pages; ?> &middot;
            <?php echo number_format($total); ?> product<?php echo $total===1?'':'s'; ?>
          </span>
          <ul class="pagination mb-0">
            <li class="page-item <?php echo $currentPage<=1?'disabled':''; ?>">
              <a class="page-link" href="<?php echo cjUrl(max(1,$currentPage-1), $_GET); ?>">Previous</a>
            </li>
            <li class="page-item disabled">
              <span class="page-link"><?php echo $currentPage; ?> / <?php echo $pages; ?></span>
            </li>
            <li class="page-item <?php echo $currentPage>=$pages?'disabled':''; ?>">
              <a class="page-link" href="<?php echo cjUrl($currentPage+1, $_GET); ?>">Next</a>
            </li>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  <?php elseif (empty($api_error)): ?>
    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i> No products found. Try a different search term or sort order.
    </div>
  <?php endif; ?>

  <div class="mt-3 text-muted small">
    <?php if (isset($total)): ?>
      Showing <?php echo count($products); ?> of <?php echo number_format($total); ?> CJ products.
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>