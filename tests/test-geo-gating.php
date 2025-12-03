<?php
/**
 * Unit tests for geo-gating-setup.php functions
 *
 * @package ForexAffiliateSuitePro
 */

// Mock WordPress functions
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

if (!defined('FASP_PATH')) {
    define('FASP_PATH', dirname(__DIR__) . '/');
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

$_mock_geo_options = array(
    'fasp_geo_enabled' => true,
    'fasp_geo_allow' => array('US', 'GB', 'CA'),
    'fasp_geo_block' => array('RU', 'CN'),
    'fasp_geo_regions' => array(),
    'fasp_geo_unknown_blocked' => true,
);

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $_mock_geo_options;
        return isset($_mock_geo_options[$option]) ? $_mock_geo_options[$option] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        global $_mock_geo_options;
        $_mock_geo_options[$option] = $value;
        return true;
    }
}

if (!function_exists('get_transient')) {
    function get_transient($key) {
        return false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($key, $value, $expiration = 0) {
        return true;
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = array()) {
        // Simulate API response for testing
        if (strpos($url, '8.8.8.8') !== false) {
            return array(
                'body' => '{"countryCode":"US"}',
                'response' => array('code' => 200),
            );
        }
        return new WP_Error('http_request_failed', 'Connection refused');
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = array();
        public function __construct($code = '', $message = '', $data = '') {
            $this->errors[$code] = $message;
        }
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return isset($response['body']) ? $response['body'] : '';
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value) {
        return stripslashes($value);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        return true;
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null) {
        return true;
    }
}

// Include the files under test
require_once FASP_PATH . 'plugin/data/countries.php';
require_once FASP_PATH . 'includes/geo-gating-setup.php';

/**
 * Test class for geo gating functions
 */
class TestGeoGating {
    
    /**
     * Test fasp_get_countries returns array
     */
    public function test_get_countries() {
        $countries = fasp_get_countries();
        
        assert(is_array($countries), 'fasp_get_countries should return an array');
        assert(count($countries) > 200, 'Should have over 200 countries');
        assert(isset($countries['US']), 'Should have US');
        assert(isset($countries['GB']), 'Should have GB');
        assert(isset($countries['KE']), 'Should have KE (Kenya)');
        
        echo "✓ fasp_get_countries returns " . count($countries) . " countries\n";
        return true;
    }
    
    /**
     * Test fasp_get_regions returns array
     */
    public function test_get_regions() {
        $regions = fasp_get_regions();
        
        assert(is_array($regions), 'fasp_get_regions should return an array');
        assert(isset($regions['africa']), 'Should have africa region');
        assert(isset($regions['europe']), 'Should have europe region');
        assert(isset($regions['asia']), 'Should have asia region');
        assert(isset($regions['eu']), 'Should have EU region');
        
        echo "✓ fasp_get_regions returns " . count($regions) . " regions\n";
        return true;
    }
    
    /**
     * Test fasp_get_region_countries
     */
    public function test_get_region_countries() {
        $africa = fasp_get_region_countries('africa');
        
        assert(is_array($africa), 'fasp_get_region_countries should return an array');
        assert(in_array('KE', $africa), 'Africa should include Kenya');
        assert(in_array('ZA', $africa), 'Africa should include South Africa');
        assert(!in_array('US', $africa), 'Africa should not include US');
        
        echo "✓ fasp_get_region_countries('africa') returns " . count($africa) . " countries\n";
        return true;
    }
    
    /**
     * Test fasp_is_country_allowed with allowlist
     */
    public function test_is_country_allowed_allowlist() {
        // US is in allowlist
        $result_us = fasp_is_country_allowed('US');
        assert($result_us === true, 'US should be allowed (in allowlist)');
        
        // DE is NOT in allowlist
        $result_de = fasp_is_country_allowed('DE');
        assert($result_de === false, 'DE should be blocked (not in allowlist)');
        
        echo "✓ fasp_is_country_allowed respects allowlist\n";
        return true;
    }
    
    /**
     * Test fasp_is_country_allowed with blocklist
     */
    public function test_is_country_allowed_blocklist() {
        // RU is in blocklist
        $result_ru = fasp_is_country_allowed('RU');
        assert($result_ru === false, 'RU should be blocked (in blocklist)');
        
        // CN is in blocklist
        $result_cn = fasp_is_country_allowed('CN');
        assert($result_cn === false, 'CN should be blocked (in blocklist)');
        
        echo "✓ fasp_is_country_allowed respects blocklist\n";
        return true;
    }
    
    /**
     * Test fasp_is_country_allowed with unknown country
     */
    public function test_is_country_allowed_unknown() {
        // Unknown country when fasp_geo_unknown_blocked is true
        $result = fasp_is_country_allowed(null);
        assert($result === false, 'Unknown country should be blocked when fasp_geo_unknown_blocked is true');
        
        echo "✓ fasp_is_country_allowed handles unknown countries\n";
        return true;
    }
    
    /**
     * Test fasp_get_client_ip
     */
    public function test_get_client_ip() {
        // Mock REMOTE_ADDR
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        
        $ip = fasp_get_client_ip();
        assert(filter_var($ip, FILTER_VALIDATE_IP) !== false, 'Should return valid IP');
        
        echo "✓ fasp_get_client_ip returns valid IP: $ip\n";
        return true;
    }
    
    /**
     * Test fasp_lookup_country_by_ip (fallback API)
     */
    public function test_lookup_country_by_ip() {
        // Test with Google DNS IP (should return US in mock)
        $country = fasp_lookup_country_by_ip('8.8.8.8');
        
        assert($country === 'US', 'Google DNS should return US');
        
        echo "✓ fasp_lookup_country_by_ip returns country code: $country\n";
        return true;
    }
    
    /**
     * Test fasp_lookup_country_by_ip with invalid IP
     */
    public function test_lookup_invalid_ip() {
        $country = fasp_lookup_country_by_ip('not-an-ip');
        
        assert($country === null, 'Invalid IP should return null');
        
        echo "✓ fasp_lookup_country_by_ip returns null for invalid IP\n";
        return true;
    }
    
    /**
     * Run all tests
     */
    public function run() {
        echo "\n=== Running Geo Gating Tests ===\n\n";
        
        $tests = array(
            'test_get_countries',
            'test_get_regions',
            'test_get_region_countries',
            'test_is_country_allowed_allowlist',
            'test_is_country_allowed_blocklist',
            'test_is_country_allowed_unknown',
            'test_get_client_ip',
            'test_lookup_country_by_ip',
            'test_lookup_invalid_ip'
        );
        
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $test) {
            try {
                $this->$test();
                $passed++;
            } catch (Exception $e) {
                echo "✗ $test failed: " . $e->getMessage() . "\n";
                $failed++;
            } catch (Error $e) {
                echo "✗ $test error: " . $e->getMessage() . "\n";
                $failed++;
            }
        }
        
        echo "\n=== Results ===\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        
        return $failed === 0;
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'test-geo-gating.php') {
    $test = new TestGeoGating();
    exit($test->run() ? 0 : 1);
}
