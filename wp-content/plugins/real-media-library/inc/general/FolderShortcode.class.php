<?php
namespace MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\folder;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Handles the shortcode for folder gallery.
 * 
 * @link https://generatewp.com/take-shortcodes-ultimate-level/
 */
class FolderShortcode {
    private static $me = null;
    public $shortcode_tag = 'folder_gallery';
    
    function __construct($args = array()){
        if ( is_admin() ){
            add_action('admin_head', array( $this, 'admin_head') );
            add_action('admin_enqueue_scripts', array($this , 'admin_enqueue_scripts' ));
        }
    }
    
    /*
     * Shortcode handler for FID attribute
     */
    public function shortcode_atts_gallery($out, $pairs, $atts) {
        $atts = shortcode_atts( array(
                'fid' => -2,
                'order' => 'DESC',
                'orderby' => 'date'
        ), $atts );
        
        // RML order is only available with ASC
        if ($atts["orderby"] == "rml") {
            $out["orderby"] = "menu_order ID";
        }
        
        if ($atts["fid"] > -2) {
            if ($atts["fid"] > -1) {
                $folder = wp_rml_get_object_by_id($atts["fid"]);
                if ($folder != null) {
                    $out["include"] .= ',' . implode(',', $folder->read($atts["order"], $atts["orderby"]));
                }
            }else{
                $out["include"] .= ',' . implode(',', folder\Creatable::xread(-1, $atts["order"], $atts["orderby"]));
            }
            $out["include"] = ltrim($out["include"], ',');
            $out["include"] = rtrim($out["include"], ',');
        }
        
        // Overwrite the default order by this shortcode
        if (isset($out["orderby"]) && $out["orderby"] == "menu_order ID") {
            $out["orderby"] = "post__in";
        }
        
        return $out;
    }
    
    /*
     * Prepares the array of names slug array for the tinyMCE editor
     * button to add the shortcode.
     * 
     * @param $namesSlug Result of View::namesSlugArray()
     * @param $withCustomAttributes Loads extra attributes to the array
     * @return array
     */
    private function prepareDirsForMCE($namesSlug, $withCustomAttributes = false) {
        $result = array(
            array("text" => __('Select'), "value" => "")
        );
        
        if (is_array($namesSlug) && count($namesSlug) > 0) {
            $allowAllFolders = get_option('rml_all_folders_gallery', '');
            for ($i = 0; $i < count($namesSlug["names"]); $i++) {
                $disabled = false;
                
                if ($allowAllFolders != "1" && $namesSlug["types"][$i] != "2") {
                    $disabled = true;
                }
                
                $cp = array(
                    "text" => $namesSlug["names"][$i],
                    "value" => $namesSlug["slugs"][$i],
                    "disabled" => $disabled
                );
                
                if ($withCustomAttributes) {
                    $cp["type"] = $namesSlug["types"][$i];
                }
                
                $result[] = $cp;
            }
        }
        
        return $result;
    }
    
    /*
     * Localized variables for TinyMCE shortcode generator.
     */
    public function localize($arr) {
        $ns = attachment\Structure::getInstance()->getView()->namesSlugArray();
        
        $arr["mce"] = array(
            "dirs" => $this->prepareDirsForMCE($ns, true),
            "raw" => $this->prepareDirsForMCE($ns)
        );
        
        return $arr;
    }
 
    /*
     * admin_head
     * calls your functions into the correct filters
     * @return void
     */
    function admin_head() {
        // check user permissions
        if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
            return;
        }
 
        // check if WYSIWYG is enabled
        if ( 'true' == get_user_option( 'rich_editing' ) ) {
            add_filter( 'mce_external_plugins', array( $this ,'mce_external_plugins' ) );
            add_filter( 'mce_buttons', array($this, 'mce_buttons' ) );
        }
    }
 
    /*
     * mce_external_plugins
     * Adds our tinymce plugin
     * @param  array $plugin_array
     * @return array
     */
    function mce_external_plugins( $plugin_array ) {
        $min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG === true ? '' : '.min';
        $plugin_array[$this->shortcode_tag] = plugins_url('assets/js/mce-buttons' . $min . '.js' , RML_FILE );
        return $plugin_array;
    }
 
    /*
     * mce_buttons
     * Adds our tinymce button
     * @param  array $buttons
     * @return array
     */
    function mce_buttons( $buttons ) {
        array_push( $buttons, $this->shortcode_tag );
        return $buttons;
    }
 
    /*
     * admin_enqueue_scripts
     * Used to enqueue custom styles
     * @return void
     */
    function admin_enqueue_scripts() {
        $min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG === true ? '' : '.min';
        wp_enqueue_style('folder_gallery_panel_shortcode', plugins_url( 'assets/css/mce-buttons' . $min . '.css' , RML_FILE ) );
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new FolderShortcode();
        }
        return self::$me;
    }
}