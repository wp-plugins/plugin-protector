<?php
/**
 * @package Plugin Protector
 * @version 0.3
 */
/*
Plugin Name: Plugin Protector
Plugin URI: http://wmpl.org/blogs/vandercar/wp/plugin-protector/
Description: Protects against inadvertant update and deletion of select plugins.
Author: Joshua Vandercar
Version: 0.3
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
			_e( $display, 'pp_domain' );
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
				<label class="description" for="pp_settings[<?php echo $key ?>][protected]"><?php _e( $display, 'pp_domain' ); ?></label><?php
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
				if ( isset ( $pp_options[ $plugin ]['protected'] ) ) {
					if ( $pp_options[ $plugin ]['protected'] ) {  // If requested plugin is protected, redirect and trigger notification with pp-action=notify-upgrade :: pass plugin through URL
						wp_redirect( admin_url( 'plugins.php?pp-action=notify-upgrade&pp-plugin=' . urlencode( $plugin ) ) );  
						exit;
					}
				}
			}
			break;
		case 'update-selected': // Intercept plugin updates from bulk actions
			$pp_action = isset($_GET['pp-action']) ? $_GET['pp-action'] : 'checking-bulk';  // Check for pp-action

			if ( 'override' != $pp_action && 'bulk-update-plugins' != $referer && ! isset( $_POST['password'] ) ) {  // If override not triggered, proceed with intercept
				$pp_options = get_option( 'pp_settings' );

				// Get selected plugins
				if ( isset( $_GET['plugins'] ) )
					$plugins = explode( ',', $_GET['plugins'] );
				elseif ( isset( $_POST['checked'] ) )
					$plugins = (array) $_POST['checked'];
				else
					$plugins = array();

				$pp_plugins = '';

				foreach ( (array) $plugins as $plugin) {  // Loop through selected plugins :: if protected, add to protected string
					if ( isset ( $pp_options[ $plugin ]['protected'] ) ) {
						if ( $pp_options[ $plugin ]['protected'] && ( 'override' != $pp_action ) ) {
							$pp_plugins .= ',' . $plugin;
						}
					}
				}
				$pp_plugins = substr( $pp_plugins, 1 );  // Remove starting comma

				if ( '' != $pp_plugins ) {  // If any selected plugins are protected, redirect and trigger notification with pp-action=notify-update :: pass plugins through URL
					wp_redirect( admin_url( 'plugins.php?pp-action=notify-update&plugins=' . urlencode( join( ',', $plugins ) ) . '&pp-plugin=' . urlencode( $pp_plugins ) ) );
					exit;
				}
			}
			break;
		case 'do-plugin-upgrade': // Updating from WP Updates page
			$pp_action = isset( $_GET['pp-action'] ) ? $_GET['pp-action'] : 'checking-bulk-core';  // Check for pp-action
			
			if ( 'override' != $pp_action && 'bulk-update-plugins' != $referer ) {  // If override not triggered, proceed with intercept
				$pp_options = get_option( 'pp_settings' );

				// Get selected plugins
				if ( isset( $_GET['plugins'] ) ) {
					$plugins = explode( ',', $_GET['plugins'] );
				} elseif ( isset( $_POST['checked'] ) ) {
					$plugins = (array) $_POST['checked'];
				} else {
					wp_redirect( admin_url( 'update-core.php' ) );
					exit;
				}

				$pp_plugins = '';

				foreach ( (array) $plugins as $plugin ) {  // Loop through selected plugins :: if protected add to protected string
					if ( isset ( $pp_options[ $plugin ]['protected'] ) ) {
						if ( $pp_options[ $plugin ]['protected'] && ( 'override' != $pp_action ) ) {
							$pp_plugins .= ',' . $plugin;
						}
					}
				}
				$pp_plugins = substr( $pp_plugins, 1 );  // Remove starting comma

				if ( '' != $pp_plugins ) {  // If any selected plugins are protected, redirect and trigger notification with pp-action=notify-update :: pass plugins through URL
					wp_redirect( admin_url( 'update-core.php?pp-action=notify-update&plugins=' . urlencode( join( ',', $plugins ) ) . '&pp-plugin=' . urlencode( $pp_plugins ) ) );
					exit;
				}
			}
			break;
		case 'delete-selected': // Deleting from link or bulk actions
			if ( ! isset( $_REQUEST['verify-delete'] ) ) {  // If verify delete has not been triggered, intercept
				$pp_options = get_option( 'pp_settings' );

				$plugins = isset( $_REQUEST['checked'] ) ? (array) $_REQUEST['checked'] : array();  // Get selected plugins

				foreach ( (array) $plugins as $plugin) {  // Loop through selected plugins :: if protected, dipsplay notification
					if ( isset ( $pp_options[ $plugin ]['protected'] ) ) {
						if ( $pp_options[ $plugin ]['protected'] ) {
							$message = 'Caution: The plugin <code>' . $plugin . '</code> has been marked as protected!';
							$message .= '' != $pp_options[ $plugin ]['notes'] ? "<blockquote>Note: " . esc_html( $pp_options[ $plugin ]['notes'] ) . "</blockquote>" : FALSE;
							pp_showNotice( $message, TRUE );
						}
					}
				}
			}
			break;
		default:
			break;
	}		

}
add_action( 'check_admin_referer', 'pp_intercept_action', 10, 2 );

/**************************************
* DETERMINE NOTICES TO SHOW AND DISPLAY
**************************************/

