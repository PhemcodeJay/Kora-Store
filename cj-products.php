<?php
/**
 * Customer-facing CJ Dropshipping catalogue.
 * Lists only active products imported from CJ, independent of the three
 * homepage carousels and their configurable product limits.
 */
require_once __DIR__ . '/header.php';

$pageSize = 12;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$statement = $pdo->query("SELECT COUNT(*) FROM tbl_product WHERE cj_product_id IS NOT NULL AND cj_product_id<>'' AND p_is_active=1");
$totalProducts = (int)$statement->fetchColumn();
$totalPages = max(1, (int)ceil($totalProducts / $pageSize));
$page = min($page, $totalPages);
$offset = ($page - 1) * $pageSize;

$statement = $pdo->prepare("SELECT p_id, p_name, p_old_price, p_current_price, p_qty, p_featured_photo
    FROM tbl_product
    WHERE cj_product_id IS NOT NULL AND cj_product_id<>'' AND p_is_active=1
    ORDER BY p_id DESC LIMIT $pageSize OFFSET $offset");
$statement->execute();
$products = $statement->fetchAll(PDO::FETCH_ASSOC);

$statement = $pdo->prepare("SELECT banner_product_category FROM tbl_settings WHERE id=1");
$statement->execute();
$settings = $statement->fetch(PDO::FETCH_ASSOC);
$banner = $settings['banner_product_category'] ?? 'banner_product_category.jpg';
?>
<div class="page-banner" style="background-image: url(assets/uploads/<?php echo htmlspecialchars($banner, ENT_QUOTES, 'UTF-8'); ?>)">
    <div class="inner"><h1>CJ Dropshipping</h1></div>
</div>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb mb_30">
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li>></li>
                        <li>CJ Dropshipping</li>
                    </ul>
                </div>

                <div class="headline">
                    <h2>Fresh products, shipped direct from our suppliers</h2>
                    <h3><?php echo number_format($totalProducts); ?> products available</h3>
                </div>

                <?php if (!$products): ?>
                    <div class="alert alert-info">No CJ Dropshipping products are available yet.</div>
                <?php else: ?>
                    <div class="product">
                        <div class="row">
                            <?php foreach ($products as $cjProduct): ?>
                                <div class="col-md-3 item-product-cat">
                                    <?php require __DIR__ . '/partials/cj-product-card.php'; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="CJ products pagination">
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                                        <a href="cj-products.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>