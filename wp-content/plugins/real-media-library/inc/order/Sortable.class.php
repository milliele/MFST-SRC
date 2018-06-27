<?php
namespace MatthiasWeb\RealMediaLibrary\order;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\folder;
use MatthiasWeb\RealMediaLibrary\attachment;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Handles the sortable content in the folder. The methods of this class contains
 * always the keyword "content".
 * 
 * @see folder\Creatable
 * @see order.js
 */
abstract class Sortable extends folder\Creatable {
    
    /*
     * @attention Synced with folder\Creatable::__construct
     */
    public function __construct($id, $parent = -1, $name = "", $slug = "", $absolute = "", $order = -1, $cnt = 0, $row = array()) {
        $this->contentCustomOrder = isset($row->contentCustomOrder) ? $row->contentCustomOrder : "2";
        
        // Parent constructor
        parent::__construct($id, $parent, $name, $slug, $absolute, $order, $cnt, $row);
    }
    
    // Documentated in IFolderContent
    public function contentOrder($attachmentId, $nextId, $lastIdInView = false) {
        // Check, if the folder needs the order enabled first
        $contentCustomOrder = $this->getContentCustomOrder();
        if ($contentCustomOrder == 0 && !$this->contentEnableOrder()) {
            throw new \Exception(__("The given folder does not allow to reorder the files.", RML_TD));
        }else if ($contentCustomOrder == "1") { // Reindex
            $this->contentReindex();
        }
        
        // Process
        global $wpdb;
        $table_name = $this->getTableName("posts");
        $this->debug("The folder $this->id wants to move $attachmentId before $nextId (lastIdInView: $lastIdInView)", __METHOD__);
        
        // Is it the real end?
        if ($nextId === false && $lastIdInView !== false) {
            $this->debug("Want to move to the end of the list and there is a pagination system with the lastIdInView...", __METHOD__);
            $nextIdTo = $this->getAttachmentNextTo($lastIdInView);
            if ($nextIdTo > 0) {
                $nextId = $nextIdTo;
            }
        }
        
        // Push to end
        if ($nextId === false) {
            $newOrder = $this->getContentAggregationNr("MAX") + 1;
            $this->debug("Order the attachment to the end and use the new order value $newOrder...", __METHOD__);
        }else{
            $_newOrder = $this->getContentNrOf($nextId); // Temp save in this, because the query can fail
            $this->debug("Order the attachment before $nextId and change the order value $_newOrder for the moved attachment....", __METHOD__);
            
            // Count up the next ids
            $wpdb->query("UPDATE $table_name SET nr = nr + 1 WHERE fid = $this->id AND nr >= $_newOrder");
            $newOrder = $_newOrder;
        }
        
        // Update the new order number
        if (isset($newOrder) && $newOrder > 0) {
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET nr=%d WHERE fid=%d AND attachment=%d", $newOrder, $this->id, $attachmentId));
            delete_media_folder_meta($this->id, "orderby");
            $this->debug("Successfully updated the order of the attachmnet", __METHOD__);
            
            // Save to old custom order
            $wpdb->query($wpdb->prepare("UPDATE " . $table_name . " SET oldCustomNr = nr WHERE fid = %d;", $this->id));
            $this->debug("Successfully updated the old custom nr of the folder", __METHOD__);
        }else{
            throw new \Exception(__("Something went wrong.", RML_TD));
        }
    }
    
