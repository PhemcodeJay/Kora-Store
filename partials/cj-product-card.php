<?php
/**
 * Shared product card for the customer-facing CJ catalogue pages.
 * Expects: $cjProduct (row from tbl_product).
 */
if (!isset($cjProduct) || !is_array($cjProduct)) {
    return;
}
$photo = basename(trim((string)($cjProduct['p_featured_photo'] ?? '')));
?>
<div class="item">
    <div class="thumb">
        <div class="photo" style="background-image:url(assets/uploads/<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>);"></div>
        <div class="overlay"></div>
    </div>
    <div class="text">
        <h3><a href="product.php?id=<?php echo (int)$cjProduct['p_id']; ?>"><?php echo htmlspecialchars($cjProduct['p_name'], ENT_QUOTES, 'UTF-8'); ?></a></h3>
        <h4>
            $<?php echo number_format((float)$cjProduct['p_current_price'], 2); ?>
            <?php if ($cjProduct['p_old_price'] !== '' && (float)$cjProduct['p_old_price'] > (float)$cjProduct['p_current_price']): ?>
                <del>$<?php echo number_format((float)$cjProduct['p_old_price'], 2); ?></del>
            <?php endif; ?>
        </h4>
        <p><span class="badge badge-success">Dropshipping</span></p>
        <?php if ((int)$cjProduct['p_qty'] > 0): ?>
            <p><a href="product.php?id=<?php echo (int)$cjProduct['p_id']; ?>"><i class="fa fa-shopping-cart"></i> Add to Cart</a></p>
        <?php else: ?>
            <div class="out-of-stock"><div class="inner">Out Of Stock</div></div>
        <?php endif; ?>
    </div>
</div>
