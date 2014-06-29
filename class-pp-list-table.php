<?php
class PPr_List_Table extends WP_List_Table {
	var $pp_options, $plugin_data, $pp_data;

	function __construct() {
		parent::__construct( array(
			'singular'  => 'protected-plugin',
			'plural'    => 'protected-plugins',
			'ajax'      => false
			) );

		$this->pp_options = is_multisite() ? get_site_option('pp_settings') : get_option( 'pp_settings' );
		$this->plugin_data = get_plugins();  // Get all plugin data
		$this->pp_data = array();  // Set array for table data

		$i = 1;
		foreach ( $this->plugin_data as $key => $value ) {  // Loop through plugin array and set cell data per column
			$pp_single = array(
				'ID' 			=> $i,
				'pp-pluginname'	=> $value['Name'],
				'pp-protected'		=> $this->protected_checkbox( $key,$value ),  // Call function to set cell with checkbox
				'notes'			=> $this->protected_notes( $key,$value ),  // Call function to set cell with notes
				);
			array_push($this->pp_data, $pp_single);
			$i++;
		}
	}

	function get_columns(){  // Define column headers
	  $columns = array(
	    'pp-protected'	=> (float) $GLOBALS['wp_version'] < 3.8 ? 'Protection' : '<div class="dashicons dashicons-lock" style="font-size: 1.75em; margin-top: -2px;"></div>',
	    'pp-pluginname'	=> 'Plugin Name',
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
	    case 'pp-pluginname':
	    case 'pp-protected':
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
				$display = (float) $GLOBALS['wp_version'] < 3.8 ? '<span style="color:#090"><strong>Protected</strong></span>' : '<div class="dashicons dashicons-lock pp-locked" style="font-size: 2.25em;"></div>';
			}
		} else {  // Set variables for unprotected
			$selected = '';
			$display = (float) $GLOBALS['wp_version'] < 3.8 ? '<span style="color:#900">None</span>' : '<div class="dashicons dashicons-lock pp-unlocked" style="font-size: 2.25em;"></div>';
		}
		
		ob_start();	 // Display checkbox field :: will write to pp_settings ?>
			<label class="description" for="pp_settings[<?php echo $key ?>][protected]">
				<input id="pp_settings[<?php echo $key ?>][protected]" type="checkbox" name="pp_settings[<?php echo $key ?>][protected]" value="1" <?php echo $selected; ?> />
				<?php _e( $display, 'pp_domain' ); ?>
			</label><?php
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