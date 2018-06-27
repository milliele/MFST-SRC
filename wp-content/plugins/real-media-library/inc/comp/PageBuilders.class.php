<?php
namespace MatthiasWeb\RealMediaLibrary\comp;
use MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * This class handles the compatibility for general page builders. If a page builder
 * has more compatibility options, please see / create another compatibility class.
 */
class PageBuilders extends general\Base {
    
    private static $me = null;
    
    /*
     * C'tor
     */
    private function __construct($root = null) {
        // Silence is golden.
    }
    
    public function init() {
        if (class_exists("Tatsu_Builder")) {
            $this->oshine_tatsu_builder();
        }
        if (defined('ELEMENTOR_VERSION')) {
            $this->elementor();
        }
        if (class_exists("Cornerstone_Preview_Frame_Loader")) {
            $this->cornerstone();
        }
    }
    
    private function cornerstone() {
        // @see class Cornerstone_Preview_Frame_Loader
        if ( ! isset( $_POST['cs_preview_state'] ) || ! $_POST['cs_preview_state'] || 'off' === $_POST['cs_preview_state'] ) {
            return;
        }
    
        // Nonce verification
        if ( ! isset( $_POST['_cs_nonce'] ) || ! wp_verify_nonce( $_POST['_cs_nonce'], 'cornerstone_nonce' ) ) {
            return;
        }
        
        add_filter("print_head_scripts",                            array($this, 'cornerstone_print_head_scripts'), 0);
    }
    
    public function cornerstone_print_head_scripts($res) {
        general\Backend::getInstance()->admin_enqueue_scripts(null);
        general\Backend::getInstance()->admin_footer();
        return $res;
    }
    
    private function elementor() {
        add_action('elementor/editor/before_enqueue_scripts',       array(general\Backend::getInstance(), 'admin_enqueue_scripts') );
        add_action('elementor/editor/wp_head',                      array(general\Backend::getInstance(), 'admin_footer'), 11);
    }
    
    /*
     * OSHINE TATSU PAGE BUILDER
     * https://themeforest.net/item/oshine-creative-multipurpose-wordpress-theme/9545812
     * 
     * The tatsu builder needs some custom CSS.
     */
    private function oshine_tatsu_builder() {
        add_action('tatsu_builder_head',                            array(general\Backend::getInstance(), 'admin_enqueue_scripts') );
        add_action('tatsu_builder_footer',                          array(general\Backend::getInstance(), 'admin_footer'), 11);
        add_action('tatsu_builder_footer',                          array($this, 'oshine_tatsu_builder_footer'), 9);
    }
    public function oshine_tatsu_builder_footer() {
        echo '<style>
.rml-container .aio-expander {
	top: -1px;
	left: -6px;
}
.rml-container .aio-list-standard a {
	padding: 5px 10px 5px 17px;
}</style>';
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new PageBuilders();
        }
        return self::$me;
    }
}

?>