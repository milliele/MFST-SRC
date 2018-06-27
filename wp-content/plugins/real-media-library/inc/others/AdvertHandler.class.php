<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Matthias Web In-Wordpress advertisement system.
 * 
 * Set constant MATTHIASWEB_DISABLE_DIALOGS to true in wp-config.php to disable the dialogs.
 * In the Settings > General you can will find an option to disable the dialogs for the
 * current blog.
 * 
 * @changelog
 * @version 1.3
 * - changed the dialog to a message strip in the plugins.php page
 * 
 * @version 1.2
 * - removed cover image for facebook advert
 * 
 * @version 1.1
 * - only advert once a week for each dialog
 * - after activation the first dialog shows in two days
 * 
 * @version 1.0
 * - initial release
 */
if (!class_exists("MatthiasWeb_AdvertHandler")) {
class MatthiasWeb_AdvertHandler {
    private static $me = null;
    public static $FILE = __FILE__;
    
    public static $VERSION = "1.0";
    
    private $option_prefix = "matthiasweb_advert_";
    
    /*
     * An array of dialog ids which have already been deactivated or disabled
     * by the checker.
     * 
     * @see this::checkAndReset
     */
    private $disabledDialogIds = array();
    
    /*
     * Advert management host
     * 
     * @const WP_MATTHIASWEB_ADVERT_HOST
     */
    private $host;
    
    /*
     * If this string is empty the dialog will be hidden
     */
    public $currentDialogId;
    
    /*
     * Cover image for the advert. The image must be in
     * the same folder as here. If the image does not exist
     * the dialog will not be shown.
     */
    public $coverImage;
    
    /*
     * Custom CSS for the given dialog
     */
    public $css;
    
    /*
     * <h1> title
     */
    public $title;
    
    /*
     * <h2> title
     */
    public $subTitle;
    
    /*
     * Body text for the dialog
     */
    public $body;
    
    /*
     * array(array("href" =>, "text" =>, "target" =>, "primary" => false), [...])
     */
    public $buttons;
    
    /*
     * If not empty check if the plugin is active. If it is active
     * then the dialog will be skipped.
     * 
     * @see https://codex.wordpress.org/Function_Reference/is_plugin_active
     */
    public $checkForPlugin = "";
    
    /*
     * The C'tor
     */
    public function __construct() {
        $this->host = defined("WP_MATTHIASWEB_ADVERT_HOST") ? WP_MATTHIASWEB_ADVERT_HOST : "https://matthias-web.com";
        
        // DO NOT CHANGE THIS
        add_action('pre_current_active_plugins', array($this, 'pre_current_active_plugins'), 1);
        add_action('wp_ajax_matthiasweb_advert', array($this, 'wp_ajax_matthiasweb_advert'));
        add_action('init', array($this, 'init'), 99);
        add_action('admin_init', array($this, 'register_fields'));
        
        $this->resetPreviousDialog();
    }
    
    /*
     * @hook init
     */
    public function init() {
        remove_action('admin_footer', 'matthiasweb_advert'); // Compatibility with older advert system
    }
    
    /*
     * Registers a field to hide the dialogs.
     * 
     * @hook register_fields
     */
    public function register_fields() {
        register_setting( 'general', $this->option_prefix . 'dialogs_off', 'esc_attr' );
        add_settings_field(
        	$this->option_prefix . 'dialogs_off',
        	'Disable MatthiasWeb messages in plugin list which promote other MatthiasWeb products',
        	array($this, "settings_field"),
        	'general'
        );
    }
    
    /*
     * @see this::register_fields
     */
    public function settings_field() {
        echo '
        <label for="' . $this->option_prefix . 'dialogs_off">
        <input name="' . $this->option_prefix . 'dialogs_off" type="checkbox"
            id="' . $this->option_prefix . 'dialogs_off" value="1" ' . checked(get_option($this->option_prefix . "dialogs_off"), true, false) . '>
        Check to disable</label>';
    }
    
    /*
     * Reset all variables for this class.
     * This should be used in each MatthiasWeb/Advert filter'
     * when new variables are setted.
     * 
     * @see filter MatthiasWeb/Advert
     */
    public function resetPreviousDialog() {
        $this->currentDialogId = "";
        $this->css = "";
        $this->coverImage = "";
        $this->title = '';
        $this->subTitle = '';
        $this->body = '';
        $this->buttons = array();
        $this->checkForPlugin = "";
    }
    
    /*
     * Check if a dialog id is already showed and "Gotted"
     */
    public function isDisabled($dialogId) {
        if (in_array($dialogId, $this->disabledDialogIds)) {
            return true;
        }
        
        return get_site_option($this->option_prefix . $dialogId);
    }
    
    /*
     * Sets the defaults for a given plugin. Should be used in the register file.
     */
    public function setDefaults() {
        $dialogIds = array(
            "hide",
            "rml-drag-and-drop",
            "rcl-order",
            "rtg-regenerate"
        );

        /*
         * Set defaults from the array
         * 
         * @filter MatthiasWeb/Advert
         * @dialogId hide (matthiasweb_advert_$dialogId)
         * @see MatthiasWeb_AdvertHandler::setDefaultAdvertOf
         */
        foreach ($dialogIds as $id) {
            add_filter("MatthiasWeb/Advert", function($advert) use ($id) {
            	return $advert->setDefaultAdvertOf($id);
            });
        }
    }
    
    /*
     * Set a default advert of a specific dialog.
     * 
     * @return this instance to return it in the specific filter
     */
    public function setDefaultAdvertOf($of) {
        $advert = $this;
        
        switch ($of) {
            /*
             * RTG advertisement.
             * 
             * Shows a link to the product page of Real Thumbnail Generator. The cover shows
             * how to reorder categories.
             */
            case "rtg-regenerate":
                if (!$advert->checkAndReset("rtg-regenerate")
                        || $advert->isInstalled_RealThumbnailGenerator()) {
                    return $advert;
                }
                
                $advert->coverImage = $advert->host("/wp-content/uploads/envato/rtg/top-1.png");
                $advert->css = "#matthiasweb-advert-image { background-position: right bottom; }";
                $advert->title = '<i class="fa fa-crop"></i>';
                $advert->subTitle = 'Regenerate thumbnails';
                $advert->body = '<p>Have you already investigated a lot of time to regnerate image thumbnails?
                    The plugin "Real Thumbnail Generator" will help you and gives you the ability
                        <ul>
                            <li>... to <strong>single regenerate</strong> your thumbnails</li>
                            <li>... to <strong>bulk regenerate</strong> your thumbnails</li>
                            <li>... to <strong>delete unused</strong> image sizes</li>
                            <li>... to create a full customizable <strong>upload structure</strong> for thumbnails: <br /><strong>wp-content/uploads/thumbnails/mediuma/image.jpg</strong></li>
                        </ul>
                    </p>';
                $advert->buttons[] = array(
                    "href" => "https://goo.gl/TuzUSR",
                    "target" => "_blank",
                    "primary" => true,
                    "text" => '<i class="fa fa-external-link"></i> Learn more and view plugin'
                ); 
                break;
            /*
             * RCL advertisement.
             * 
             * Shows a link to the product page of Real Categories Management. The cover shows
             * how to reorder categories.
             */
            case "rcl-order":
                if (!$advert->checkAndReset("rcl-order")
                        || $advert->isInstalled_RealCategoriesLibrary()) {
                    return $advert;
                }
                
                $advert->coverImage = $advert->host("/wp-content/uploads/envato/rcm/feature-cat-order.gif");
                $advert->css = "#matthiasweb-advert-image { background-size: auto; background-position: left top; height: 350px; }";
                $advert->title = '<i class="fa fa-sort"></i>';
                $advert->subTitle = 'Custom category order';
                $advert->body = '<p>A custom category order for all of your categories with an ease of one click?
                    No problem, the plugin "Real Categories Management" solves this and gives you the ability
                        <ul>
                            <li>... to create a tree beside your posts</li>
                            <li>... to create a <strong>custom category order</strong></li>
                            <li>... to <strong>drag&drop</strong> the posts to categories</li>
                            <li>... switch between taxonomies/pages <strong>without page reload</strong></li>
                            <li>... to create categories for "Pages"</li>
                            <li>... to manage also your <strong>Custom Post Types</strong></li>
                        </ul>
                    </p>';
                $advert->buttons[] = array(
                    "href" => "https://goo.gl/TODsWE",
                    "target" => "_blank",
                    "primary" => true,
                    "text" => '<i class="fa fa-external-link"></i> Learn more and view plugin'
                ); 
                break;
            /*
             * RML advertisement.
             * 
             * Shows a link to the product page of Real Media Library. The cover shows
             * how to drag and drop between folders.
             */
            case "rml-drag-and-drop":
                if (!$advert->checkAndReset("rml-drag-and-drop")
                        || $advert->isInstalled_RealMediaLibrary()) {
                    return $advert;
                }
                
                $advert->coverImage = $advert->host("/wp-content/uploads/envato/rml/rml-drag-and-drop.gif");
                $advert->css = "#matthiasweb-advert-image { background-size: auto; background-position: left top; }";
                $advert->title = '<i class="fa fa-folder-open"></i>';
                $advert->subTitle = 'Folders in Media Library';
                $advert->body = '<p>
                    What do you think about a folder management directly in your Media Library with all of your
                    <strong>existing and future images</strong>?
                    No problem, the plugin "Real Media Library" solves this and gives you the ability
                    <ul>
                        <li>... to create a full customizable folder tree view in your media library.</li>
                        <li>... to <strong>drag&drop</strong> your media files / images to folders</li>
                        <li>... to use the folder tree in the <strong>insert-media dialog</strong></li>
                        <li>... to upload files directly to a folder</li>
                        <li>... to create a <strong>image gallery</strong> from a folder</li>
                        <li>... to order images in your galleries</li>
                    </p>';
                $advert->buttons[] = array(
                    "href" => "http://goo.gl/1NRkYT",
                    "target" => "_blank",
                    "primary" => true,
                    "text" => '<i class="fa fa-external-link"></i> Learn more and view plugin'
                ); 
                break;
            /*
             * Facebook advertisement.
             * 
             * This advert will be shown when the user activates the
             * new plugin. It shows the facebook like button that will redirect to
             * MatthiasWeb' Facebook site.
             */
            case "hide":
                if (!$advert->checkAndReset("hide")) {
                    return $advert;
                }
                
                //$advert->coverImage = $advert->host("/wp-content/uploads/images/in-app-adverts/fb.jpg");
                $advert->title = '<i class="fa fa-facebook-official"></i>';
                $advert->subTitle = 'MatthiasWeb now on Facebook!';
                $advert->css = '#matthiasweb-advert-wrap > h1 { margin-right: 15px; } #matthiasweb-advert-wrap > h1, #matthiasweb-advert-content { float: left; }';
                $advert->body = '<p>
                    First, I should say a huge <strong>thank you</strong> for buying this plugin!
                    You are on Facebook? The developer Matthias will be happy with your "Like" on his facebook site!
                    </p>
                    <p>
                    If you <i class="fa fa-heart"></i> the currently activated plugin... let this
                    know your facebook friends.
                    </p>';
                $advert->buttons[] = array(
                    "href" => "https://www.facebook.com/MatthiasWeb.Software/",
                    "target" => "_blank",
                    "primary" => true,
                    "text" => '<i class="fa fa-external-link"></i> Open MatthiasWeb on Facebook'
                );
                break;
            default:
                break;
        }
        return $this;
    }
    
    /*
     * Merge the two above methods for a simplier usage.
     * This function should be used by all to-register adverts.
     * It does simple checks which are needed for every advert.
     * 
     * @param $dialogId The dialog id
     * @param $screenId The screen id to check (optional)
     * @return boolean
     * @see this::_checkAndReset
     */
    public function checkAndReset($dialogId, $screenId = "") {#
        // The check if already set
        $result = $this->_checkAndReset($dialogId, $screenId);
        
        if (!$result) {
            $this->disabledDialogIds[] = $dialogId;
        }
    
        // There is already a dialog registered to show
        if (!empty($this->currentDialogId)) {
            return false;
        }
        
        // Constant disabled
        if (defined('MATTHIASWEB_DISABLE_DIALOGS') && MATTHIASWEB_DISABLE_DIALOGS) {
            return false;
        }
        
        // Disabled for this blog?
        if (($dialogsOff = get_option($this->option_prefix . "dialogs_off")) !== false
            && $dialogsOff > 0) {
            return false;
        }
        
        // Is generelly a dialog allowed for this time?
        $nextTs = get_site_option($this->option_prefix . "next_ts");
        
        // First time? Show the dialog in four days!
        if (empty($nextTs)) {
            update_site_option($this->option_prefix . "next_ts", time() + 24 * 60 * 60 * 4);
            return false;
        }
        
        // Time check
        if ($dialogId !== "hide" && $nextTs !== false && time() < $nextTs) {
            return false;
        }
        
        // Set it to the current dialog
        $this->currentDialogId = $dialogId;
        
        // If false, add it to the blocked list
        return $result;
    }
    
    private function _checkAndReset($dialogId, $screenId = "") {
        if ($this->isDisabled($dialogId)) {
            // "Got it" pressed for this
            return false;
        }else{
            // Check screen id
            if (!empty($screenId)) {
                if (!function_exists("get_current_screen")) {
                    return false;
                }
            
                $screen = get_current_screen();
                //error_log($screen->id);
                if ($screen->id !== $screenId) {
                    return false;
                }
            }
            
            $this->resetPreviousDialog();
            return true;
        }
    }
    
    /*
     * This function is called when the user presses
     * the "Got it!" button in the dialog.
     * 
     * @see print_script
     */
    public function wp_ajax_matthiasweb_advert() {
        check_ajax_referer("matthiasWebAdvertGotIt", "nonce");
        $id = $_POST["id"];
        if (!empty($id)) {
            update_site_option($this->option_prefix . $id, true);
            
            // This option allows only one dialog per 1 week
            $nextTimestamp = time() + 24 * 60 * 60 * 7;
            update_site_option($this->option_prefix . "next_ts", $nextTimestamp);
            
            do_action("MatthiasWeb/Advert/GotIt/" . $id, $this);
            do_action("MatthiasWeb/Advert/GotIt", $this, $id);
        }
    }
    
    /*
     * Initialize advertisment for a given plugin.
     * 
     * @param $FILE The plugin
     * @param $handler The directory to advertisements relative to the plugin
     */
    public function register($FILE, $handler = null) {
        // Use external file to register our adverts
        if ($handler !== null) {
            $absHandler = dirname($FILE) . $handler;
            if (file_exists($absHandler)) {
                require_once($absHandler);
            }
        }
        
        // Set the defaults
        $this->setDefaults();
        
        register_deactivation_hook($FILE, array($this, "register_deactivation_hook"));
    }
    
    /*
     * The dialog with facebook should be available
     * after each activation of MatthiasWeb' plugin.
     */
    public function register_deactivation_hook() {
        delete_site_option($this->option_prefix . "next_ts"); // The next advertisement should pop up in 4 days
    }
    
    /*
     * Print out a given advertisment.
     */
    public function pre_current_active_plugins() {
        if (!current_user_can("install_plugins")) {
            return;
        }
        
        // Get the current dialog
        apply_filters("MatthiasWeb/Advert", $this);
        
        // Check if the dialog can be shown
        if (empty($this->currentDialogId)) {
            return;
        }
        
        // Check for a plugin
        if (!empty($this->checkForPlugin) && is_plugin_active($this->checkForPlugin)) {
            return;
        }
        
        if (empty($this->title)) {
            return;
        }
        
        $nonceGotIt = wp_create_nonce("matthiasWebAdvertGotIt");
        do_action("MatthiasWeb/Advert/" . $this->currentDialogId, $this);
        
        // Show it!
        $this->print_style();
        $this->print_script($nonceGotIt, $this->currentDialogId);
        
        // Create buttons html
        $buttonHTML = '';
        if (count($this->buttons) > 0) {
            foreach ($this->buttons as $value) {
                $href = isset($value["href"]) ? $value["href"] : null;
                $target = isset($value["target"]) ? $value["target"] : "";
                $text = isset($value["text"]) ? $value["text"] : null;
                $isPrimary = isset($value["primary"]) ? $value["primary"] : false;
                if ($href != null && $text != null) {
                    $buttonHTML .= '<a href="' . $href . '" target="' . $target . '" class="button ' . (($isPrimary) ? 'button-primary' : '') . '">' . $text . '</a>';
                }
            }
        }
        
        ?>
    <div id="matthiasweb-advert">
        <?php if (!empty($this->coverImage)) { ?>
        <div id="matthiasweb-advert-image" style="background-image:url('<?php echo $this->coverImage; ?>');"></div>
        <?php }else{ ?>
        <div style="height:10px"></div>
        <?php } ?>
        <div id="matthiasweb-advert-wrap">
            <h1><?php echo $this->title; ?></h1>
            <div id="matthiasweb-advert-content">
                <h2><?php echo $this->subTitle; ?></h2>
                <?php echo $this->body; ?>
            </div>
            <div class="clear"></div>
        </div>
        <div id="matthiasweb-advert-footer">
            <?php echo $buttonHTML; ?>
            <a href="?matthiasweb-advert-off" class="button" id="matthiasweb-advert-close">
                <i class="fa fa-times"></i> Close
            </a>
        </div>
        <div id="matthiasweb-advert-using">
            This message is showing up because you are using the following plugins of MatthiasWeb: <strong><?php echo $this->installedPluginsString(); ?></strong>
        </div>
    </div>
        <?php
    }
    
        public function print_style() {
?>
<style>
#matthiasweb-advert {
    background: #fff;
    color: black;
    height: auto !important;
    margin: 15px 0px;
    border: 1px solid #dadada;
}
#matthiasweb-advert p {
    padding: 0;
    margin: 0px 0px 5px 0px;
}
#matthiasweb-advert h1,
#matthiasweb-advert h2 {
    color: #4C67A1;
}
#matthiasweb-advert-image {
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center top;
    height: 272px;
    box-shadow: 0px 0px 5px #000;
    width: 400px;
    float: left;
    margin: 15px 20px 0px -10px;
}
#matthiasweb-advert-wrap {
    padding: 0px 15px 15px 15px;
}
#matthiasweb-advert-wrap ul {
    line-height: 14px;
}
#matthiasweb-advert-footer {
    padding: 10px 15px;
    background: #eaeaea;
    border-top: 1px solid #dadada;
    text-align: left;
}
#matthiasweb-advert-using {
    background: #eaeaea;
    padding: 0px 15px 10px 15px;
    font-size: 11px;
    line-height: 14px;
    text-align: left;
    color: #4c4c4c;
}
<?php
        echo $this->css . "</style>";
    }

    public function print_script($nonce, $dialogId) {
        $adminurl = admin_url('admin-ajax.php');
?>
<script type="text/javascript">
    /* global jQuery */
    try {
    if (jQuery && jQuery(window).width() >= 815) {
        jQuery(document).ready(function($) {
            /*$("html").addClass("matthias-web-advert");*/
            $(document).on("click", "#matthiasweb-advert-close", function(e) {
                $.post(
                    '<?php echo $adminurl; ?>', 
                    {
                        'action': 'matthiasweb_advert',
                        'nonce': '<?php echo $nonce; ?>', 
                        'id': '<?php echo $dialogId; ?>'
                    },
                    function(response) { });
                $("#matthiasweb-advert").fadeOut();
                e.preventDefault();
                return false;
            });
        });
    }
    }catch (e) {}
</script>
<?php
    }
    
    /*
     * Check the installed plugins installed and activated.
     */
    public function installedPluginsString() {
        $products = array();
        if ($this->isInstalled_RealMediaLibrary()) {
            $products[] = "Real Media Library";
        }
        if ($this->isInstalled_RealCategoriesLibrary()) {
            $products[] = "Real Categories Library";
        }
        if ($this->isInstalled_RealThumbnailGenerator()) {
            $products[] = "Real Thumbnail Generator";
        }
        /*
        if ($this->isInstalled_GGSearch()) {
            $products[] = "Real Dashboard Seach";
        }
        if ($this->isInstalled_Webabu()) {
            $products[] = "Welcome Back Buyer";
        }
        */
        return implode(", ", $products);
    }
    
    public function isInstalled_RealMediaLibrary() {
        return is_plugin_active("real-media-library/real-media-library.php");
    }
    
    public function isInstalled_RealCategoriesLibrary() {
        return is_plugin_active("real-category-library/real-category-library.php");
    }
    
    public function isInstalled_RealThumbnailGenerator() {
        return is_plugin_active("real-thumbnail-generator/real-thumbnail-generator.php");
    }
    
    /*
    public function isInstalled_GGSearch() {
        return is_plugin_active("ggsearch/ggsearch.php");
    }
    
    public function isInstalled_Webabu() {
        return is_plugin_active("welcome-back-buyer/welcome-back-buyer.php");
    }
    */
    
    public function host($absolute) {
        return $this->host . $absolute;
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new MatthiasWeb_AdvertHandler();
        }
        return self::$me;
    }
}
}
?>