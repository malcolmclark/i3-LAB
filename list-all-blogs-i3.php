<?php
/**
 * @package List All Blogs (IBP)
 * @version 1.0.0
 */
/*
Plugin Name: List All Blogs (IBP)
Plugin URI: http://www.force4good.co.uk/
Description: Lists all blogs on a WPMU network
Author: Bhakti Ganesha Devananda
Version: 1.0.0
Author URI: http://www.force4good.co.uk

Usage: Network Activate.
Todo: Hard coded do_action in footer still being used. ie Plugin is not portable!
Todo:

Deprecated: 2 Shortcodes for use in widgets and pages - [bloglist-client] and [
bloglist-subject]

 */

// DEBUGGING/ DEV
if (WP_DEBUG) {
	require_once ABSPATH . 'wp-content/includes/debug-logger.php';
//	require_once ABSPATH . 'wp-content/includes/sandbox.php';

}

class ListAllBlogs {

	/**
	 * Construct the plugin
	 */
	function __construct() {

		/**
		 * If true, current blog (host to the list) is also displayed
		 * @var boolean
		 * @todo  Be nice to remove link, when true.
		 */
		$this->link_self = TRUE;
		$this->user_def_cats = array('Subject', 'Person', 'Client', 'Other 1', 'Other 2');

		// This plugin only runs in the admin, but we need it initialized on init - author of rewrite rules plugin. Not sure why we hook into init to initialize menu. possibly too early for other action hooks, which I register here in construct.

		add_action('init', array($this, 'action_init'));

		/*
			 * Lists all blogs on network
			 * See do_action hook for list-all-blogs in footer.php of child theme
			 * Currently hard coded, so plugin not v portable.

		*/

		add_action('list-all-blogs', array($this, 'build_blog_list'));

		// Inject the script for the page site-new.php (the page hook).
		add_action('admin_print_scripts-site-new.php', array($this, 'admin_script_site_new'));

// Add custom menu in network admin screen
		add_action('network_admin_menu', array($this, 'build_network_admin_plugin_menu'));

// Add Blog Cat to blog_cats in wp_sitemeta upon new blog creation
		add_action('wpmu_new_blog', array($this, 'add_new_blog_site_cat'), 10, 6);

/**
 * Register the admin menu hook under Tools as sub menu
 * Deactivated cos only want (((network))) admin access
 */
		// add_action('admin_menu', array($this, 'build_i3_admin_menu'));
	}

	/**
	 * Initialize the plugin
	 */
	public function action_init() {

		if (!is_admin()) {
			return;
		}

		// Not sure when to use this design pattern. Seems to work ok, calling network admin stuff in contruct.

	}

	/**
	 * Pure func to return all blogs on network as array of key=id & val=blogname
	 * @return arr ([blog_id => blogname)
	 */

