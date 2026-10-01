<?php
/**
 * Operational repair for existing CJ imports.
 *
 * Earlier importer versions saved gallery files in assets/uploads instead of
 * assets/uploads/product_photos and could record a filename even when the
 * download failed. This repairs those records and re-enables storefront
 * visibility using the current cj-config.php settings.
 *
 * Run from the project root:  php cj-repair.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This repair script can only be run from the command line.\n");
}

require_once __DIR__ . '/admin/inc/config.php';
require_once __DIR__ . '/admin/inc/cj-api.php';
require_once __DIR__ . '/admin/inc/cj-helpers.php';

$uploadRoot = __DIR__ . '/assets/uploads';
$galleryDir = $uploadRoot . '/product_photos';
if (!is_dir($galleryDir) && !mkdir($galleryDir, 0755, true) && !is_dir($galleryDir)) {
    fwrite(STDERR, "Could not create assets/uploads/product_photos.\n");
    exit(1);
}

$cj = new CJApiClient();
$featuredFixed = 0;
$galleryFixed = 0;
$featuredUnchanged = 0;
$galleryUnchanged = 0;

$products = $pdo->query("SELECT p_id, cj_product_id, p_featured_photo FROM tbl_product
    WHERE cj_product_id IS NOT NULL AND cj_product_id<>'' ORDER BY p_id")->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $row) {
    $detail = null;
    $photo = trim((string)$row['p_featured_photo']);
    $localFeatured = $photo !== '' && !preg_match('~^https?://~i', $photo)
        && is_file($uploadRoot . '/' . basename($photo));

    if (!$localFeatured) {
        try {
            $detail = $cj->getProduct($row['cj_product_id']);
            $featuredUrl = cj_decode_first(CJApiClient::pick($detail,
                ['bigImage', 'imageUrl', 'productImage', 'image_url', 'image'], ''));
            $localName = cj_download_image($featuredUrl);
            if ($localName !== '') {
                $pdo->prepare('UPDATE tbl_product SET p_featured_photo=? WHERE p_id=?')
                    ->execute([$localName, $row['p_id']]);
                $featuredFixed++;
                printf("Fixed featured image: product %d\n", $row['p_id']);
            } else {
                $featuredUnchanged++;
                fprintf(STDERR, "Could not download featured image: product %d\n", $row['p_id']);
            }
        } catch (Throwable $e) {
            $featuredUnchanged++;
            fprintf(STDERR, "Could not refresh product %d: %s\n", $row['p_id'], $e->getMessage());
        }
    }

    $photos = $pdo->prepare('SELECT pp_id, photo FROM tbl_product_photo WHERE p_id=?');
    $photos->execute([$row['p_id']]);
    $galleryRows = $photos->fetchAll(PDO::FETCH_ASSOC);
    $brokenRows = [];

    foreach ($galleryRows as $gallery) {
        $name = trim((string)$gallery['photo']);
        if ($name === '') {
            $brokenRows[] = $gallery;
            continue;
        }

        // Old records may be a remote URL or a local file in the wrong folder.
        if (preg_match('~^https?://~i', $name)) {
            $localName = cj_download_image($name, 'product_photos');
        } else {
            $localName = basename($name);
            $oldPath = $uploadRoot . '/' . $localName;
            $newPath = $galleryDir . '/' . $localName;
            if (is_file($oldPath) && !is_file($newPath)) {
                @rename($oldPath, $newPath);
            }
            if (!is_file($galleryDir . '/' . $localName)) $localName = '';
        }

        if ($localName !== '') {
            $pdo->prepare('UPDATE tbl_product_photo SET photo=? WHERE pp_id=?')
                ->execute([$localName, $gallery['pp_id']]);
            $galleryFixed++;
        } else {
            $brokenRows[] = $gallery;
        }
    }

    // Some old imports recorded a gallery filename even though the download
    // never completed. Re-fetch those products and rebuild the broken rows.
    if ($brokenRows) {
        try {
            if (!isset($detail)) {
                $detail = $cj->getProduct($row['cj_product_id']);
            }
            $rawImages = CJApiClient::pick($detail,
                ['productImage', 'productImageSet', 'imageList', 'productImageList', 'images'], []);
            $imageUrls = [];
            if (is_string($rawImages) && $rawImages !== '' && $rawImages[0] === '[') {
                $decoded = json_decode($rawImages, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $item) {
                        if (is_string($item) && trim($item) !== '') $imageUrls[] = trim($item);
                    }
                }
            } elseif (is_array($rawImages)) {
                foreach ($rawImages as $item) {
                    $url = is_array($item)
                        ? (string)CJApiClient::pick($item, ['url', 'normalUrl', 'big', 'path'])
                        : trim((string)$item);
                    if ($url !== '') $imageUrls[] = $url;
                }
            }

            $used = [];
            foreach (array_unique($imageUrls) as $url) {
                if (count($used) >= count($brokenRows)) break;
                $localName = cj_download_image($url, 'product_photos');
                if ($localName === '' || in_array($localName, $used, true)) continue;
                $used[] = $localName;
                $pdo->prepare('UPDATE tbl_product_photo SET photo=? WHERE pp_id=?')
                    ->execute([$localName, $brokenRows[count($used) - 1]['pp_id']]);
                $galleryFixed++;
            }

            // Do not leave broken image rows in the storefront gallery.
            $delete = $pdo->prepare('DELETE FROM tbl_product_photo WHERE pp_id=?');
            foreach (array_slice($brokenRows, count($used)) as $broken) {
                $delete->execute([$broken['pp_id']]);
                $galleryUnchanged++;
            }
        } catch (Throwable $e) {
            $galleryUnchanged += count($brokenRows);
            fprintf(STDERR, "Could not refresh gallery for product %d: %s\n", $row['p_id'], $e->getMessage());
        }
    }
}

if (!empty($cj_config['feature_new_products'])) {
    $pdo->exec("UPDATE tbl_product SET p_is_featured=1 WHERE cj_product_id IS NOT NULL AND cj_product_id<>''");
}
if (!empty($cj_config['show_categories_on_menu'])) {
    $pdo->exec("UPDATE tbl_top_category SET show_on_menu=1 WHERE tcat_id IN (
        SELECT DISTINCT m.tcat_id
        FROM tbl_product p
        JOIN tbl_end_category e ON e.ecat_id=p.ecat_id
        JOIN tbl_mid_category m ON m.mcat_id=e.mcat_id
        WHERE p.cj_product_id IS NOT NULL AND p.cj_product_id<>''
    )");
}

printf("Featured images fixed: %d; unchanged: %d\n", $featuredFixed, $featuredUnchanged);
printf("Gallery images fixed: %d; unchanged: %d\n", $galleryFixed, $galleryUnchanged);
