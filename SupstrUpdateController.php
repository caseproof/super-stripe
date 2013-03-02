<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class SupstrUpdateController
{
  public function is_connected($license_key) {
    $url = SUPSTR_ENDPOINT . "/connect/active/{$license_key}";

    $args = array( 'method' => 'GET',
                   'timeout' => 45,
                   'blocking' => true,
                   'sslverify' => false,
                   'headers' => array( 'content-type' => 'application/json' ),
                   'body' => '' );
    
    $resp = wp_remote_get( $url, $args );

    if(is_wp_error($resp)) {
      return false;
    }
    else {
      $json = json_decode($resp['body']);
      return $json->active;
    }
  }
} //End class