	private function get_all_blogs() {
		global $wpdb;

		$all_blogs_sql_result = $wpdb->get_results("
				SELECT blog_id
				FROM {$wpdb->blogs}
				WHERE site_id = '{$wpdb->siteid}'
			/*	AND spam = '0'
				AND deleted = '0'
				AND archived = '0'*/
    ");

		$all_blogs = array();
		foreach ($all_blogs_sql_result as $blog) {
			$all_blogs[$blog->blog_id] = get_blog_option($blog->blog_id, 'blogname');
		}
		// write_log($all_blogs);
		return $all_blogs;
	}

	/**
	 * 	Builds list item for each blog
	 *
	 * @param  int $id Blog ID
	 *
	 *
	 *
	 */

	private function build_list_item($id) {
		global $blog_id;
		$current_blog_id = $blog_id; // get´this blog id

		$blogname = get_blog_details($id)->blogname;

		// Do not display blog hosting the list, if $link_self false
		// If id equals current_blog_id and link_self is not true
		if ($id == $current_blog_id && !$this->link_self) {
			return;
		}

		$listItem = '<li>';
		// If displayed, make current blog bold text.
		$listItem .= ($id === $current_blog_id) ? '<strong>' : '';

		$url = ($id !== $current_blog_id) ? get_home_url($id) : '#';
		// Add a "/" to the end of the URL because WordPress wasn't doing that automatically in v3.0.4. YMMV.
		$url .= ($id !== $current_blog_id) ? (substr($url, -1) != '/') ? '/' : '' : '';

		$listItem .= '<a id="' . $id . '" href="' . $url . '">' . $blogname . '</a>';
		$listItem .= ($id === $current_blog_id) ? '</strong>' : '';
		$listItem .= '</li>';

		return $listItem;
	}

/**
 * Outputs blog list
 *
 * @param
 */

	public function build_blog_list($type = 1) {

		// Get category of current blog
		$current_blog_id = get_current_blog_id();
		$blog_cats = get_site_option('blog_cats');
		$current_blog_cat = $blog_cats[$current_blog_id];

		$uList = '<h3>All blogs on the network:</h3><ul>';

		// $this->build_list_item(1, 'Home', $link_self);

		natsort($blog_cats);

		foreach ($blog_cats as $blog_id => $blog_cat) {
			// only build li if matches blog cat of blog calling this method (current blog)
			if ($blog_cat === $current_blog_cat) {
				$uList .= $this->build_list_item($blog_id);
			}
		}
		$uList .= '</ul>';
		echo $uList; // Cant return, cos cant echo in add_action?
	}

	/*public function build_blog_list_old() {

		$uList = '<h3>All blogs on the network:</h3><ul>';

		// $this->build_list_item(1, 'Home', $link_self);
		$all_blogs = $this->get_all_blogs();
		natsort($all_blogs);

		foreach ($all_blogs as $blog_id => $blog_title) {
			$uList .= $this->build_list_item($blog_id, $blog_title, $this->link_self);
		}
		$uList .= '</ul>';
		echo $uList; // Cant return, cos cant echo in add_action?
	}*/

// http://stackoverflow.com/questions/10339053/wordpress-multisite-how-to-add-custom-blog-options-to-add-new-site-form-in-ne/10372861#10372861

	// Dont forget - no hyphens in func names!
	public function admin_script_site_new() {
		// write_log('site new');
		wp_register_script('add_site_cat_frm_elem',
			plugins_url('js/add_site_cat_frm_elem.js', __FILE__));

		// Only way I can see to easily inject dynamic data into js file.
		wp_localize_script('add_site_cat_frm_elem', 'user_def_cats', $this->user_def_cats);

		wp_enqueue_script('add_site_cat_frm_elem');
	}

	/**
	 * @param $blog_id
	 * @param $user_id
	 * @param $domain
	 * @param $path
	 * @param $site_id
	 * @param $meta
	 */
	public function add_new_blog_site_cat($blog_id, $user_id, $domain, $path, $site_id, $meta) {

		// Make sure the user can perform this action and the request came from the correct page. ???

		// switch_to_blog($blog_id);

		$blog_cats = get_site_option('blog_cats');

		if (!empty($_POST['blog']['new_blog_cat'])) {
			$blog_cats[$blog_id] = $_POST['blog']['new_blog_cat'];
			// $site_cat_val['meta'] = $meta; // {s:6:"public";i:1;s:6:"WPLANG";s:5:"en_GB";}
		}
		// save option into the wpmu db, as opposed to blog specific (update_option).
		// This will be auto autoloaded, when using update to create.
		//		update_site_option('site_cat', $site_cat_val); // Not used since we must be in admin realm of WP, so no need to use 'site' option. http://dustinbolton.com/wordpress-options-in-standalone-vs-multisite-aka-update_option-vs-update_site_option/
		// write_log($blog_cats);
		update_site_option('blog_cats', $blog_cats);

		// restore_current_blog();
	}

	/**
	 *
	 * Get site cat of a blog
	 * To be used in site-settings admin page eg
	 */
	static function get_blog_cat($blog_id) {

		$blog_cats = get_site_option('blog_cats');

		foreach ($blog_cats as $key => $value) {
			if ($blog_id === $key) {
				// write_log("Cat: " . $value);
				return $value;
			}
		}
		// write_log($blog_cats);
	}

	/**
	 * Probably wont use this, since access to plugin for network admin. Good practice though!
	 * Add our sub-menu page to the Admin Nav
	 */
	public function build_network_admin_plugin_menu() {

		// add_menu_page('My Plugin Settings', 'My Plugin Settings', 'manage_network', 'myplugin-settings', 'blog_cats');

		// write_log('tools.php');

		// cos I could not follow simple instructions, fatal error haunted me for 24hrs!
		add_menu_page(
			'Site Categories - i3 Plugin Options Page', // html title
			'Site Categories - i3',
			'manage_options',
			'manage-site-category_options-page-for_list-all-blogs-i3', // tools.php?page=my-custom-menu-page_list-all-blogs-i3
			array($this, 'build_plugin_options_page')
		);
	}
	// Create html for options page

	public function build_plugin_options_page() {

		if (is_multisite() && current_user_can('manage_network')) {

			?>
			<styles>
			</styles>
    <div class="wrap">

    <h2>i3 - Blog Category Settings</h2>

    <?php

			if (isset($_POST['action']) && $_POST['action'] == 'update_blogs_cat') {

				//write_log($_POST);

				// paired with wp_nonce_field
				check_admin_referer('save_network_settings', 'my-network-plugin');

				//sample code from Professional WordPress book

				//store option values in a variable
				$blog_cats = $_POST['blog_cats'];

				//use array map function to sanitize option values
				$blog_cats = array_map('sanitize_text_field', $blog_cats);

				// write_log($blog_cats);
				// save option values
				update_site_option('blog_cats', $blog_cats);

				//just assume it all went according to plan
				echo '<div id="message" class="updated fade"><p><strong>i3 Plugin Settings Updated!</strong></p></div>';

			} //if POST

			?>

<form method="post">
<input type="hidden" name="action" value="update_blogs_cat" />

<table class="form-table">
<thead>
		<th scope="col">Link to Blog</th>
		<th scope="col">Blog ID</th>
		<th scope="col">Category</th>
	</thead>
	<tbody>
<?php
// Get all live blogs on the network.
			// Array ([1] => WPMU )

			$live_blogs = $this->get_all_blogs();

			// write_log($live_blogs);
			// Grab also stored list of blogs and the category info
			// Array ([[blog_id]] => [blog cat id] [2] => 1  )
			$blog_cats = get_site_option('blog_cats');
			// write_log($blog_cats);

			wp_nonce_field('save_network_settings', 'my-network-plugin');

			if (is_array($live_blogs)) {

				foreach ($live_blogs as $key => $value) {

					$blog_details = get_blog_details($key);
					?>

	 <tr valign="top">
	          <td>
	         <?php
// In case blog id does not exist
					if ($blog_details) {
						echo "<a href='$blog_details->siteurl' target='_blank'>" . $blog_details->blogname . "</a>";
					}?>
	     </td>
	     <td>
	         <?php echo $key;
/**
 * In following html select, we use the $key from $live_blogs to access the blog category in $blog_cats
 *
 */
					?>
	     </td>
	     <td>
	         <select name="blog_cats[<?php echo $key; // key is blog id                                                                                            ?>]">
         		<option value="0">Please Select</option>

<?php
foreach ($this->user_def_cats as $index => $val2) {
						echo "<option value=\"" . ($index + 1) . "\" " .
						selected($blog_cats[$key], ($index + 1)) . ">" .
							$val2 . "</option>";}
					?>
	         </select>
	     </td>
	 </tr>

	 <?php }} // end foreach ?>
			</tbody>
        </table>

            <p class="submit">
        <input type="submit" class="button-primary" name="update_blogs_cat" value="Save Settings" />

</p>
</form>

    <?php

		} else {

			echo '<p>My Network plugin must be used with WP Multisite.  Please configure WP Multisite before using this plugin.  In addition, this page can only be accessed in the by a super admin.</p>';
			/*Note: if your plugin is meant to be used also by single wordpress installations you would configure the settings page here, perhaps by calling a function.*/

		}

		?>
</div>
<?php

	}

	private function __FOLLOWING_METHODS_NOT_IN_USE__() {
		return false;
	}

	/**
	 * Probably wont use this, since access to plugin for network admin. Good practice though!
	 * Add our sub-menu page to the Admin Nav
	 */
	public function build_i3_admin_menu() {

		// write_log('tools.php');

		// cos I could not follow simple instructions, fatal error haunted me for 24hrs!
		add_submenu_page(
			'tools.php',
			'i3 Site Categories - Plugin Options Page', // html title
			'i3 Site Categories',
			'manage_options',
			'manage-site-category_options-page-for_list-all-blogs-i3', // tools.php?page=my-custom-menu-page_list-all-blogs-i3
			array($this, 'show_i3_options_page')
		);
	}
	// Create html for options page
	public function show_i3_options_page() {

		$blog_id = get_current_blog_id();
		$blog_cat_id = ($this->get_blog_cat($blog_id)) ? $this->get_blog_cat($blog_id) : " not yet assigned.";
		// write_log($blog_id);
		?>
	    <div class="wrap">
	        <h2>My Plugin Options</h2>
	        your form goes here and Im so happy!
<p>	   Site Cat for this blog is <?php echo $blog_cat_id; ?>.
</p>
	    </div>
	    <?php
}

	// does same as get_option
	public function get_site_cat() {

		global $wpdb;
		$results = $wpdb->get_results('
			SELECT option_value
			FROM wp_options
			WHERE option_name = "site_cat" ', OBJECT);

		$final = array();
		foreach ($results as $result) {
			$final[] = $result;
		}

//		return $final;
		// write_log($final);

	}

	public function update_site_cat() {
		write_log('Updating Site Cat having saved site settings');
	}

}

// ListAllBlogs::get_blog_cat(11);

$objInst = new ListAllBlogs();

// add_action('wpmu_update_blog_options', array($objInst, 'update_site_cat'), 10, 6);

// Test site_cat retrieval
// add_action('wp_head', array($objInst, 'get_site_cat'));

/*
 *
 *
 *
 * Testing & Old Code
 *
 *
 *
 */
// function build_blog_list($link_self = false, $queryType = 'bySubject') {
// 	global $wpdb;

// 	//$query = $wpdb->get_results("       SELECT option_id FROM wp_options WHERE option_id=123   ");

// 	$result = $wpdb->get_row('SELECT option_value FROM wp_options WHERE option_id=123 ', ARRAY_A);

// 	print_r($result);
// }

/* It is important that these lines be added in this order. The first line prevents WordPress from turning line breaks into paragraph tags, since this keeps shortcodes from working. The second line is the one that makes the shortcodes work.
 */
// add_filter( 'widget_text', 'shortcode_unautop' );
// add_filter( 'widget_text', 'do_shortcode' );

// Adds a [bloglist] shortcode, so I can embed the menu into the static homepage.

// add_shortcode('bloglist-subject', function($atts){
//    return  build_blog_list(false,'bySubject');
// });

// add_shortcode('bloglist-client', function($atts){
//    return  build_blog_list(false,'byClient');
// });

?>
