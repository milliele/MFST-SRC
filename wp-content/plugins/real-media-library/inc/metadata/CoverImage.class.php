<?php
namespace MatthiasWeb\RealMediaLibrary\metadata;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\api;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Implements a cover image for root folder, collections,
 * galleries and normal folders.
 * 
 * @see interface IMetadata
 * @see meta.js for more details and javascript hooks
 */
class CoverImage implements api\IMetadata {
    public function __construct() {
        add_action("delete_attachment", array($this, "delete_attachment"));
    }
    
    public function delete_attachment($postid) {
        delete_metadata('realmedialibrary', null, "coverImage", $postid, true);
    }
    
    /*
     * Enqueue scripts and styles for the media picker.
     * 
     * @see meta.js for more informations about the media picker.
     */
    public function scripts() {
        if (general\Backend::getInstance()->isScreenBase("upload")) { 
            wp_enqueue_media();
            wp_enqueue_script( 'wp-media-picker', plugins_url( 'assets/js/jquery.wp-media-picker.js', RML_FILE ), array( 'jquery', 'jquery-ui-widget', 'media' ), '0.5.0', true );
            wp_enqueue_style( 'wp-media-picker', plugins_url( 'assets/css/jquery.wp-media-picker.css', RML_FILE ), array(), '0.5.0' );
        }
    }
    
    /*
     * The general custom fields. This creates the field for
     * a cover image.
     */
    public function content($content, $folder) {
        $content .= '<tr>
            <th scope="row">' . __('Cover image', RML_TD) . '</th>
            <td><div class="spinner is-active" style="float: initial;margin: 0;"></div>
                <fieldset style="display:none;">
                <input name="coverImage" id="coverImage" type="text" value="' . $this->getAttachmentID($folder->getId()) . '" class="rml-meta-media-picker">
            </fieldset></td>
        </tr>';
        
        return $content;
    }
    
    /*
     * Save the general infos: CoverImage.
     */
    public function save($response, $folder) {
        $fid = $folder->getId();
        if (isset($_POST["coverImage"])) {
            $newCoverImage = trim($_POST["coverImage"]);
            if (!is_numeric($newCoverImage)) {
                // delete meta
                delete_media_folder_meta($fid, "coverImage");
            }else if ($newCoverImage != $this->getAttachmentID($fid) && wp_attachment_is_image($newCoverImage)) {
                // update or add meta
                update_media_folder_meta($fid, "coverImage", $newCoverImage);
            }
        }
        
        return $response;
    }
    
    /*
     * Get the cover image of a given folder.
     * 
     * @param $fid The folder id
     * @return int Attachment ID or empty string
     */
    public function getAttachmentID($fid) {
        return get_media_folder_meta($fid, "coverImage", true);
    }
}