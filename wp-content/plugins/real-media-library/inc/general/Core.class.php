<?php
namespace MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\order;
use MatthiasWeb\RealMediaLibrary\metadata;
use MatthiasWeb\RealMediaLibrary\usersettings;
use MatthiasWeb\RealMediaLibrary\comp;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Include files, where autoloading is not possible
require_once("Base.class.php");
require_once("Exceptions.collection.php");

/*
 * Register the following things needed for the plugin.
 *  Filters & Actions
 *  Database initialization / update
 *  Trigger migration system
 *  Register autoload
 *  Register textdomain
 */
class Core extends Base {
    
    private static $me = null;
    
    private function __construct() {
        // Register autoload
        spl_autoload_register(array($this, 'autoload_register'));
        
        // Load API
        require_once(RML_PATH . "/inc/api/attachment.php");
        require_once(RML_PATH . "/inc/api/folders.php");
        require_once(RML_PATH . "/inc/api/meta.php");
        
        // Register creatables
        wp_rml_register_creatable(RML_NS . '\\folder\\Folder', RML_TYPE_FOLDER);
        wp_rml_register_creatable(RML_NS . '\\folder\\Collection', RML_TYPE_COLLECTION);
        wp_rml_register_creatable(RML_NS . '\\folder\\Gallery', RML_TYPE_GALLERY);

        // Permissions
        add_filter('RML/Folder/TreeNodeLi/Class',               array(attachment\Permissions::getInstance(), 'liClass'), 10, 2);
        add_filter('RML/Validate/Insert',                       array(attachment\Permissions::getInstance(), 'insert'), 10, 3);
        add_filter('RML/Validate/Create',                       array(attachment\Permissions::getInstance(), 'create'), 10, 4);
        add_filter('RML/Validate/Rename',                       array(attachment\Permissions::getInstance(), 'setName'), 10, 3);
        add_filter('RML/Validate/Delete',                       array(attachment\Permissions::getInstance(), 'deleteFolder'), 10, 3);
        add_filter('wp_die_ajax_handler',                       array($this, 'update_count'));
        add_filter('wp_die_handler',                            array($this, 'update_count'));
        
        // Register actions
        add_action('admin_init',                                array(Options::getInstance(), 'register_fields'));
        add_action('RML/Migration',                             array(Migration::getInstance(), 'migration'), 10, 2);
        add_action('plugins_loaded',                            array(Migration::getInstance(), 'plugins_loaded'));
        add_action('plugins_loaded',                            array($this, 'plugins_loaded'));
        add_action('init',                                      array($this, 'init'));
        
        /*
         * ================================= COMPATIBILITY
         * Allow bigger compatibilities for other plugins.
         * Have a look at the class' constructors for all needed filters and actions.
         */
        add_action('init',                                      array(comp\PolyLang::getInstance(), 'init'));
        add_action('init',                                      array(comp\WPML::getInstance(), 'init'));
        add_action('init',                                      array(comp\PageBuilders::getInstance(), 'init'));
    }
    
