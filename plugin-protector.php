<?php
/**
 * @package Plugin Protector
 * @version 1.4
 */
/*
Plugin Name: Plugin Protector
Plugin URI: http://vandercar.net/wp/plugin-protector/
Description: Protects against inadvertant update and deletion of select plugins.
Author: Joshua Vandercar
Version: 1.4
Author URI: http://vandercar.net
*/

define( 'PPr_VERSION', '1.4' );
define( 'PPr_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'PPr_DIR_URL', plugin_dir_url( __FILE__ ) );

is_admin() ? require_once( PPr_DIR_PATH . 'wp-side-notice/class-wp-side-notice.php' ) : FALSE;
is_admin() ? Plugin_Protector::get_instance() : FALSE;

class Plugin_Protector {

	/*---------------------------------------------------------------------------------*
	 * Attributes
	 *---------------------------------------------------------------------------------*/

	/**
	 * Instance of this class.
	 *
	 * @since    1.2
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Side notices.
	 *
	 * @since    1.4
	 *
	 * @var      array
	 */
	protected $notices;

	/*---------------------------------------------------------------------------------*
	 * Consturctor / The Singleton Pattern
	 *---------------------------------------------------------------------------------*/

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.2
	 */
	private function __construct() {

		global $pagenow;

		// Set plugin protector notices
		$this->notices = new WP_Side_Notice( 'pp' );

		// check if plugin has updated and respond accordingly
		add_action( 'admin_init', array( $this, 'check_plugin_update' ) );

		// load activation notice to guide users to the next step
		add_action( 'admin_notices', array( $this, 'display_plugin_activation_message' ) );

		// add notices on plugin activation
		//register_activation_hook( PRESSGRAM_DIR_PATH . 'pressgram.php', array( $this, 'add_wpsn_notices' ) );

		// remove active plugin marker
		register_deactivation_hook( __FILE__, array( $this, 'remove_activation_marker' ) );

		// check current admin page
		if ( 'plugins.php' == $pagenow ) {
			
			// call to enqueue admin scripts and styles
			add_action( 'admin_enqueue_scripts', array( $this, 'add_stylesheets_and_javascript' ) );

			if ( ! class_exists( 'WP_List_Table' ) ) {
		    	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
			}

			require_once( PPr_DIR_PATH . 'class-pp-list-table.php' );

		}

		// call addition of custom column
		is_multisite() ? add_filter( 'manage_plugins-network_columns', array( $this, 'pp_add_customized_column' ) ) : add_filter( 'manage_plugins_columns', array( $this, 'pp_add_customized_column' ) );

		// call display for custom column
		add_action( 'manage_plugins_custom_column', array( $this, 'pp_manage_plugin_customized_column' ), 10, 2 );

		// add menu item
		is_multisite() ? add_action( 'network_admin_menu', array( $this, 'pp_add_plugins_link' ) ) : add_action( 'admin_menu', array( $this, 'pp_add_plugins_link' ) );

		// save settings at plugin.php?page=pp-protected
		add_action( 'admin_init', array( $this, 'pp_write_settings' ) );

		// intercept various plugin page actions
		add_action( 'check_admin_referer', array( $this, 'pp_intercept_action' ), 10, 2 );

		// determine notices to display
		is_multisite() ? add_action( 'network_admin_notices', array( $this, 'pp_notice' ) ) : add_action( 'admin_notices', array( $this, 'pp_notice' ) );


	} // end constructor

	/*---------------------------------------------------------------------------------*
	 * Public Functions
	 *---------------------------------------------------------------------------------*/

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.2
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		} // end if

		return self::$instance;

	} // end get_instance

	/**
	 * Displays a plugin message as soon as the plugin is activated.
	 *
	 * @since    1.2
	 */
	public function display_plugin_activation_message() {

		if ( ! get_option( 'pp_activated' ) ) {

			// Show the notice
			$html = '<div class="updated">';
				//$html .= '<a href="http://pressgr.am"><img src="' . PPr_DIR_URL . 'pressgram-logo.png" style="float: left; width: 2em; height: 2em; margin-right: 0.4em; margin-top: 0.4em" /></a>';
				$html .= '<p style="display: inline-block">';
					$html .= __( "<strong>Nice!</strong> You are ready to protect your plugins - <a href='plugins.php?page=pp-protected'>click here</a> to prevent updates and deletion of select plugins." );
				$html .= '</p>';
			$html .= '</div><!-- /.updated -->';

			echo $html;

			update_option( 'pp_activated', TRUE );

		} // end if

	} // end display_plugin_activation_message

	/**
	 * Deletes activation marker so it can be displayed when the plugin is reinstalled or reactivated
	 *
	 * @since    1.2
	 */
	public static function remove_activation_marker() {

		delete_option( 'pp_activated' );

	} // end remove_activation_marker

	/**
	 * Check for plugin update and updates notices
	 *
	 * @since    1.2
	 */
	public function check_plugin_update() {

		// if current version is 2.2.0 and previos is older, then transfer options
		(float) PPr_VERSION > (float) get_option( 'pp_db_version' ) ? $this->add_wpsn_notices() : FALSE;

	} // end check_plugin_update

	/**
	 * Define WP Side Notices for use in plugin
	 *
	 * @since    1.2
	 */
	public function add_wpsn_notices() {

		$side_notices = $this->notices;

		$pp_notices = array(
			'pp-info' => array(
				'name' => 'pp-info',
				'trigger' => TRUE,
				'time' => time() - 5,
				'dismiss' => '',
				'content' => '<a href="http://wordpress.org/plugins/plugin-protector">Plugin Protector</a> developed by <a href="http://vandercar.net/wp">UaMV</a>.',
				'style' => array( 'height' => '72px', 'color' => '#dd9701', 'icon' => 'f348' ),
				'location' => array( 'plugins.php' ),
				),
			'pp-support' => array(
				'name' => 'pp-support',
				'trigger' => TRUE,
				'time' => time() - 5,
				'dismiss' => '',
				'content' => 'Require assistance? Find (or offer) support in the <a href="http://wordpress.org/support/plugin/plugin-protector/">support forum</a>.',
				'style' => array( 'height' => '72px', 'color' => '#dd9701', 'icon' => 'f338' ),
				'location' => array( 'plugins.php' ),
				),
			'pp-give' => array(
				'name' => 'pp-give',
				'trigger' => TRUE,
				'time' => time() - 5,
				'dismiss' => '',
				'content' => 'How are we doing? <a href="http://wordpress.org/support/view/plugin-reviews/plugin-protector#postform">Review the plugin</a> or <a href="http://vandercar.net/wp">give a dollar</a> for every headache we\'ve averted.',
				'style' => array( 'height' => '72px', 'color' => '#dd9701', 'icon' => 'f313' ),
				'location' => array( 'plugins.php' ),
				)
			);


		// remove the old notices
		method_exists( 'WP_Side_Notice', 'remove' ) ? $side_notices->remove() : FALSE;
		
		// add each notice defined above
		foreach ( $pp_notices as $notice => $args ) {
			$side_notices->add( $args );
		}

		update_option( 'pp_db_version', PPr_VERSION );

	} // end display_notices

	/**
	 * Registers the plugin's administrative stylesheets and JavaScript
	 *
	 * @since    1.2
	 */
	public function add_stylesheets_and_javascript() {
		
		wp_enqueue_style( 'pp-style', PPr_DIR_URL . 'css/main.css', array(), PPr_VERSION, 'screen' );
		
	} // end add_stylesheets_and_javascript


	/**************************************
	* ADD PLUGIN PAGE CUSTOM COLUMN
	**************************************/

	function pp_add_customized_column( $plugins_columns ) {
		$plugins_columns['plugin_protected'] = (float) $GLOBALS['wp_version'] < 3.8 ? _x( 'Protection', 'column name' ) : _x( '<div class="dashicons dashicons-lock" style="font-size: 1.75em; margin-top: -2px;"></div>', 'column name' );
		return $plugins_columns;
	}

	/**************************************
	* PLUGIN PAGE CUSTOM COLUMN DATA
	**************************************/

	function pp_manage_plugin_customized_column( $column_name, $id ) {
		$pp_options = is_multisite() ? get_site_option( 'pp_settings' ) : get_option( 'pp_settings' );
		
		switch( $column_name ) {
			case 'plugin_protected':
				$pp_notes = isset ( $pp_options[ $id ]['notes'] ) ? esc_attr( $pp_options[ $id ]['notes'] ) : '';  // Get notes
				if ( isset ( $pp_options[ $id ]['protected'] ) ) {  // Set display for protected
					if ( $pp_options[ $id ]['protected'] === "1" ) {
						$display = '<div class="dashicons dashicons-lock" title="' . $pp_notes . '" style="font-size: 1.75em; margin-top: -2px;"></div>';
						$display .= (float) $GLOBALS['wp_version'] < 3.8 ? '<strong>Protected</strong>' : '';
					}
				} else {  // Set display for unprotected
					$display = '';
				}
				_e( $display, 'pp_domain' );
				break;
			default:
				break;
		}
	}

	/**************************************
	* ADD PROTECTED LINK TO PLUGIN MENU
	**************************************/

	function pp_add_plugins_link() {
		add_plugins_page( 'Protected Plugins', 'Protection', 'update_plugins', 'pp-protected', array( $this, 'pp_plugins_page' ) );
	}


	/**************************************
	* UPDATE PROTECTED PLUGINS DATA
	**************************************/

	function pp_write_settings() {
		if ( isset( $_GET['action'] ) && 'update' == $_GET['action'] && ! isset( $_GET['pp-wpsn-action'] ) ) {

			$pp_settings = $_POST['pp_settings'];

			foreach ( $pp_settings as $plugin => $protected_data ) {  // Prepare text field for database
				$pp_settings[ $plugin ]['notes'] = stripslashes( sanitize_text_field( $protected_data['notes'] ) );
			}
			is_multisite() ? update_site_option( 'pp_settings', $pp_settings ) : update_option( 'pp_settings', $pp_settings );

		}
	}

	/**************************************
	* DISPLAY PROTECTION PAGE USING WP_LIST_TABLE
	**************************************/

	function pp_plugins_page() {
	 
		$pp_table = new PPr_List_Table();  // Create PP_List_Table object :: begin page display ?>

		<div class="wrap">
			<?php
				
				$this->notices->display();
			?>
			<h2>Protected Plugins</h2>
			<?php echo (float) $GLOBALS['wp_version'] < 3.8 ? '<style type="text/css">.widefat #pp-protected.column-pp-protected {width: 6em;}</style>' : ''; ?>
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
							wp_redirect( self_admin_url( 'plugins.php?pp-action=notify-upgrade&pp-plugin=' . urlencode( $plugin ) ) );  
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
						wp_redirect( self_admin_url( 'plugins.php?pp-action=notify-update&plugins=' . urlencode( join( ',', $plugins ) ) . '&pp-plugin=' . urlencode( $pp_plugins ) ) );
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
						wp_redirect( self_admin_url( 'update-core.php' ) );
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
						wp_redirect( self_admin_url( 'update-core.php?pp-action=notify-update&plugins=' . urlencode( join( ',', $plugins ) ) . '&pp-plugin=' . urlencode( $pp_plugins ) ) );
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
								$this->pp_showNotice( $message, TRUE );
							}
						}
					}
				}
				break;
			default:
				break;
		}		

	}
	
	/**************************************
	* DETERMINE NOTICES TO SHOW AND DISPLAY
	**************************************/

	function pp_notice() {
		global $pagenow;  // Get WP global for current page

		if ( 'plugin-editor.php' == $pagenow ) {  // Having entered the plugin editor, do this ...
			wp_reset_vars( array( 'plugin' ) );  // reset plugin var

			global $plugin, $file;

			! empty ( $plugin ) ? update_option( 'pp_currently_editing', $plugin ) : FALSE ;

			if ( empty ( $_GET['file'] ) && empty ( $plugin ) ) {		
				$plugins = get_plugins();
				$plugin = array_keys($plugins);
				$plugin = $plugin[0];
				 update_option( 'pp_currently_editing', $plugin );
			} elseif ( empty( $plugin ) ) {
				$plugin = get_option( 'pp_currently_editing' );
			}
			
			$pp_options = get_option( 'pp_settings' );

			if ( isset ( $pp_options[ $plugin ]['protected'] ) ) {
				if ( $pp_options[ $plugin ]['protected'] ) {  // If requested plugin is protected, redirect and trigger notification with pp-action=notify-upgrade :: pass plugin through URL
					$updated = isset ( $_GET['a'] ) ? TRUE : FALSE;
					// Define content of notice
					if ( $updated ) {  // If edit has been committed
						$message = 'ALERT: Protection of <code>' . $plugin . '</code> has been overriden. Any further edits committed below to the file (<code>' . $file . '</code>) will override protection!';
						$message .= '' != $pp_options[ $plugin ]['notes'] ? "<blockquote>Note: " . esc_html( $pp_options[ $plugin ]['notes'] ) . "</blockquote>" : FALSE;
						
						$this->pp_showNotice( $message, TRUE );
					} else {
						$message = 'CAUTION: This plugin (<code>' . $plugin . '</code>) has been marked as protected. Any edits committed below to the file (<code>' . $file . '</code>) will override this protection!';
						$message .= '' != $pp_options[ $plugin ]['notes'] ? "<blockquote>Note: " . esc_html( $pp_options[ $plugin ]['notes'] ) . "</blockquote>" : FALSE;
						
						$this->pp_showNotice( $message, TRUE );
					}
				}
			}				
		}

		if ( isset( $_GET['pp-action'] ) ) {
			global $pagenow;  // Get WP global for current page
			$pp_options = get_option( 'pp_settings' );

			switch ( $_GET['pp-action'] ) {  // Act on certain plugin protector actions
				case 'notify-upgrade':  // Having intercepted a single plugin upgrade, do this ...
					$pp_plugin = $_GET['pp-plugin'];

					// Define content of notice
					$message = 'This plugin (<code>' . $pp_plugin . '</code>) has been protected. To override protection and initiate update, click <a href="' . wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $pp_plugin . '&pp-action=override' ), 'upgrade-plugin_' . $pp_plugin ) . '">here</a>.';
					$message .= '' != $pp_options[ $pp_plugin ]['notes'] ? "<blockquote>Note: " . esc_html( $pp_options[ $pp_plugin ]['notes'] ) . "</blockquote>" : FALSE;
					
					$this->pp_showNotice( $message, TRUE );
					break;
				case 'notify-update':  // Having intercepted a bulk plugin update, do this ...
					$pp_plugins = explode( ',', $_GET['pp-plugin'] );  // Set protected plugin array

					foreach ( $pp_plugins as $pp_plugin ) {  // Loop through protected plugins :: define content of notice and call
						$message = 'The plugin <code>' . $pp_plugin . '</code> is protected.';
						$message .= '' != $pp_options[ $pp_plugin ]['notes'] ? "<blockquote>Note: " . esc_html( $pp_options[ $pp_plugin ]['notes'] ) . "</blockquote>" : FALSE;
						$this->pp_showNotice( $message, TRUE );
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
					
					$this->pp_showNotice( 'To override protection and initiate update, click <a href="' . wp_nonce_url( self_admin_url( $pagenow .'?action=' . $action . '&plugins=' . $_GET['plugins'] . '&pp-action=override' ), $pp_nonce ) . '">here</a>.', TRUE );
					break;
				case 'override':  // Having overridden a plugin protector warning, do this ...
					$this->pp_showNotice( 'You have overridden plugin protection. Initializing update.' );
					break;
				default:
					break;
			}
		}
	}

	/**************************************
	* ASSEMBLE NOTICES
	**************************************/

	function pp_showNotice( $message, $errormsg = false ) {
		echo $errormsg ? '<div id="message" class="error">' : '<div id="message" class="updated fade">';
		echo '<p><strong>';
		_e( $message );
		echo '</strong></p></div>';
	}

}

?>