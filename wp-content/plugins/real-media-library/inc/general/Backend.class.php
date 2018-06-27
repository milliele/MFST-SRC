<?php
namespace MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Handling the scripts and styles for the plugin. Handling also the
 * localize Javascript with a single object name.
 */
class Backend extends Base {
	private static $me = null;
        
    private function __construct() {
        
    }
    
    /*
     * Data for the wp localize script.
     * 
     * @return array
     */
    private function localize_js() {
        return array(
            'debug' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG,
            'lang' => array(
                "warnDelete"                        => $this->media_view_strings(false),
                "uploaderUsesLeftTree"              => __('<strong>Note: </strong> The files will be uploaded to selected folder.', RML_TD),
                "save"                              => __('Save'),
                "close"                             => __('Close'),
                "cancel"                            => __('Cancel'),
                "shortcutRemove"                    => __('Remove shortcut', RML_TD),
                "shortcut"                          => __('Shortcut', RML_TD),
                "shortcutInfo"                      => __('This is a shortcut of a media library file. Shortcuts doesn\'t need any physical storage <strong>(0kb)</strong>. If you want to change the file itself, you must do this in the original file (for example replace media file through a plugin).<br/>Note also that the fields in the shortcuts can be different to the original file, for example "Title", "Description" or "Caption".', RML_TD),
                "done"                              => __('Done', RML_TD),
                "failed"                            => __('Failed', RML_TD),
                "noMedia"                           => __('No media files found.'),
                //"moveSingleFile"                    => __('Move {0} file', RML_TD),
                //"moveMultipleFiles"                 => __('Move {0} files', RML_TD),
                "uploadRemainFiles"                 => __('+ {0} more files to upload...', RML_TD),
                "searchRearrange"                   => __('The search is active, please remove the search term to rearrange your folder structure.', RML_TD),
                "moveSingleFile"                    => __('Move {0} file<br/><span>Hold any key to create a file shortcut.<br /><strong class="rml-move-original-files">(Moves the shortcut, not original file)</strong></span>', RML_TD),
                "moveMultipleFiles"                 => __('Move {0} files<br/><span>Hold any key to create a file shortcut.<br /><strong class="rml-move-original-files">(Moves the shortcut, not original file)</strong></span>', RML_TD),
                "appendSingleFile"                  => __('Link {0} file<br/><span>Release key to move.</span>', RML_TD),
                "appendMultipleFiles"               => __('Link {0} files<br/><span>Release key to move.</span>', RML_TD),
                "uploadingCollection"               => __('A collection can not contain files. Upload moved to Uncategorized...', RML_TD),
                "uploadingGallery"                  => __('A gallery can contain only images. Upload moved to Uncategorized...', RML_TD),
                "deleteFailed"                      => __('In this folder are sub directories, please delete them first!', RML_TD),
                "deleteRoot"                        => __('Do not delete root. :(', RML_TD),
                "deleteConfirm"                     => __('Would you like to delete this folder? Note: All files in this folder will be moved to the unorganized folder.', RML_TD),
                "deleteConfirmTitle"                => __('Are you sure?', RML_TD),
                "deleteConfirmSubmit"               => __("Yes, delete it!", RML_TD),
                "deleteConfirmCancel"               => __("Cancel"),
                "sortConfirmTitle"                  => __("Sort files", RML_TD),
                "sortConfirmText"                   => __("You want to order the files in this folder. If you confirm, you can <strong>drag&drop</strong> the files. If you want to reset the order, select the folder again and go to folder details (see image above).", RML_TD),
                "moveFromAllConfirmSubmit"          => __("Move it!", RML_TD),
                "moveFromAllConfirmText"            => __("You want to move one or more files from different folder sources because you are in the \"All Files\" view.", RML_TD),
                "renameRoot"                        => __('Do not rename root. :(', RML_TD),
                "renamePrompt"                      => __('Tell me the new name', RML_TD),
                "renamePromptTitle"                 => __('Rename folder', RML_TD),
                "orderFailedFilterOn"               => __("In the current view of uploads are filters active. Please reset these and refresh the view."),
                "detailsFailedOn"                   => __('Please go to your media library "Media > Library" to open the details window for the selected folder.', RML_TD),
                "detailsFailedAlreadyOpen"          => __('A folder details window is already opened.', RML_TD),
                "wipe"                              => __('Are you sure?', RML_TD),
                "metadata" => array(
                    "coverImage" => array(
                        "label_add" => __('Set cover image', RML_TD),
                        "label_replace" => __('Select another cover image', RML_TD),
                        "label_remove" => __('No cover image', RML_TD),
                        "label_modal" => __('Select cover image', RML_TD),
                        "label_button" => __('Set as cover image', RML_TD)
                    )
                ),
                
                "createTypes" => array(
                    "folder" => array(
                        "toolTipTitle" => __("Click this to create a <strong>new folder</strong>", RML_TD),
                        "toolTipText" => __("A folder can contain every type of file and collections, but no galleries. If you want to create a subfolder simply select a folder from the list and click this button.", RML_TD)
                    ),
                    "collection" => array(
                        "toolTipTitle" => __("Click this to create a <strong>new collection</strong>", RML_TD),
                        "toolTipText" => __("A collection can contain no files. But you can create there other collections and <strong>galleries</strong>. The mentioned above gallery is only a <i>gallery data folder</i>, that means they are not automatically in your frontend (your website).<br/><br/>You can create a <strong>visual gallery</strong> from this <i>gallery data folder</i> via the Visual Editor in your page/post.", RML_TD)
                    ),
                    "gallery" => array(
                        "toolTipTitle" => __("Click this to create a <strong>new gallery data folder</strong>", RML_TD),
                        "toolTipText" => __("A <i>gallery data folder</i> can only contain images. It is simplier for you to distinguish where your visual galleries are.<br/><br/>You can also order the images into <strong>a custom image order</strong> per drag&drop.", RML_TD)
                    )
                ),
                "toolbarItems" => array(
                    "settings" => array(
                        "toolTipTitle" => __("Settings", RML_TD),
                        "toolTipText" => __('General settings for the current logged in user.', RML_TD)
                    ),
                    "order" => array(
                        "toolTipTitle" => __("Reorder files in this folder", RML_TD),
                        "toolTipText" => __('Start to reorder the files / images in the current folder. The order of a folder can be reset in the media options.<br />Go to the folder details to order a folder by <strong>title, filename, ID, ...</strong>', RML_TD),
                        "toolTipTextDisabledLink" => __("The selected folder <strong>does not allow</strong> to reorder the files / images.", RML_TD)
                    ),
                    "refresh" => array(
                        "toolTipTitle" => __("Refresh", RML_TD),
                        "toolTipText" => __("Refreshes the current folder view.", RML_TD)
                    ),
                    "rename" => array(
                        "toolTipTitle" => __("Rename", RML_TD),
                        "toolTipText" => __("Rename the current selected folder.", RML_TD),
                        "toolTipTextDisabledLink" => __("The selected folder can not be renamed. Please select a user-created folder.", RML_TD)
                    ),
                    "delete" => array(
                        "toolTipTitle" => __("Delete", RML_TD),
                        "toolTipText" => __("Delete the current selected folder.", RML_TD),
                        "toolTipTextDisabledLink" => __("The selected folder can not be deleted. Please select a user-created folder.", RML_TD)
                    ),
                    "rearrange" => array(
                        "toolTipTitle" => __("Rearrange", RML_TD),
                        "toolTipText" => __("Change the hierarchical order of the folders.", RML_TD)
                    ),
                    "details" => array(
                        "toolTipTitle" => __("Folder details", RML_TD),
                        "toolTipText" => __("Select a folder and view more details about it.", RML_TD),
                        "toolTipTextDisabledLink" => __("Please select a folder to make this button enabled.", RML_TD)
                    ),
                    "restrictionsInherits" => __("New folders inherit this restriction", RML_TD),
                    "restrictionsSuffix" => __("The current selected folder has some restrictions:", RML_TD),
                    "restrictions" => array(
                        "par" => __("You can not change <b>parent</b> folder"),
                        "rea" => __("You can not <b>rearrange</b> subfolders", RML_TD),
                        "cre" => __("You can not <b>create</b> subfolders", RML_TD),
                        "ins" => __("You can not <b>insert</b> new files. New files will be moved to Uncategorized...", RML_TD),
                        "ren" => __("You can not <b>rename</b> the folder", RML_TD),
                        "del" => __("You can not <b>delete</b> the folder", RML_TD),
                        "mov" => __("You can not <b>move</b> files outside the folder", RML_TD)
                    )
                ),
                'mceButtonTooltip' => __('Gallery from Media Folder', RML_TD),
                'mceListBoxDirsTooltip' => __('Note: You can only select galleries. Folders and collections are grayed.', RML_TD),
                'mceBodyGallery' => __('Folder', RML_TD),
                'mceBodyLinkTo' => __('Link to'),
                'mceBodyColumns' => __('Columns'),
                'mceBodyRandomOrder' => __('Random Order'),
                'mceBodySize' => __('Size'),
                'mceBodyLinkToValues' => array(
                    array("value" => "post", "text" => __('Attachment File')),
                    array("value" => "file", "text" => __('Media File')),
                    array("value" => "none", "text" => __('None'))
                ),
                'mceBodySizeValues' => array(
                    array("value" => "thumbnail", "text" => __('Thumbnail')),
                    array("value" => "medium", "text" => __('Medium')),
                    array("value" => "large", "text" => __('Large')),
                    array("value" => "full", "text" => __('Full Size'))
                )
            )
        );
    }
    