    public function init() {
        global $shortcode_tags;
        $this->update_db_check();
        add_shortcode("folder-gallery", $shortcode_tags['gallery']);
        
        // Add our folder shortcode
        FolderShortcode::getInstance();
        
        /*
         * ================================= ACTIONS
         * General actions
         */
        if (Options::load_frontend()) {
            add_action('wp_enqueue_scripts',                    array(Backend::getInstance(), 'admin_enqueue_scripts') );
            add_action('wp_footer',                             array(Backend::getInstance(), 'admin_footer'));
        }
        add_action('customize_controls_print_footer_scripts',   array(Backend::getInstance(), 'admin_footer'));
        
        add_action('delete_attachment',                         array(attachment\Shortcut::getInstance(), 'delete_attachment'));
        //add_action('rest_api_init',                             array(REST::getInstance(), 'rest_api_init')); @TODO temporarely deactivated
        add_action('admin_enqueue_scripts',                     array(Backend::getInstance(), 'admin_enqueue_scripts'), 0 );
        add_action('admin_footer',                              array(Backend::getInstance(), 'admin_footer'));
        add_action('plugin_row_meta',                           array(Backend::getInstance(), 'plugin_row_meta'), 10, 2 );
        
        add_action('wp_ajax_rml_bulk_move',                     array(Ajax::getInstance(), 'wp_ajax_bulk_move'));
        add_action('wp_ajax_rml_bulk_sort',                     array(Ajax::getInstance(), 'wp_ajax_bulk_sort'));
        add_action('wp_ajax_rml_relocate',                      array(Ajax::getInstance(), 'wp_ajax_relocate'));
        add_action('wp_ajax_rml_folder_count',                  array(Ajax::getInstance(), 'wp_ajax_folder_count'));
        add_action('wp_ajax_rml_folder_rename',                 array(Ajax::getInstance(), 'wp_ajax_folder_rename'));
        add_action('wp_ajax_rml_folder_delete',                 array(Ajax::getInstance(), 'wp_ajax_folder_delete'));
        add_action('wp_ajax_rml_folder_create',                 array(Ajax::getInstance(), 'wp_ajax_folder_create'));
        add_action('wp_ajax_rml_cnt_reset',                     array(Ajax::getInstance(), 'wp_ajax_cnt_reset'));
        add_action('wp_ajax_rml_slug_reset',                    array(Ajax::getInstance(), 'wp_ajax_slug_reset'));
        add_action('wp_ajax_rml_wipe',                          array(Ajax::getInstance(), 'wp_ajax_wipe'));
        add_action('wp_ajax_rml_sidebar_resize',                array(Ajax::getInstance(), 'wp_ajax_sidebar_resize'));
        add_action('wp_ajax_rml_tree_content',                  array(Ajax::getInstance(), 'wp_ajax_tree_content'));
        add_action('wp_ajax_rml_mce_options',                   array(Ajax::getInstance(), 'wp_ajax_mce_options'));
        add_action('wp_ajax_rml_options_default',               array(Ajax::getInstance(), 'wp_ajax_options_default'));
        add_action('wp_ajax_rml_migrate_dismiss',               array(Ajax::getInstance(), 'wp_ajax_migrate_dismiss'));
        add_action('wp_ajax_rml_migration',                     array(Ajax::getInstance(), 'wp_ajax_migration'));
        add_action('wp_ajax_rml_shortcut_infos',                array(Ajax::getInstance(), 'wp_ajax_rml_shortcut_infos'));
        add_action('wp_ajax_rml_export',                        array(Ajax::getInstance(), 'wp_ajax_export'));
        add_action('wp_ajax_rml_import',                        array(Ajax::getInstance(), 'wp_ajax_import'));
        
        add_action('pre-upload-ui',                             array(attachment\Upload::getInstance(), 'pre_upload_ui'));
        add_action('add_attachment',                            array(attachment\Upload::getInstance(), 'add_attachment'));
        
        add_action('wp_prepare_attachment_for_js',              array(attachment\Filter::getInstance(), 'wp_prepare_attachment_for_js'), 10, 3);
        add_action('delete_attachment',                         array(attachment\Filter::getInstance(), 'delete_attachment'));
        add_action('pre_get_posts',                             array(attachment\Filter::getInstance(), 'pre_get_posts'), RML_PRE_GET_POSTS_PRIORITY);
        
        // Order
        add_action('RML/Item/MoveFinished',                     array(RML_NS . '\\order\\Sortable', 'item_move_finished'), 1, 4);
        
        add_action('RML/Options/Register',                      array(order\Options::getInstance(), 'register'));
        add_action('RML/Options/Register',                      array(comp\ExImport::getInstance(), 'options_register'));
        
        add_action('wp_ajax_rml_attachment_order',              array(order\Ajax::getInstance(), 'wp_ajax_attachment_order'));
        add_action('wp_ajax_rml_attachment_order_reset_all',    array(order\Ajax::getInstance(), 'wp_ajax_attachment_order_reset_all'));
        add_action('wp_ajax_rml_attachment_order_reset',        array(order\Ajax::getInstance(), 'wp_ajax_attachment_order_reset'));
        add_action('wp_ajax_rml_attachment_order_reindex',      array(order\Ajax::getInstance(), 'wp_ajax_attachment_order_reindex'));
        add_action('wp_ajax_rml_attachment_order_by',           array(order\Ajax::getInstance(), 'wp_ajax_attachment_order_by'));
        add_action('wp_ajax_rml_attachment_order_by_last_custom', array(order\Ajax::getInstance(), 'wp_ajax_attachment_order_by_last_custom'));
        
        // Meta data
        add_action('RML/Folder/Deleted',                        array(metadata\Meta::getInstance(), 'folder_deleted'), 10, 2);

        add_action('wp_ajax_rml_meta_content',                  array(metadata\Ajax::getInstance(), 'wp_ajax_meta_content'));
        add_action('wp_ajax_rml_meta_save',                     array(metadata\Ajax::getInstance(), 'wp_ajax_meta_save'));
        
        /*
         * ================================= FILTERS
         * General filters:
         */
        add_filter('RML/Backend/JS_Localize',                   array(Util::getInstance(), 'nonces'));
        add_filter('RML/Backend/JS_Localize',                   array(RML_NS . '\\order\\Sortable', 'js_localize'));
        add_filter('posts_clauses',                             array(attachment\Filter::getInstance(), 'posts_clauses'), 10, 2);
        add_filter('media_view_strings',                        array(Backend::getInstance(), 'media_view_strings'));
        add_filter('media_row_actions',                         array(Backend::getInstance(), 'media_row_actions'), 10, 2);
        
        add_filter('add_post_metadata',                         array(attachment\Shortcut::getInstance(), 'add_post_metadata'), RML_PRE_GET_POSTS_PRIORITY, 5);
        add_filter('update_post_metadata',                      array(attachment\Shortcut::getInstance(), 'update_post_metadata'), RML_PRE_GET_POSTS_PRIORITY, 5);
        add_filter('get_post_metadata',                         array(attachment\Shortcut::getInstance(), 'get_post_metadata'), RML_PRE_GET_POSTS_PRIORITY, 4);
  
        add_filter('attachment_fields_to_edit',                 array(attachment\CustomField::getInstance(), 'attachment_fields_to_edit'), 10, 2);
        add_filter('attachment_fields_to_save',                 array(attachment\CustomField::getInstance(), 'attachment_fields_to_save'), 10 , 2);
                
        add_filter('restrict_manage_posts',                     array(attachment\Filter::getInstance(), 'restrict_manage_posts'));
        add_filter('ajax_query_attachments_args',               array(attachment\Filter::getInstance(), 'ajax_query_attachments_args'));
        add_filter('mla_media_modal_query_final_terms',         array(attachment\Filter::getInstance(), 'ajax_query_attachments_args'));

        add_filter('shortcode_atts_gallery',                    array(FolderShortcode::getInstance(), 'shortcode_atts_gallery'), 1, 3 );
                
        // Order
        add_filter('RML/Folder/Insert/AnyParentHasMeta',        array(RML_NS . '\\order\\Sortable', 'folder_insert_anyParentHasMetadata'), 10, 3);
        add_filter('RML/Folder/Insert/AnyParentHasMeta/orderAutomatically', array(RML_NS . '\\order\\Sortable', 'folder_insert_anyParentHasMetadata_orderAutomatically'), 10, 4);
        add_filter('posts_clauses',                             array(RML_NS . '\\order\\Sortable', 'posts_clauses'), 10, 2);
        add_filter('RML/Folder/TreeNode/Href',                  array(RML_NS . '\\order\\Sortable', 'treeHref'), 10, 3);
        add_filter('mla_media_modal_query_final_terms',         array(RML_NS . '\\order\\Sortable', 'mla_media_modal_query_final_terms'), 10, 2);
        
        add_filter('RML/Folder/Meta/ActionButtons',             array(order\Options::getInstance(), 'meta_actionbuttons'), 10, 2);
        add_filter("RML/Folder/TreeNode/Content",               array(order\Options::getInstance(), 'treeNode_content'), 10, 2);
                
        add_filter('RML/Backend/Nonces',                        array(order\Ajax::getInstance(), 'nonces'));
        
        // Meta data
        add_filter('RML/Backend/Nonces',                        array(metadata\Ajax::getInstance(), 'nonces'));
        
        /*
         * ================================= OTHERS
         */
        if (_wp_rml_active())
            add_thickbox();
        add_rml_meta_box( "general", metadata\Meta::getInstance(), false, 0 );
        add_rml_meta_box( "galleryOrder", new order\GalleryOrder(), false, 999 );
        add_rml_meta_box( "actions", new metadata\Actions(), false, 999 );
        
        // Internal testing
        //$test = new \MatthiasWeb\RealMediaLibrary\comp\complexquery\ResetNames($GLOBALS["wpdb"]);
        //$this->debug(wp_rml_create_all_parents_sql(10));
        //$this->debug($test->procedure());
        /*
        add_filter("RML/User/Settings/Groups", function($groups) {
            $groups["physical"] = __("Physical");
            return $groups;
        });
        add_rml_user_settings_box( "demo", new usersettings\Demo(), false, 10, "physical");
        add_rml_user_settings_box( "demo", new usersettings\Demo(), false);
        add_rml_meta_box( "coverImage", new metadata\CoverImage(), true );
        add_rml_meta_box( "description", new metadata\Description, false );
        */
    }
    
