<?php
namespace MatthiasWeb\RealMediaLibrary\general;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Handles the view for dropdowns and UL's for the folders.
 */
class View extends Base {
    private $structure;
    
    public function __construct($structure) {
        $this->structure = $structure;
    }
    
    public function optionsFasade($selected, $disabled, $useAll = true) {
        return $this->optionsHTML($selected, null, "", "&nbsp;&nbsp;", $useAll, $disabled);
    }
    
    /*
     * Gets a HTML formatted string for <option>.
     * 
     * @recursive
     */
    public function optionsHTML($selected = -1, $tree = null, $slashed = "", $spaces = "&nbsp;&nbsp;", $useAll = true, $disabled = null) {
        $return = '';
        $selected = $selected == -1 ? _wp_rml_root() : $selected;
        
        if ($disabled === null) {
            $disabled = array();
        }
        
        if ($tree == null) {
            $root = _wp_rml_root();
            $tree = $this->structure->getTree();
            if ($useAll) {
                $return .= '<option value="" ' . $this->optionsSelected($selected, "") . '
                                    ' . ((in_array(RML_TYPE_ALL, $disabled)) ? 'disabled="disabled"' : '') . '
                                    >' . __('All', RML_TD) . '</option>';
            }
            $return .= '<option value="' . $root . '" ' . $this->optionsSelected($selected, $root) . '
                            data-slug="/"
                            ' . ((in_array(RML_TYPE_ROOT, $disabled)) ? 'disabled="disabled"' : '') . '
                            data-id="' . $root . '">' . __('Unorganized pictures', RML_TD) . '</option>';
        }
        
        if(!is_null($tree) && count($tree) > 0) {
            foreach($tree as $parent) {
                $return .= '<option value="' . $parent->getId() . '" ' . $this->optionsSelected($selected, $parent->getId()) . '
                                    data-slug="/' . $parent->getAbsolutePath() . '"
                                    data-id="' . $parent->getId() . '"
                                    ' . ((in_array($parent->getType(), $disabled)) ? 'disabled="disabled"' : '') . '>
                                    ' . $spaces . '&nbsp;' . $parent->getName(true) . '
                            </option>';
                
                if (is_array($parent->getChildren()) &&
                    count($parent->getChildren()) > 0
                    ) {
                    $return .= $this->optionsHTML($selected, $parent->getChildren(), $slashed, str_repeat($spaces, 2), $useAll, $disabled);
                }
            }
        }
        
        return $return;
    }
    
    /*
     * Gets the html string for the left tree.
     * 
     * @param $selected the current selected id ("" = All files, -1 = Root)
     * @param $tree the tree array, default is the structure tree
     * @param $list the list id (for custom lists)
     * @recursive
     * @uses this::createNode
     */
    public function treeHTML($selected = -1, $tree = null, $list = "") {
        $return = '';
        $selected = $selected == -1 ? _wp_rml_root() : $selected;
        
        // First item
        if ($tree == null) {
            $tree = $this->structure->getTree();
        }
        
        // Create list
        $return .= '<ul>';
        if(!is_null($tree) && count($tree) > 0) {
            foreach($tree as $parent) { // the parent here is the actual folder
                /*f
                 * Filter the li classes for a folder element.
                 * 
                 * @param {string[]} $classes The css classes
                 * @param {IFolder} $parent The parent folder object
                 * @returns {string[]}
                 * @filter RML/Folder/TreeNodeLi/Class
                 */
                $liClasses = apply_filters('RML/Folder/TreeNodeLi/Class', array(), $parent);
            
                // Create the output
                $return .= '<li id="list_' . $parent->getId() . '" class="' . implode(' ', $liClasses) . '">';
                $return .= $this->createNode($parent, $parent->getId(), $parent->getType(), "/" . $parent->getAbsolutePath(), $parent->getName(), $parent->getCnt(),
                                $selected, array(), $list);
                
                // Recusrive functionality call
                if (is_array($parent->getChildren()) &&
                    count($parent->getChildren()) > 0
                    ) {
                    $return .= $this->treeHTML($selected, $parent->getChildren(), $list);
                }else{
                    $return .= '<ul></ul>';
                }
                
                $return .= '</li>';
            }
        }
        $return .= '</ul>';
        
        return $return;
    }
    
