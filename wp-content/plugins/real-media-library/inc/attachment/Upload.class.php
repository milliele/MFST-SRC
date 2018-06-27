<?php
namespace MatthiasWeb\RealMediaLibrary\attachment;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Handling uploader, so files can be uploaded directly to a folder.
 */
class Upload {
	private static $me = null;
        
    private function __construct() {
        // Silence is golden.
    }
    
    /*
     * Handles the upload to move an attachment directly to a given folder
     * 
     * @hooked add_attachment
     */
    public function add_attachment($postID) {
    	// Move to the given folder
    	$rmlFolder = isset($_REQUEST["rmlFolder"]) ? $_REQUEST["rmlFolder"] : null;
    	if ($rmlFolder !== null) {
    		$r = wp_rml_move($rmlFolder, array($postID));
    	}else{
    	    _wp_rml_synchronize_attachment($postID, _wp_rml_root());
    	}
    	
    	//if ($_SERVER['REQUEST_URI']) === "/wp-admin/async-upload.php") {
    	//    attachment\CountCache::getInstance()->wp_die();
    	//}
    }
    
    /*
     * The grid view and post editor upload can be modified through the left
     * tree view to upload files directly.
     * 
     * @hooked pre-upload-ui
     */
    public function pre_upload_ui() {
        global $pagenow;
        
        // Get the options depending on the current page
        if ($pagenow === "media-new.php") {
            $options = wp_rml_dropdown(Filter::getInstance()->lastQueriedFolder(), array(RML_TYPE_COLLECTION, RML_TYPE_ALL));
            $label = __("You can simply upload files directly to a folder. Select a folder and upload files.", RML_TD);
            $style = "display:block;";
        }else{
            $options = '<option selected="true" value="-1">' . __("Loading...", RML_TD) . '</option>';
            $label = __("upload to folder", RML_TD);
            $style = "display:none;";
        }
        
        echo '<p class="attachments-filter-upload-chooser" style="' . $style . '">' . $label . '<br/></p>
        <p style="' . $style . '">
            <select>' . $options . '</select>
        </p>';
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new Upload();
        }
        return self::$me;
    }
}

?>