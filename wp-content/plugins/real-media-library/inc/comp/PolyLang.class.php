<?php
namespace MatthiasWeb\RealMediaLibrary\comp;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\attachment;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * This class handles the compatibility for poly lang.
 */
class PolyLang extends general\Base {
    
    private static $me = null;
    
    private $active = false;
    
    /*
     * Avoid duplicate call of move action
     */
    private $previousIds = null;
    private $previousFolderId = null;
    
    /*
     * C'tor
     */
    private function __construct($root = null) {
        // Silence is golden.
    }
    
    public function init() {
        $this->active = function_exists("pll_get_post_translations");
        
        if ($this->active) {
            add_action('pll_translate_media',       array($this, 'pll_translate_media'), 10, 3);
            add_action('RML/Options/Register',      array($this, 'options_register'));
            add_action('RML/Item/MoveFinished',     array($this, 'item_move_finished'), 10, 4);
        }
    }
    
    /*
     * Register option for PolyLang
     */
    public function options_register() {
        register_setting( 'media', 'rml_polylang_move', 'esc_attr' );
        add_settings_field(
            'rml_polylang_move',
            '<label for="rml_polylang_move">'.__('PolyLang: Automatically move translations' , RML_TD ).'</label>' ,
            array($this, 'html_options_move'),
            'media',
            'rml_options_general'
        );
    }
    
    public function html_options_move() {
        $value = get_option( 'rml_polylang_move', '1' );
        echo '<input type="checkbox" id="rml_polylang_move"
                name="rml_polylang_move" value="1" ' . checked(1, $value, false) . ' />
                <label>' . __('If you move a file also move the associated translation files.', RML_TD) . '</label>';
    }
    
    /*
     * A file is moved (not copied) and then move also all the translations.
     */
    public function item_move_finished($folderId, $ids, $folder, $isShortcut) {
        if (!$isShortcut && get_option( 'rml_polylang_move', '1' ) === '1'
            && json_encode($ids) !== json_encode($this->previousIds)
            && $folderId !== $this->previousFolderId) {
            $moveToFolder = array();
            $this->previousFolderId = $folderId;
            $this->previousIds = $ids;
            
            // Iterate all moved ids
            foreach ($ids as $post_id) {
                $translations = pll_get_post_translations($post_id);
                
                // Iterate all translation ids
                foreach ($translations as $tr_id) {
                    if (!in_array($tr_id, $ids)) {
                        $moveToFolder[] = $tr_id;
                    }
                }
            }
            
            if (count($moveToFolder) > 0) {
                $this->debug("Polylang: While moving to folder $folderId there are some translations which also must be moved: " . json_encode($moveToFolder), __METHOD__);
                wp_rml_move($folderId, $moveToFolder);
            }
        }
    }
    
    /*
     * New translation created => synchronize with original post.
     * Then reset the count cache for the unogranized folder.
     */
    public function pll_translate_media($post_id, $tr_id, $lang_slug) {
        $folderId = wp_attachment_folder($post_id);
        _wp_rml_synchronize_attachment($tr_id, $folderId);
        $this->debug("Polylang: Move translation id " . $tr_id . " to the original file (" . $post_id . ") folder id " . $folderId, __METHOD__);
        
        attachment\CountCache::getInstance()->addNewAttachment($tr_id)
            ->resetCountCacheOnWpDie(_wp_rml_root());
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new PolyLang();
        }
        return self::$me;
    }
}

?>