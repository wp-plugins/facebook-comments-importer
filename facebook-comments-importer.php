<?php
/*
Plugin Name: Facebook Comments Importer
Plugin URI: 
Description: This plugin imports the comments posted on your Facebook fan page to your blog.
Version: 1.1
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
load_plugin_textdomain('facebook-comments-importer');

require_once('fbci.class.php');
$facebook = null ;

/**
* Scheduling block
*/

register_activation_hook(__FILE__, 'fbci_activation');
register_deactivation_hook(__FILE__, 'fbci_deactivation');

add_action('fbci_cron_import', 'fbci_import_all_comments');
add_filter('get_avatar', 'fbci_get_avatar', 10, 5);

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
 * Returns the Facebook avatar. Used by the get_avatar filter.
 * @author : Justin Silver
 * 
 * @package WordPress
 * @since 2.5
 *
 * @param	 object	   $avatar			The default avatar.
 * @param    string    $id_or_email     Author’s User ID (an integer or string), 
 *										an E-mail Address (a string) or the 
 *										comment object from the comment loop
 *										provided by get_avatar.
 * @param	 string 	$size			Size of avatar to return. provided by get_avatar.
 * @return   string             		The avatar img tag if possible
 */
function fbci_get_avatar($avatar, $id_or_email, $size='50') {
    if (!is_object($id_or_email)) { 
		$id_or_email = get_comment($id_or_email);
    }

    if (is_object($id_or_email)) {
        $alt = '';
        if ($id_or_email->comment_agent=='facebook-comment-importer plugin'){
            $fb_url = $id_or_email->comment_author_url;
            $fb_array = split("/", $fb_url);
            $fb_id = $fb_array[count($fb_array)-1];
            if (strlen($fb_id)>1) {
                $img = "http://graph.facebook.com/".$fb_id."/picture";
                return "<img alt='{$alt}' src='{$img}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
            }
        }
    }
    return $avatar;
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
	register_setting( 'fbci-settings-group', 'fbci_author_str' );
}

function fbci_settings_page() {
?>
<div class="wrap">
<h2>Facebook Comments Importer</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'fbci-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row"><?php _e('Facebook Fan Page ID :', 'facebook-comments-importer') ; ?></th>
        <td>
			<input type="text" name="fbci_page_id" value="<?php echo get_option('fbci_page_id'); ?>" size="50" />
			<br/>
			<?php _e('For exemple, if your page url is <i>www.facebook.com/pages/BlogName/<b>123456</b></i>, your Fan Page ID is <b>123456</b>', 'facebook-comments-importer') ; ?>
		</td>
        </tr>
		<tr valign="top">
        <th scope="row"><?php _e('Comment author text :', 'facebook-comments-importer') ; ?></th>
        <td>
			<input type="text" name="fbci_author_str" value="<?php echo get_option('fbci_author_str', '%name% via Facebook'); ?>" size="50" />
			<br/>
			<?php _e('You can use the following tags : %name%, %first_name%, %last_name%', 'facebook-comments-importer') ; ?>
		</td>
        </tr>
    </table>
	
	<?php
	
	if(get_option('fbci_page_id') != '') {
		try {
			$fbci = new FacebookCommentImporter(get_option('fbci_page_id')) ;
			$test = $fbci->fan_page_test() ;
		} catch (Exception $e) {
			$test = __('Error: Cannot make the test. ', 'facebook-comments-importer') . $e->getMessage() ;
		}
	?>
	
		<h3>Test result for this ID :</h3>
		<div>
			<?php 
				$dir = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) ;
				echo '<img src="' . $dir . 'images/' ;
				if(substr ($test, 0, 2) == 'OK') {
					echo 'accept.png' ;
				} else {
					echo 'exclamation.png' ;
				}
				echo '" style="vertical-align:middle; margin-right:5px;" />' . $test; 
			?>
		</div>
	
	<?php
	}
	?>
    
	<p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Test & Save Changes', 'facebook-comments-importer'); ?>" />
    </p>

</form>

</div>
<?php
}
?>