<?php
defined( 'ABSPATH' ) OR exit;
/**
 * Plugin Name: Illuminage Resource Directory 
 * Plugin URI: http://www.illuminage.com
 * Description: Sets up a simple category based resource directory that clients can maintain and display on a Page.
 * Version: 1.1
 * Author: MaKERS
 * Author URI: http://www.illuminage.com
  */

// * @property wpdb $wpdb

if (!class_exists('IA_Resource_Directory'))
{
	class IA_Resource_Directory
	{
		private
				$wpdb,
				$cat_table_name,
				$item_table_name;


		public function __construct()
		{
			global $wpdb;
			$this->wpdb = &$wpdb;
			$this->cat_table_name = $this->wpdb->prefix . 'ia_rd_categories';
			$this->item_table_name = $this->wpdb->prefix . 'ia_rd_items';
			
			if (is_admin())
			{
				add_action('admin_menu', array(&$this, 'add_menu'));
				//admin scripts/css
				add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue'));
			}
			else
			{
				//front scripts/css
				add_action('wp_enqueue_scripts', array(&$this, 'front_enqueue'));
			}

			add_shortcode('IA_Resource_Directory', array(&$this, 'the_directory'));

			// ajax edit actions
			add_action('wp_ajax_ia-rd-update-category',array($this,'admin_update_category'));
			add_action('wp_ajax_ia-rd-update-item',array($this,'admin_update_item'));

		}

		//***********************************************
		// add scripts and styles
		function admin_enqueue(){
			wp_register_style( 'ia-rd-admin-style', plugins_url('/styles/admin.css', __FILE__),'','','screen' );
			wp_enqueue_style( 'ia-rd-admin-style' );

			wp_register_script( 'ia_rd_editable', plugins_url('/js/jeditable.min.js', __FILE__) );
			wp_enqueue_script('ia_rd_editable');
			//wp_register_script( 'ia_rd_validate', plugins_url('/js/jquery.validate.min.js', __FILE__) );
			//wp_enqueue_script('ia_rd_validate');
		}

		function front_enqueue() {
			wp_register_style( 'ia-rd-style', plugins_url('/styles/directory.css', __FILE__),'','','screen' );
			wp_enqueue_style( 'ia-rd-style' );
		}

		//***********************************************
		//the public facing site function to display categories and items
		public function the_directory($atts)
		{
			extract( shortcode_atts( array(
				'cat_cols' => 1,
				'item_cols' => 1,
				'hilite_first_cat' => 'no'
			), $atts ) );

			//make sure the parameters are in the permitted ranges
			if (!in_array($cat_cols,array(1,2)))
			{
				$cat_cols = 1;
			}
			if (!in_array($item_cols,array(1,2)))
			{
				$item_cols = 1;
			}
			if (!in_array($hilite_first_cat,array('yes','no')))
			{
				$hilite_first_cat = 'no';
			}

			//check if a category has been passed to the page
			if (isset($_GET['rc']))
			{
				$catid = intval($_GET['rc']);
				return $this->get_resource_items_by_cat($catid, $item_cols);
			}
			else
			{
				return $this->get_categories($cat_cols, $hilite_first_cat);
			}
		}

		//front end display functions
		public function get_categories($cat_cols, $hilite_first_cat)
		{
		   $output = '';

		   $sql = 'SELECT * FROM ' . $this->cat_table_name . ' ORDER BY cat_display_order ASC';

		   $catrows = $this->wpdb->get_results($sql);

		   if ($this->wpdb->num_rows == 0)
		   {
			   $output = 'This Resource Directory is empty.';
		   }
		   else
		   {

			$cat_count = count($catrows);
	
			if ($cat_cols>1)
			{
				$col_break_pos = intval($cat_count / $cat_cols) + 1;
			}			

			$output = '<div id="iard_wrapper">';
			$output .= '<ul class="iard_categories">';
	
			$row_count = 1;
			foreach ($catrows as $row)
			{
				if ($row_count==1 && $hilite_first_cat == 'yes')  //emulate hilite
				{
					$output .= '<li class="ia_rd_category_hilite"><a href="?rc=' . $row->cat_id . '">'. $row->cat_name . '</a></li></ul><br style="clear:left" /><ul class="iard_categories">';
				}
				else
				{
					if (isset($col_break_pos) && $row_count % $col_break_pos == 0)
					{
						$output .= '</ul><ul class="iard_categories">';
						$output .= '<li><a href="?rc=' . $row->cat_id . '">'. $row->cat_name . '</a></li>';
					}
					else
					{
						$output .= '<li><a href="?rc=' . $row->cat_id . '">'. $row->cat_name . '</a></li>';
					}		
				}					
				
				$row_count++;
			}
			$output .= '</ul></div>';
		   }

		   return $output;
		}

		public function get_resource_items_by_cat($catid)
		{
			$cat_name = $this->wpdb->get_var($this->wpdb->prepare("SELECT cat_name FROM $this->cat_table_name WHERE cat_id=%d", $catid));

			$sql = 'SELECT * FROM ' . $this->item_table_name . ' WHERE item_cat=' . $catid;
			$results = $this->wpdb->get_results($sql);

			$output = '<div id="iard_wrapper"><h2>' . $cat_name . '</h2>';

			$output .= '<div class="iard_search_link"><a href="' . get_permalink() . '">Search again</a></div>';

			if ($this->wpdb->num_rows == 0)
			{
				$output .= 'No resources match your request.';
			}
			else
			{
				$output .= '<ul class="iard_items_list">';
				foreach($results as $row)
				{
					$output .= '<li><div class="iard_ritem_name">' . $row->item_name . '</div>
						<a href="' . $row->item_url . '" target="_blank">' . $row->item_url . '</a></li>';
				}
				$output .= '</ul></div>';
			}

			return $output;
		}

	   //***********************************************
	   // Admin Menu
	   public function add_menu()
	   {		   
		   add_menu_page( 'IA Resource Directory', 'Resource Dir.', 'manage_options', 'ia-resource-directory-categories', array(&$this, 'plugin_settings_categories'),'dashicons-portfolio','55');
		   add_submenu_page( 'ia-resource-directory-categories', 'Resource Directory Categories', 'Resource Categories', 'manage_options', 'ia-resource-directory-categories', array(&$this, 'plugin_settings_categories'));
		   add_submenu_page( 'ia-resource-directory-categories', 'Resource Directory Items', 'Resource Items', 'manage_options', 'ia-resource-directory-items', array(&$this, 'plugin_settings_items'));
	   } 
	   /**
		* Admin Menu Callbacks
		*/
	   public function plugin_settings_categories()
		{
			if (!current_user_can('manage_options'))
			{
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
			
			//check for POST if adding a new category
			if (isset($_POST['cat_name']))
			{
				if (!empty($_POST['cat_name']) && check_admin_referer('add-category','_nonce-add') )
				{
					//add the category to the db
					$catname = $_POST['cat_name'];
					$this->admin_add_category($catname);

					$flash = $this->admin_notice_message('Category Added', 'updated');
				}
				else die("Security check failure.");
			}
			else if ( isset($_GET['action']) && isset($_GET['id']) ) //check for GET if deleting a category
			{
				if ( $_GET['action']=='delete' && is_numeric($_GET['id']) )
				{
					$id = absint($_GET['id']);
					if (check_admin_referer('delete-category-' . $id))
					{
						$this->admin_delete_category($id);
						$flash = $this->admin_notice_message('Category Deleted', 'updated');
						//i'd really much rather redirect here to get rid of the nonce in the qs, but it appears we are too late in the render
					}
					else die("Security check failure.");
				}				
			}

			//now get the list of categories for this blog install
		   $cats = $this->admin_get_categories();

		   // Render the settings template
		   include(sprintf("%s/templates/menu_categories.php", dirname(__FILE__)));
	   }

	   public function plugin_settings_items()
	   {
		   if(!current_user_can('manage_options'))
		   {
			   wp_die(__('You do not have sufficient permissions to access this page.'));
		   }

		   //check if we are adding a new item
		   if (isset($_POST['action']) )
		   {
			   if ($_POST['action'] == 'additem' && check_admin_referer('add-resource','_nonce-a') )
			   {
				   //add the category to the db
					$itemname = $_POST['item_name'];
					$itemurl = $_POST['item_url'];
					$itemcat = absint($_POST['item_cat']);

					if (empty($itemname) || empty($itemurl) || $itemcat == 0)
					{
						$flash = $this->admin_notice_message('You must enter values for all fields.', 'error');
					}
					else
					{
						//check that the URL is well formed

						$newitem = array('name' => $itemname, 'url' => $itemurl, 'cat' => $itemcat);
						$this->admin_add_item($newitem);
						$flash = $this->admin_notice_message('Resource Added', 'updated');
					}				   
			   }
			}
			else if ( isset($_GET['action']) && isset($_GET['id']) ) //check for GET if deleting a resource
		    {
				if ( $_GET['action']=='delete' && is_numeric($_GET['id']) )
				{
					$id = absint($_GET['id']);
					if (check_admin_referer('delete-resource-' . $id))
					{
						$this->admin_delete_resource($id);
						$flash = $this->admin_notice_message('Resource Deleted', 'updated');						
					}
					else die("Security check failure.");
				}
		    }

			$items=$active_catid='';
			if (isset($_GET['cat']))
			{
				$active_catid = absint($_GET['cat']);
				$items = $this->admin_get_all_items($active_catid);
			}
			else
				$items = $this->admin_get_all_items();

		   $cats = $this->admin_get_categories();		   

		   // Render the settings template
		   include(sprintf("%s/templates/menu_items.php", dirname(__FILE__)));
	   }

	   //***********************************************
	   // db action functions

	   //get categories to display in admin
	   function admin_get_categories()
	   {
		   $sql = 'SELECT * FROM ' . $this->cat_table_name . ' ORDER BY cat_display_order ASC';

		   $catrows = $this->wpdb->get_results($sql);

		   $output = array();

		   foreach ($catrows as $row)
		   {
			   $output[] = array('id'=>$row->cat_id, 'name'=>$row->cat_name);
		   }

		   return $output;
	   }


	   function admin_add_category($catname)
		{
			$this->wpdb->show_errors();
			$today = date('Y-m-d h:i:s');
			$data = array('cat_name' => $catname, 'create_date' => $today);

			$this->wpdb->insert($this->cat_table_name, $data);
		}

		//edit a category via in place editing over AJAX call through jeditable
		function admin_update_category()
		{
			$this->wpdb->show_errors();

			$params = explode(':', $_POST['id']);

			if (count($params) != 2)
				die();

			$catid = $params[0];
			$fieldname = $params[1];

			if ($fieldname == 'name')
			{
				$newcatname = $_POST['value'];
				$data = array('cat_name' => $newcatname);
				$where = array('cat_id' => $catid);
				$this->wpdb->update($this->cat_table_name, $data, $where);
				echo $newcatname;
			}
			die();
		}

		function admin_delete_category($id)
		{
			if ($id == 0) die('Invalid parameters.');

			$where = array('cat_id'=>$id);
			$this->wpdb->delete($this->cat_table_name, $where);
			//wp_redirect(admin_url('admin.php?page=ia-resource-directory-categories&action=deleted'));
		}

		function admin_get_all_items($cat = null)
		{
			$sql = 'SELECT rgi.*, rgc.cat_name FROM ' . $this->item_table_name . ' as rgi INNER JOIN ' . $this->cat_table_name . ' rgc ON rgi.item_cat = rgc.cat_id ';

			if (!is_null($cat))
			{
				$sql .= ' WHERE item_cat= %d ORDER BY item_name ASC';
				$sql = $this->wpdb->prepare($sql,$cat);
			}
			else
			{
				$sql .= " ORDER BY item_name ASC";
			}

			$itemrows = $this->wpdb->get_results($sql);

			$output = array();		
			foreach ($itemrows as $row)
			{
				$output[] = array(
					'id'=>$row->item_id,
					'name'=>$row->item_name,
					'url'=>$row->item_url,
					'cat'=>$row->item_cat,
					'catname'=>$row->cat_name
				);
			}
			return $output;
		}

	   function admin_add_item($newitem)
	   {
			$this->wpdb->show_errors();
			$today = date('Y-m-d h:i:s');

			$url = $this->prep_url($newitem['url']);

			$data = array('item_name'=>$newitem['name'], 'item_url'=>$url, 'item_cat'=>$newitem['cat'], 'create_date'=>$today);

			$this->wpdb->insert($this->item_table_name, $data);
	   }

	   function admin_update_item()
		{
			$this->wpdb->show_errors();

			$params = explode(':', $_POST['id']);

			if (count($params) != 2)
				die('Invalid value');

			$itemid = absint($params[0]);
			$fieldname = $params[1];

			$newvalue =  $_POST['value'];
			$where = array('item_id'=>$itemid);

			switch($fieldname)
			{
				case "name":
					$data = array('item_name' => $newvalue);
					break;
				case "url":
					$data = array('item_url' => $this->prep_url($newvalue));
					break;
				case "cat":
					$catdata = explode(':',$newvalue);
					$data = array('item_cat' => $catdata[0]);
					$newvalue=$catdata[1];
					break;
				default:
					die('Inavlid value');
			}

			$this->wpdb->update($this->item_table_name, $data, $where);

			echo $newvalue;

			die();
		}

		function admin_delete_resource($id)
		{
			if ($id == 0) die('Invalid parameters.');

			$where = array('item_id'=>$id);
			$this->wpdb->delete($this->item_table_name, $where);
		}

	   function admin_notice_message($msg,$type)
	   {
		   $output = "<div class='$type'><p>$msg</p></div>";
		   return $output;
	   }

	   //***********************************************
		//helper functions

	   //make sure URL's have http:// scheme
		function prep_url($str = '')
		{
			if ($str == 'http://' OR $str == '')
			{
				return '';
			}

			$url = parse_url($str);

			if ( ! $url OR ! isset($url['scheme']))
			{
				$str = 'http://'.$str;
			}
			return $str;
		}


		//***********************************************
		//db setup 
				
		public static function install()
		{
			if ( ! current_user_can( 'activate_plugins' ) )
				return;

			global $wpdb;

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$cat_table_name = $wpdb->prefix . "ia_rd_categories";
			$item_table_name = $wpdb->prefix . "ia_rd_items";

			$sql = "CREATE TABLE IF NOT EXISTS $cat_table_name (
				cat_id smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
				cat_name varchar(100) NOT NULL,
				cat_name_slug varchar(100) DEFAULT NULL,
				cat_display_order smallint(5) UNSIGNED NOT NULL DEFAULT '0',
				create_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (cat_id)
			 );";

			dbDelta( $sql );			

			$sql = "CREATE TABLE IF NOT EXISTS $item_table_name (
				item_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				item_name varchar(200) NOT NULL,
				item_url varchar(200) DEFAULT NULL,
				item_cat smallint(5) UNSIGNED NOT NULL,
				create_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (item_id)
			 );";

			dbDelta( $sql );

			add_option( "ia_rd_db_version", '1.0' );
		}
	 }
 }


if (class_exists('IA_Resource_Directory'))
{
	new IA_Resource_Directory();

	register_activation_hook( __FILE__, array('IA_Resource_Directory','install'));

	//unistall moved to filed uninstall.php
}
?>
