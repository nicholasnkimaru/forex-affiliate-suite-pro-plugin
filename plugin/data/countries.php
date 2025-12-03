<?php
/**
 * Countries and Regions Data
 *
 * ISO 3166-1 alpha-2 country codes with region mappings
 *
 * @package ForexAffiliateSuitePro
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all countries with ISO 3166-1 alpha-2 codes
 *
 * @return array Associative array of country code => country name
 */
if (!function_exists('fasp_get_countries')) {
    function fasp_get_countries() {
        return array(
            'AF' => __('Afghanistan', 'forex-affiliate-suite-pro'),
            'AX' => __('Åland Islands', 'forex-affiliate-suite-pro'),
            'AL' => __('Albania', 'forex-affiliate-suite-pro'),
            'DZ' => __('Algeria', 'forex-affiliate-suite-pro'),
            'AS' => __('American Samoa', 'forex-affiliate-suite-pro'),
            'AD' => __('Andorra', 'forex-affiliate-suite-pro'),
            'AO' => __('Angola', 'forex-affiliate-suite-pro'),
            'AI' => __('Anguilla', 'forex-affiliate-suite-pro'),
            'AQ' => __('Antarctica', 'forex-affiliate-suite-pro'),
            'AG' => __('Antigua and Barbuda', 'forex-affiliate-suite-pro'),
            'AR' => __('Argentina', 'forex-affiliate-suite-pro'),
            'AM' => __('Armenia', 'forex-affiliate-suite-pro'),
            'AW' => __('Aruba', 'forex-affiliate-suite-pro'),
            'AU' => __('Australia', 'forex-affiliate-suite-pro'),
            'AT' => __('Austria', 'forex-affiliate-suite-pro'),
            'AZ' => __('Azerbaijan', 'forex-affiliate-suite-pro'),
            'BS' => __('Bahamas', 'forex-affiliate-suite-pro'),
            'BH' => __('Bahrain', 'forex-affiliate-suite-pro'),
            'BD' => __('Bangladesh', 'forex-affiliate-suite-pro'),
            'BB' => __('Barbados', 'forex-affiliate-suite-pro'),
            'BY' => __('Belarus', 'forex-affiliate-suite-pro'),
            'BE' => __('Belgium', 'forex-affiliate-suite-pro'),
            'BZ' => __('Belize', 'forex-affiliate-suite-pro'),
            'BJ' => __('Benin', 'forex-affiliate-suite-pro'),
            'BM' => __('Bermuda', 'forex-affiliate-suite-pro'),
            'BT' => __('Bhutan', 'forex-affiliate-suite-pro'),
            'BO' => __('Bolivia', 'forex-affiliate-suite-pro'),
            'BA' => __('Bosnia and Herzegovina', 'forex-affiliate-suite-pro'),
            'BW' => __('Botswana', 'forex-affiliate-suite-pro'),
            'BR' => __('Brazil', 'forex-affiliate-suite-pro'),
            'BN' => __('Brunei Darussalam', 'forex-affiliate-suite-pro'),
            'BG' => __('Bulgaria', 'forex-affiliate-suite-pro'),
            'BF' => __('Burkina Faso', 'forex-affiliate-suite-pro'),
            'BI' => __('Burundi', 'forex-affiliate-suite-pro'),
            'KH' => __('Cambodia', 'forex-affiliate-suite-pro'),
            'CM' => __('Cameroon', 'forex-affiliate-suite-pro'),
            'CA' => __('Canada', 'forex-affiliate-suite-pro'),
            'CV' => __('Cape Verde', 'forex-affiliate-suite-pro'),
            'KY' => __('Cayman Islands', 'forex-affiliate-suite-pro'),
            'CF' => __('Central African Republic', 'forex-affiliate-suite-pro'),
            'TD' => __('Chad', 'forex-affiliate-suite-pro'),
            'CL' => __('Chile', 'forex-affiliate-suite-pro'),
            'CN' => __('China', 'forex-affiliate-suite-pro'),
            'CO' => __('Colombia', 'forex-affiliate-suite-pro'),
            'KM' => __('Comoros', 'forex-affiliate-suite-pro'),
            'CG' => __('Congo', 'forex-affiliate-suite-pro'),
            'CD' => __('Congo (Democratic Republic)', 'forex-affiliate-suite-pro'),
            'CR' => __('Costa Rica', 'forex-affiliate-suite-pro'),
            'CI' => __("Côte d'Ivoire", 'forex-affiliate-suite-pro'),
            'HR' => __('Croatia', 'forex-affiliate-suite-pro'),
            'CU' => __('Cuba', 'forex-affiliate-suite-pro'),
            'CW' => __('Curaçao', 'forex-affiliate-suite-pro'),
            'CY' => __('Cyprus', 'forex-affiliate-suite-pro'),
            'CZ' => __('Czech Republic', 'forex-affiliate-suite-pro'),
            'DK' => __('Denmark', 'forex-affiliate-suite-pro'),
            'DJ' => __('Djibouti', 'forex-affiliate-suite-pro'),
            'DM' => __('Dominica', 'forex-affiliate-suite-pro'),
            'DO' => __('Dominican Republic', 'forex-affiliate-suite-pro'),
            'EC' => __('Ecuador', 'forex-affiliate-suite-pro'),
            'EG' => __('Egypt', 'forex-affiliate-suite-pro'),
            'SV' => __('El Salvador', 'forex-affiliate-suite-pro'),
            'GQ' => __('Equatorial Guinea', 'forex-affiliate-suite-pro'),
            'ER' => __('Eritrea', 'forex-affiliate-suite-pro'),
            'EE' => __('Estonia', 'forex-affiliate-suite-pro'),
            'ET' => __('Ethiopia', 'forex-affiliate-suite-pro'),
            'FJ' => __('Fiji', 'forex-affiliate-suite-pro'),
            'FI' => __('Finland', 'forex-affiliate-suite-pro'),
            'FR' => __('France', 'forex-affiliate-suite-pro'),
            'GA' => __('Gabon', 'forex-affiliate-suite-pro'),
            'GM' => __('Gambia', 'forex-affiliate-suite-pro'),
            'GE' => __('Georgia', 'forex-affiliate-suite-pro'),
            'DE' => __('Germany', 'forex-affiliate-suite-pro'),
            'GH' => __('Ghana', 'forex-affiliate-suite-pro'),
            'GI' => __('Gibraltar', 'forex-affiliate-suite-pro'),
            'GR' => __('Greece', 'forex-affiliate-suite-pro'),
            'GL' => __('Greenland', 'forex-affiliate-suite-pro'),
            'GD' => __('Grenada', 'forex-affiliate-suite-pro'),
            'GP' => __('Guadeloupe', 'forex-affiliate-suite-pro'),
            'GU' => __('Guam', 'forex-affiliate-suite-pro'),
            'GT' => __('Guatemala', 'forex-affiliate-suite-pro'),
            'GN' => __('Guinea', 'forex-affiliate-suite-pro'),
            'GW' => __('Guinea-Bissau', 'forex-affiliate-suite-pro'),
            'GY' => __('Guyana', 'forex-affiliate-suite-pro'),
            'HT' => __('Haiti', 'forex-affiliate-suite-pro'),
            'HN' => __('Honduras', 'forex-affiliate-suite-pro'),
            'HK' => __('Hong Kong', 'forex-affiliate-suite-pro'),
            'HU' => __('Hungary', 'forex-affiliate-suite-pro'),
            'IS' => __('Iceland', 'forex-affiliate-suite-pro'),
            'IN' => __('India', 'forex-affiliate-suite-pro'),
            'ID' => __('Indonesia', 'forex-affiliate-suite-pro'),
            'IR' => __('Iran', 'forex-affiliate-suite-pro'),
            'IQ' => __('Iraq', 'forex-affiliate-suite-pro'),
            'IE' => __('Ireland', 'forex-affiliate-suite-pro'),
            'IL' => __('Israel', 'forex-affiliate-suite-pro'),
            'IT' => __('Italy', 'forex-affiliate-suite-pro'),
            'JM' => __('Jamaica', 'forex-affiliate-suite-pro'),
            'JP' => __('Japan', 'forex-affiliate-suite-pro'),
            'JO' => __('Jordan', 'forex-affiliate-suite-pro'),
            'KZ' => __('Kazakhstan', 'forex-affiliate-suite-pro'),
            'KE' => __('Kenya', 'forex-affiliate-suite-pro'),
            'KI' => __('Kiribati', 'forex-affiliate-suite-pro'),
            'KP' => __('Korea (North)', 'forex-affiliate-suite-pro'),
            'KR' => __('Korea (South)', 'forex-affiliate-suite-pro'),
            'KW' => __('Kuwait', 'forex-affiliate-suite-pro'),
            'KG' => __('Kyrgyzstan', 'forex-affiliate-suite-pro'),
            'LA' => __('Laos', 'forex-affiliate-suite-pro'),
            'LV' => __('Latvia', 'forex-affiliate-suite-pro'),
            'LB' => __('Lebanon', 'forex-affiliate-suite-pro'),
            'LS' => __('Lesotho', 'forex-affiliate-suite-pro'),
            'LR' => __('Liberia', 'forex-affiliate-suite-pro'),
            'LY' => __('Libya', 'forex-affiliate-suite-pro'),
            'LI' => __('Liechtenstein', 'forex-affiliate-suite-pro'),
            'LT' => __('Lithuania', 'forex-affiliate-suite-pro'),
            'LU' => __('Luxembourg', 'forex-affiliate-suite-pro'),
            'MO' => __('Macao', 'forex-affiliate-suite-pro'),
            'MK' => __('Macedonia', 'forex-affiliate-suite-pro'),
            'MG' => __('Madagascar', 'forex-affiliate-suite-pro'),
            'MW' => __('Malawi', 'forex-affiliate-suite-pro'),
            'MY' => __('Malaysia', 'forex-affiliate-suite-pro'),
            'MV' => __('Maldives', 'forex-affiliate-suite-pro'),
            'ML' => __('Mali', 'forex-affiliate-suite-pro'),
            'MT' => __('Malta', 'forex-affiliate-suite-pro'),
            'MH' => __('Marshall Islands', 'forex-affiliate-suite-pro'),
            'MQ' => __('Martinique', 'forex-affiliate-suite-pro'),
            'MR' => __('Mauritania', 'forex-affiliate-suite-pro'),
            'MU' => __('Mauritius', 'forex-affiliate-suite-pro'),
            'MX' => __('Mexico', 'forex-affiliate-suite-pro'),
            'FM' => __('Micronesia', 'forex-affiliate-suite-pro'),
            'MD' => __('Moldova', 'forex-affiliate-suite-pro'),
            'MC' => __('Monaco', 'forex-affiliate-suite-pro'),
            'MN' => __('Mongolia', 'forex-affiliate-suite-pro'),
            'ME' => __('Montenegro', 'forex-affiliate-suite-pro'),
            'MS' => __('Montserrat', 'forex-affiliate-suite-pro'),
            'MA' => __('Morocco', 'forex-affiliate-suite-pro'),
            'MZ' => __('Mozambique', 'forex-affiliate-suite-pro'),
            'MM' => __('Myanmar', 'forex-affiliate-suite-pro'),
            'NA' => __('Namibia', 'forex-affiliate-suite-pro'),
            'NR' => __('Nauru', 'forex-affiliate-suite-pro'),
            'NP' => __('Nepal', 'forex-affiliate-suite-pro'),
            'NL' => __('Netherlands', 'forex-affiliate-suite-pro'),
            'NC' => __('New Caledonia', 'forex-affiliate-suite-pro'),
            'NZ' => __('New Zealand', 'forex-affiliate-suite-pro'),
            'NI' => __('Nicaragua', 'forex-affiliate-suite-pro'),
            'NE' => __('Niger', 'forex-affiliate-suite-pro'),
            'NG' => __('Nigeria', 'forex-affiliate-suite-pro'),
            'NO' => __('Norway', 'forex-affiliate-suite-pro'),
            'OM' => __('Oman', 'forex-affiliate-suite-pro'),
            'PK' => __('Pakistan', 'forex-affiliate-suite-pro'),
            'PW' => __('Palau', 'forex-affiliate-suite-pro'),
            'PS' => __('Palestine', 'forex-affiliate-suite-pro'),
            'PA' => __('Panama', 'forex-affiliate-suite-pro'),
            'PG' => __('Papua New Guinea', 'forex-affiliate-suite-pro'),
            'PY' => __('Paraguay', 'forex-affiliate-suite-pro'),
            'PE' => __('Peru', 'forex-affiliate-suite-pro'),
            'PH' => __('Philippines', 'forex-affiliate-suite-pro'),
            'PL' => __('Poland', 'forex-affiliate-suite-pro'),
            'PT' => __('Portugal', 'forex-affiliate-suite-pro'),
            'PR' => __('Puerto Rico', 'forex-affiliate-suite-pro'),
            'QA' => __('Qatar', 'forex-affiliate-suite-pro'),
            'RE' => __('Réunion', 'forex-affiliate-suite-pro'),
            'RO' => __('Romania', 'forex-affiliate-suite-pro'),
            'RU' => __('Russia', 'forex-affiliate-suite-pro'),
            'RW' => __('Rwanda', 'forex-affiliate-suite-pro'),
            'WS' => __('Samoa', 'forex-affiliate-suite-pro'),
            'SM' => __('San Marino', 'forex-affiliate-suite-pro'),
            'ST' => __('São Tomé and Príncipe', 'forex-affiliate-suite-pro'),
            'SA' => __('Saudi Arabia', 'forex-affiliate-suite-pro'),
            'SN' => __('Senegal', 'forex-affiliate-suite-pro'),
            'RS' => __('Serbia', 'forex-affiliate-suite-pro'),
            'SC' => __('Seychelles', 'forex-affiliate-suite-pro'),
            'SL' => __('Sierra Leone', 'forex-affiliate-suite-pro'),
            'SG' => __('Singapore', 'forex-affiliate-suite-pro'),
            'SK' => __('Slovakia', 'forex-affiliate-suite-pro'),
            'SI' => __('Slovenia', 'forex-affiliate-suite-pro'),
            'SB' => __('Solomon Islands', 'forex-affiliate-suite-pro'),
            'SO' => __('Somalia', 'forex-affiliate-suite-pro'),
            'ZA' => __('South Africa', 'forex-affiliate-suite-pro'),
            'SS' => __('South Sudan', 'forex-affiliate-suite-pro'),
            'ES' => __('Spain', 'forex-affiliate-suite-pro'),
            'LK' => __('Sri Lanka', 'forex-affiliate-suite-pro'),
            'SD' => __('Sudan', 'forex-affiliate-suite-pro'),
            'SR' => __('Suriname', 'forex-affiliate-suite-pro'),
            'SZ' => __('Swaziland', 'forex-affiliate-suite-pro'),
            'SE' => __('Sweden', 'forex-affiliate-suite-pro'),
            'CH' => __('Switzerland', 'forex-affiliate-suite-pro'),
            'SY' => __('Syria', 'forex-affiliate-suite-pro'),
            'TW' => __('Taiwan', 'forex-affiliate-suite-pro'),
            'TJ' => __('Tajikistan', 'forex-affiliate-suite-pro'),
            'TZ' => __('Tanzania', 'forex-affiliate-suite-pro'),
            'TH' => __('Thailand', 'forex-affiliate-suite-pro'),
            'TL' => __('Timor-Leste', 'forex-affiliate-suite-pro'),
            'TG' => __('Togo', 'forex-affiliate-suite-pro'),
            'TO' => __('Tonga', 'forex-affiliate-suite-pro'),
            'TT' => __('Trinidad and Tobago', 'forex-affiliate-suite-pro'),
            'TN' => __('Tunisia', 'forex-affiliate-suite-pro'),
            'TR' => __('Turkey', 'forex-affiliate-suite-pro'),
            'TM' => __('Turkmenistan', 'forex-affiliate-suite-pro'),
            'TC' => __('Turks and Caicos Islands', 'forex-affiliate-suite-pro'),
            'TV' => __('Tuvalu', 'forex-affiliate-suite-pro'),
            'UG' => __('Uganda', 'forex-affiliate-suite-pro'),
            'UA' => __('Ukraine', 'forex-affiliate-suite-pro'),
            'AE' => __('United Arab Emirates', 'forex-affiliate-suite-pro'),
            'GB' => __('United Kingdom', 'forex-affiliate-suite-pro'),
            'US' => __('United States', 'forex-affiliate-suite-pro'),
            'UY' => __('Uruguay', 'forex-affiliate-suite-pro'),
            'UZ' => __('Uzbekistan', 'forex-affiliate-suite-pro'),
            'VU' => __('Vanuatu', 'forex-affiliate-suite-pro'),
            'VE' => __('Venezuela', 'forex-affiliate-suite-pro'),
            'VN' => __('Vietnam', 'forex-affiliate-suite-pro'),
            'VG' => __('Virgin Islands (British)', 'forex-affiliate-suite-pro'),
            'VI' => __('Virgin Islands (U.S.)', 'forex-affiliate-suite-pro'),
            'YE' => __('Yemen', 'forex-affiliate-suite-pro'),
            'ZM' => __('Zambia', 'forex-affiliate-suite-pro'),
            'ZW' => __('Zimbabwe', 'forex-affiliate-suite-pro'),
        );
    }
}