    /*
     * Autoload php files (classes and interfaces)
     */
    public function autoload_register($className) {
        $namespace = RML_NS . "\\";
        if (0 === strpos($className, $namespace)) {
            $name = substr($className, strlen($namespace));
            $last = explode("\\", $name);
            $isInterface = substr($last[count($last) - 1], 0, 1) === "I";
            $filename = RML_PATH . '/inc/' . str_replace('\\', '/', $name) . '.' . ($isInterface ? 'interface' : 'class') . '.php';
            if (file_exists($filename)) {
                require_once($filename);
            }
        }
    }
    
    /*
     * Plugins loaded. Localize the plugin
     */
    public function plugins_loaded() {
        load_plugin_textdomain( RML_TD, FALSE, dirname(plugin_basename(RML_FILE)) . '/languages/' );
    }
    
    /*
     * Updates the database version in the options and runs the migration system.
     * It also installs the needed database tables.
     * 
     * @see Migration class
     */
    public function update_db_check() {
        $installed = get_option( 'rml_db_version' );
        if ($installed != RML_VERSION) {
            $this->debug("(Re)install the database tables", __FUNCTION__);
            require_once(RML_PATH . '/inc/others/install.php');
            rml_install();
            $this->debug("Trigger the migration system from $installed to " . RML_VERSION, __METHOD__);
            
            /*a
             * This action is fired when Real Media Library gets an update.
             * 
             * @param {string} $installed The old installed version
             * @param {string} $new The new installed version
             * @action RML/Migration
             */
            do_action("RML/Migration", $installed, RML_VERSION);
        }
    }
    
    /*
     * Hack the wp die filter to make the last update count.
     * 
     * @filter wp_die_ajax_handler
     * @filter wp_die_handler
     * @see attachment\CountCache::wp_die
     */
    public function update_count($str) {
        attachment\CountCache::getInstance()->wp_die();
        return $str;
    }

    public function getTableName($name = "") {
        return self::tableName($name);
    }
    
    public static function tableName($name = "") {
        global $wpdb;
        return $wpdb->prefix . "realmedialibrary" . (($name == "") ? "" : "_" . $name);
    }
    
    public static function print_r($row) {
        echo '<pre>';
        print_r($row);
        echo '</pre>';
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new Core();
        }
        return self::$me;
    }
    
    public static function get_object_vars_from_public($obj) {
        return get_object_vars($obj);
    }
    
}