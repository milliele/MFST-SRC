<?php
namespace MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * This class handles all hooks for the custom field in a attachment.s
 */
class CustomField extends general\Base {
    private static $me = null;
    
    private function __construct() {
        // Silence is golden.
    }
    
    /*
     * When editing a attachment show up a select
     * option to change the parent folder.
     * 
     * @hooked attachment_fields_to_edit
     */
    public function attachment_fields_to_edit($form_fields, $post) {
        $folderID = wp_attachment_folder($post->ID);
        
        // Check move permission
        $selectDisabled = "";
        if ($folderID > 0) {
            $folder = wp_rml_get_object_by_id($folderID);
            if (is_rml_folder($folder) && $folder->isRestrictFor("mov")) {
                $selectDisabled = 'disabled="disabled"';
            }
        }
        
        $textToMove = wp_attachment_is_shortcut($post->ID)
                        ? __("When you move this shortcut, the folder location of the source/main file will not be changed.", RML_TD)
                        : __("When you move this attachment, the folder location of the associated shortcuts of this attachment will not be changed.", RML_TD);
        
        // Create form field
        $form_fields['rml_dir'] = array(
        	'label' => __('Folder', RML_TD),
        	'input' => 'html',
        	'html'  => 
        	    '<div class="rml-folder-edit" ' . $selectDisabled . '>' .
        	    Structure::getInstance()->getView()->getHTMLBreadcrumbByID($folderID) . '
        	       <select name="attachments[' . $post->ID . '][rml_folder]" ' . $selectDisabled . '>
	                        ' . Structure::getInstance()->getView()->optionsFasade($folderID, null, false) . '
        	       </select>
        	    </div>
        	    <p class="description">' . $textToMove . '</p>',
        );
        
        // Create form field
        $form_fields['rml_shortcut'] = array(
        	'label' => '',
        	'input' => 'html',
        	'html'  => '<div class="rml-shortcut-info" data-id="' . $post->ID . '"></div>'
        );
        return $form_fields;
    }
    
    public function getShortcutInfoContainer($postId) {
        $post = get_post($postId);
        $output = "";
        
        if ($post !== null) {
            // Return output
            $output = '<div class="rml-shortcut-info-container" data-id="' . $postId . '">
                <div style="clear:both;"></div>
                <h2>' . __('Shortcut infos', RML_TD) . '</h2>';
            
            $shortcut = wp_attachment_is_shortcut($post, true);
            $output .= '<p class="description">';
            if ($shortcut > 0) {
                $output .= __('This is a shortcut of a media library file. Shortcuts doesn\'t need any physical storage <strong>(0kb)</strong>. If you want to change the file itself, you must do this in the original file (for example replace media file through a plugin).<br/>Note also that the fields in the shortcuts can be different to the original file, for example "Title", "Description" or "Caption".', RML_TD) . '
                    <a target="_blank" href="' . admin_url("post.php?post=" . $shortcut . "&action=edit") . '">Open original file.</a>';
            }else{
                $shortcuts = wp_attachment_get_shortcuts($post->ID, false, true);
                $shortcutsCnt = count($shortcuts);
                if ($shortcutsCnt > 0) {
                    $output .= sprintf(_n("For this file is one shortcut available in the following folder:", "For this file are %s shortcuts available in the following folders:", $shortcutsCnt, RML_TD), $shortcutsCnt);
                    foreach ($shortcuts as $value) {
                        $folderName = $value["folderId"] == "-1" ? wp_rml_get_object_by_id(-1)->getName(true) : htmlentities($value["name"]);
                        $output .= '<div>';
                        $output .= $folderName . ' (<a target="_blank" href="' . admin_url("post.php?post=" . $value["attachment"] . "&action=edit") . '">Open shortcut file</a>)';
                        $output .= '</div>';
                    }
                }else{
                    $output .= __("This file has no associated shortcuts. You can create shortcuts by moving files per mouse and hold any key.", RML_TD);
                }
            }
            $output .= '</p>';
            
            /*f
             * This content is showed in the attachment details. It shows informations
             * about the shortcut.
             * 
             * @param {string} $output HTML output
             * @param {WP_Post} $post The attachment
             * @param {int} $shortcut If > 0 it is an attachment id (source)
             * @returns {string} The HTML output
             * @filter RML/Shortcut/Info
             */
            apply_filters("RML/Shortcut/Info", $output, $post, $shortcut);
    	    $output .= '</div>';
        }
        return $output;
    }
    
    /*
     * When saving a attachment change the parent folder.
     * 
     * @hooked attachment_fields_to_save
     */
    public function attachment_fields_to_save($post, $attachment) {
        if (isset($attachment['rml_folder'])){
            if (wp_rml_get_object_by_id($attachment['rml_folder']) === null) {
                $attachment['rml_folder'] = _wp_rml_root();
            }
            // Get previous folder id
            $updateCount = array(wp_attachment_folder($post["ID"]), $attachment["rml_folder"]);
            
            // Update to new folder id
            $result = wp_rml_move($attachment['rml_folder'], array($post['ID']));
            if (is_array($result)) {
                $post['errors']['rml_folder']['errors'][] = implode(" ", $result);
            }
            
            // Reset the count of both folders manually because we do not use the wp_rml_move api method
            CountCache::getInstance()->resetCountCache($updateCount);
        }
        
        return $post;
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new CustomField();
        }
        return self::$me;
    }
}