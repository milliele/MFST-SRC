<?php
/**
Plugin Name: WP Real Media Library
Plugin URI: https://matthias-web.com/wordpress/real-media-library/
Description: Organize your wordpress media library in a nice way.
Author: Matthias Günter
Version: 3.4.3
Author URI: https://matthias-web.com
Licence: GPLv2
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if (defined('RML_PATH')) return;
define('RML_PATH', dirname ( __FILE__ ));
define('RML_MIN_PHP_VERSION', "5.3.0");
define('RML_NS', "MatthiasWeb\\RealMediaLibrary");
define('RML_FILE', __FILE__);
define('RML_TD', 'real-media-library');
define('RML_VERSION', '3.4.3');
define('RML_PRE_GET_POSTS_PRIORITY', 9999999);

/**
 * CONSTANT FOLDER TYPES
 */
define('RML_TYPE_FOLDER', 0);
define('RML_TYPE_COLLECTION', 1);
define('RML_TYPE_GALLERY', 2);
define('RML_TYPE_ALL', 3);
define('RML_TYPE_ROOT', 4);

// Check PHP Version
if ((version_compare(phpversion(), RML_MIN_PHP_VERSION) >= 0)) {
    require_once(RML_PATH . "/inc/others/start.php");
}else{
    if (!function_exists("rml_skip_php_admin_notice")) {
        function rml_skip_php_admin_notice() {
            if (current_user_can("install_plugins")) {
            ?>
            <div class="notice notice-error">
                <p><strong>Real Media Library</strong> could not be initialized because you need minimum PHP version <?php echo RML_MIN_PHP_VERSION; ?> ... you are running: <?php echo phpversion(); ?>.
                <a target="_blank" href="http://justifiedgrid.com/support/fix/why-is-my-php-old/">Why is my PHP old?</a></p>
            </div>
            <?php
            }
        }
    }
    add_action( 'admin_notices', 'rml_skip_php_admin_notice' );
}
?>