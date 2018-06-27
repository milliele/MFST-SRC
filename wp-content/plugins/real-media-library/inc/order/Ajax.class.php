<?php
namespace MatthiasWeb\RealMediaLibrary\order;
use MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Handles ajax requests for the order functionality.
 */
class Ajax extends general\Base {
    
    private static $me = null;

    private function __construct() {
        // Silence is golden.
    }
    
    /*
     * Create nonces for the order ajax requests.
     * 
     * @hooked RML/Backend/Nonces
     */
    public function nonces($nonces) {
        $nonces["attachmentOrder"] = wp_create_nonce("rmlAjaxAttachmentOrder");
        $nonces["attachmentOrderResetAll"] = wp_create_nonce("rmlAjaxAttachmentOrderResetAll");
        $nonces["attachmentOrderReset"] = wp_create_nonce("rmlAjaxAttachmentOrderReset");
        $nonces["attachmentOrderReindex"] = wp_create_nonce("rmlAjaxAttachmentOrderReindex");
        $nonces["attachmentOrderBy"] = wp_create_nonce("rmlAjaxAttachmentOrderBy");
        $nonces["attachmentOrderByLastCustom"] = wp_create_nonce("rmlAjaxAttachmentOrderByLastCustom");
        return $nonces;
    }
    
    /*
     * Set an order by a given string
     * 
     * @REQUEST method the id of the folder (must be gallery)
     * @see Order
     */
    public function wp_ajax_attachment_order_by() {
        // Security checks
        general\Util::getInstance()->checkNonce('rmlAjaxAttachmentOrderBy');
        
        // Process
        $fid = isset($_REQUEST["method"]) ? $_REQUEST["method"] : 0;
        $orderby = isset($_REQUEST["orderby"]) ? $_REQUEST["orderby"] : null;
        
        if ($fid > 0 && !empty($orderby) && GalleryOrder::order($fid, $orderby)) {
            wp_send_json_success();
        }else{
            wp_send_json_error();
        }
    }
    
    /*
     * Reset a given gallery to the last custom order.
     * 
     * @REQUEST method the id of the folder (must be gallery)
     * @see Order
     */
    public function wp_ajax_attachment_order_by_last_custom() {
        // Security checks
        general\Util::getInstance()->checkNonce('rmlAjaxAttachmentOrderByLastCustom');
        
        // Process
        $fid = isset($_REQUEST["method"]) ? $_REQUEST["method"] : 0;
        $folder = wp_rml_get_object_by_id($fid);
        if (is_rml_folder($folder) && $folder->getContentOldCustomNrCount() > 0 && $folder->contentRestoreOldCustomNr()) {
            wp_send_json_success();
        }else{
            wp_send_json_error();
        }
    }
    
    /*
     * Reset an order for a given folder id.
     * 
     * @REQUEST method the id of the folder (must be gallery)
     * @see Order
     */
    public function wp_ajax_attachment_order_reset() {
        // Security checks
        general\Util::getInstance()->checkNonce('rmlAjaxAttachmentOrderReset');
        
        // Process
        $fid = isset($_REQUEST["method"]) ? $_REQUEST["method"] : null;
        $folder = wp_rml_get_object_by_id($fid);
        if (is_rml_folder($folder) && $folder->contentDeleteOrder()) {
            wp_send_json_success();
        }else{
            wp_send_json_error();
        }
    }
    
    /*
     * Reindex an order for a given folder id.
     * 
     * @REQUEST method the id of the folder (must be gallery)
     * @see Order
     */
    public function wp_ajax_attachment_order_reindex() {
        // Security checks
        general\Util::getInstance()->checkNonce('rmlAjaxAttachmentOrderReindex');
        
        // Process
        $fid = isset($_REQUEST["method"]) ? $_REQUEST["method"] : null;
        $folder = wp_rml_get_object_by_id($fid);
        if (is_rml_folder($folder) && $folder->contentReindex()) {
            wp_send_json_success();
        }else{
            wp_send_json_error();
        }
    }
    
    /*
     * Order a gallery.
     * 
     * @POST attachmentId The attachment id which should be moved
     * @POST nextId The next attachment id to attachmentId or false for the end
     * @POST lastId The last attachment id of the view in frontend
     */
    public function wp_ajax_attachment_order() {
        // Security checks
        general\Util::getInstance()->checkNonce('rmlAjaxAttachmentOrder');
        
        // Process
        $folderId = isset($_POST["folderId"]) ? $_POST["folderId"] : null;
        $attachmentId = isset($_POST["attachmentId"]) ? $_POST["attachmentId"] : 0;
        $nextId = isset($_POST["nextId"]) ? $_POST["nextId"] : false;
        $lastIdInView = isset($_POST["lastId"]) ? $_POST["lastId"] : false;
        
        if ($nextId === "false") {
            $nextId = false;
        }
        
        wp_attachment_order_update($folderId, $attachmentId, $nextId, $lastIdInView);
    }
    
    /*
     * Reset all orders of all galleries.
     */
    public function wp_ajax_attachment_order_reset_all() {
        // Security checks
        general\Util::getInstance()->checkNonce('rmlAjaxAttachmentOrderResetAll');
        
        // Process
        Sortable::delete_all_order();
        wp_send_json_success();
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new Ajax();
        }
        return self::$me;
    }
}