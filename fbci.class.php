<?php
require('facebook.php');

/**
* Manage the import of Facebook comments to Wordpress.
*
* see Facebook Graphe Api documentation for more details
* at http://developers.facebook.com/docs/api
*
* @package WordPress
* @since ??
*/

class FacebookCommentImporter {

	protected $page_id = '' ;
	protected $facebook = '' ;

	/**
	 * Constructor
	 *
	 * @package WordPress
	 * @since 1.0
	 *
	 * @param    string    $page_id    a Facebook page/profile ID
	 */
	public function __construct($page_id) {
		$this->page_id = $page_id ;
		$this->facebook = new Facebook(array(
		  'appId'  => '106793439375373',
		  'secret' => 'dbc0fe0aa03a505300d2569b7a004663',
		  'cookie' => false,
		));
	}
	
	/**
	 * Make a quick test on the Fan Page or Profile
	 * 
	 * @package WordPress
	 * @since 1.0
	 *
	 * @return   string		A description of the error, or a description starting with 'OK' if all is right.
	 */
	public function fan_page_test(){	
		try{
			$wall = $this->get_wall(10, true) ; 
			if(count($wall) < 1) {
				throw new Exception(__('Cannot find a post linked to this blog on the last 10 posts of the Facebook wall. Check that at least one post on your wall links to a post of your blog.', 'facebook-comments-importer'));
			} else {
				return __('OK. The followink post on your wall is linked to your blog: ', 'facebook-comments-importer') . '"' . $wall[0]["name"] . '".' ; 
			}
		} catch(Exception $e) {
			return 'Error: ' . $e->getMessage() ; 
		}
	}
	
	/**
	 * Get a Facebook Fan Page or Profile
	 * 
	 * 
	 *
	 * @package WordPress
	 * @since 1.0
	 *
	 * @return   array                the Fan Page, see FB Graphe API
	 */
	public function get_fan_page(){	
		try{  
			$fan_page = $this->facebook->api('/' . $this->page_id) ;  
		} catch(Exception $e) {
			throw new Exception(__('Error while getting this fan page : ', 'facebook-comments-importer') . $e->getMessage()); 
		}
		
		return $fan_page ;
	}

	/**
	 * Get the wall of the Facebook Fan Page or Profile
	 * 
	 * Return the list of posts, with comments, etc.
	 * Return only the posts that are linked to a post of the blog.
	 * The link is made between the FB post "link" attribute and the
	 * wordpress permalink.
	 *
	 * @package WordPress
	 * @since 1.0
	 *
	 * @param    int    $count    Number of items (posts) to fetch from the wall (Default : 30)
	 * @param    bool    $only_commented    If true, only returns items that have comments (Default : false)
	 * @return   array                the wall, see FB Graphe API
	 */
	public function get_wall($count = 30, $only_commented = false){	
		try{
			$q = '/' . $this->page_id . '/feed?limit=' . $count ;
			$fan_wall = $this->facebook->api($q) ;
			$fan_wall = $fan_wall[data] ;
			foreach($fan_wall as $key => $item){
				// remove the no-commented posts
				if($only_commented && $item["comments"]["count"] == 0){
					unset($fan_wall[$key]);
					continue ;
				}
				// We only keep the items that are linked to a blog post
				if(((int) url_to_postid($item['link'])) == 0){
					unset($fan_wall[$key]);
				}
			}
		} catch(Exception $e) {
			throw new Exception(__('Error while getting this wall : ', 'facebook-comments-importer') . $e->getMessage()); 
		}
		return $fan_wall ;
	}
	
	/**
	 * Get a Facebook user
	 * 
	 * Return a set of informations of a Facebook user (name, picture, etc.)
	 *
	 * @package WordPress
	 * @since 1.0
	 *
	 * @param    string    $user_id    the user ID (see Facebook API guide)
	 * @return   array                the user, see FB Graphe API for more info.
	 */
	public function get_user($user_id){	
		try{
			$user = $this->facebook->api('/' . $user_id) ;  
		} catch(Exception $e) { 
			throw new Exception(__('Error while getting this user : ', 'facebook-comments-importer') . $e->getMessage()); 
		}
		return $user ;
	}
	
