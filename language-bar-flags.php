<?php
/*
	Plugin Name: Language Bar Flags
	Plugin URI: http://blog.meloniq.net/2011/11/28/language-bar-flags/
	Description: Replace or disable standard WordPress bar in the top of website and display similar bar but with configurable language flags to other language versions of Your website.
	Author: MELONIQ.NET
	Version: 1.0.4
	Author URI: http://blog.meloniq.net
*/


/**
 * Avoid calling file directly
 */
if ( ! function_exists( 'add_action' ) )
	die( 'Whoops! You shouldn\'t be doing that.' );


/**
 * Plugin version and textdomain constants
 */
define( 'LANGBF_VERSION', '1.0.4' );
define( 'LANGBF_TD', 'language-bar-flags' );


/**
 * Process actions on plugin activation
 */
register_activation_hook( plugin_basename( __FILE__ ), 'langbf_activate' );


/**
 * Load Text-Domain
 */
load_plugin_textdomain( LANGBF_TD, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );


/**
 * Load Countries arrays
 */
include_once( dirname( __FILE__ ) . '/countries.php' );


/**
 * Initialize admin menu
 */
if ( is_admin() ) {
	add_action( 'admin_menu', 'langbf_add_menu_links' );
}


/**
 * Load front-end scripts
 */
