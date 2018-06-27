<?php
namespace MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\order;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * This class handles all hooks for the general filters.
 */
class Filter extends general\Base {
    private static $me = null;
    
    private function __construct() {
    	add_filter('RML/Backend/JS_Localize', array($this, 'localize'));
    }
    
    /*
     * @api wp_attachment_folder()
     */
    public function getAttachmentFolder($attachmentId, $default = null) {
        $isArray = is_array($attachmentId);
        $attachmentId = $isArray ? $attachmentId : array($attachmentId);
        
        if (count($attachmentId) > 0) {
            global $wpdb;
            $attachments_in = implode(",", $attachmentId);
            $table_name = general\Core::getInstance()->getTableName("posts");
            $folders = $wpdb->get_col("SELECT DISTINCT(rmlposts.fid) FROM $table_name AS rmlposts WHERE rmlposts.attachment IN ($attachments_in)");
            if ($isArray) {
                return $folders;
            }else{
                return isset($folders[0]) ? $folders[0] : (($default === null) ? _wp_rml_root() : $default);
            }
        }
        return $default;
    }
    
    /*
     * Localize my filter variables for javascripts
     * 
     * @filter RML/Backend/JS_Localize
     */
    public function localize($arr) {
    	//$namesSlugArray = Structure::getInstance()->getView()->namesSlugArray();
        $mode = get_user_option( 'media_library_mode', get_current_user_id() ) ? get_user_option( 'media_library_mode', get_current_user_id() ) : 'grid';
    	
    	// General for filters
    	$arr["ajaxUrl"] = admin_url('admin-ajax.php');
    	$arr["apiUrl"] = general\REST::url();
    	$arr["pluginUrl"] = plugins_url("/", RML_FILE);
    	$arr["blogId"] = get_current_blog_id();
    	$arr["listMode"] = $mode;
    	$arr["root"] = _wp_rml_root();
    	$arr["hasUserSettings"] = has_filter("RML/User/Settings/Content");
    	
    	return $arr;
    }
	
	/*
	 * Changes the SQL query like this way to JOIN the realmedialibrary_posts
	 * table and search for the given folder.
	 * 
	 * @hooked posts_clauses
	 */
	public function posts_clauses($clauses, $query) {
	    global $wpdb;
        $table_name = general\Core::getInstance()->getTableName("posts");
        $saveInCookie = (is_admin() && defined('DOING_AJAX') && DOING_AJAX || general\Backend::getInstance()->isScreenBase("upload")) && !headers_sent();
	    
	    // Shortcut destinations
	    $fields = trim($clauses["fields"], ",");
	    
	    if (!empty($query->query_vars['parsed_rml_folder'])) {
	        $folderId = $query->query_vars['parsed_rml_folder'];
	        $root = _wp_rml_root();
	        $cookieValue = $root; // Save the last queried cookie for "New media" dropdown
	        
	        /*f
	         * Do a filter to restrict the RML posts clauses and apply an own clauses modifier.
	         * If you want to use your own implementation of posts_clauses you can add this code
	         * to to restrict the RML standard posts_clauses: <code>$clauses["_restrictRML"] = true;</code>
	         * 
	         * @param {array} $clauses The list of clauses for the query
	         * @param {WP_Query} $query The WP_Query instance
	         * @param {int} $folderId The folder ID to query
	         * @param {int} $root The root folder ID, see also {@link RML/RootParent}
	         * @returns {array} $clauses
	         * @filter RML/Filter/PostsClauses
	         * @see https://developer.wordpress.org/reference/hooks/posts_clauses/
	         */
	        $clauses = apply_filters("RML/Filter/PostsClauses", $clauses, $query, $folderId, $root);
	        $builtIn = !isset($clauses["_restrictRML"]);
	        if (!$builtIn) {
	            unset($clauses["_restrictRML"]);
	        }
	        
	        // Folder relevant data
	        if ($builtIn === true && ($folderId > 0 || $folderId == $root)) {
    	        // Change fields
    	        $fields = trim($clauses["fields"], ",");
    	        $clauses["fields"] = $fields . ", rmlposts.fid, rmlposts.isShortcut ";
    	        
    	        // Change join regarding the folder id
    	        $clauses["join"] .= " LEFT JOIN $table_name AS rmlposts ON rmlposts.attachment = ".$wpdb->posts.".ID ";
    	        
    	        if ($folderId > 0) {
    	            $clauses["join"] .= $wpdb->prepare(" AND rmlposts.fid = %d ", $folderId);
    	            $clauses["where"] .= " AND rmlposts.fid IS NOT NULL ";
    	        }else{
    	            $clauses["where"] .= $wpdb->prepare(" AND (rmlposts.fid IS NULL OR rmlposts.fid = %d) ", $root);
    	        }
    	        
    	        $cookieValue = $folderId;
	        }
	        
	        // Save cookie value
	        if ($saveInCookie && $builtIn) {
	            $this->lastQueriedFolder($cookieValue);
	        }
	    }else if ($query->get("post_type") === "attachment") {
	        // Reset last queried folder to unorganized
	        if ($saveInCookie) {
	            $this->lastQueriedFolder(_wp_rml_root());
	        }
	    }
	    
	    return $clauses;
	}
	
