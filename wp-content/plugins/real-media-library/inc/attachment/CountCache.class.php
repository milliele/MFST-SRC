<?php
namespace MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * This class handles the count cache for the folder structure.
 */
class CountCache extends general\Base {
    
    private static $me = null;
    
    /*
     * An array of new attachment ID's which should be updated
     * with the this::updateCountCache method. This includes also
     * deleted attachments. The "new" means the attachments which are changed,
     * but new for the update.
     * 
     * @see this::wp_die
     */
    private $newAttachments = array();
    
    private $folderIdsOnWpDie = array();
    
    /*
     * C'tor
     */
    private function __construct($root = null) {
        // Silence is golden.
    }
    
    /*
     * Handle the count cache for the folders. This should avoid
     * a lack SQL subquery which loads data from the posts table.
     * 
     * @param $folders Array of folders ID, if null then all folders with cnt = NULL are updated
     * @param $attachments Array of attachments ID, is merged with $folders if given
     * @param $onlyReturn Set to true if you only want the SQL query
     * @return void or SQL query
     */
    public function updateCountCache($folders = null, $attachments = null, $onlyReturn = false) {
        global $wpdb;
        
        $table_name = general\Core::getInstance()->getTableName();
        
        // Create where statement
        $where = "";
        
        // Update by specific folders
        if (is_array($folders) && count($folders) > 0) {
            $folders = array_unique($folders);
            $where .= " tn.id IN (" . implode(",", $folders) . ") ";
        }
        
        // Update by attachment IDs, catch all touched 
        if (is_array($attachments) && count($attachments) > 0) {
            $attachments = array_unique($attachments);
            $attachments_in = implode(",", $attachments);
            $table_posts = general\Core::getInstance()->getTableName("posts");
            $where .= ($where === "" ? "" : " OR") . " tn.id IN (SELECT DISTINCT(rmlposts.fid) FROM $table_posts AS rmlposts WHERE rmlposts.attachment IN ($attachments_in)) ";
        }
        
        // Default where statement
        if ($where === "") {
            $where = "tn.cnt IS NULL";
        }
        
        $sqlStatement = "UPDATE $table_name AS tn
            SET cnt = (SELECT COUNT(*)
            	FROM " . general\Core::getInstance()->getTableName("posts") . " AS rmlpostscnt
            	WHERE rmlpostscnt.fid = tn.id
            )
            WHERE $where";
        if ($onlyReturn) {
            return $sqlStatement;
        }else{
            $wpdb->query($sqlStatement);
        }
    }
    
    /*
     * Reset the count cache for the current blog id
     * 
     * @param $folderId Array If you pass folder id/ids array, only this one will be resetted.
     * @attention The content of the array is not prepared for the statement
     */
    public function resetCountCache($folderId = null) {
        global $wpdb;
        
        $table_name = general\Core::getInstance()->getTableName();
        $blog_id = get_current_blog_id();
        
        if (is_array($folderId)) {
            $wpdb->query("UPDATE $table_name SET cnt=NULL WHERE id IN (" . implode(",", $folderId) . ")");
        }else{
            $wpdb->query("UPDATE $table_name SET cnt=NULL");
        }
        return $this;
    }
    
    public function resetCountCacheOnWpDie($folderId) {
        if (!in_array($folderId, $this->folderIdsOnWpDie)) {
            $this->folderIdsOnWpDie[] = $folderId;
        }
    }
    
    /*
     * Update @ the end of the script execution the count of the given
     * added / deleted attachments.
     * 
     * @uses this::updateCountCache
     */
    public function wp_die() {
        if (count($this->newAttachments) > 0) {
            $this->updateCountCache(null, $this->newAttachments);
        }
        if (count($this->folderIdsOnWpDie) > 0) {
            $this->debug("Update count cache on wp die for this folders: " . json_encode($this->folderIdsOnWpDie), __METHOD__);
            $this->updateCountCache($this->folderIdsOnWpDie);
        }
    }
    
    public function addNewAttachment($id) {
        $this->newAttachments[] = $id;
        return $this;
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new CountCache();
        }
        return self::$me;
    }
}

?>