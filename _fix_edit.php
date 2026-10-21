<?php
$file = 'C:/Users/Phemcode/Documents/Jobs/Clients/Kora Store/admin/product.php';
$lines = file($file);

// Replace lines 32-92 (1-indexed, indices 31-91) with digital-aware listing
$newCode = <<<'CODE'
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
													t2.ecat_name,
													t3.mcat_name,
													t4.tcat_name,
													'physical' as product_type
											FROM tbl_product t1
											JOIN tbl_end_category t2 ON t1.ecat_id = t2.ecat_id
											JOIN tbl_mid_category t3 ON t2.mcat_id = t3.mcat_id
											JOIN tbl_top_category t4 ON t3.tcat_id = t4.tcat_id
											ORDER BY t1.p_id DESC");
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
				usort($all_products, function($a, $b) { return $b['p_id'] - $a['p_id']; });
				
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
CODE;

// Lines 32-92 (indices 31-91)
$newLines = array_slice($lines, 0, 31); // lines 1-31
$newLines[] = $newCode . "\n";
$newLines = array_merge($newLines, array_slice($lines, 92)); // from line 93 onwards

$content = implode('', $newLines);
file_put_contents($file, $content);
echo "Replaced product listing with digital-aware version\n";
echo "Done\n";