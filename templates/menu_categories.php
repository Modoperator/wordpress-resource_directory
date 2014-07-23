<style>
	
</style>

<div class="iard_wrap">
    <h2>Resource Directory Categories</h2>

	<?php if (isset($flash)) echo $flash ?>
	
    <form method="post" action="">

		<div>
			<strong>Add a Category</strong>
			<br /><br />
			<label>Category Name:</label> <input type="text" name="cat_name" id="cat_name" size="50" />
			<?php wp_nonce_field('add-category','_nonce-add')?>
		</div>

		<?php @submit_button(); ?>

		<?php

		if (count($cats)>0)
		{
			echo '<h3>Current Categories</h3>';			
			echo '<table class="iard_table iard_categories">';
			echo '<tr><th>Category (click a category to edit-in-place)</th><th>&nbsp;</th><th>&nbsp;</th>';

			foreach($cats as $cat)
			{
				$catid = $cat['id'];
				$delete = wp_nonce_url( $_SERVER['REQUEST_URI'] . '&action=delete&id=' . $catid, 'delete-category-'.$catid );

				echo '<tr>
					<td><span class="edit" id="' . $catid . ':name">'. $cat['name'] . '</span></td>
					<td><a href="' . admin_url('admin.php') . '?page=ia-resource-directory-items&cat='. $catid . '">View Resources</a></td>
					<td><a href="'. $delete . '" class="iard_delete_lnk">Delete</a></td></tr>';
			}

			echo '</table>';
		}
		else
		{
			echo 'No categories have been entered yet.';
		}
	?>
        
    </form>
</div>

<script type="text/javascript">
	jQuery(document).ready(function($){
			$('.edit').editable('<?php echo admin_url('admin-ajax.php'); ?>?action=ia-rd-update-category', {
			cancel    : 'Cancel',
			submit    : 'OK',
			indicator : '<b style="color:green">Saving ...</b>',
			tooltip   : 'Click to edit...',
			cssclass  : 'jedit'
		});
		$(".iard_delete_lnk").click(function() {
			return confirm ("Delete this?");
		});
	});

</script>