    /*
     * Modify the media view strings for a shortcut hint in the media grid view.
     * This function is also used to return the single string for the note when 
     * $strings is false.
     * 
     * 'warnDelete'    
     * 'warnBulkDelete'
     * @return array
     */
    public function media_view_strings($strings) {
        $str = __("\n\nNote: If you want to delete a shortcut file, the source file will NOT bet deleted.\nIf you want to delete a non-shortcut file, all associated shortcuts are deleted, too.", RML_TD);
        if ($strings === false) {
            return $str;
        }
        
        if (isset($strings["warnDelete"])) {
            $strings["warnDelete"] .= $str;
        }
        if (isset($strings["warnBulkDelete"])) {
            $strings["warnBulkDelete"] .= $str;
        }
        return $strings;
    }
    
    /*
     * Modify the media view strings for a shortcut hint in the media table view.
     * 
     * @see this::media_view_strings
     */
    public function media_row_actions($actions, $post) {
        if (isset($actions["delete"])) {
            $actions["delete"] = str_replace("showNotice.warn();", "window.rml.library.warnDelete();", $actions["delete"]);
        }
        
        // Add a table mode "helper" to create the rml icon
        if (wp_attachment_is_shortcut($post)) {
            $actions["rmlShortcutSpan"] = '&nbsp;';
        }
        return $actions;
    }
    
