<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class SupstrUtils {
  public static function get_txn_by_num($txn_num) {
    global $wpdb;
    $query = "SELECT pm.post_id " .
               "FROM {$wpdb->postmeta} AS pm " .
              "WHERE pm.meta_key='_supstr_txn_num' " .
                "AND pm.meta_value=%s " .
              "LIMIT 1";

    $query = $wpdb->prepare($query, $txn_num);
    $post_id = $wpdb->get_var($query);

    if( !empty($post_id) ) {
      $post = get_post($post_id);

      if( !empty($post) and !is_wp_error($post) )
        return $post;
    }

    return false;
  }

  /**
  * Calculate the HMAC SHA1 hash of a string.
  *
  * @param string $key The key to hash against
  * @param string $data The data to hash
  * @param int $blocksize Optional blocksize
  * @return string HMAC SHA1
  */
  public static function el_crypto_hmacSHA1($key, $data, $blocksize = 64) {
    if (strlen($key) > $blocksize) $key = pack('H*', sha1($key));
    $key = str_pad($key, $blocksize, chr(0x00));
    $ipad = str_repeat(chr(0x36), $blocksize);
    $opad = str_repeat(chr(0x5c), $blocksize);
    $hmac = pack( 'H*', sha1( ($key ^ $opad) . pack( 'H*', sha1( ($key ^ $ipad) . $data))));
    return base64_encode($hmac);
  }

  /** Create temporary URLs to your protected Amazon S3 files.
    *
    * @param string $accessKey Your Amazon S3 access key
    * @param string $secretKey Your Amazon S3 secret key
    * @param string $bucket The bucket (bucket.s3.amazonaws.com)
    * @param string $path The target file path
    * @param int $expires In minutes
    * @return string Temporary Amazon S3 URL
    * @see http://awsdocs.s3.amazonaws.com/S3/20060301/s3-dg-20060301.pdf
    */
  public static function el_s3_getTemporaryLink($accessKey, $secretKey, $bucket, $path, $expires = '5:00', $time = null) {
    if( is_null($time) ) {
      $time = time();
    }
    else {
      $ta = date_parse($time);
      $time = mktime($ta['hour'],$ta['minute'],$ta['second'],$ta['month'],$ta['day'],$ta['year']);
    }

    // Calculate expiry time
    $ex = explode(':',$expires);
    $ex_min = (int)$ex[0];
    $ex_sec = isset($ex[1]) ? (int)$ex[1] : 0;
    $expires = $time + ($ex_min * 60) + $ex_sec;
    // Fix the path; encode and sanitize
    $path = str_replace('%2F', '/', rawurlencode($path = ltrim($path, '/')));
    // Path for signature starts with the bucket
    $signpath = '/'. $bucket .'/'. $path;
    // S3 friendly string to sign
    $signsz = implode("\n", $pieces = array('GET', null, null, $expires, $signpath));
    // Calculate the hash
    $signature = self::el_crypto_hmacSHA1($secretKey, $signsz);
    // Glue the URL ...
    $url = sprintf('http://%s.s3.amazonaws.com/%s', $bucket, $path);
    // ... to the query string ...
    $qs = http_build_query($pieces = array(
      'AWSAccessKeyId' => $accessKey,
      'Expires' => $expires,
      'Signature' => $signature,
    ));
    // ... and return the URL!
    return "{$url}?{$qs}";
  }
}
