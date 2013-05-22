<?php
/**
 * @package Plugin Protector
 * @version 0.1
 */
/*
Plugin Name: Plugin Protector
Plugin URI: http://wmpl.org/blogs/vandercar/wp/plugin-protector/
Description: Protects against inadvertant update and deletion of select plugins.
Author: Joshua Vandercar
Version: 0.1
Author URI: http://wmpl.org/blogs/vandercar/
*/

/**************************************
* ADD PLUGIN PAGE CUSTOM COLUMN
**************************************/

function pp_add_customized_column( $plugins_columns ) {
	$plugins_columns['plugin_protected'] = _x( 'Protection', 'column name' );
	return $plugins_columns;
}
add_filter( 'manage_plugins_columns', 'pp_add_customized_column' );

/**************************************
* PLUGIN PAGE CUSTOM COLUMN DATA
**************************************/

function pp_manage_plugin_customized_column( $column_name, $id ) {
	$pp_options = get_option( 'pp_settings' );
	
	switch( $column_name ) {
		case 'plugin_protected':
			$pp_notes = isset ( $pp_options[ $id ]['notes'] ) ? esc_attr( $pp_options[ $id ]['notes'] ) : '';  // Get notes
			if ( isset ( $pp_options[ $id ]['protected'] ) ) {  // Set display for protected
				if ( $pp_options[ $id ]['protected'] === "1" ) {
					$display = '<span style="color:#090;" title="' . $pp_notes . '">Protected</span>';
				}
			} else {  // Set display for unprotected
				$display = '<span style="color:#900;">None</span>';
			}
			echo $display;
			break;
		default:
			break;
	}
}
add_action( 'manage_plugins_custom_column', 'pp_manage_plugin_customized_column', 10, 2 );

/**************************************
* ADD PROTECTED LINK TO PLUGIN MENU
**************************************/

function pp_add_plugins_link() {
	add_plugins_page( 'Protected Plugins', 'Protection', 'activate_plugins', 'pp-protected', 'pp_plugins_page' );
}
add_action( 'admin_menu', 'pp_add_plugins_link' );

/**************************************
* UPDATE PROTECTED PLUGINS DATA
**************************************/

function pp_write_settings() {
	if ( isset( $_GET['action'] ) && 'update' == $_GET['action'] ) {
		$pp_settings = $_POST['pp_settings'];

		foreach ( $pp_settings as $plugin => $protected_data ) {  // Prepare text field for database
			$pp_settings[ $plugin ]['notes'] = stripslashes( sanitize_text_field( $protected_data['notes'] ) );
		}
		update_option( 'pp_settings', $pp_settings );
	}
}
add_action( 'admin_init', 'pp_write_settings' );

/**************************************
* DISPLAY PROTECTION PAGE USING WP_LIST_TABLE
**************************************/

