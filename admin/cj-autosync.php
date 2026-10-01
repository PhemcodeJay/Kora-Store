<?php
/**
 * cj-autosync.php — CLI / scheduled task for automatic CJ product imports.
 *
 * Usage:
 *   php admin/cj-autosync.php
 *
 * Schedule with Windows Task Scheduler:
 *   Program:  C:\php\php.exe   (or your PHP CLI path)
 *   Args:     admin/cj-autosync.php
 *   Start in: C:\Users\Phemcode\Downloads\Compressed\CyberTrendHub-Ecommerce-main\CyberTrendHub-Ecommerce-main
 *   Trigger:  Daily (or every N hours, >= auto_sync_interval_hours)
 *
 * Exit codes:  0 = ran successfully,  1 = disabled/config error,  2 = fatal
 */

// ---------- bootstrap (same as admin header, without the HTML) ----------
ob_start();
error_reporting(E_ALL);

// Resolve base path — works when invoked from project root OR from admin/ subdir
$scriptDir = __DIR__;                        // directory of this file (admin/)
$rootDir   = dirname($scriptDir);            // project root (..)

// Load config + DB + CJ stack
require_once $rootDir . '/admin/inc/config.php';       // auth/session setup + $pdo
require_once $rootDir . '/admin/inc/cj-config.php';    // $cj_config
require_once $rootDir . '/admin/inc/cj-api.php';       // CJApiClient
require_once $rootDir . '/admin/inc/cj-helpers.php';   // cj_import_product / cj_auto_import

// CJ's token cache lives under upload/ (relative to project root)
if (!is_dir($rootDir . '/upload')) {
    @mkdir($rootDir . '/upload', 0755, true);
}

// ---------- guard: run only from CLI (no web exposure) ----------
if (php_sapi_name() !== 'cli') {
    file_put_contents($logFile = $rootDir . '/upload/cj_autosync.log',
        date('[Y-m-d H:i:s] ') . "BLOCKED: web request not allowed\n", FILE_APPEND);
    http_response_code(403);
    echo "This script may only be run from the command line.\n";
    exit(1);
}

// ---------- config checks ----------
$conf = $GLOBALS['cj_config'] ?? [];
$root = $rootDir;

if (empty($conf['enabled'])) {
    $msg = "DISABLED: cj_config['enabled'] is false.\n";
    file_put_contents("$root/upload/cj_autosync.log", date('[Y-m-d H:i:s] ') . $msg, FILE_APPEND);
    echo $msg;
    exit(1);
}
if (empty($conf['auto_sync_enabled'])) {
    $msg = "DISABLED: cj_config['auto_sync_enabled'] is false.\n";
    file_put_contents("$root/upload/cj_autosync.log", date('[Y-m-d H:i:s] ') . $msg, FILE_APPEND);
    echo $msg;
    exit(1);
}

$intervalHours = (int)($conf['auto_sync_interval_hours'] ?? 24);
$count         = (int)($conf['auto_sync_count']    ?? 5);
$sortBy        = $conf['auto_sync_sort_by']        ?? 'createTime';
$sortOrder     = $conf['auto_sync_sort_order']     ?? 'desc';
$logFile       = "$root/upload/cj_autosync.log";

// ---------- throttle: don't run more often than the interval ----------
$lastLog = @file_get_contents("$root/upload/cj_autosync_last_run.txt");
$lastRun = $lastLog !== false ? (int)$lastLog : 0;
$elapsed = time() - $lastRun;
if ($elapsed < $intervalHours * 3600 && $lastRun > 0) {
    $msg = "SKIPPED: last run was $elapsed seconds ago (interval = {$intervalHours}h).\n";
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg, FILE_APPEND);
    echo $msg;
    exit(0);
}

// ---------- run import ----------
file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Starting auto-sync (count=$count, sortBy=$sortBy $sortOrder)...\n", FILE_APPEND);

try {
    $cj   = new CJApiClient();
    $pdo  = $GLOBALS['pdo'] ?? null;   // set by admin/inc/config.php

    if (!$pdo || !($pdo instanceof PDO)) {
        throw new RuntimeException('No $pdo available (admin/inc/config.php did not set one)');
    }

    $results = cj_auto_import($pdo, $cj, $count);
    $ok   = count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'ok'));
    $skip = count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'skipped'));
    $fail = count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'failed'));

    $summary = "DONE: $ok imported, $skip skipped, $fail failed.";
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $summary . "\n", FILE_APPEND);
    foreach ($results as $r) {
        file_put_contents($logFile,
            date('[Y-m-d H:i:s] ') . "  [{$r['status']}] pid={$r['pid']}: " . ($r['msg'] ?? '') . "\n",
            FILE_APPEND);
    }

    // stamp last-run timestamp
    file_put_contents("$root/upload/cj_autosync_last_run.txt", (string)time());

    echo $summary . "\n";
    echo "Log: $logFile\n";
    exit(0);

} catch (Throwable $e) {
    $msg = "FATAL: " . $e->getMessage() . " (in {$e->getFile()}:{$e->getLine()})\n";
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg, FILE_APPEND);
    echo $msg;
    exit(2);
}
