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

  public static function lookup_currency_symbol( $code ) {
    $code = strtolower($code);

    static $lookup;

    if( !isset( $lookup ) ) {
      $lookup = array(
        'aed' => array('name' => 'UAE Dirham'), 
        'afn' => array('name' => 'Afghani', 'symbol' => '؋'), 
        'all' => array('name' => 'Lek', 'symbol' => 'Lek'), 
        'amd' => array('name' => 'Armenian Dram', 'symbol' => 'Դ'), 
        'ang' => array('name' => 'Netherlands Antillian Guilder', 'symbol' => 'ƒ'), 
        'aoa' => array('name' => 'Kwanza', 'symbol' => 'Kz'), 
        'ars' => array('name' => 'Argentine Peso', 'symbol' => '$'), 
        'aud' => array('name' => 'Australian Dollar', 'symbol' => '$', 'country' => 'au'), 
        'awg' => array('name' => 'Aruban Guilder', 'symbol' => 'ƒ'), 
        'azn' => array('name' => 'Azerbaijanian Manat', 'symbol' => 'ман'), 
        'bam' => array('name' => 'Convertible Marks', 'symbol' => 'KM'), 
        'bbd' => array('name' => 'Barbados Dollar', 'symbol' => '$'), 
        'bdt' => array('name' => 'Taka'), 
        'bgn' => array('name' => 'Bulgarian Lev', 'symbol' => 'лв'), 
        'bhd' => array('name' => 'Bahraini Dinar'), 
        'bif' => array('name' => 'Burundi Franc', 'symbol' => 'FBu'), 
        'bmd' => array('name' => 'Bermudian Dollar', 'symbol' => '$'), 
        'bnd' => array('name' => 'Brunei Dollar', 'symbol' => '$'), 
        'bob' => array('name' => 'Boliviano', 'symbol' => '$b'), 
        'bov' => array('name' => 'Mvdol'), 
        'brl' => array('name' => 'Brazilian Real', 'symbol' => 'R$'), 
        'bsd' => array('name' => 'Bahamian Dollar', 'symbol' => '$'), 
        'btn' => array('name' => 'Ngultrum', 'symbol' => 'Nu.'), 
        'bwp' => array('name' => 'Pula', 'symbol' => 'P'), 
        'byr' => array('name' => 'Belarussian Ruble', 'symbol' => 'p.'), 
        'bzd' => array('name' => 'Belize Dollar', 'symbol' => 'BZ$'), 
        'cad' => array('name' => 'Canadian Dollar', 'symbol' => '$'), 
        'cdf' => array('name' => 'Congolese Franc', 'symbol' => 'FC'), 
        'che' => array('name' => 'WIR Euro'), 
        'chf' => array('name' => 'Swiss Franc', 'country' => 'ch'), 
        'chw' => array('name' => 'WIR Franc'), 
        'clf' => array('name' => 'Unidades de fomento', 'symbol' => 'UF'), 
        'clp' => array('name' => 'Chilean Peso', 'symbol' => '$'), 
        'cny' => array('name' => 'Yuan Renminbi', 'symbol' => '¥'), 
        'cop' => array('name' => 'Colombian Peso', 'symbol' => '$'), 
        'cou' => array('name' => 'Unidad de Valor Real'), 
        'crc' => array('name' => 'Costa Rican Colon', 'symbol' => '₡'), 
        'cuc' => array('name' => 'Peso Convertible', 'symbol' => '$'), 
        'cup' => array('name' => 'Cuban Peso', 'symbol' => '₱'), 
        'cve' => array('name' => 'Cape Verde Escudo', 'symbol' => '$'), 
        'czk' => array('name' => 'Czech Koruna', 'symbol' => 'Kč'), 
        'djf' => array('name' => 'Djibouti Franc', 'symbol' => 'Fdj'), 
        'dkk' => array('name' => 'Danish Krone', 'symbol' => 'kr', 'country' => 'dk'), 
        'dop' => array('name' => 'Dominican Peso', 'symbol' => 'RD$'), 
        'dzd' => array('name' => 'Algerian Dinar', 'symbol' => 'دج'), 
        'eek' => array('name' => 'Kroon'), 
        'egp' => array('name' => 'Egyptian Pound', 'symbol' => '£'), 
        'ern' => array('name' => 'Nakfa', 'symbol' => 'Nfk'), 
        'etb' => array('name' => 'Ethiopian Birr', 'symbol' => 'Br'), 
        'eur' => array('name' => 'Euro', 'symbol' => '€', 'country' => 'eu'), 
        'fjd' => array('name' => 'Fiji Dollar', 'symbol' => '$'), 
        'fkp' => array('name' => 'Falkland Islands Pound', 'symbol' => '£'), 
        'gbp' => array('name' => 'Pound Sterling', 'symbol' => '£', 'country' => 'gb'), 
        'gel' => array('name' => 'Lari'),
        'ggp' => array('symbol' => '£'), 
        'ghc' => array('symbol' => '¢'), 
        'ghs' => array('name' => 'Cedi', 'symbol' => 'GH₵'), 
        'gip' => array('name' => 'Gibraltar Pound', 'symbol' => '£'), 
        'gmd' => array('name' => 'Dalasi', 'symbol' => 'D'), 
        'gnf' => array('name' => 'Guinea Franc', 'symbol' => 'FG'), 
        'gtq' => array('name' => 'Quetzal', 'symbol' => 'Q'), 
        'gyd' => array('name' => 'Guyana Dollar', 'symbol' => '$'), 
        'hkd' => array('name' => 'Hong Kong Dollar', 'symbol' => '$'), 
        'hnl' => array('name' => 'Lempira', 'symbol' => 'L'), 
        'hrk' => array('name' => 'Croatian Kuna', 'symbol' => 'kn'), 
        'htg' => array('name' => 'Gourde', 'symbol' => 'G'), 
        'huf' => array('name' => 'Forint', 'symbol' => 'Ft'), 
        'idr' => array('name' => 'Rupiah', 'symbol' => 'Rp'), 
        'ils' => array('name' => 'New Israeli Sheqel', 'symbol' => '₪'), 
        'imp' => array('symbol' => '£'), 
        'inr' => array('name' => 'Indian Rupee', 'country' => 'in','symbol' => '&#8377;'), 
        'iqd' => array('name' => 'Iraqi Dinar', 'symbol' => 'ع.د'), 
        'irr' => array('name' => 'Iranian Rial', 'symbol' => '﷼'), 
        'isk' => array('name' => 'Iceland Krona', 'symbol' => 'kr'), 
        'jep' => array('symbol' => '£'), 
        'jmd' => array('name' => 'Jamaican Dollar', 'symbol' => 'J$'), 
        'jod' => array('name' => 'Jordanian Dinar'), 
        'jpy' => array('name' => 'Yen', 'symbol' => '¥'), 
        'kes' => array('name' => 'Kenyan Shilling', 'symbol' => 'KSh'), 
        'kgs' => array('name' => 'Som', 'symbol' => 'лв'), 
        'khr' => array('name' => 'Riel', 'symbol' => '៛'), 
        'kmf' => array('name' => 'Comoro Franc', 'symbol' => 'CF'), 
        'kpw' => array('name' => 'North Korean Won', 'symbol' => '₩'), 
        'krw' => array('name' => 'Won', 'symbol' => '₩'), 
        'kwd' => array('name' => 'Kuwaiti Dinar', 'symbol' => 'K.D.'), 
        'kyd' => array('name' => 'Cayman Islands Dollar', 'symbol' => '$'), 
        'kzt' => array('name' => 'Tenge', 'symbol' => 'лв'), 
        'lak' => array('name' => 'Kip', 'symbol' => '₭'), 
        'lbp' => array('name' => 'Lebanese Pound', 'symbol' => '£'), 
        'lkr' => array('name' => 'Sri Lanka Rupee', 'symbol' => '₨'), 
        'lrd' => array('name' => 'Liberian Dollar', 'symbol' => '$'), 
        'lsl' => array('name' => 'Loti', 'symbol' => 'L'), 
        'ltl' => array('name' => 'Lithuanian Litas', 'symbol' => 'Lt'), 
        'lvl' => array('name' => 'Latvian Lats', 'symbol' => 'Ls'), 
        'lyd' => array('name' => 'Libyan Dinar', 'symbol' => 'LD'), 
        'mad' => array('name' => 'Moroccan Dirham', 'symbol' => 'م.', 'country' => 'ma'), 
        'mdl' => array('name' => 'Moldovan Leu'), 
        'mga' => array('name' => 'Malagasy Ariary', 'symbol' => 'Ar'), 
        'mkd' => array('name' => 'Denar', 'symbol' => 'ден'), 
        'mmk' => array('name' => 'Kyat', 'symbol' => 'K'), 
        'mnt' => array('name' => 'Tugrik', 'symbol' => '₮'), 
        'mop' => array('name' => 'Pataca', 'symbol' => 'MOP$'), 
        'mro' => array('name' => 'Ouguiya', 'symbol' => 'UM'), 
        'mur' => array('name' => 'Mauritius Rupee', 'symbol' => '₨'), 
        'mvr' => array('name' => 'Rufiyaa', 'symbol' => 'Rf'), 
        'mwk' => array('name' => 'Kwacha', 'symbol' => 'MK'), 
        'mxn' => array('name' => 'Mexican Peso', 'symbol' => '$'), 
        'mxv' => array('name' => 'Mexican Unidad de Inversion (UDI)'), 
        'myr' => array('name' => 'Malaysian Ringgit', 'symbol' => 'RM'), 
        'mzn' => array('name' => 'Metical', 'symbol' => 'MT'), 
        'nad' => array('name' => 'Namibia Dollar', 'symbol' => '$'), 
        'ngn' => array('name' => 'Naira', 'symbol' => '₦'), 
        'nio' => array('name' => 'Cordoba Oro', 'symbol' => 'C$'), 
        'nok' => array('name' => 'Norwegian Krone', 'symbol' => 'kr', 'country' => 'no'), 
        'npr' => array('name' => 'Nepalese Rupee', 'symbol' => '₨'), 
        'nzd' => array('name' => 'New Zealand Dollar', 'symbol' => '$', 'country' => 'nz'), 
        'omr' => array('name' => 'Rial Omani', 'symbol' => '﷼'), 
        'pab' => array('name' => 'Balboa', 'symbol' => 'B/.'), 
        'pen' => array('name' => 'Nuevo Sol', 'symbol' => 'S/.'), 
        'pgk' => array('name' => 'Kina', 'symbol' => 'K'), 
        'php' => array('name' => 'Philippine Peso', 'symbol' => 'Php'), 
        'pkr' => array('name' => 'Pakistan Rupee', 'symbol' => '₨'), 
        'pln' => array('name' => 'Zloty', 'symbol' => 'zł'), 
        'pyg' => array('name' => 'Guarani', 'symbol' => 'Gs'), 
        'qar' => array('name' => 'Qatari Rial', 'symbol' => '﷼'), 
        'ron' => array('name' => 'New Leu', 'symbol' => 'lei'), 
        'rsd' => array('name' => 'Serbian Dinar', 'symbol' => 'Дин.'), 
        'rub' => array('name' => 'Russian Ruble', 'symbol' => 'руб'), 
        'rwf' => array('name' => 'Rwanda Franc', 'symbol' => 'FRw'), 
        'sar' => array('name' => 'Saudi Riyal', 'symbol' => '﷼'), 
        'sbd' => array('name' => 'Solomon Islands Dollar', 'symbol' => '$'), 
        'scr' => array('name' => 'Seychelles Rupee', 'symbol' => '₨'), 
        'sdg' => array('name' => 'Sudanese Pound'), 
        'sek' => array('name' => 'Swedish Krona', 'symbol' => 'kr'), 
        'sgd' => array('name' => 'Singapore Dollar', 'symbol' => '$'), 
        'shp' => array('name' => 'Saint Helena Pound', 'symbol' => '£'), 
        'sll' => array('name' => 'Leone', 'symbol' => 'Le'), 
        'sos' => array('name' => 'Somali Shilling', 'symbol' => 'S'), 
        'srd' => array('name' => 'Surinam Dollar', 'symbol' => '$'), 
        'std' => array('name' => 'Dobra', 'symbol' => 'Db'), 
        'svc' => array('name' => 'El Salvador Colon', 'symbol' => '$'), 
        'syp' => array('name' => 'Syrian Pound', 'symbol' => '£'), 
        'szl' => array('name' => 'Lilangeni', 'symbol' => 'L'), 
        'thb' => array('name' => 'Baht', 'symbol' => '฿'), 
        'tjs' => array('name' => 'Somoni'), 
        'tmt' => array('name' => 'Manat', 'symbol' => 'm'), 
        'tnd' => array('name' => 'Tunisian Dinar', 'symbol' => 'DT'), 
        'top' => array('name' => 'Pa\'anga', 'symbol' => 'DT'), 
        'try' => array('name' => 'Turkish Lira', 'symbol' => 'TL'), 
        'ttd' => array('name' => 'Trinidad and Tobago Dollar', 'symbol' => 'TT$'), 
        'tvd' => array('symbol' => '$'), 
        'twd' => array('name' => 'New Taiwan Dollar', 'symbol' => 'NT$'), 
        'tzs' => array('name' => 'Tanzanian Shilling'), 
        'uah' => array('name' => 'Hryvnia', 'symbol' => '₴'), 
        'ugx' => array('name' => 'Uganda Shilling', 'symbol' => 'USh'), 
        'usd' => array('name' => 'US Dollar', 'symbol' => '$', 'country' => 'us'), 
        'usn' => array('name' => 'US Dollar (Next day)'), 
        'uss' => array('name' => 'US Dollar (Same day)'), 
        'uyi' => array('name' => 'Uruguay Peso en Unidades Indexadas'), 
        'uyu' => array('name' => 'Peso Uruguayo', 'symbol' => '$U'), 
        'uzs' => array('name' => 'Uzbekistan Sum', 'symbol' => 'лв'), 
        'vef' => array('name' => 'Bolivar Fuerte', 'symbol' => 'Bs'), 
        'vnd' => array('name' => 'Dong', 'symbol' => '₫'), 
        'vuv' => array('name' => 'Vatu', 'symbol' => 'VT'), 
        'wst' => array('name' => 'Tala'), 
        'xaf' => array('name' => 'CFA Franc BEAC', 'symbol' => 'FCFA', 'country' => 'cm'), 
        'xag' => array('name' => 'Silver'),
        'xau' => array('name' => 'Gold'), 
        'xba' => array('name' => 'Bond Markets Units European Composite Unit (EURCO)'), 
        'xbb' => array('name' => 'European Monetary Unit (E.M.U.-6)'), 
        'xbc' => array('name' => 'European Unit of Account 9(E.U.A.-9)'), 
        'xbd' => array('name' => 'European Unit of Account 17(E.U.A.-17)'), 
        'xcd' => array('name' => 'East Caribbean Dollar', 'symbol' => '$', 'country' => 'kn'),
        'xdr' => array('name' => 'SDR'), 
        'xfu' => array('name' => 'UIC-Franc'), 
        'xof' => array('name' => 'CFA Franc BCEAO', 'symbol' => 'CFA', 'country' => 'sn'), 
        'xpd' => array('name' => 'Palladium'), 
        'xpf' => array('name' => 'CFP Franc', 'country' => 'pf'), 
        'xpt' => array('name' => 'Platinum'), 
        'xts' => array('name' => 'Codes specifically reserved for testing purposes'), 
        'xxx' => array('name' => 'The codes assigned for transactions where no currency is involved are:'), 
        'yer' => array('name' => 'Yemeni Rial', 'symbol' => '﷼'), 
        'zar' => array('name' => 'Rand', 'symbol' => 'R', 'country' => 'za'), 
        'zmk' => array('name' => 'Zambian Kwacha', 'symbol' => 'ZK'), 
        'zwd' => array('symbol' => 'Z$'), 
        'zwl' => array('name' => 'Zimbabwe Dollar', 'symbol' => '$')
      );
    }

    // if the code is defined then return the symbol
    return (isset($lookup[$code]['symbol']) ? $lookup[$code]['symbol'] : '$'); 
  }
}
