<?php
namespace MatthiasWeb\RealMediaLibrary\usersettings;
use MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\api;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Demo implements api\IUserSettings {
    
    public function content($content, $user) {
        $content .= '<tr>
            <th scope="row">Demo for user #' . $user . '</th>
            <td>
                <textarea name="demo" type="text" class="regular-text" style="width: 100%;box-sizing: border-box;">Your Text</textarea>
                <p class="description">Data is not saved</p>
            </td>
        </tr>
        <tr class="rml-meta-margin"></tr>';
        
        return $content;
    }
    
    public function save($response, $user) {
        $response["errors"][] = "An error occured with demo text: " . $_POST["demo"] . ". This is only a demo.";
        return $response;
    }

    public function scripts() {
        // Silence is golden.
    }
}