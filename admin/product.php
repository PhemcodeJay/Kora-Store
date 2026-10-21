<?php require_once('header.php'); ?>

<section class="content-header">
	<div class="content-header-left">
		<h1>View Products</h1>
	</div>
	<div class="content-header-right">
		<a href="product-add.php" class="btn btn-primary btn-sm">Add Product</a>
	</div>
</section>

<section class="content">
	<div class="row">
		<div class="col-md-12">
			<div class="box box-info">
				<div class="box-body table-responsive">
					<table id="example1" class="table table-bordered table-hover table-striped">
					<thead class="thead-dark">
							<tr>
								<th width="10">#</th>
								<th>Photo</th>
								<th width="160">Product Name</th>
								<th width="60">Old Price</th>
								<th width="60">(C) Price</th>
								<th width="60">Quantity</th>
								<th>Featured?</th>
								<th>Active?</th>
								<th>Category</th>
								<th width="80">Action</th>
							</tr>
						</thead>
			<tbody>
				<?php
				$i=0;
				
				// Fetch physical products from tbl_product
				$statement = $pdo->prepare("SELECT
													t1.p_id,
													t1.p_name,
													t1.p_old_price,
													t1.p_current_price,
													t1.p_qty,
													t1.p_featured_photo,
													t1.p_is_featured,
													t1.p_is_active,
													t1.ecat_id,
\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t(t1.is_dropshipping ? 1 : 0) as is_dropshipping,
													t2.ecat_name,
													t3.mcat_name,
													t4.tcat_name,
													'physical' as product_type
											FROM tbl_product t1
											JOIN tbl_end_category t2 ON t1.ecat_id = t2.ecat_id
											JOIN tbl_mid_category t3 ON t2.mcat_id = t3.mcat_id
											JOIN tbl_top_category t4 ON t3.tcat_id = t4.tcat_id
											ORDER BY is_dropshipping DESC, t1.p_id DESC");
				$statement->execute();
				$physical_result = $statement->fetchAll(PDO::FETCH_ASSOC);
				
				// Fetch digital products from tbl_digital_products
				$stmt2 = $pdo->prepare("SELECT
												id as p_id,
												name as p_name,
												'' as p_old_price,
												price as p_current_price,
												0 as p_qty,
												product_image as p_featured_photo,
												1 as p_is_featured,
												1 as p_is_active,
												0 as ecat_id,
												'' as ecat_name,
												'' as mcat_name,
												'' as tcat_name,
												'digital' as product_type
										FROM tbl_digital_products
										ORDER BY id DESC");
				$stmt2->execute();
				$digital_result = $stmt2->fetchAll(PDO::FETCH_ASSOC);
				
				// Combine results
				$all_products = array_merge($physical_result, $digital_result);
				usort($all_products, function($a, $b) {
    // Priority: dropshipping first, then physical products, then digital products
    $a_priority = 0;
    $b_priority = 0;

    // Determine priority based on product type and dropshipping status
    if ($a['product_type'] === 'physical') {
        if ($a['is_dropshipping'] == 1) $a_priority = 1; // dropshipping physical
        else $a_priority = 2; // other physical
    } else {
        $a_priority = 3; // digital
    }

    if ($b['product_type'] === 'physical') {
        if ($b['is_dropshipping'] == 1) $b_priority = 1; // dropshipping physical
        else $b_priority = 2; // other physical
    } else {
        $b_priority = 3; // digital
    }

    return $a_priority - $b_priority;
});
				
				foreach ($all_products as $row) {
					$i++;
					?>
					<tr>
						<td><?php echo $i; ?></td>
						<td style="width:82px;">
							<?php if ($row['product_type'] === 'digital' && !empty($row['p_featured_photo'])): ?>
								<img src="../assets/uploads/<?php echo htmlspecialchars($row['p_featured_photo']); ?>" alt="<?php echo htmlspecialchars($row['p_name']); ?>" style="width:80px;">
							<?php elseif (!empty($row['p_featured_photo'])): ?>
								<img src="../assets/uploads/<?php echo htmlspecialchars($row['p_featured_photo']); ?>" alt="<?php echo htmlspecialchars($row['p_name']); ?>" style="width:80px;">
							<?php else: ?>
								<span class="text-muted" style="font-size:10px;">No Image</span>
							<?php endif; ?>
						</td>
						<td><?php echo htmlspecialchars($row['p_name']); ?></td>
						<td>$<?php echo number_format($row['p_old_price'], 2); ?></td>
						<td>$<?php echo number_format($row['p_current_price'], 2); ?></td>
						<td><?php echo ($row['product_type'] === 'digital') ? 'N/A' : $row['p_qty']; ?></td>
						<td>
							<?php if($row['p_is_featured'] == 1) {echo '<span class="badge badge-success" style="background-color:green;">Yes</span>';} else {echo '<span class="badge badge-success" style="background-color:red;">No</span>';} ?>
						</td>
						<td>
							<?php if($row['p_is_active'] == 1) {echo '<span class="badge badge-success" style="background-color:green;">Yes</span>';} else {echo '<span class="badge badge-danger" style="background-color:red;">No</span>';} ?>
						</td>
						<td>
							<?php if ($row['product_type'] === 'digital'): ?>
								<span class="badge bg-info">Digital</span>
							<?php else: ?>
								<?php echo $row['tcat_name']; ?><br><?php echo $row['mcat_name']; ?><br><?php echo $row['ecat_name']; ?>
							<?php endif; ?>
						</td>
						<td>
							<a href="product-edit.php?id=<?php echo $row['p_id']; ?>" class="btn btn-primary btn-xs">Edit</a>
							<?php if ($row['product_type'] === 'physical'): ?>
								<a href="#" class="btn btn-danger btn-xs" data-href="product-delete.php?id=<?php echo $row['p_id']; ?>" data-toggle="modal" data-target="#confirm-delete">Delete</a>
							<?php else: ?>
								<a href="#" class="btn btn-danger btn-xs" data-href="digital-product-delete.php?id=<?php echo $row['p_id']; ?>" data-toggle="modal" data-target="#confirm-delete">Delete</a>
							<?php endif; ?>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</section>


<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">Delete Confirmation</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure want to delete this item?</p>
                <p style="color:red;">Be careful! This product will be deleted from the order table, payment table, size table, color table and rating table also.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger btn-ok">Delete</a>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>