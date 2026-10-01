<?php
/**
 * cj-autosync.php
 *
 * Background / scheduled auto-import of CJ Dropshipping products.
 *
 * Usage:
 *   php cj-autosync.php              — run a sync (respects auto_sync_enabled flag)
 *   php cj-autosync.php --force      — run even if the integration is disabled
 *   php cj-autosync.php --dry-run   — fetch the catalog but do not insert anything
 *
 * Scheduling (Windows Task Scheduler):
 *   Program:  C:\path\to\php.exe
 *   Args:     -f C:\path\to\cj-autosync.php
 *   Trigger:  Daily (or whatever interval you prefer)
 *
 * The script honours two safeguards so it can be left to run unattended:
 *   1. The integration must be enabled in admin/inc/cj-config.php
 *      ('enabled' => true) unless --force is passed.
 *   2. It will not run again within auto_sync_interval_hours of the last run
 *      (tracked in upload/cj_last_sync.json).
 */
if (php_sapi_name() === 'cli') {
    // CLI-only cleanup: ensure no output buffering surprises
    if (ob_get_level()) ob_end_clean();
}

require_once __DIR__ . '/admin/inc/config.php';      // $pdo + session + BASE_URL
require_once __DIR__ . '/admin/inc/cj-api.php';      // CJApiClient (loads cj-config.php)
require_once __DIR__ . '/admin/inc/cj-helpers.php';  // cj_import_product / cj_auto_import

$cj   = new CJApiClient();
$pdo  = $GLOBALS['pdo'] ?? null;
$conf = &$GLOBALS['cj_config'];

if (!$pdo instanceof PDO) {
    fprintf(STDERR, "No PDO database handle available (admin/inc/config.php did not set one).\n");
    exit(2);
}

$cliArgs = $_SERVER['argv'] ?? [];
$force   = in_array('--force', $cliArgs, true);
$dryRun  = in_array('--dry-run', $cliArgs, true);

// ---- integration enabled check ----
$isEnabled = (bool)($conf['enabled'] ?? true);
if (!$isEnabled && !$force) {
    fprintf(STDERR, "CJ integration is disabled in cj-config.php.\n");
    fprintf(STDERR, "Edit admin/inc/cj-config.php and set 'enabled' => true,\n");
    fprintf(STDERR, "or run with --force to bypass.\n");
    exit(2);
}

// ---- interval check (avoid running too often) ----
$intervalHours  = (int)($conf['auto_sync_interval_hours'] ?? 24);
$lastSyncFile   = __DIR__ . '/upload/cj_last_sync.json';
$now            = time();
$shouldRun      = true;
$lastRun        = null;

if (is_file($lastSyncFile)) {
    $last = json_decode((string)file_get_contents($lastSyncFile), true);
    if (isset($last['at']) && is_numeric($last['at'])) {
        $lastRun   = (int)$last['at'];
        $elapsed   = ($now - $lastRun) / 3600;
        $shouldRun = ($elapsed >= $intervalHours);
        if (!$shouldRun) {
            printf("Last sync was %s hours ago (interval is %d h). Skipping.\n",
                number_format($elapsed, 1), $intervalHours);
            exit(0);
        }
    }
}

// ---- fetch the catalog ----
$count = (int)($conf['auto_sync_count'] ?? 5);
printf("Fetching newest %d CJ products...\n", $count);

$list = $cj->getProducts([
    'page'       => 1,
    'pageSize'   => $count,
    'sortBy'     => $conf['auto_sync_sort_by'] ?? 'createTime',
    'sortOrder'  => $conf['auto_sync_sort_order'] ?? 'desc',
]);

$products = [];
if (is_array($list) && isset($list['list']) && is_array($list['list'])) {
    $products = $list['list'];
} elseif (is_array($list) && isset($list['data']) && is_array($list['data'])) {
    $d = $list['data'];
    $products = $d['list'] ?? $d['items'] ?? $d['content'] ?? [];
}

if (empty($products)) {
    printf("No products returned by CJ (or catalog call failed).\n");
    if (is_array($list) && isset($list['message'])) {
        printf("CJ message: %s\n", $list['message']);
    }
    exit(1);
}

printf("Fetched %d products from CJ.\n\n", count($products));

// ---- import loop ----
$opts = [
    'markup'     => $conf['price_markup']     ?? 2.5,
    'old_factor' => $conf['old_price_factor'] ?? 1.25,
    'qty'        => $conf['default_qty']       ?? 50,
    'max_photos' => $conf['max_photos']        ?? 5,
];

$ok   = 0;
$skip = 0;
$fail = 0;

foreach ($products as $idx => $p) {
    $pid  = $p['pid'] ?? $p['id'] ?? $p['productId'] ?? '';
    $name = CJApiClient::pick($p,
        ['productNameEn', 'productName', 'name'], '(no name)');

    if ($pid === '') {
        printf("[%d] Skipped: no pid\n", $idx + 1);
        $skip++;
        continue;
    }

    if ($dryRun) {
        printf("[%d] WOULD IMPORT pid=%s  %s\n", $idx + 1, $pid, $name);
        $ok++;
        continue;
    }

    try {
        $res = cj_import_product($pdo, $cj, $pid, $opts);
        $status = $res[0];
        $msg    = $res[1];

        if ($status === 'ok' || $status === 'imported') {
            $ok++;
            printf("[%d] IMPORTED  pid=%s  %s\n", $idx + 1, $pid, $msg);
        } elseif ($status === 'skipped') {
            $skip++;
            printf("[%d] SKIPPED  pid=%s  %s\n", $idx + 1, $pid, $msg);
        } else {
            $fail++;
            printf("[%d] FAILED   pid=%s  %s\n", $idx + 1, $pid, $msg);
        }
    } catch (Throwable $e) {
        $fail++;
        printf("[%d] EXCEPTION pid=%s  %s\n", $idx + 1, $pid, $e->getMessage());
    }
}

printf("\n--- Summary ---\n");
printf($dryRun ? "Would import: %d\n" : "Imported:     %d\n", $ok);
printf("Skipped:      %d\n", $skip);
printf("Failed:       %d\n", $fail);

// ---- record last-run timestamp ----
if (!$dryRun) {
    $record = ['at' => $now, 'imported' => $ok, 'skipped' => $skip, 'failed' => $fail];
    @file_put_contents($lastSyncFile, json_encode($record));
    printf("\nLast-sync record written to upload/cj_last_sync.json\n");
}

exit($fail > 0 ? 1 : 0);