<?php
namespace MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\comp;
use MatthiasWeb\RealMediaLibrary\folder;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Handling AJAX requests.
 */
class Ajax {
	private static $me = null;
        
    private function __construct() {
        // Silence is golden.
    }
    
    /*
     * Get the shortcut info container for a specific post id.
     */
    public function wp_ajax_rml_shortcut_infos() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxShortcutInfo');
        if (isset($_POST["id"])) {
            echo attachment\CustomField::getInstance()->getShortcutInfoContainer($_POST["id"]);
        }
        wp_die();
    }
    
    /*
     * Export json string of folders.
     */
    public function wp_ajax_export() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxWipe', 'manage_options');
        wp_send_json_success(array("str" => json_encode(comp\ExImport::getInstance()->getFolders() )));
    }
    
    /*
     * Import json string of folders.
     */
    public function wp_ajax_import() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxWipe', 'manage_options');
        $isTextImport = isset($_POST["import"]) && !empty($_POST["import"]);
        $isTaxImport = isset($_POST["method"]) && !empty($_POST["method"]);
        
        if ($isTextImport) {
            $import = urldecode($_POST["import"]);
            $import = json_decode($import, true);
            comp\ExImport::getInstance()->import($import);
            wp_send_json_success();
        }else if ($isTaxImport) {
            comp\ExImport::getInstance()->importTaxonomy($_POST["method"]);
            wp_send_json_success();
        }else{
            wp_send_json_error();
        }
    }
    
    /*
     * Resets the count of the folders.
     */
    public function wp_ajax_cnt_reset() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxWipe', 'manage_options');
        attachment\CountCache::getInstance()->resetCountCache();
        wp_send_json_success();
    }
    
    /*
     * Resets the slugs of the folders.
     */
    public function wp_ajax_slug_reset() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxWipe', 'manage_options');
        Util::getInstance()->resetAllSlugsAndAbsolutePathes("html_entity_decode");
        wp_send_json_success();
    }
    
    /*
     * Wipes the RML settings. That means: Attachment relations to
     * the folders and the folders.
     * 
     * @REQUEST method 'all' or 'rel'
     */
    public function wp_ajax_wipe() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxWipe', 'manage_options');
        
        // Process
        global $wpdb;
        $table_name = Core::getInstance()->getTableName();
        $table_posts = Core::getInstance()->getTableName("posts");
        
        $sqlMeta = "DELETE FROM $table_posts";
        $sqlFolders = "DELETE FROM $table_name";
        
        $method = $_REQUEST["method"];
        if ($method == "all") {
            $wpdb->query($sqlMeta);
            $wpdb->query($sqlFolders);
        }else if ($method == "rel") {
            $wpdb->query($sqlMeta);
        }
        attachment\CountCache::getInstance()->resetCountCache();
        
        /*a
         * The folder settings are wiped. $method can be replaced with "all"
         * or "rel".
         * 
         * @action RML/Wipe/$method
         */
        do_action("RML/Wipe/" + $method);
        wp_send_json_success();
    }
    
    /*
     * Creates a folder.
     * 
     * @POST name The name of the folder
     * @POST parent The ID of the parent folder, use _wp_rml_root() for Root level
     * @POST type The type of the folder (see /real-media-library.php contants)
     */
    public function wp_ajax_folder_create() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxFolderCreate');
        
        // Process
        $name = isset($_POST["name"]) ? $_POST["name"] : "";
        $parent = isset($_POST["parent"]) ? $_POST["parent"] : _wp_rml_root();
        $type = isset($_POST["type"]) ? $_POST["type"] : -1;
        
        $result = wp_rml_create($name, $parent, $type);
        
        if (is_array($result)) {
            wp_send_json_error($result);
        }else{
            wp_send_json_success(array("id" => $result));
        }
    }
    
    /*
     * Renames a folder.
     * 
     * @POST name The new name of the folder
     * @POST id The folder id which should be renamed
     */
    public function wp_ajax_folder_rename() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxFolderRename');
        
        // Process
        $name = isset($_POST["name"]) ? $_POST["name"] : "";
        $id = isset($_POST["id"]) ? $_POST["id"] : _wp_rml_root();
        
        $result = wp_rml_rename($name, $id);
        
        if ($result === true) {
            $folder = wp_rml_get_by_id($id, null, true);
            wp_send_json_success(array(
                "slug" => $folder->getAbsolutePath()
            ));
        }else{
            wp_send_json_error($result);
        }
    }
    
    /*
     * Deletes a folder.
     * 
     * @POST id The folder id
     */
    public function wp_ajax_folder_delete() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxFolderDelete');
        
        // Process
        $id = isset($_POST["id"]) ? $_POST["id"] : _wp_rml_root();
        
        $result = wp_rml_delete($id);
        
        if ($result === true) {
            wp_send_json_success();
        }else{
            wp_send_json_error($result);
        }
    }
    
    /*
     * Moves one or more attachments to a given folder.
     * 
     * @POST ids (array) One or more attachment ids
     * @POST to The folder id
     * @POST isShortcut Is the movement to create shortcuts (shortcuts)
     */
    public function wp_ajax_bulk_move() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxBulkMove');
        
        // Process
        $ids = isset($_POST["ids"]) ? $_POST["ids"] : null;
        $to = isset($_POST["to"]) ? $_POST["to"] : null;
        $isShortcut = isset($_POST["isShortcut"]) ? $_POST["isShortcut"] == "true" : false;
        
        $result = wp_rml_move($to, $ids, false, $isShortcut);
        
        if (is_array($result)) {
            wp_send_json_error($result);
        }else{
            wp_send_json_success();
        }
    }
    
    /*
     * Relocated a single entry in the folder tree. It is more performant as the bulk sort.
     * It uses the total prev and next id, that means it is collected from all <a> links in
     * the folder tree.
     */
    public function wp_ajax_relocate() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxBulkSort');
        
        // Process
        $id = isset($_POST["id"]) ? $_POST["id"] : null;
        $parent = isset($_POST["parent"]) ? $_POST["parent"] : null;
        $nextId = isset($_POST["nextId"]) && is_numeric($_POST["nextId"]) ? $_POST["nextId"] : false;
        
        $folder = wp_rml_get_object_by_id($id);
        if (is_rml_folder($folder)) {
            $result = $folder->relocate($parent, $nextId);
                
            if ($result === true) {
                wp_send_json_success();
            }else{
                wp_send_json_error(implode(" ", $result));
            }
        }else{
            wp_send_json_error();
        }
    }
    
    /*
     * Get the current folder count of one or more folders.
     * 
     * @REQUEST ids (array|string) Array or imploded (,) string of folder ids
     *                             Use ALL for the all files count
     * @NOTICE this should be optimized with a single query
     */
    public function wp_ajax_folder_count() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxFolderCount');
        
        $result = array();
        $struct = attachment\Structure::getInstance();
        
        // Default folder counts
        $root = _wp_rml_root();
        $result[""] = $struct->getCntAttachments();
        $result[$root] = $struct->getCntRoot();
        
        // Iterate through our folders
        $folders = $struct->getParsed();
        if (is_array($folders)) {
            foreach ($folders as $value) {
                $id = $value->getId();
                $result[$id] = $value->getCnt();
            }
        }

        wp_send_json_success($result);
    }
    
    /*
     * Save the size of the resized sidebar so the sidebar.dummy.php
     * can modify the CSS.
     * 
     * @POST width The new width of the sidebar
     */
    public function wp_ajax_sidebar_resize() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxSidebarResize');
        
        // Process
        $width = isset($_POST["width"]) ? $_POST["width"] : 0;
        
        if ($width > 0) {
            setcookie( "rml_" . get_current_blog_id() . "_resize", $width, strtotime( '+365 days' ), '/' );
            wp_send_json_success();
        }else{
            wp_send_json_error();
        }
    }
    
    /*
     * Print out the content for the meta options (custom fields)
     * for a given folder id.
     * 
     * @POST folderId the folder id
     */
    public function wp_ajax_meta_content() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxMetaContent');
        
        // Process
        echo metadata\Meta::getInstance()->content();
    }
    
    /*
     * Get the HTML for the real media library nodes.
     * 
     * @void
     */
    public function wp_ajax_tree_content() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxTreeContent');
        
        $selected = isset($_POST["rml_folder"]) ? $_POST["rml_folder"] : "";
        
        // Tree HTML
        $result = array();
        $folders = attachment\Structure::getInstance();
        $view = $folders->getView();
        $result["nodes"] = $view->treeHTML($selected);
        
        // Slug array
        $result["namesSlug"] = $view->namesSlugArray();
        $result["cntRoot"] = $folders->getCntRoot();
        
        // Names slug array
        wp_send_json_success($result);
    }
    
    /*
     * Get the mce options for the folder shortcode
     * 
     * @see FolderShortcode::localize
     */
    public function wp_ajax_mce_options() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxTreeContent');
        
        // Tree HTML
        $result = FolderShortcode::getInstance()->localize(array());

        // Names slug array
        wp_send_json_success($result);
    }
    
    /*
     * Get <options> for a <select> dropdown with only folders and galleries selectable.
     * 
     * @see wp_rml_dropdown
     * @see attachment\Upload
     */
    public function wp_ajax_options_default() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxTreeContent');
        
        wp_send_json_success(wp_rml_dropdown(attachment\Filter::getInstance()->lastQueriedFolder(), array(RML_TYPE_COLLECTION, RML_TYPE_ALL)));
    }
    
    /*
     * Dismiss an update notice.
     * 
     * @POST build The build version of the update
     */
    public function wp_ajax_migrate_dismiss() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxMigrateDismiss');
        
        // Process
        $build = isset($_POST["build"]) ? $_POST["build"] : 0;
        
        if ($build > 0) {
            Migration::getInstance()->dismiss($build);
        }
    }
    
    /*
     * Make an migration update
     * 
     * @GET method The build version of the update
     */
    public function wp_ajax_migration() {
        // Security checks
        Util::getInstance()->checkNonce('rmlAjaxMigrateDismiss');
        
        // Process
        $build = isset($_REQUEST["method"]) ? $_REQUEST["method"] : 0;
        
        if ($build > 0) {
            $migration = Migration::getInstance();
            switch ($build) {
                case "07102016":
                    $migration->do_07102016();
                    wp_send_json_success();
                    break;
                case "20161229":
                    $migration->do_20161229();
                    wp_send_json_success();
                    break;
                default:
                    break;
            }
        }
        wp_send_json_error();
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new Ajax();
        }
        return self::$me;
    }
}

?>