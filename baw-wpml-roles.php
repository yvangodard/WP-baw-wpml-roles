<?php
/*
Plugin Name: WPML par rôles
Description: Permet de choisir quels rôles peuvent ou non voir la colonnes et la metabox WPML
Author: Julio Potier
Author URI: http://boiteaweb.fr
Version: 1.0
Licence: GPLv2
*/

add_action( 'admin_menu', 'bawwpml_create_menu' );
function bawwpml_create_menu()
{
	add_options_page( 'WPML par Rôles', 'WPML par Rôles', 'manage_options', 'wpml_roles', 'bawwpml_settings_page' );
	register_setting( 'bawwpml_settings', 'bawwpml' );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bawwpml_settings_action_links' );
function bawwpml_settings_action_links( $links )
{
	array_unshift( $links, '<a href="' . admin_url( 'options-general.php?page=wpml_roles' ) . '">' . __( 'Settings' ) . '</a>' );
	return $links;
}

function bawwpml_settings_page()
{
	add_settings_section( 'bawwpml_settings_page', 'Rôles et actions', '__return_false', 'bawwpml_settings_1' );
		add_settings_field( 'bawwpml_field_roles', false, 'bawwpml_field_roles', 'bawwpml_settings_1', 'bawwpml_settings_page' );
?>
	<style>th[scope="row"]{display:none}</style>
	<div class="wrap">
		<h2>WPML par Rôles</h2>
		<form action="options.php" method="post">
			<?php settings_fields( 'bawwpml_settings' ); ?>
			<?php submit_button(); ?>
			<?php do_settings_sections( 'bawwpml_settings_1' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
<?php
}

function bawwpml_field_roles() {
	global $current_user;
	$roles = get_editable_roles();
	unset( $roles['subscriber'] );
	$bawwpml = (array) get_option( 'bawwpml' );
	foreach ($roles as $id => $role) {
		echo'
		<div class="postbox" style="width:200px;float:left;margin-right:10px">
		<h3 style="padding:5px"><span>'.translate_user_role( $role['name']).'</span></h3>
		<div class="inside">';
		$cpts = get_post_types( array( 'public'=>true, 'show_ui'=>true ), 'objects' );
		unset( $cpts['attachment'] );
		foreach ($cpts as $name => $cpt) {
			echo '<h4>'.$cpt->labels->name.'</h4>';
			echo '<label><input type="checkbox" '.checked( isset( $bawwpml['roles'][ $id ][ $name ]['col'] ), true, false ).' name="bawwpml[roles]['.$id.']['.$name.'][col]" value="1" /> Masquer la colonne langues</label><br />';
			echo '<label><input type="checkbox" '.checked( isset( $bawwpml['roles'][ $id ][ $name ]['box'] ), true, false ).' name="bawwpml[roles]['.$id.']['.$name.'][box]" value="1" /> Masquer la metabox langues</label><br />';
		}
		echo '
		<div class="clear"></div>
		</div>

		</div>
		</div>';
	}
}

function get_current_user_role() {
	global $current_user;
	$user = new WP_User( $current_user->ID );
	$role = reset( $user->roles );
	return $role ? $role : '';
}

add_action( 'load-edit.php', 'hack_configure_custom_column' );
add_action( 'load-edit-pages.php', 'hack_configure_custom_column' );
function hack_configure_custom_column() {
	global $sitepress, $current_user;
	$bawwpml = get_option( 'bawwpml' );
	$post_type = $GLOBALS['typenow']; 
	if ( isset( $bawwpml['roles'][ get_current_user_role() ][ $post_type ]['col'] ) ) {
		remove_filter( 'manage_' . $post_type . 's_columns', array( $sitepress, 'add_posts_management_column' ) );
		remove_filter( 'manage_' . $post_type . '_posts_columns', array( $sitepress, 'add_posts_management_column' ) ); 
		remove_action( 'manage_' . $post_type . 's_custom_column', array( $sitepress, 'add_content_for_posts_management_column' ) );
		remove_action( 'admin_print_scripts', array( $sitepress, '__set_posts_management_column_width' ) ); 
	}

}

add_action( 'admin_head-post.php', 'hack_remove_meta_box', 11 );
add_action( 'admin_head-post-new.php', 'hack_remove_meta_box', 11 );
function hack_remove_meta_box() {
	global $typenow, $sitepress, $current_user;
	$bawwpml = get_option( 'bawwpml' );
	if ( isset( $bawwpml['roles'][ get_current_user_role() ][ $typenow ]['box'] ) ) {
		remove_action( 'admin_head', array( $sitepress, 'post_edit_language_options' ) );
	}
}