    public function plugin_row_meta($links, $file) {
        if( false !== strpos($file, '/real-media-library.php') ){
            $links[] = '<a target="_blank" href="https://matthias-web.com/wordpress/real-media-library/add-ons/"><strong>'.__('Browse Add-Ons', RML_TD).'</strong></a>';
        } 
        return $links;
    }
    
    /*
     * Enqueue scripts and styles for the RML sidebar
     */
    public function admin_enqueue_scripts($hook) {
        if (!_wp_rml_active()) {
            return;
        }
        
        $debug = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG === true;
        $min = !$debug ? ".min" : "";
        $aioTreeVersion = "1.3.12";
        
        /*==============================
    	 * Scripts
    	 * 
    	 * The javascript should be build like it is
    	 * not conflicting with any other plugin. So, RML
    	 * includes a own hook system window.rmlHooks
    	 *
    	 * jQuery scripts (Helper)
    	 * core.js, widget.js, mouse.js, draggable.js, droppable.js, sortable.js
    	 */
    	$requires = array("jquery", "jquery-ui-core", "jquery-ui-widget", "jquery-ui-mouse", "jquery-ui-draggable", "jquery-ui-droppable", "jquery-ui-sortable", "jquery-touch-punch");
        foreach ($requires as $value) {
            wp_enqueue_script($value);
        }
    	
    	/*
    	 * Only minified scripts
    	 */
    	wp_enqueue_script('jquery-tooltipster',             plugins_url( 'assets/lib/tooltipster/jquery.tooltipster' . $min . '.js', RML_FILE ), $requires, RML_VERSION);
    	wp_enqueue_script('jquery-nested-sortable',         plugins_url( 'assets/lib/nestedSortable/jquery.mjs.nestedSortable' . $min . '.js', RML_FILE ), $requires, RML_VERSION);
        wp_enqueue_script('jquery-aio-tree',                plugins_url( 'assets/extlib/allInOneTree/jquery.ui.aio-tree' . $min . '.js', RML_FILE ), $requires, $aioTreeVersion);
    	wp_enqueue_script('wp-media-picker',                plugins_url( 'assets/extlib/wpMediaPicker/jquery.wp-media-picker' . $min . '.js', RML_FILE ), $requires, RML_VERSION);

    	/*
    	 * RML relevant scripts (hooks)
    	 */
    	if (!$debug) {
    	    wp_enqueue_script('rml-general',                plugins_url( 'assets/js/realmedialibrary.min.js', RML_FILE ), $requires, RML_VERSION);
    	}else{
        	wp_enqueue_script('rml-general',                plugins_url( 'assets/js/rml.0-general.js', RML_FILE ), $requires, RML_VERSION);
        	wp_enqueue_script('rml-library',                plugins_url( 'assets/js/rml.1-library.js', RML_FILE ), $requires, RML_VERSION);
        	wp_enqueue_script('rml-grid',                   plugins_url( 'assets/js/rml.2-grid.js', RML_FILE ), $requires, RML_VERSION);
        	wp_enqueue_script('rml-list',                   plugins_url( 'assets/js/rml.3-list.js', RML_FILE ), $requires, RML_VERSION);
        	wp_enqueue_script('rml-modal',                  plugins_url( 'assets/js/rml.4-modal.js', RML_FILE ), $requires, RML_VERSION);
        	wp_enqueue_script('rml-order',                  plugins_url( 'assets/js/rml.5-order.js', RML_FILE ), $requires, RML_VERSION);
        	wp_enqueue_script('rml-meta',                   plugins_url( 'assets/js/rml.6-meta.js', RML_FILE ), $requires, RML_VERSION);
        	wp_enqueue_script('rml-uploader',               plugins_url( 'assets/js/rml.8-uploader.js', RML_FILE ), $requires, RML_VERSION);
        	wp_enqueue_script('rml-options',                plugins_url( 'assets/js/rml.7-options.js', RML_FILE ), $requires, RML_VERSION);
            wp_enqueue_script('rml-usersettings',           plugins_url( 'assets/js/rml.9-usersettings.js', RML_FILE ), $requires, RML_VERSION);
        	wp_enqueue_script('rml-main',                   plugins_url( 'assets/js/rml.99-main.js', RML_FILE ), $requires, RML_VERSION);
    	}
    	wp_localize_script('rml-general', 'rmlOpts',        apply_filters('RML/Backend/JS_Localize', $this->localize_js()));
    	
    	/*==============================
    	 * Styles
    	 */
    	
    	wp_enqueue_style('font-awesome',                    'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
    	wp_enqueue_style('rml-font',                        plugins_url( 'assets/minifyfont/css/minifyfont.css', RML_FILE ), array(), RML_VERSION);
        wp_enqueue_style('jquery-tooltipster',              plugins_url( 'assets/lib/tooltipster/tooltipster' . $min . '.css', RML_FILE ), array(), RML_VERSION);
        wp_enqueue_style('aio-tree',                        plugins_url( 'assets/extlib/allInOneTree/jquery.ui.aio-tree' . $min . '.css', RML_FILE ), array(), $aioTreeVersion);
        wp_enqueue_style('aio-tree-theme-wordpress',        plugins_url( 'assets/extlib/allInOneTree/jquery.ui.aio-tree-theme-wordpress' . $min . '.css', RML_FILE ), array(), $aioTreeVersion);
        wp_enqueue_style('wp-media-picker',                 plugins_url( 'assets/extlib/wpMediaPicker/jquery.wp-media-picker' . $min . '.css', RML_FILE ), array(), RML_VERSION);
        wp_enqueue_style('rml-main-style',                  plugins_url( 'assets/css/style' . $min . '.css', RML_FILE ), array(), RML_VERSION);
        wp_enqueue_style('rml-sweetalert',                  plugins_url( 'assets/extlib/sweetalert/sweetalert' . $min . '.css', RML_FILE ), array(), RML_VERSION);
    	
    	/*
    	 * Options media relevant styles
    	 */
    	if ($this->isScreenBase("options-media")) {
    	    wp_enqueue_style('rml-options-style',           plugins_url( 'assets/css/options' . $min . '.css', RML_FILE ), array(), RML_VERSION);
    	}
    	
    	/*a
	     * This action is fired when all scripts and styles of Real Media Library
	     * are enqueued to WordPress. You do not need to worry about front-end functionlity.
	     * You also need not to worry about permissions, the action is only fired
	     * when the user is allowed to use Real Media Library.
	     * 
	     * @example <caption>Enqueue a script</caption>
	     * add_action( 'RML/Backend/Scripts', function() {
	     *  wp_enqueue_script('my-rml-script', 'script.js', array('rml-general', 'rml-main'));
	     * } );
	     * @action RML/Backend/Scripts
	     * @see {@link RML/Backend/Scripts/MediaLibrary} for media library only scripts
	     */
    	do_action("RML/Backend/Scripts");
    	
    	if ($this->isScreenBase("upload")) {
    	    /*a
    	     * This action is fired when all scripts and styles of Real Media Library
    	     * are enqueued to WordPress <strong>AND</strong> the current screen is upload.php.
    	     * 
    	     * @example <caption>Enqueue a script to the upload.php screen</caption>
    	     * add_action( 'RML/Backend/Scripts/MediaLibrary', function() {
    	     *  wp_enqueue_script('my-rml-script', 'script.js', array('rml-general', 'rml-main'));
    	     * } );
    	     * @action RML/Backend/Scripts/MediaLibrary
    	     * @see {@link RML/Backend/Scripts} for global scripts
    	     */
    	    do_action("RML/Backend/Scripts/MediaLibrary");
    	}
    }
    
    public function isScreenBase($base, $log = false) {
        if (function_exists("get_current_screen")) {
            $screen = get_current_screen();
        }else{
            return false;
        }
        
        if ($log) {
            error_log($screen->base);
        }
        
        if (isset($screen->base)) {
            return $screen->base == $base;
        }else{
            return false;
        }
    }
    
    public function admin_footer() {
        if ($this->isScreenBase("theme-install") || !_wp_rml_active()) {
            return;
        }
        
        $pathes = array(
            "inc/admin_footer/sidebar.dummy.php",
            );
            
        for ($i = 0; $i < count($pathes); $i++) {
            require_once(RML_PATH . '/' . $pathes[$i]);
        }
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new Backend();
        }
        return self::$me;
    }
}

?>