<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}
/*
Integration of Basic MailChimp into Buy Now for Stripe
*/
class SupstrMailchimpController
{
  public function load_hooks() {
    add_action('supstr-process-signup', 'SupstrMailChimpController::process_signup', 10, 1);
  }
  
  public static function process_signup($atts) {
    if($atts->mailchimp!='true')
      return;

    //If no checkbox lets kill it
    if(!isset($_POST['supstr_mailchimp']))
      return;
    
    $fname = (isset($_POST['supstr_first_name']) && !empty($_POST['supstr_first_name']))?stripslashes($_POST['supstr_first_name']):'';
    $lname = (isset($_POST['supstr_last_name']) && !empty($_POST['supstr_last_name']))?stripslashes($_POST['supstr_last_name']):'';
    $email = (isset($_POST['supstr_email']) && !empty($_POST['supstr_email']))?stripslashes($_POST['supstr_email']):'';
    $apikey       = $atts->mailchimp_apikey;
    $list_id      = $atts->mailchimp_list_id;
    $double_optin = (int)($atts->mailchimp_double_optin=='true');
    
    preg_match('#-(.*)$#', $apikey, $matches);
    
    if(empty($matches) or !isset($matches[1]))
      return;
    
    if(!empty($apikey) && !empty($list_id) && !empty($email))
    {
      $url = "http://".$matches[1].".api.mailchimp.com/1.3/?output=php&method=listSubscribe&apikey={$apikey}&id={$list_id}&email_address={$email}&double_optin={$double_optin}&merge_vars[fname]={$fname}&merge_vars[lname]={$lname}";
      
      $response = wp_remote_get($url); //We're sending this blindly for now, no need to handle the response.
    }
  }
} //END CLASS

