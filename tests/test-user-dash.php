<?php
/**
 * Unit tests for user-dash.php functions
 *
 * @package ForexAffiliateSuitePro
 */

// Mock WordPress functions for unit testing outside of WordPress
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs((int) $maybeint);
    }
}

if (!function_exists('get_userdata')) {
    function get_userdata($user_id) {
        return (object) array(
            'ID' => $user_id,
            'user_login' => 'testuser',
            'display_name' => 'Test User'
        );
    }
}

if (!function_exists('user_can')) {
    function user_can($user_id, $capability) {
        return $capability === 'manage_options';
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key, $single = false) {
        $meta = array(
            '_fasp_verified_deriv' => '1',
            '_fasp_downloaded' => '1',
            '_fasp_booked' => '0',
            '_fasp_deposit' => '0',
            '_fasp_trade' => '0'
        );
        return isset($meta[$key]) ? $meta[$key] : '';
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return $capability === 'manage_options';
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
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

if (!function_exists('get_posts')) {
    function get_posts($args) {
        return array();
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'http://example.com' . $path;
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($key, $value = null, $url = null) {
        if (is_array($key)) {
            $url = $value;
            $args = $key;
        } else {
            $args = array($key => $value);
        }
        
        if ($url === null) {
            $url = 'http://example.com/';
        }
        
        $query_string = http_build_query($args);
        $separator = (strpos($url, '?') !== false) ? '&' : '?';
        
        return $url . $separator . $query_string;
    }
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

// Include the file under test
require_once dirname(__DIR__) . '/includes/user-dash.php';

/**
 * Test class for user dashboard functions
 */
class TestUserDash {
    
    /**
     * Test fasp_get_user_dashboard_data returns expected structure
     */
    public function test_fasp_get_user_dashboard_data_returns_array() {
        $data = fasp_get_user_dashboard_data(1);
        
        assert(is_array($data), 'fasp_get_user_dashboard_data should return an array');
        echo "✓ fasp_get_user_dashboard_data returns array\n";
        
        // Check required keys
        $required_keys = array(
            'user_id',
            'user',
            'is_logged_in',
            'is_admin',
            'platforms',
            'verifications',
            'progress',
            'progress_count',
            'total_steps',
            'utm_params',
            'coaches',
            'resources',
            'is_preview_mode'
        );
        
        foreach ($required_keys as $key) {
            assert(array_key_exists($key, $data), "fasp_get_user_dashboard_data should contain key: $key");
        }
        echo "✓ fasp_get_user_dashboard_data contains all required keys\n";
        
        return true;
    }
    
    /**
     * Test fasp_get_user_dashboard_data with user ID
     */
    public function test_fasp_get_user_dashboard_data_with_user_id() {
        $data = fasp_get_user_dashboard_data(123);
        
        assert($data['user_id'] === 123, 'user_id should be 123');
        echo "✓ fasp_get_user_dashboard_data correctly sets user_id\n";
        
        return true;
    }
    
    /**
     * Test fasp_get_progress_count returns correct count
     */
    public function test_fasp_get_progress_count() {
        $count = fasp_get_progress_count(1);
        
        assert(is_int($count), 'fasp_get_progress_count should return an integer');
        assert($count >= 0 && $count <= 5, 'Progress count should be between 0 and 5');
        echo "✓ fasp_get_progress_count returns valid count: $count\n";
        
        return true;
    }
    
    /**
     * Test fasp_is_preview_user_mode
     */
    public function test_fasp_is_preview_user_mode() {
        $result = fasp_is_preview_user_mode();
        
        assert(is_bool($result), 'fasp_is_preview_user_mode should return a boolean');
        echo "✓ fasp_is_preview_user_mode returns boolean\n";
        
        return true;
    }
    
    /**
     * Test fasp_render_deriv_connect_button returns string
     */
    public function test_fasp_render_deriv_connect_button() {
        $html = fasp_render_deriv_connect_button(1);
        
        assert(is_string($html), 'fasp_render_deriv_connect_button should return a string');
        echo "✓ fasp_render_deriv_connect_button returns string\n";
        
        return true;
    }
    
    /**
     * Run all tests
     */
    public function run() {
        echo "\n=== Running User Dashboard Tests ===\n\n";
        
        $tests = array(
            'test_fasp_get_user_dashboard_data_returns_array',
            'test_fasp_get_user_dashboard_data_with_user_id',
            'test_fasp_get_progress_count',
            'test_fasp_is_preview_user_mode',
            'test_fasp_render_deriv_connect_button'
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
            }
        }
        
        echo "\n=== Results ===\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        
        return $failed === 0;
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'test-user-dash.php') {
    $test = new TestUserDash();
    exit($test->run() ? 0 : 1);
}
