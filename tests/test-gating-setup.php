<?php
/**
 * Unit tests for gating-setup.php functions
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

if (!function_exists('get_the_ID')) {
    function get_the_ID() {
        return 1;
    }
}

// Mock option storage
$_mock_options = array(
    'fasp_gating_require_login' => true,
    'fasp_gating_roles' => array('administrator', 'subscriber'),
    'fasp_gating_blocked_message' => 'Access denied',
    'fasp_gating_blocked_redirect' => 'http://example.com/login',
);

$_mock_post_meta = array(
    1 => array('_fasp_gating_override' => ''),
    2 => array('_fasp_gating_override' => 'allow_all'),
    3 => array('_fasp_gating_override' => 'block_all'),
    4 => array('_fasp_gating_override' => 'require_login'),
);

$_mock_users = array(
    1 => array('roles' => array('administrator')),
    2 => array('roles' => array('subscriber')),
    3 => array('roles' => array('customer')),
);

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $_mock_options;
        return isset($_mock_options[$option]) ? $_mock_options[$option] : $default;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key, $single = false) {
        global $_mock_post_meta;
        if (isset($_mock_post_meta[$post_id][$key])) {
            return $_mock_post_meta[$post_id][$key];
        }
        return '';
    }
}

if (!function_exists('get_userdata')) {
    function get_userdata($user_id) {
        global $_mock_users;
        if (!isset($_mock_users[$user_id])) {
            return false;
        }
        return (object) array(
            'ID' => $user_id,
            'roles' => $_mock_users[$user_id]['roles']
        );
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($content) {
        return strip_tags($content, '<p><a><br><strong><em>');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

// Mock WordPress hook functions
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        // No-op for tests
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $args = 1) {
        // No-op for tests
        return true;
    }
}

if (!function_exists('register_setting')) {
    function register_setting($group, $name, $args = array()) {
        return true;
    }
}

if (!function_exists('add_meta_box')) {
    function add_meta_box($id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null) {
        return true;
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null) {
        return true;
    }
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

// Include the file under test
require_once dirname(__DIR__) . '/includes/gating-setup.php';

/**
 * Test class for gating functions
 */
class TestGatingSetup {
    
    /**
     * Test fasp_is_user_allowed_by_gating with per-page allow_all override
     */
    public function test_allow_all_override() {
        // Post 2 has allow_all override
        $result = fasp_is_user_allowed_by_gating(0, 2);
        assert($result === true, 'allow_all override should allow access even for guest users');
        echo "✓ allow_all override allows all users\n";
        return true;
    }
    
    /**
     * Test fasp_is_user_allowed_by_gating with per-page block_all override
     */
    public function test_block_all_override() {
        // Post 3 has block_all override
        $result = fasp_is_user_allowed_by_gating(1, 3);
        assert($result === false, 'block_all override should block access');
        echo "✓ block_all override blocks all users\n";
        return true;
    }
    
    /**
     * Test fasp_is_user_allowed_by_gating with require_login override
     */
    public function test_require_login_override() {
        // Post 4 has require_login override
        $result_guest = fasp_is_user_allowed_by_gating(0, 4);
        $result_user = fasp_is_user_allowed_by_gating(1, 4);
        
        assert($result_guest === false, 'require_login override should block guest');
        assert($result_user === true, 'require_login override should allow logged-in user');
        echo "✓ require_login override works correctly\n";
        return true;
    }
    
    /**
     * Test fasp_is_user_allowed_by_gating with allowed role
     */
    public function test_allowed_role() {
        // User 1 is administrator (allowed), Post 1 has no override
        $result = fasp_is_user_allowed_by_gating(1, 1);
        assert($result === true, 'User with allowed role should have access');
        echo "✓ User with allowed role has access\n";
        return true;
    }
    
    /**
     * Test fasp_is_user_allowed_by_gating with non-allowed role
     */
    public function test_non_allowed_role() {
        // User 3 is customer (not in allowed roles), Post 1 has no override
        $result = fasp_is_user_allowed_by_gating(3, 1);
        assert($result === false, 'User with non-allowed role should be blocked');
        echo "✓ User with non-allowed role is blocked\n";
        return true;
    }
    
    /**
     * Test fasp_get_gating_blocked_message
     */
    public function test_get_blocked_message() {
        $message = fasp_get_gating_blocked_message();
        assert(is_string($message), 'fasp_get_gating_blocked_message should return a string');
        assert(strlen($message) > 0, 'Blocked message should not be empty');
        echo "✓ fasp_get_gating_blocked_message returns message: '$message'\n";
        return true;
    }
    
    /**
     * Test fasp_get_gating_redirect_url
     */
    public function test_get_redirect_url() {
        $url = fasp_get_gating_redirect_url();
        assert(is_string($url), 'fasp_get_gating_redirect_url should return a string');
        echo "✓ fasp_get_gating_redirect_url returns URL: '$url'\n";
        return true;
    }
    
    /**
     * Run all tests
     */
    public function run() {
        echo "\n=== Running Gating Setup Tests ===\n\n";
        
        $tests = array(
            'test_allow_all_override',
            'test_block_all_override',
            'test_require_login_override',
            'test_allowed_role',
            'test_non_allowed_role',
            'test_get_blocked_message',
            'test_get_redirect_url'
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
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'test-gating-setup.php') {
    $test = new TestGatingSetup();
    exit($test->run() ? 0 : 1);
}