function langbf_load_scripts() {
	wp_register_script( 'langbf_tooltip', plugins_url( '/js/tooltip.slide.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'langbf_tooltip' );
}
add_action( 'wp_print_scripts', 'langbf_load_scripts' );


/**
 * Load back-end scripts
 */
function langbf_load_admin_scripts() {
  wp_enqueue_script( 'jquery-ui-tabs' );
}
add_action( 'admin_enqueue_scripts', 'langbf_load_admin_scripts' );


/**
 * Load front-end styles
 */
function langbf_load_styles() {
	wp_register_style( 'langbf_style', plugins_url( 'style.css', __FILE__ ) );
	wp_enqueue_style( 'langbf_style' );
}
add_action( 'wp_print_styles', 'langbf_load_styles' );


/**
 * Load back-end styles
 */
function langbf_load_admin_styles() {
	wp_register_style( 'langbf_admin_style', plugins_url( 'admin-style.css', __FILE__ ) );
	wp_enqueue_style( 'langbf_admin_style' );
}
add_action( 'admin_enqueue_scripts', 'langbf_load_admin_styles' );


/**
 * Print code in footer
 */
function langbf_load_html() {

	if ( get_option('langbf_active') != 'yes' )
		return;

	if ( get_option('langbf_disable_wpbar') == 'yes' ) {
		add_filter( 'show_admin_bar', '__return_false' );
		remove_action( 'personal_options', '_admin_bar_preferences' );
	}

	$target = ( get_option('langbf_new_window') == 'yes' ) ? 'target="_blank"' : '';
	$bar_title = get_option('langbf_title');

	$langs = get_option('langbf_langs');
	$native_names = langbf_get_countries( 'all', 'native' );
	$output = '';
	foreach ( $native_names as $code => $country ) {
		if ( isset( $langs[ $code ]['active'] ) && $langs[ $code ]['active'] == 'yes' ) {
			$output .= '<li><a href="' . $langs[ $code ]['url'] . '" ' . $target . ' title="' . $country . '" class="langbf_' . $code . '">' . $country . '</a></li>';
		}
	}
?>
	<div id="langbf_bar">
		<div class="langbf_links">
			<?php if ( ! empty( $bar_title ) ) { echo '<span class="langbf_title">' . $bar_title . '</span>'; } ?>
			<ul>
				<?php echo $output; ?>
			</ul>
		</div>
	</div><!-- #langbf_bar -->
<?php
}
add_action( 'wp_footer', 'langbf_load_html' );


/**
 * Print css in footer
 */
function langbf_load_css() {
	if ( get_option('langbf_active') != 'yes' )
		return;

	if ( is_admin_bar_showing() ) {
		$margin_top = 52;
		$top = 26;
	} else {
		$margin_top = 26;
		$top = 0;
	}
?>
	<style type="text/css">
	html {
		margin-top: <?php echo $margin_top ?>px !important;
	}
	* html body { 
		margin-top: <?php echo $margin_top ?>px !important;
	}
	#langbf_bar {
		top: <?php echo $top ?>px !important;
	}
	</style>
<?php
}
add_action( 'wp_footer', 'langbf_load_css' );


/**
 * Print css in footer
 */
function langbf_load_js() {
	if ( get_option('langbf_active') != 'yes' )
		return;
?>
	<script type="text/javascript">
	// <![CDATA[
	jQuery(document).ready( function(){
		jQuery("#langbf_bar a[title]").tooltip({
			offset: [10, 0],
			position: 'bottom center',
			effect: 'slide'
		} );
	} );
	// ]]>
	</script>
<?php
}
add_action( 'wp_footer', 'langbf_load_js' );


/**
 * Populate administration menu of the plugin
 */
function langbf_add_menu_links() {

	add_options_page( __( 'Language Bar Flags', LANGBF_TD ), __( 'Language Bar Flags', LANGBF_TD ), 'administrator', 'langbf', 'langbf_menu_settings' );
}


/**
 * Create settings page in admin
 */
function langbf_menu_settings() {

	include_once( dirname( __FILE__ ) . '/admin_settings.php' );
}


/**
 * Create announcement on langbf setting page
 */
function langbf_announcement() {

	if ( get_option( 'langbf_announcement' ) )
		return;

	if ( ! langbf_is_theme_provider( 'appthemes' ) ) {
		echo '<div class="update-nag">';
		_e( 'You are not using any of AppThemes Premium Themes, check what You are missing.', LANGBF_TD );
		printf( __( ' <a target="_blank" href="%s">Show me themes!</a>', LANGBF_TD ), 'http://bit.ly/s23oNj' );
		echo '</div>';
	}

}


/**
 * Check theme provider, used for announcement
 */
function langbf_is_theme_provider( $provider ) {

	if ( $provider == 'appthemes' ) {
		// All modern versions
		if ( defined( 'APP_TD' ) )
			return true;
		// ClassiPress, Clipper, JobRoller
		if ( defined( 'APP_POST_TYPE' ) )
			return true;
		// Vantage, Quality Control, Ideas
		if ( defined( 'VA_VERSION' ) || defined( 'QC_VERSION' ) || defined( 'IDEAX_VERSION' ) )
			return true;
	}

	return false;
}


/**
 * Action on plugin activate
 */
function langbf_activate() {
	// install default options
	langbf_install_options();
}


/**
 * Install default options
 */
function langbf_install_options() {

	$previous_version = get_option( 'langbf_db_version' );

	// fresh install
	if ( ! $previous_version ) {
		$domain = str_replace( 'http://www.', '', home_url( '/' ) );
		$domain = str_replace( 'https://www.', '', $domain );
		$domain = str_replace( 'http://', '', $domain );
		$domain = str_replace( 'https://', '', $domain );

		$url_prefix = 'http://www.';

		$active_langs = array();
		$active_langs['pl']['url'] = $url_prefix . 'pl.' . $domain;
		$active_langs['pl']['active'] = 'yes';
		$active_langs['uk']['url'] = $url_prefix . 'uk.' . $domain;
		$active_langs['uk']['active'] = 'yes';
		$active_langs['ie']['url'] = $url_prefix . 'ie.' . $domain;
		$active_langs['ie']['active'] = 'yes';

		update_option( 'langbf_active', 'yes' );
		update_option( 'langbf_langs', $active_langs );
	}

	if ( version_compare( $previous_version, '1.0.4', '<' ) ) {
		update_option( 'langbf_disable_wpbar', 'yes' );
		update_option( 'langbf_new_window', 'no' );
	}

	//Update DB version
	update_option( 'langbf_db_version', LANGBF_VERSION );
	delete_option( 'langbf_announcement' );
}

