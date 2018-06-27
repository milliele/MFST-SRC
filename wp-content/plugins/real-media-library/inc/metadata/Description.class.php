<?php
namespace MatthiasWeb\RealMediaLibrary\metadata;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\api;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Implements a description field.
 * 
 * @see inc/api/meta.php
 * @see interface IMetadata for more details
 */
class Description implements api\IMetadata {
    
    public function getDescription($folder_id) {
        return get_media_folder_meta($folder_id, "description", true);
    }
    
    /*
     * The input field.
     *
     * @see interface IMetadata
     */
    public function content($content, $folder) {
        $description = $this->getDescription($folder->getId());
        $content .= '<tr>
            <th scope="row">' . __('Description') . '</th>
            <td>
                <textarea name="description" type="text" class="regular-text" style="width: 100%;box-sizing: border-box;">' . esc_textarea($description) . '</textarea>
            </td>
        </tr>
        <tr class="rml-meta-margin"></tr>';
        
        return $content;
    }
    
    /*
     * Save the general infos: Name
     * 
     * @see interface IMetadata
     */
    public function save($response, $folder) {
        $toSaveFID = $folder->getId();
        $description = $this->getDescription($toSaveFID);
        
        if (isset($_POST["description"])) {
            $newDesc = $_POST["description"];
            if ($newDesc != $description) {
                if (strlen($newDesc) > 0) {
                    update_media_folder_meta($toSaveFID, "description", $newDesc);
                }else{
                    // Delete it
                    delete_media_folder_meta($toSaveFID, "description");
                }
            }
        }
        
        return $response;
    }
    
    /*
     * The general scripts and styles.
     *
     * @see interface IMetadata
     */
    public function scripts() {
        // Silence is golden.
    }
}