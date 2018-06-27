<?php
namespace MatthiasWeb\RealMediaLibrary\metadata;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\attachment;
use MatthiasWeb\RealMediaLibrary\api;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Create general functionality for the custom
 * folder fields.
 * 
 * For an example see the function-doc of this::content_general
 * and this::save_general
 * 
 * @see inc/api/meta.php
 * @see interface IMetadata for more details
 */
class Meta extends general\Base implements api\IMetadata {
    
    private static $me = null;
    private $view = null;
    private $boxes = array();

    private function __construct() {
        // Add our folder meta table to wpdb
        global $wpdb;
        if (!isset($wpdb->realmedialibrary_meta)) {
            $wpdb->realmedialibrarymeta = general\Core::tableName("meta");
        }
        
        $this->view = attachment\Structure::getInstance()->getView();
    }
    
    /*
     * The general custom fields.
     *
     * @see interface IMetadata
     */
    public function content($content, $folder) {
        $type = $folder->getType();
        if ($type !== RML_TYPE_ROOT) {
            $content .= '<tr>
                <th scope="row">' . __('Name', RML_TD) . '</th>
                <td>
                    <input name="name" type="text" value="' . esc_attr($folder->getName()) . '" class="regular-text">
                </td>
            </tr>
            <tr class="single-row">
                <th scope="row">' . __('Path', RML_TD) . '</th>
                <td>
                    <label>' . $folder->getPath(' <i class="fa fa-chevron-right" style="font-size: 11px;opacity: 0.5;"></i> ') . '</label>
                </td>
            </tr>';
        }

        $content .= '<tr class="single-row">
            <th scope="row">' . __('Folder type', RML_TD) . '</th>
            <td>
                <label>' . $folder->getTypeIcon() . ' ' . $folder->getTypeName() . ' <i class="rml-meta-helper" title="' . esc_attr($folder->getTypeDescription()) . '">' . __('What does this mean?', RML_TD) . '</i></label>
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
        if (isset($_POST["name"])) {
            $newName = trim($_POST["name"]);
            if ($newName != $folder->getName()) {
                // Rename of normal folder
                $result = wp_rml_rename($newName, $folder->getId());
                
                if ($result === true) {
                    $response["data"]["newSlug"] = $folder->getAbsolutePath(true);
                }else{
                    $response["errors"] = $result;
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
    
    /*
     * Get content for the form in sweetAlert dialog.
     *
     * @param $fid the folder ID
     * @return HTML formatted string or empty string
     * @see meta.js
     */
    public function prepare_content($fid) {
        $folder = null;
        $inputID = "all";
        $type = RML_TYPE_ALL;
        if (!empty($fid)) {
            $folder = wp_rml_get_object_by_id($fid);
            $inputID = $folder->getId();
            $type = $folder->getType();
            
            if ($folder === null) {
                return "404";
            }
        }
        
        /*f
         * Add a tab group to the folder details or user settings dialog.
         * You cam use this function together with add_rml_meta_box()
         * or add_rml_user_settings_box(). Allowed $types: "User/Settings"|"Folder/Meta".
         * 
         * @param {array} $tabs The tabs with key (unique tab name) and value (display text)
         * @filter RML/$type/Groups
         * @returns {array} The tabs
         * @since 3.3
         */
        $tabs = apply_filters("RML/" . ($type === RML_TYPE_ALL ? "User/Settings" : "Folder/Meta") . "/Groups", array(
            "general" => __("General", RML_TD)
        ));
        
        // Create content form
        $content = '<form class="rml-meta" method="POST" action="">
            <input type="hidden" name="folderId" value="' . $inputID . '" />
            <input type="hidden" name="folderType" value="' . $type . '" />
            <ul class="rml-meta-errors"></ul>
            <nav><ul>';
        
        // Create tab groups
        foreach ($tabs as $key => $value) {
            $content .= '<li class="nav-tab ' . ($key === "general" ? "nav-tab-active" : "") . '" data-key="' . esc_attr($key) . '">' . $value . '</li>';
        }
        $content .= '</ul></nav>';
        
        // Create content per tab group
        foreach ($tabs as $key => $value) {
            // Table
            $content .= '<table class="form-table"
                style="' . ($key === "general" ? "" : "display:none;") . '"
                data-key="' . esc_attr($key) . '"><tbody>';
            $hookAddition = $key === "general" ? "" : "/" . $key;
                
            if ($type === RML_TYPE_ALL) {
                /*f
                 * Add content to the general settings. Do not use this filter directly instead use the 
                 * add_rml_user_settings_box() function!
                 * 
                 * @param {string} $content The HTML content
                 * @param {int} $user The current user id
                 * @filter RML/User/Settings/Content[/$tabGroup]
                 * @returns {string} The HTML content
                 * @since 3.2
                 */
                $content .= apply_filters('RML/User/Settings/Content' . $hookAddition, "", get_current_user_id());
            }else{
                /*f
                 * Add content to the folder metabox. Do not use this filter directly instead use the 
                 * add_rml_meta_box() function!
                 * 
                 * @param {string} $content The HTML content
                 * @param {IFolder} $folder The folder object
                 * @filter RML/Folder/Meta/Content[/$tabGroup]
                 * @returns {string} The HTML content
                 * @since 3.3.1 $folder can never be null
                 */
                $content .= apply_filters('RML/Folder/Meta/Content' . $hookAddition, "", $folder);
            }
            $content .= '</tbody></table>';
        }
        
        $content .= '</form>';
        return $content;
    }
    
    /*
     * Checks if a meta box is already registered.
     * 
     * @see meta.php
     * @see add_rml_meta_box()
     */
    public function add($name, $instance) {
        if ($this->get($name) !== null) {
            return false;
        }else{
            $this->boxes[$name] = $instance;
            return true;
        }
    }
    
    /*
     * Get the instance for a given meta box name.
     * 
     * @return instance or null
     */
    public function get($name) {
        foreach ($this->boxes as $key => $value) {
            if ($key === $name) {
                return $value;
            }
        }
        return null;
    }
    
    public function exists($name) {
        return $this->get($name) !== null;
    }
    
    /*
     * Delete the metas when a folder is deleted.
     * 
     * @hooked RML/Folder/Deleted
     */
    public function folder_deleted($fid, $oldData) {
        truncate_media_folder_meta($fid);
    }

    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new Meta();
        }
        return self::$me;
    }
}