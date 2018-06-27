<?php
namespace MatthiasWeb\RealMediaLibrary\api;

/**
 * This interface provides elementary action methods for folder content. All folder
 * types (Folder, Collection, Gallery, ...) have implemented this interface.
 * Also the root ("Unorganized") is a folder and implements this interface.
 * 
 * @since 3.3.1
 */ 
interface IFolderContent {
    /**
     * See API function for more information.
     * 
     * @throws Exception
     * @return true
     * @see wp_attachment_order_update()
     */
    public function contentOrder($attachmentId, $nextId, $lastIdInView = false);
    
    /**
     * Index the order table.
     * 
     * @param boolean $delete Delete the old order
     * @returns boolean
     */
    public function contentIndex($delete = true);
    
    /**
     * This function retrieves the order of the order
     * table and removes empty spaces, for example:
     * <pre>0 1 5 7 8 9 10 =>
     * 0 1 2 3 4 5 6</pre>
     * 
     * @returns boolean
     */
    public function contentReindex();
    
    /**
     * Enable the order functionlity for this folder.
     * 
     * @returns boolean
     * @see IFolderContent::getContentCustomOrder()
     */
    public function contentEnableOrder();
    
    /**
     * Deletes the complete order for this folder.
     * 
     * @returns boolean
     * @see IFolderContent::getContentCustomOrder()
     */
    public function contentDeleteOrder();
    
    /**
     * Restore the current order number to the old custom order number.
     * 
     * @returns boolean
     */
    public function contentRestoreOldCustomNr();
    
    /*
     * Checks if the folder is allowed to use custom content order.
     * 
     * @returns boolean
     */
    public function isContentCustomOrderAllowed();
    
    /**
     * The content custom order defines the state of the content order functionality:
     * 
     * <pre>0 = No content order defined
     * 1 = Content order is enabled
     * 2 = Custom content order is not allowed</pre>
     * 
     * @returns integer The content custom order value
     * @see IFolderContent::isContentCustomOrderAllowed()
     * @see IFolderContent::contentEnableOrder()
     */
    public function getContentCustomOrder();
    
    /**
     * Get the next attachment id for a specific attachment. It returns false if
     * the attachment is at the end or the folder has no custom content order.
     * 
     * @param integer $attachmentId The attachment id
     * @returns boolean Int or false
     */
    public function getAttachmentNextTo($attachmentId);
    
    /**
     * Get the whole order table.
     * 
     * @param boolean $fromCache load the data from the cache
     * @param boolean $indexMode the return is an indexed array with attachment id key
     * @returns boolean|int[]
     */
    public function getContentOrderNumbers($fromCache = true, $indexMode = true);
    
    /**
     * Gets the biggest order number;
     * 
     * @param string $function The SQL aggregation function (MIN or MAX)
     * @returns integer
     */
    public function getContentAggregationNr($function = "MAX");
    
    /**
     * Get the order number for a specific attachment in this folder.
     * 
     * @param integer $attachmentId The attachment id
     * @return int|boolean
     */
    public function getContentNrOf($attachmentId);
        
    /**
     * Get the old custom order number count so we can decide if already available.
     * 
     * @returns int Count
     */
    public function getContentOldCustomNrCount();
    
    

}
?>