	/**
	 * Get an array of the wall's comments.
	 * 
	 * Return an array of specific data for a comment (Not the Facebook array)
	 * Each item of the array have the following fields :
	 * - author_name : 		the username of the commenter
	 * - author_link : 		the link to the commenter FB profile 
	 * - author_picture : 	the FB profile picture of the commenter (not used for the moment)
	 * - message : 			the comment's message
	 * - created_time : 	the comment's date. The format is as '2010-08-02T12:23:29+0000'
	 *                  	You can convert it with date_create($comment["created_time"])
	 * - post_link :		the permalink to the wordpress post
	 * - post_name : 		the title of the FB item, wich should be the title of the wordpress post
	 *
	 * @package WordPress
	 * @since 1.0
	 *
	 * @param    array    $wall    an array provided by the get_wall function.
	 * @return   array             an array, as explained before.
	 */
	public function get_comments($wall){
		$comments = array() ;
		try{
			foreach($wall as $feed) {  
				$comments_stream = $this->facebook->api('/' . $feed["id"] . '/comments') ;
				
				if(isset($comments_stream[data])){
					foreach($comments_stream[data] as $comment){
						$user = $this->get_user($comment["from"]["id"]) ;
						
						// Generate the author string
						$author_str  = get_option('fbci_author_str', '%name% via Facebook') ;
						$tags = array('%name%', '%first_name%', '%last_name%');
						$replacements = array($user["name"], $user["first_name"], $user["last_name"]);

						$author_str = str_replace($tags, $replacements, $author_str);
						
						$comments[] = array(
							"author_name" => $user["name"],
							"author_str" => $author_str,
							"author_link" => $user["link"],
							"author_picture" => "http://graph.facebook.com/". $user[id] ."/picture",
							"message" => $comment["message"],
							"created_time" => $comment["created_time"],
							"post_link" => $feed["link"],
							"post_name" => $feed["name"],
							"comment_id" => $comment["id"]
						);
					}
				}
			}		
			return $comments ;
		} catch(Exception $e) {
			throw new Exception(__('Error while getting the comments : ', 'facebook-comments-importer') . $e->getMessage()); 
		}
	}
	
	
	/**
	 * Insert a FB comment in the wordpress database.
	 * 
	 * Get a comment form the get_comments function and insert it into
	 * the database. Not that this function doesn't use wp_new_comment
	 * but wp_insert_comment. This is because wp_new_comment does not allow
	 * to change the date of the comment, and we want to set the real date
	 * of the facebook comment.
	 * However, this function calls wp_filter_comment and wp_notify_postauthor
	 * in order to simulate the wp_new_comment behavior.
	 * Also inserts a record in the commentmeta table to link the WP comment 
	 * to the FB one.
	 *
	 * @package WordPress
	 * @since 2.9.0
	 *
	 * @param    array    $wall    	an array (an item of the array provided by 
									the get_comments function.)
	 */
	public function import_comment($comment){
		if(!($this->is_comment_imported($comment["comment_id"]))){
			//build the array to pass to wp_new_comment
			$post_id = (int) url_to_postid($comment['post_link']) ;
			if($post_id > 0) {
				$commentdata = array(
					'comment_post_ID' => $post_id,
					'comment_author' => $comment["author_str"],
					'comment_author_email' => get_bloginfo('admin_email'),
					'comment_author_url' => $comment["author_link"],
					'comment_content' => $comment["message"],
					'comment_agent' => 'facebook-comment-importer plugin',
					'comment_date' => get_date_from_gmt(date_format(date_create($comment["created_time"]), 'Y-m-d H:i:s')),
					'comment_parent' => 0,
					'comment_approved' => 1,
					'comment_type' => 'comment'
				);

				$commentdata = wp_filter_comment($commentdata);
				$comment_id = wp_insert_comment($commentdata);
				wp_notify_postauthor($comment_id, $commentdata['comment_type']);
			
				// add a meta to recognize the comment with the facebook comment id.
				add_comment_meta($comment_id, 'fbci_comment_id', $comment["comment_id"]) ;
			}
		}
	}
	
	/**
	 * Import all the comments
	 *
	 * Do all the job : checks all the comments from facebook, then inserts
	 * them into the wordpress database.
	 * uses get_comment, get_wall and import_comment
	 *
	 * Note that this only checks the 30 last comments from FB to avoid 
	 * performance problems.
	 *
	 * @package WordPress
	 * @since 2.9
	 */
	public function import_comments() {
		$comments = $this->get_comments($this->get_wall(30, true));
		foreach($comments as $comment){
			$this->import_comment($comment) ;
		}
	}
	
	/**
	 * Check if a Facebook comment is already imported in Wordpress
	 *
	 * The check uses the commentmeta table where the FB comments ids 
	 * are stored.
	 *
	 * Note that this only checks the 30 last comments from FB to avoid 
	 * performance problems.
	 *
	 * @package WordPress
	 * @since 2.9.0
	 *
	 * @param    string    $comment_id    	a Facebook comment ID (see FB API doc)
     * @return   bool    					true if already imported.
	 */
	public function is_comment_imported($comment_id) {
		global $wpdb ;
		$nb_comments = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->commentmeta WHERE meta_key = 'fbci_comment_id' and meta_value = '".$comment_id."';"));
		return ($nb_comments > 0) ;
	}
}
?>