/**
 * Get regions with their country mappings
 *
 * @return array Associative array of region => array of country codes
 */
if (!function_exists('fasp_get_regions')) {
    function fasp_get_regions() {
        return array(
            'africa' => array(
                'name' => __('Africa', 'forex-affiliate-suite-pro'),
                'countries' => array('DZ', 'AO', 'BJ', 'BW', 'BF', 'BI', 'CM', 'CV', 'CF', 'TD', 'KM', 'CG', 'CD', 'CI', 'DJ', 'EG', 'GQ', 'ER', 'ET', 'GA', 'GM', 'GH', 'GN', 'GW', 'KE', 'LS', 'LR', 'LY', 'MG', 'MW', 'ML', 'MR', 'MU', 'MA', 'MZ', 'NA', 'NE', 'NG', 'RW', 'ST', 'SN', 'SC', 'SL', 'SO', 'ZA', 'SS', 'SD', 'SZ', 'TZ', 'TG', 'TN', 'UG', 'ZM', 'ZW'),
            ),
            'asia' => array(
                'name' => __('Asia', 'forex-affiliate-suite-pro'),
                'countries' => array('AF', 'AM', 'AZ', 'BH', 'BD', 'BT', 'BN', 'KH', 'CN', 'CY', 'GE', 'HK', 'IN', 'ID', 'IR', 'IQ', 'IL', 'JP', 'JO', 'KZ', 'KW', 'KG', 'LA', 'LB', 'MO', 'MY', 'MV', 'MN', 'MM', 'NP', 'KP', 'OM', 'PK', 'PS', 'PH', 'QA', 'SA', 'SG', 'KR', 'LK', 'SY', 'TW', 'TJ', 'TH', 'TL', 'TR', 'TM', 'AE', 'UZ', 'VN', 'YE'),
            ),
            'europe' => array(
                'name' => __('Europe', 'forex-affiliate-suite-pro'),
                'countries' => array('AL', 'AD', 'AT', 'BY', 'BE', 'BA', 'BG', 'HR', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GI', 'GR', 'HU', 'IS', 'IE', 'IT', 'LV', 'LI', 'LT', 'LU', 'MK', 'MT', 'MD', 'MC', 'ME', 'NL', 'NO', 'PL', 'PT', 'RO', 'RU', 'SM', 'RS', 'SK', 'SI', 'ES', 'SE', 'CH', 'UA', 'GB'),
            ),
            'north_america' => array(
                'name' => __('North America', 'forex-affiliate-suite-pro'),
                'countries' => array('AG', 'BS', 'BB', 'BZ', 'CA', 'CR', 'CU', 'CW', 'DM', 'DO', 'SV', 'GD', 'GP', 'GT', 'HT', 'HN', 'JM', 'MQ', 'MX', 'MS', 'NI', 'PA', 'PR', 'KN', 'LC', 'VC', 'TT', 'TC', 'US', 'VI'),
            ),
            'south_america' => array(
                'name' => __('South America', 'forex-affiliate-suite-pro'),
                'countries' => array('AR', 'BO', 'BR', 'CL', 'CO', 'EC', 'FK', 'GF', 'GY', 'PY', 'PE', 'SR', 'UY', 'VE'),
            ),
            'oceania' => array(
                'name' => __('Oceania', 'forex-affiliate-suite-pro'),
                'countries' => array('AS', 'AU', 'FJ', 'PF', 'GU', 'KI', 'MH', 'FM', 'NR', 'NC', 'NZ', 'NU', 'PW', 'PG', 'WS', 'SB', 'TK', 'TO', 'TV', 'VU'),
            ),
            'eu' => array(
                'name' => __('European Union', 'forex-affiliate-suite-pro'),
                'countries' => array('AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'),
            ),
            'mena' => array(
                'name' => __('Middle East & North Africa', 'forex-affiliate-suite-pro'),
                'countries' => array('DZ', 'BH', 'EG', 'IR', 'IQ', 'IL', 'JO', 'KW', 'LB', 'LY', 'MA', 'OM', 'PS', 'QA', 'SA', 'SY', 'TN', 'AE', 'YE'),
            ),
            'apac' => array(
                'name' => __('Asia Pacific', 'forex-affiliate-suite-pro'),
                'countries' => array('AU', 'BD', 'BN', 'KH', 'CN', 'FJ', 'HK', 'IN', 'ID', 'JP', 'KZ', 'KR', 'LA', 'MY', 'MV', 'MN', 'MM', 'NP', 'NZ', 'PK', 'PG', 'PH', 'SG', 'LK', 'TW', 'TH', 'TL', 'VN'),
            ),
        );
    }
}

/**
 * Get countries for a region
 *
 * @param string $region Region key
 * @return array Array of country codes
 */
if (!function_exists('fasp_get_region_countries')) {
    function fasp_get_region_countries($region) {
        $regions = fasp_get_regions();
        if (isset($regions[$region]['countries'])) {
            return $regions[$region]['countries'];
        }
        return array();
    }
}

/**
 * Get region name
 *
 * @param string $region Region key
 * @return string Region name
 */
if (!function_exists('fasp_get_region_name')) {
    function fasp_get_region_name($region) {
        $regions = fasp_get_regions();
        if (isset($regions[$region]['name'])) {
            return $regions[$region]['name'];
        }
        return $region;
    }
}
