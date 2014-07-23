<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
   exit();

// For Single site
if ( !is_multisite() )
{
	global $wpdb;

	$cat_table_name = $wpdb->prefix . "ia_rd_categories";
	$item_table_name = $wpdb->prefix . "ia_rd_items";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$sql = 'DROP TABLE IF EXISTS ' . $item_table_name;
	$wpdb->query( $sql );
	$sql = 'DROP TABLE IF EXISTS ' . $cat_table_name;
	$wpdb->query( $sql );

	delete_option('ia_rd_db_version');
}
// For Multisite
else
{

	global $wpdb;
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
    $original_blog_id = get_current_blog_id();

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	foreach ( $blog_ids as $blog_id )
    {
        switch_to_blog( $blog_id );

		$cat_table_name = $wpdb->prefix . "ia_rd_categories";
		$item_table_name = $wpdb->prefix . "ia_rd_items";

		$sql = 'DROP TABLE IF EXISTS ' . $item_table_name;
		$wpdb->query( $sql );
		$sql = 'DROP TABLE IF EXISTS ' . $cat_table_name;
		$wpdb->query( $sql );
		
        delete_option('ia_rd_db_version');
    }

    switch_to_blog( $original_blog_id );
}

?>