function pp_plugins_page() {

	if( ! class_exists( 'WP_List_Table' ) ) {
    	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}

	class PP_List_Table extends WP_List_Table {
		var $pp_options, $plugin_data, $pp_data;

		function __construct() {
			parent::__construct( array(
				'singular'  => 'protected-plugin',
				'plural'    => 'protected-plugins',
				'ajax'      => false
				) );

			$this->pp_options = get_option( 'pp_settings' );
			$this->plugin_data = get_plugins();  // Get all plugin data
			$this->pp_data = array();  // Set array for table data

			$i = 1;
			foreach ( $this->plugin_data as $key => $value ) {  // Loop through plugin array and set cell data per column
				$pp_single = array(
					'ID' 			=> $i,
					'pluginname'	=> $value['Name'],
					'protected'		=> $this->protected_checkbox( $key,$value ),  // Call function to set cell with checkbox
					'notes'			=> $this->protected_notes( $key,$value ),  // Call function to set cell with notes
					);
				array_push($this->pp_data, $pp_single);
				$i++;
			}
		}

		function get_columns(){  // Define column headers
		  $columns = array(
		    'pluginname'	=> 'Plugin Name',
		    'protected'		=> 'Protected',
		    'notes'			=> 'Note',
		  );
		  return $columns;
		}
		 
		function prepare_items() {  // Prepares data for WP_List_Table
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = array();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			$this->items = $this->pp_data;
		}

		function column_default( $item, $column_name ) {
		  switch( $column_name ) { 
		    case 'pluginname':
		    case 'protected':
		      return $item[ $column_name ];
		    case 'notes':
		      return $item[ $column_name ];
		    default:
		      return print_r( $item, true );  //Show the whole array for troubleshooting purposes
		  }
		}

		function protected_checkbox( $key, $plugin ) {
			$plugin_name = sanitize_title( $plugin['Name'] );
			if ( isset( $this->pp_options[ $key ]['protected'] ) ) {  // Set variables for protected
				if ( $this->pp_options[ $key ]['protected'] === "1" ) {
					$selected = 'checked="checked"';
					$display = '<span style="color:#090;">Protected</span>';
				}
			} else {  // Set variables for unprotected
				$selected = '';
				$display = '<span style="color:#900;">Not Protected</span>';
			}
			
			ob_start();	 // Display checkbox field :: will write to pp_settings ?>
				<input id="pp_<?php echo $plugin_name ?>_protected" type="checkbox" name="pp_settings[<?php echo $key ?>][protected]" value="1" <?php echo $selected; ?> />
				<label class="description" for="pp_settings[<?php echo $key ?>][protected]"><?php _e($display, 'pp_domain'); ?></label><?php
			return ob_get_clean();
		}

		function protected_notes( $key, $plugin ) {
			$plugin_name = sanitize_title( $plugin['Name'] );
			if ( isset( $this->pp_options[ $key ]['notes'] ) ) {  // Set variables if notes exist
				$notes = esc_textarea( $this->pp_options[ $key ]['notes'] );
			} else {  // Set variables if notes do not exist
				$notes = '';
			}

			ob_start();	 // Display notes field :: will write to pp_settings ?>
			<input id="pp_<?php echo $plugin_name ?>_notes" type="text" name="pp_settings[<?php echo $key ?>][notes]" value="<?php echo esc_attr( $notes ); ?>" style="width:95%" /><?php
			return ob_get_clean();
		}
	}
 
	$pp_table = new PP_List_Table();  // Create PP_List_Table object :: begin page display ?>

	<div class="wrap">
		<h2>Protected Plugins</h2>
		<form method="post" action="plugins.php?page=pp-protected&action=update" class="pp-admin">
			<?php
				$pp_table->prepare_items();
				$pp_table->display();
			?>
			<input type="submit" class="button-primary" value="<?php _e('Save Plugin Protections'); ?>" action="update" />
		</form>
	</div><?php
}

/**************************************
* INTERCEPT UPDATE & DELETE ACTIONS
**************************************/

function pp_intercept_action( $referer, $query_arg ) {  // $referer = nonce-action
	global $pagenow, $action;  // Grab WP globals for current page and current action

	switch ( $action ) {  // Intercept and redirect on certain page/action combinations
		case 'upgrade-plugin':  // Intercept single plugin updates from update now link
			$pp_action = isset( $_GET['pp-action'] ) ? $_GET['pp-action'] : 'checking-single';  // Check for pp-action
			
			if ( ('override' != $pp_action ) && ! isset( $_POST['password'] ) ) {  // If override not triggered, proceed with intercept
				$pp_options = get_option( 'pp_settings' );
			
				$plugin = $_GET['plugin'];
				if ( $pp_options[ $plugin ]['protected'] ) {  // If requested plugin is protected, intercept
					wp_redirect( admin_url( 'plugins.php?pp-action=notify-upgrade&pp-plugin=' . urlencode( $plugin ) ) );  // Redirect and trigger notification with pp-action=notify-upgrade
					exit;
				}
			}
			break;
		case 'update-selected': // Intercept plugin updates from bulk actions
			$pp_action = isset($_GET['pp-action']) ? $_GET['pp-action'] : 'checking-bulk';
			if ( 'override' != $pp_action && 'bulk-update-plugins' != $referer && ! isset( $_POST['password'] ) ) {
				$pp_options = get_option( 'pp_settings' );

				if ( isset( $_GET['plugins'] ) )
					$plugins = explode( ',', $_GET['plugins'] );
				elseif ( isset( $_POST['checked'] ) )
					$plugins = (array) $_POST['checked'];
				else
					$plugins = array();

				$pp_plugins = '';

				foreach ( (array) $plugins as $plugin) {
					if ( $pp_options[ $plugin ]['protected'] && ( 'override' != $pp_action ) ) {
						$pp_plugins .= ',' . $plugin;
					}
				}
				$pp_plugins = substr( $pp_plugins, 1 );

				if ( '' != $pp_plugins ) {
					wp_redirect( admin_url( 'plugins.php?pp-action=notify-update&plugins=' . urlencode( join( ',', $plugins ) ) . '&pp-plugin=' . urlencode( $pp_plugins ) ) );
					exit;
				}
			}
			break;
		case 'do-plugin-upgrade': // Updating from WP Updates page
			$pp_action = isset( $_GET['pp-action'] ) ? $_GET['pp-action'] : 'checking-bulk-core';
			if ( 'override' != $pp_action && 'bulk-update-plugins' != $referer ) {
				$pp_options = get_option( 'pp_settings' );

				if ( isset( $_GET['plugins'] ) ) {
					$plugins = explode( ',', $_GET['plugins'] );
				} elseif ( isset( $_POST['checked'] ) ) {
					$plugins = (array) $_POST['checked'];
				} else {
					wp_redirect( admin_url( 'update-core.php' ) );
					exit;
				}

				$pp_plugins = '';

				foreach ( (array) $plugins as $plugin ) {
					if ( $pp_options[ $plugin ]['protected'] && ( 'override' != $pp_action ) ) {
						$pp_plugins .= ',' . $plugin;
					}
				}
				$pp_plugins = substr( $pp_plugins, 1 );

				if ( '' != $pp_plugins ) {
					wp_redirect( admin_url( 'update-core.php?pp-action=notify-update&plugins=' . urlencode( join( ',', $plugins ) ) . '&pp-plugin=' . urlencode( $pp_plugins ) ) );
					exit;
				}
			}
			break;
		case 'delete-selected': // Deleting from link or bulk actions
			if ( ! isset( $_REQUEST['verify-delete'] ) ) {
				$pp_options = get_option( 'pp_settings' );

				$plugins = isset( $_REQUEST['checked'] ) ? (array) $_REQUEST['checked'] : array();

				foreach ( (array) $plugins as $plugin) {
					if ( $pp_options[ $plugin ]['protected'] ) {
						$message = 'Caution: The plugin <code>' . $plugin . '</code> has been marked as protected!';
						$message .= '' != $pp_options[ $plugin ]['notes'] ? "<blockquote>Note: " . esc_html( $pp_options[ $plugin ]['notes'] ) . "</blockquote>" : FALSE;
						pp_showNotice( $message, TRUE );
					}
				}
			}
			break;
		default:
			
			break;
	}		

}
add_action( 'check_admin_referer', 'pp_intercept_action', 10, 2 );