function pp_notice() {
		
	if ( isset( $_GET['pp-action'] ) ) {
		global $pagenow;  // Get WP global for current page
		$pp_options = get_option( 'pp_settings' );

		switch ( $_GET['pp-action'] ) {  // Act on certain plugin protector actions
			case 'notify-upgrade':  // Having intercepted a single plugin upgrade, do this ...
				$pp_plugin = $_GET['pp-plugin'];

				// Define content of notice
				$message = 'This plugin (<code>' . $pp_plugin . '</code>) has been protected. To override protection and initiate update, click <a href="' . wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $pp_plugin . '&pp-action=override' ), 'upgrade-plugin_' . $pp_plugin ) . '">here</a>.';
				$message .= '' != $pp_options[ $pp_plugin ]['notes'] ? "<blockquote>Note: " . esc_html( $pp_options[ $pp_plugin ]['notes'] ) . "</blockquote>" : FALSE;
				
				pp_showNotice( $message, TRUE );
				break;
			case 'notify-update':  // Having intercepted a bulk plugin update, do this ...
				$pp_plugins = explode( ',', $_GET['pp-plugin'] );  // Set protected plugin array

				foreach ( $pp_plugins as $pp_plugin ) {  // Loop through protected plugins :: define content of notice and call
					$message = 'The plugin <code>' . $pp_plugin . '</code> is protected.';
					$message .= '' != $pp_options[ $pp_plugin ]['notes'] ? "<blockquote>Note: " . esc_html( $pp_options[ $pp_plugin ]['notes'] ) . "</blockquote>" : FALSE;
					pp_showNotice( $message, TRUE );
				}

				switch ( $pagenow ) {  // Set nonce and action depending on page
					case 'plugins.php':
						$pp_nonce = 'bulk-plugins';
						$action = 'update-selected';
						break;
					case 'update-core.php':
						$pp_nonce = 'upgrade-core';
						$action = 'do-plugin-upgrade';
					default:
						break;
				}
				
				pp_showNotice( 'To override protection and initiate update, click <a href="' . wp_nonce_url( self_admin_url( $pagenow .'?action=' . $action . '&plugins=' . $_GET['plugins'] . '&pp-action=override' ), $pp_nonce ) . '">here</a>.', TRUE );
				break;
			case 'override':  // Having overridden a plugin protector warning, do this ...
				pp_showNotice( 'You have overridden plugin protection. Initializing update.' );
				break;
			default:
				break;
		}
	}
}
add_action( 'admin_notices', 'pp_notice' );

/**************************************
* ASSEMBLE NOTICES
**************************************/

function pp_showNotice( $message, $errormsg = false )
{
	if ( $errormsg )
		echo '<div id="message" class="error">';
	else
		echo '<div id="message" class="updated fade">';

	echo '<p><strong>';
	_e( $message, 'pp_domain' );
	echo '</strong></p></div>';
}?>