	public function lastQueriedFolder($folder = null) {
	    $key = "rml_" . get_current_blog_id() . "_lastquery";
	    if ($folder !== null) {
	        setcookie( $key, $folder, strtotime( '+365 days' ), '/' );
	    }
	    return isset($_COOKIE[$key]) ? $_COOKIE[$key] : _wp_rml_root();
	}
	
    /*
     * Define a new query option for \WP_Query.
     * "rml_folder" integer
     * 
     * @hooked pre_get_posts
     */
    public function pre_get_posts($query) {
        $folder = $this->getFolder($query, general\Backend::getInstance()->isScreenBase("upload"));
    	
    	if ($folder !== null) {
    	    $query->set('parsed_rml_folder', $folder);
    	}
    }
    
    /*
     * Get folder from different sources.
     * 
     * @return folder id or null
     */
    public function getFolder($query, $fromRequest = false) {
    	$folder = null;
    	
    	if ($query !== null && 
    		($queryFolder = $query->get('rml_folder')) &&
    		isset($queryFolder)) {
    			
	        // Query rml folder from query itself
    		$folder = $queryFolder;
    	}else if(_wp_rml_active()) {
    		if ($fromRequest) {
	    		if (isset($_REQUEST["rml_folder"])) {
	    	        // Query rml folder from list mode
	        		$folder = $_REQUEST["rml_folder"];
	        	}else if (isset($_POST["query"]["rml_folder"])) {
	    	        // Query rml folder from grid mode
	    	        $folder = $_POST["query"]["rml_folder"];
	        	}else{
	        		return;
	        	}
    		}
        }else{
    		return null;
    	}
    	return is_numeric($folder) ? $folder : null;
    }
    
    public function ajax_query_attachments_args($query) {
    	$fid = $this->getFolder(null, true);
    	if ($fid !== null) {
    		$query["rml_folder"] = $fid;
    	}
    	return $query;
    }
    
    /*
     * Add the attachment ID to the count update when deleting it
     * 
     * @hooked delete_attachment
     * @see CountCache::wp_die
     * @see CountCache::$newAttachments
     */
    public function delete_attachment($postID) {
        //wp_rml_move(_wp_rml_root(), array($postID)); // Simulate an move to unorganized @deprecated
        
        // Reset folder count
        //CountCache::getInstance()->addNewAttachment($postID);
        CountCache::getInstance()->resetCountCacheOnWpDie(wp_attachment_folder($postID));
        
        // Delete row in posts table
        global $wpdb;
        $table_name = general\Core::getInstance()->getTableName("posts");
        $sql = $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE attachment = %d", $postID));
        
        // Reindex folder
        $folder = wp_rml_get_object_by_id(wp_attachment_folder($postID));
        if (is_rml_folder($folder)) {
            $folder->contentReindex();
        }
    }
    
    /*
     * Add a attribute to the ajax output. The attribute represents
     * the folder order number if it is a gallery.
     * 
     * @used order.js
     * @hooked wp_prepare_attachment_for_js
     * @movedpermanently Filter::wp_prepare_attachment_for_js()
     */
    public function wp_prepare_attachment_for_js($response, $attachment, $meta) {
		// append attribute
		$folderId = $this->getFolder(null, true);
		$response['rmlFolderId'] = !empty($folderId) ? $folderId : _wp_rml_root();
		$response['rmlGalleryOrder'] = -1;
		$response['rmlIsShortcut'] = wp_attachment_is_shortcut($attachment->ID, true);
		
		if (isset($_POST["query"]) &&
				is_array($_POST["query"]) &&
				isset($_POST["query"]["orderby"]) &&
				$_POST["query"]["orderby"] == "rml") {
			$folder = wp_rml_get_object_by_id($folderId);
			if (is_rml_folder($folder)) {
			    $orders = $folder->getContentOrderNumbers();
    			if (is_array($orders) && isset($orders[$attachment->ID])) {
    				$response['rmlGalleryOrder'] = $orders[$attachment->ID];
    			}
			}
		}
		
		// return
		return $response;
	}
	
    /*
     * Create a select option in list table of attachments
     * 
     * @hooked restrict_manage_posts
     */
    public function restrict_manage_posts() {
        $screen = get_current_screen();
    	if ($screen->id == "upload") {
    		echo '<select name="rml_folder" id="filter-by-rml-folder" class="attachment-filters attachment-filters-rml">
    			' . Structure::getInstance()->getView()->optionsFasade(
    						isset($_REQUEST['rml_folder']) ? $_REQUEST['rml_folder'] : "",
    						array()
						) . '
    		</select>&nbsp;';
    	}
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new Filter();
        }
        return self::$me;
    }
}