    /*
     * Create a <a>-Node for the treeHTML function.
     * 
     * @param $obj the folder object
     * @param $fid the folder ID
     * @param $type the type of the folder
     * @param $slug the slug for the node
     * @param $name the name for the node
     * @param $cnt the shown count for the node
     * @param $currentFid the current selected folder ID for the this::treeActive function
     * @param $classes an array of classes for this node
     * @param $list the list type ("" is the list in the media library,
     *              otherwise it is used for the customList ID)
     * @return formatted HTML string
     * 
     * @see this::treeHTML
     * @uses this::treeHref
     * @uses this::treeActive
     * 
     * @filter RML/Folder/TreeNode/Icon (Parameters: func_get_args())
     * @filter RML/Folder/TreeNode/Class (Parameters: func_get_args())
     * @filter RML/Folder/TreeNode/Content (Parameters: func_get_args())
     */
    public function createNode($obj, $fid, $type, $slug, $name, $cnt, $currentFid, $classes = array(), $list = "") {
        // Get href
        $href = $this->treeHref($fid, $type, $list);
        $icon = $type === RML_TYPE_ALL ? '<i class="fa fa-files-o"></i>' : wp_rml_get_object_by_id($fid)->getTypeIcon();
        if ($type === RML_TYPE_FOLDER) {
            $icon .= '<i class="fa fa-folder"></i>';
        }

        /*
         * Get classes for this tree node
         */
        $funcArgs = func_get_args();
        $classes = implode(
                    ' ',
                    /*f
                     * Filter the a-tag classes for a folder element.
                     * 
                     * @param {string[]} $classes The css classes
                     * @param {arguments[]} $args Arguments
                     * @returns {string[]}
                     * @filter RML/Folder/TreeNode/Class
                     */
                    apply_filters('RML/Folder/TreeNode/Class',
                        array_merge(array(
                            "rml-fid-" . $fid,
                            "rml-type-" . $type,
                            $this->treeActive($currentFid, $fid)
                        ), $classes), $funcArgs)
                );
        
        /*
         * The output
         */
        // Create attributes
        $slug = empty($slug) ? "" : 'data-slug="' . $slug . '"';
        $restrictions = $obj !== null ? $obj->getRowData("restrictions") : "";
        $contentCustomOrder = $obj !== null ? $obj->getRowData("contentCustomOrder") : "2";
        
        // The result
        return '
        <a href="' . $href . '" class="' . $classes . '" ' . $slug . ' data-content-custom-order="' . $contentCustomOrder . '" data-aio-type="' . $type . '" data-aio-id="' . $fid . '" data-restrictions="' . $restrictions . '">
            ' . $icon . '
            <div class="aio-node-name" title="' . esc_attr($name) . '">' . htmlentities($name) . '</div>
            ' . apply_filters('RML/Folder/TreeNode/Content', "", $funcArgs) . '
            <span class="aio-cnt aio-cnt-' .  $cnt . '">' . $cnt . '</span>
        </a>
        ';
    }
    
    public function optionsSelected($selected, $value) {
        if ((is_array($selected) && in_array($value, $selected)) || $selected == $value) {
            return 'selected="selected"';
        }else{
            return '';
        }
    }
    
    /*
     * Create link for a tree node.
     * 
     * @param $id the folder id
     * @param $type the type of the folder
     * @filter RML/Folder/TreeNode/Href
     */
    public function treeHref($id, $type, $list = "") {
        $query = array();
        if ($type !== RML_TYPE_ALL) {
            $query['rml_folder'] = $id;   
        }
        
        $query_result = http_build_query(apply_filters("RML/Folder/TreeNode/Href", $query, $id, $type, $list));
        return admin_url('upload.php' . (empty($query_result) ? '' : '?' . $query_result));
    }
    
    public function treeActive($selected, $value) {
        if ($selected == $value) {
            return 'active';
        }else{
            return '';
        }
    }
        
    /*
     * Get array for the javascript backbone view.
     * The private namesSlugArray is for caching purposes
     * and can be resetted with the given function.
     */
    private $namesSlugArrayCache = null;

    public function namesSlugArray($tree = null, $spaces = "--", $forceReload = false) {
        if ($forceReload || $this->namesSlugArrayCache == null) {
            $result = $this->namesSlugArrayRec($tree, $spaces);
        }else{
            $result = $this->namesSlugArrayCache;
        }
        $this->namesSlugArrayCache = $result;
        return $result;
    }
    
    private function namesSlugArrayRec($tree = null, $spaces = "--") {
        $return = array(
            "names" => array(),
            "slugs" => array(),
            "types" => array()
        );
        
        if ($tree == null) {
            $tree = $this->structure->getTree();
            $return["names"][] = __('Unorganized pictures', RML_TD);
            $return["slugs"][] = _wp_rml_root();
            $return["types"][] = 0;
        }
        
        if(!is_null($tree) && count($tree) > 0) {
            foreach($tree as $parent) {
                $return["names"][] = $spaces . ' ' . $parent->getName();
                $return["slugs"][] = $parent->getId();
                $return["types"][] = $parent->getType();
                
                if (is_array($parent->getChildren()) &&
                    count($parent->getChildren()) > 0
                    ) {
                    $append = $this->namesSlugArrayRec($parent->getChildren(), $spaces . "--");
                    $return["names"] = array_merge($return["names"], $append["names"]);
                    $return["slugs"] = array_merge($return["slugs"], $append["slugs"]);
                    $return["types"] = array_merge($return["types"], $append["types"]);
                }
            }
        }
        
        return $return;
    }
    
    public function getHTMLBreadcrumbByID($id) {
        // Get folder
        $folder = wp_rml_get_object_by_id($id);
        if ($folder === null) {
            return "";
        }
        
        // Check if parent exists
        $output = '<i class="fa fa-folder-open"></i>';
        $breadcrumb = $folder->getAllParents();
        $breadcrumb[] = $folder->getId();
        
        // Iterate
        for ($i = 0; $i < count($breadcrumb); $i++) {
            $parent = wp_rml_get_object_by_id($breadcrumb[$i]);
            $output .= '<span class="folder">' . $parent->getName(true) . '</span>';
            
            // When not last, insert seperator
            if ($i < count($breadcrumb) - 1) {
                $output .= '<i class="fa fa-chevron-right"></i>';
            }
        }
        
        return $output;
    }
    
    public function getStructure() {
        return $this->structure;
    }
}

?>