    // Documentated in IFolderContent
    public function contentIndex($delete = true) {
        // Check, if this folder is allowed for custom content order
        if (!$this->isContentCustomOrderAllowed()) {
            return false;
        }
        
        // First, delete the old entries from this folder
        if ($delete && !$this->contentDeleteOrder()) {
            return false;
        }
        
        // Create INSERT-SELECT statement for this folder
        global $wpdb;
        $sql = $wpdb->prepare("UPDATE " . $this->getTableName("posts") . " AS rmlp2
                LEFT JOIN (
                    SELECT
                    	wpp2.ID AS attachment,
                    	wpp2.fid AS fid,
                    	@rownum := @rownum + 1 AS nr,
                    	@rownum AS oldCustomNr
                    FROM (SELECT @rownum := 0) AS r,
                    	(SELECT wpp.ID, rmlposts.fid
                    		FROM $wpdb->posts AS wpp
                    		INNER JOIN " . $this->getTableName("posts") . " AS rmlposts ON ( wpp.ID = rmlposts.attachment )
                    		WHERE rmlposts.fid = %d
                    		AND wpp.post_type = 'attachment'
                    		AND wpp.post_status = 'inherit'
                    		GROUP BY wpp.ID ORDER BY wpp.post_date DESC, wpp.ID DESC) 
                    	AS wpp2
                ) AS rmlnew ON rmlp2.attachment = rmlnew.attachment
                SET rmlp2.nr = rmlnew.nr, rmlp2.oldCustomNr = rmlnew.oldCustomNr
                WHERE rmlp2.fid = %d", $this->id, $this->id);
        $wpdb->query($sql);
        $this->debug("Indexed the content order of $this->id", __METHOD__);
        return true;
    }
    
    // Documentated in IFolderContent
    public function contentReindex() {
        if ($this->getContentCustomOrder() != 1) {
            return false;
        }
        
        global $wpdb;
        $table_name = $this->getTableName("posts");
        $sql = "UPDATE $table_name AS rml2
                LEFT JOIN (
                	SELECT @rownum := @rownum + 1 AS nr, t.attachment
                    FROM ( SELECT rml.attachment
                        FROM $table_name AS rml
                        WHERE rml.fid = $this->id
                        ORDER BY rml.nr ASC )
                        AS t, (SELECT @rownum := 0) AS r
                ) AS rmlnew ON rml2.attachment = rmlnew.attachment
                SET rml2.nr = rmlnew.nr
                WHERE rml2.fid = $this->id";
        
        $wpdb->query($sql);
        $this->debug("Reindexed the content order of $this->id", __METHOD__);
        return true;
    }
    
    // Documentated in IFolderContent
    public function contentEnableOrder() {
        // Check, if this folder is allowed for custom content order
        if (!$this->contentIndex(false)) {
            return false;
        }
        
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE " . $this->getTableName() . " SET contentCustomOrder=1 WHERE id = %d", $this->id));
        $this->contentCustomOrder = 1;
        return true;
    }
    
    // Documentated in IFolderContent
    public function contentDeleteOrder() {
        if ($this->getContentCustomOrder() != 1) {
            return false;
        }
        
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE " . $this->getTableName("posts") . " SET nr=NULL, oldCustomNr=NULL WHERE fid=%d", $this->id));
        $wpdb->query($wpdb->prepare("UPDATE " . $this->getTableName() . " SET contentCustomOrder=0 WHERE id=%d", $this->id));
        delete_media_folder_meta($this->id, "orderby");
        $this->debug("Deleted order of the folder $this->id", __METHOD__);
        return true;
    }
    
    // Documentated in IFolderContent
    public function contentRestoreOldCustomNr() {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE " . $this->getTableName("posts") . " SET nr = oldCustomNr WHERE fid=%d;", $this->id));
        delete_media_folder_meta($this->id, "orderby");
        $this->debug("Restored the order of folder $this->id to the old custom order", __METHOD__);
        return true;
    }
    
    // Documentated in IFolderContent
    public function isContentCustomOrderAllowed() {
        return $this->getContentCustomOrder() != 2;
    }
    
    // Documentated in IFolderContent
    public function getContentCustomOrder() {
        return $this->contentCustomOrder;
    }
    
    // Documentated in IFolderContent
    public function getAttachmentNextTo($attachmentId) {
        if ($this->getContentCustomOrder() != 1) {
            return false;
        }
        
        global $wpdb;
        $sql = $wpdb->prepare("SELECT o.attachment
                        FROM (SELECT *
                            FROM " . $this->getTableName("posts") . "
                            WHERE fid=%d ORDER BY nr) AS o
                        WHERE o.nr > (SELECT o2.nr FROM (SELECT nr FROM " . $this->getTableName("posts") . " WHERE attachment=%d AND fid=%d) AS o2)
                        LIMIT 1;", $this->id, $attachmentId, $this->id);
        $nextNr = $wpdb->get_var($sql);
        $nextNr = !($nextNr > 0) ? false : $nextNr;
        return $nextNr;
    }
    
    // Documentated in IFolderContent
    public function getContentOrderNumbers($fromCache = true, $indexMode = true) {
        if ($this->getContentCustomOrder() != 1) {
            return false;
        }
        
        global $wpdb;
        
        $fid = $this->id;
        if ($fromCache && isset($this->orderNumbers[$fid])) {
            $results = $this->orderNumbers[$fid];
        }else{
            $results = $wpdb->get_results($wpdb->prepare("SELECT o.attachment, o.nr  FROM " . $this->getTableName("posts") . " AS o WHERE o.fid = %d", $fid), ARRAY_A );
            $this->orderNumbers[$fid] = $results;
            
            if (count($results) == 0) {
                return false;
            }
        }
        
        if ($indexMode && count($results) > 0) {
            $_result = array();
            foreach ($results as $key => $value) {
                $_result[((int)$value["attachment"])] = (int) $value["nr"];
            }
            $results = $_result;
        }
        
        return $results;
    }
    
    // Documentated in IFolderContent
    public function getContentAggregationNr($function = "MAX") {
        if (!in_array($function, array("MAX", "MIN"))) {
            throw new \Exception("Only max or min aggregation function allowed!");
        }
        
        global $wpdb;
        $max = $wpdb->get_var($wpdb->prepare("SELECT " . $function . "(o.nr) FROM " . $this->getTableName("posts") . " AS o WHERE o.fid = %d", $this->id));
        return !($max > 0) ? false : $max;
    }
    
    // Documentated in IFolderContent
    public function getContentNrOf($attachmentId) {
        global $wpdb;
        
        $nextNr = $wpdb->get_var($wpdb->prepare("SELECT o.nr FROM " . $this->getTableName("posts") . " AS o WHERE o.attachment = %d AND o.fid = %d", $attachmentId, $this->id));
        return !($nextNr > 0) ? false : $nextNr;
    }
    
    // Documentated in IFolderContent
    public function getContentOldCustomNrCount() {
        global $wpdb;
        $result = $wpdb->get_col($wpdb->prepare("SELECT COUNT(oldCustomNr) FROM " . $this->getTableName("posts") . " WHERE fid=%d", $this->id));
        return $result[0];
    }
    
    /* STATIC FOR ACTIONS AND FILTERS */
    /*
     * When moving to a folder with content custom order, reindex the folder content.
     * 
     * @hooked RML/Item/MoveFinished
     */
    public static function item_move_finished($folderId, $ids, $folder, $isShortcut) {
        $core = general\Core::getInstance();
        general\Util::getInstance()->doActionAnyParentHas($folder, "Folder/Insert", func_get_args());
        
        if ($folder->getContentCustomOrder() == 1) {
            // Check if order by is automatically @deprecated
            /*$order = get_media_folder_meta($folderId, "orderby", true);
            if (!empty($order)) {
                $orderAutomatically = get_media_folder_meta($folderId, "orderAutomatically", true);
                if ($orderAutomatically) {
                    $core->debug("New content in folder $folderId, automatically order by $order ...", __METHOD__);
                    GalleryOrder::order($folderId, $order);
                    return;
                }
            }*/
            
            $core->debug("$folderId detected some new files, synchronize with custom content order...", __METHOD__);
            $folder->contentReindex();
        }
    }
    
    /**
     * Add a condition for the automatic order
     */
    public static function folder_insert_anyParentHasMetadata($conditions, $folder, $args) {
        if ($folder->getContentCustomOrder() != 2) {
            $conditions[] = "rmlmeta.meta_key = 'orderAutomatically' AND rmlmeta.meta_value <> '' AND rmltmp.lvl = '0'";
            $conditions[] = "rmlmeta.meta_key = 'orderBy' AND rmltmp.lvl = '0'";
        }
        return $conditions;
    }
    
    public static function folder_insert_anyParentHasMetadata_orderAutomatically($metas, $folder, $args, $all_metas) {
        // Search for orderAutomatically
        foreach ($metas as $meta) {
            if ($meta["folderId"] == $folder->getId()) {
                // Search for orderBy
                if (isset($all_metas["orderby"])) {
                    foreach ($all_metas["orderby"] as $metaOrderBy) {
                        if ($metaOrderBy["folderId"] == $folder->getId()) {
                            $order = $metaOrderBy["value"];
                            general\Core::getInstance()->debug("New content in folder " . $folder->getId() . ", automatically order by $order ...", __METHOD__);
                            GalleryOrder::order($folder->getId(), $order, false);
                            break;
                        }
                    }
                }
                break;
            }
        }
    }
    
    /*
     * JOIN the order table and orderby the nr.
     * It is only affected when
     * $query = new \WP_Query(array(
     *      'post_status' => 'inherit',
     *      'post_type' => 'attachment',
     *      'rml_folder' => 4,
     *      'orderby' => 'rml'
     * ));
     * 
     * @param $pieces array clauses
     * @param &$query \WP_Query object
     * @return $pieces
     */
    public static function posts_clauses($pieces, $query) {
        if (!empty($query->query_vars['parsed_rml_folder']) &&
            (empty($query->query['orderby']) ||
                (isset($query->query['orderby']) && $query->query['orderby'] == "rml")
            )
        ) {
            global $wpdb;
            // Get folder
            $folder = wp_rml_get_object_by_id($query->query_vars['parsed_rml_folder']);
            if ($folder === null || $folder->getContentCustomOrder() != 1) {
                $pieces["orderby"] = $wpdb->posts.  ".post_date DESC, " . $wpdb->posts.  ".ID DESC";
                return $pieces;
            }
            
            // left join and order by
            $pieces["join"] .= " LEFT JOIN " . general\Core::getInstance()->getTableName("posts") . " AS rmlorder ON rmlorder.fid=$folder->id AND rmlorder.attachment = " . $wpdb->posts . ".ID ";
            $pieces["orderby"] = "rmlorder.nr, " . $wpdb->posts.  ".post_date DESC, " . $wpdb->posts.  ".ID DESC";
        }
        
        return $pieces;
    }
    
    /*
     * Create a toolbar icon to move.
     * This should be only visible if we are in orderby
     * post_date DESC and in the gallery are more images > 1
     * Otherwise redirect to to order by date with <a>-class "_external".
     * 
     * @filter RML/Backend/JS_Localize
     */
    public static function js_localize($arr) {
        $mode = get_user_option( 'media_library_mode', get_current_user_id() ) ? get_user_option( 'media_library_mode', get_current_user_id() ) : 'grid';
        
        if ((!isset($_GET["orderby"]) || $_GET["orderby"] !== "rml" || isset($_GET["attachment-filter"])) && $mode != "grid") {
            $query = array();
            if (isset($_GET["rml_folder"])) {
                $query["rml_folder"] = $_GET["rml_folder"];
            }
            $query["orderby"] = "rml";
            $query["order"] = "asc";
            $href = "?" . http_build_query($query) . "#order";
            $arr["wpListModeOrder"] = $href;
        }else{
            $arr["wpListModeOrder"] = "1";
        }
        return $arr;
    }
    
    /*
     * Add GET query parameter for galleries.
     */
    public static function treeHref($query, $id, $type) {
        // Get folder
        $folder = wp_rml_get_object_by_id($id);
        if ($folder !== null && $folder->getContentCustomOrder() == 1) {
            $query['orderby'] = "rml";
            $query['order'] = "asc";
        }else{
            unset($query['orderby']);
            unset($query['order']);
        }
        
        return $query;
    }
    
    /*
     * Media Library Assistent extension.
     */
    public static function mla_media_modal_query_final_terms($query) {
        $folderId = attachment\Filter::getInstance()->getFolder(null, true);
        if ($folderId !== null) {
            $folder = wp_rml_get_object_by_id($folderId);
            if ($folder !== null && $folder->getContentCustomOrder() == 1) {
                $query['orderby'] = "rml";
                $query['order'] = "asc";
            }
        }
        return $query;
    }
    
    /*
     * Deletes the complete order. Use it with CAUTION!
     */
    public static function delete_all_order() {
        global $wpdb;
        $wpdb->query("UPDATE " . general\Core::getInstance()->getTableName("posts") . " SET nr=null, oldCustomNr=NULL");
        $wpdb->query("UPDATE " . general\Core::getInstance()->getTableName() . " SET contentCustomOrder=0");
        general\Core::getInstance()->debug("Deleted the whole order of all folders", __METHOD__);
    }
}

?>