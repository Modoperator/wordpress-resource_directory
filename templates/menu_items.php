<div class="iard_wrap">
    <h2>Resource Directory Items</h2>

	<?php if (isset($flash)) echo $flash ?>

    <form method="post" action="">

		<div>
			<strong>Add a Resource Item</strong>
			<br /><br />
			<label>Category:</label> <select name="item_cat">
				<option value="">--Select--</option>
				<?php
				$cat_options='';
				$json_cats = array();
				foreach($cats as $cat)
				{
					if ($active_catid == absint($cat['id']))
					{
						$selected = 'selected="selected"';
					}
					else $selected = '';

					$cat_options .= '<option value="'. $cat['id'] .'" ' . $selected . '>' . $cat['name'] . '</option>';

					$json_id = $cat['id'] .':'.$cat['name'];
					$json_cats[$json_id] =  $cat['name'];
				}
				echo $cat_options;
				?>
			</select>
			<br />
			<label>Resource Title:</label> <input type="text" name="item_name" id="item_name" size="50" />
			<br />
			<label>Resource URL:</label> <input type="text" name="item_url" id="item_url" size="50" /> <em>(Enter full url including http:// or https://)</em>
			<?php wp_nonce_field('add-resource','_nonce-a')?>
			<input type="hidden" name="action" value="additem" />
		</div>

		<?php @submit_button(); ?>

		<?php

		echo '<h3>Current Resources</h3>';
		echo 'Filter by Category: <select id="ddl_filter_cats"><option></option>' . $cat_options . '</select>';
		echo '<input type="button" value="Filter" class="button button-primary" id="filter_cats">';

		if (count($items)>0)
		{
			echo '<br /><br />Click a field entry to edit-in-place.';
			echo '<table class="iard_table iard_resources">';
			echo '<tr><th>Resource Title</th><th>URL</th><th>Category</th><th></th>';

			foreach($items as $item)
			{
				$rid = $item['id'];
				$delete = wp_nonce_url($_SERVER['REQUEST_URI'] . '&action=delete&id=' . $item['id'],'delete-resource-' . $rid);

				echo '<tr><td><span class="edit" id="' . $rid . ':name">'. $item['name'] . '</span></td>
					<td><span class="edit" id="' . $rid . ':url">'. $item['url'] . '</span></td>
					<td><span class="edit_select" id="' . $rid . ':cat">'. $item['catname'] . '</span></td>
					<td><a href="' . $delete . '" class="iard_delete_lnk">Delete</a></td></tr>';
			}

			echo '</table>';
		}
		else
		{
			echo '<p>No Resource Items are in this category.</p>';
		}
	?>
    </form>
</div>

<script type="text/javascript">
	jQuery(document).ready(function($){
			$('.edit').editable('<?php echo admin_url('admin-ajax.php'); ?>?action=ia-rd-update-item', {
			cancel    : 'Cancel',
			submit    : 'Save',
			indicator : '<b style="color:green">Saving ...</b>',
			tooltip   : 'Click to edit...',
			cssclass  : 'jedit'
		});

		$('.edit_select').editable('<?php echo admin_url('admin-ajax.php'); ?>?action=ia-rd-update-item', {
			type      : 'select',
			data	  : '<?php echo json_encode($json_cats) ?>',
			cancel    : 'Cancel',
			submit    : 'Save',
			indicator : '<b style="color:green">Saving ...</b>',
			tooltip   : 'Click to edit...',
			cssclass  : 'jedit'		
		});

		$('#filter_cats').click(function(){
			window.location.href = ("<?php echo admin_url('admin.php?page=ia-resource-directory-items&cat=') ?>" + $('#ddl_filter_cats').val());
		})
		$(".iard_delete_lnk").click(function() {
			return confirm ("Delete this?");
		});
	});
</script>