function pp_notice() {
		
	if ( isset( $_GET['pp-action'] ) ) {
		global $pagenow;
		$pp_options = get_option( 'pp_settings' );

		switch ( $_GET['pp-action'] ) {
			case 'notify-upgrade':
				$pp_plugin = $_GET['pp-plugin'];
				$message = 'This plugin (<code>' . $pp_plugin . '</code>) has been protected. To override protection and initiate update, click <a href="' . wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $pp_plugin . '&pp-action=override' ), 'upgrade-plugin_' . $pp_plugin ) . '">here</a>.';
				$message .= '' != $pp_options[ $pp_plugin ]['notes'] ? "<blockquote>Note: " . esc_html( $pp_options[ $pp_plugin ]['notes'] ) . "</blockquote>" : FALSE;
				pp_showNotice( $message, TRUE );
				break;
			case 'notify-update':
				$pp_plugins = explode( ',', $_GET['pp-plugin'] );
				foreach ( $pp_plugins as $pp_plugin ) {
					$message = 'The plugin <code>' . $pp_plugin . '</code> is protected.';
					$message .= '' != $pp_options[ $pp_plugin ]['notes'] ? "<blockquote>Note: " . esc_html( $pp_options[ $pp_plugin ]['notes'] ) . "</blockquote>" : FALSE;
					pp_showNotice( $message, TRUE );
				}
				'plugins.php' == $pagenow ? $pp_nonce = 'bulk-plugins' : FALSE;
				'update-core.php' == $pagenow ? $pp_nonce = 'upgrade-core' : FALSE;
				'plugins.php' == $pagenow ? $action = 'update-selected' : FALSE;
				'update-core.php' == $pagenow ? $action = 'do-plugin-upgrade' : FALSE;
				pp_showNotice( 'To override protection and initiate update, click <a href="' . wp_nonce_url( self_admin_url( $pagenow .'?action=' . $action . '&plugins=' . $_GET['plugins'] . '&pp-action=override' ), $pp_nonce ) . '">here</a>.', TRUE );
				break;
			case 'override':
				pp_showNotice( 'You have overridden plugin protection. Initializing update.' );
				break;
			default:
				break;
		}
	}
}
add_action( 'admin_notices', 'pp_notice' );

function pp_showNotice( $message, $errormsg = false )
{
	if ( $errormsg )
		echo '<div id="message" class="error">';
	else
		echo '<div id="message" class="updated fade">';

	echo '<p><strong>' . $message . '</strong></p></div>';
} 
?>