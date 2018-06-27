<?php
// We have now ensured that we are running the good PHP version.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Load core
require_once(RML_PATH . '/inc/general/Core.class.php');
MatthiasWeb\RealMediaLibrary\general\Core::getInstance();

// Matthias advert
$mwaSubfolder = "/inc/others/";
require_once( RML_PATH . $mwaSubfolder . 'AdvertHandler.class.php' );
MatthiasWeb_AdvertHandler::getInstance()
    ->register( RML_FILE );
unset($mwaSubfolder);
?>