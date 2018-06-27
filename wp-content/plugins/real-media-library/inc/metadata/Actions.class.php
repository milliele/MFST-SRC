<?php
namespace MatthiasWeb\RealMediaLibrary\metadata;
use MatthiasWeb\RealMediaLibrary\api;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Implements action buttons to the meta box. The <a>-button
 * must have an id so it can be registered to the hook
 * metadata/action/{ID}. The ID should start with prefix
 * rml-meta-action-{ID}.
 * 
 * @see interface IMetadata
 * @see meta.js for more details and javascript hooks
 * @filter RML/Folder/Meta/ActionButtons (Parameter: array of buttons)
 */
class Actions implements api\IMetadata {
    public function scripts() {
        // @see meta.js for the hook folderMeta/action/{ID}
    }
    
    /*
     * The general custom fields. This creates the field for
     * a cover image.
     */
    public function content($content, $folder) {
        /*f
         * Implements action buttons to the meta box. The <a>-button
         * must have an id so it can be registered to the hook
         * metadata/action/{ID}. The ID should start with prefix
         * rml-meta-action-{ID}.
         * 
         * @param {string[]} $buttons The HTML buttons
         * @param {IFolder} $folder The folder object
         * @returns {string[]}
         * @filter RML/Folder/Meta/ActionsButtons
         */
        $buttons = apply_filters("RML/Folder/Meta/ActionButtons", array(), $folder);
        
        if (count($buttons) > 0) {
            $content .= '<tr>
                <th scope="row">' . __('Actions', RML_TD) . '</th>
                <td><fieldset>' . implode(" ", $buttons) . '</fieldset></td>
            </tr>';
        }
        
        return $content;
    }
    
    /*
     * Nothing to save.
     */
    public function save($response, $folder) {
        return $response;
    }
}