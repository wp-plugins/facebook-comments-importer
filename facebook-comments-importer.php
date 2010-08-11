<?php
/*
Plugin Name: Facebook Comments Importer
Plugin URI: 
Description: This plugin imports the comments posted on your Facebook fan page to your blog.
Version: 1.0.1
Author: Neoseifer22
Author URI: 
License: GPL2

Copyright 2010  Neoseifer  (email : neoseifer_at_free_dot_fr)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

require_once('fbci.class.php');
$facebook = null ;
//load_plugin_textdomain('facebook-comments-importer','WP_PLUGIN_DIR .'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) ;');

/**
* Scheduling block
*/

register_activation_hook(__FILE__, 'fbci_activation');
register_deactivation_hook(__FILE__, 'fbci_deactivation');

add_action('fbci_cron_import', 'fbci_import_all_comments');

function fbci_activation() {
	wp_schedule_event(time()+600, 'hourly', 'fbci_cron_import');
}

function fbci_deactivation() {
	wp_clear_scheduled_hook('fbci_cron_import');
}


/**
 * Imports all the comments from Facebook to the database
 * 
 * This method do all the job silently. In case of error,
 * il logs in the log file.
 *
 * @package WordPress
 * @since 2.9.0
 */
function fbci_import_all_comments() {
	try{
		$fbci = new FacebookCommentImporter(get_option('fbci_page_id')) ;
		$fbci->import_comments();
	} catch (Exception $e) {
		fbci_log($e->getMessage());
	} 
}

/**
 * Logs a text into the log file
 * 
 * Take a text in parameter then add it to the log file
 * Insert the date/time before the text.
 * The log file is in the plugin folder.
 *
 * @package WordPress
 * @since 1.0
 *
 * @param    string    $text    The string to log in the file
 * @return   int                0 if error occurs.
 */
function fbci_log($text){
	$filename = WP_PLUGIN_DIR .'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . 'log.txt' ;
	$text = $date('d M Y H:i:s') . $text ;
	if (is_writable($filename)) {
		if (!$handle = fopen($filename, 'a')) {
			 // cannot open the log file
			 return 0;
		}

		if (fwrite($handle, $text) === FALSE) {
			// cannot write to the log file
			return 0;
		}
		fclose($handle);
	}
}

/**
* Administration Menu block
*/
add_action('admin_menu', 'fbci_create_menu');

function fbci_create_menu() {
	//create new top-level menu
	add_submenu_page('options-general.php','Facebook Comments Importer Settings', 'FB Comments Importer', 'administrator', __FILE__, 'fbci_settings_page');
	
	//call register settings function
	add_action( 'admin_init', 'fbci_register_mysettings' );
}


function fbci_register_mysettings() {
	register_setting( 'fbci-settings-group', 'fbci_page_id' );
}

function fbci_settings_page() {
?>
<div class="wrap">
<h2>Facebook Comments Importer</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'fbci-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row"><?php _e('Facebook Fan Page ID :') ; ?></th>
        <td>
			<input type="text" name="fbci_page_id" value="<?php echo get_option('fbci_page_id'); ?>" />
			<br/>
			<?php _e('For exemple, if your page url is <i>www.facebook.com/pages/BlogName/<b>123456</b></i>, your Fan Page ID is <b>123456</b>') ; ?>
		</td>
        </tr>
    </table>
	
	<?php
	if(get_option('fbci_page_id') != '') {
		try {
			$fbci = new FacebookCommentImporter(get_option('fbci_page_id')) ;
			$test1['type'] = 'OK' ;
			$fan_page = $fbci->get_fan_page() ;
			if(isset($fan_page['fan_count'])){
				$test1['message'] = sprintf(__('"%1$s" is a fan page, with %2$d fans.'), $fan_page['name'], $fan_page['fan_count']);
			} else {
				$test1['message'] = sprintf(__('"%1$s" is a personal profile.'), $fan_page['name']);
			}
		} catch (Exception $e) {
			$test1['message'] = __('Error : ') . $e->getMessage() ;
			$test1['type'] = 'Error' ;
		}
		
		try {
			$test2['type'] = 'OK' ;
			$wall = $fbci->get_wall(30) ;
			$test2['message'] = __('At least one item of your wall is linked to a post of your blog.');
			if(count($wall) == 0) {
				$test2['message'] = __('Warning : Cannot find an item on your wall that is linked to one of your blog\'s post.') ;
				$test2['type'] = 'Warning' ;
			}
		} catch (Exception $e) {
			$test2['message'] = __('Error : ') . $e->getMessage() . '<br>' . __('Check if your fan page / profile is public.')  ;
			$test2['type'] = 'Error' ;
		}
		
		try {
			$test3['type'] = 'OK' ;
			$comments = $fbci->get_comments($fbci->get_wall(30, true)) ;
			if(count($comments) == 0) {
				$test3['message'] = __('Warning : ');
				$test3['type'] = 'Warning' ;
			}
			$test3['message'] .= sprintf(__('%1$d comments found.'), count($comments));
			if(count($comments) == 0) {
				$test3['message'] .= __('But access seems to be ok.') ;
			}
		} catch (Exception $e) {
			$test3['message'] =  __('Error : ') . $e->getMessage() ;
			$test3['type'] = 'Error' ;
		}
	?>
	
		<h3>Test result for this ID :</h3>
		<ol>
			<li>
			<?php 
				$dir = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) ;
				echo '<img src="' . $dir . 'images/' ;
				if($test1['type'] == 'Error') {
					echo 'exclamation.png' ;
				}
				else if($test1['type'] == 'Warning') {
					echo 'error.png' ;
				} 
				else {
					echo 'accept.png' ;
				}
				echo '" style="vertical-align:middle; margin-right:5px;" />' . $test1['message']; 
			?>
			</li>
			<li>
			<?php 
				echo '<img src="' . $dir . 'images/' ;
				if($test2['type'] == 'Error') {
					echo 'exclamation.png' ;
				}
				else if($test2['type'] == 'Warning') {
					echo 'error.png' ;
				} 
				else {
					echo 'accept.png' ;
				}
				echo '" style="vertical-align:middle; margin-right:5px;" />' . $test2['message']; 
			?>
			</li>
			<li>
			<?php 
				echo '<img src="' . $dir . 'images/' ;
				if($test3['type'] == 'Error') {
					echo 'exclamation.png' ;
				}
				else if($test3['type'] == 'Warning') {
					echo 'error.png' ;
				} 
				else {
					echo 'accept.png' ;
				}
				echo '" style="vertical-align:middle; margin-right:5px;" />' . $test3['message']; 
			?>
			</li>
		</ol>
	
	<?php
	}
	?>
    
	<p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Test & Save Changes'); ?>" />
    </p>

</form>

</div>
<?php
}
?>