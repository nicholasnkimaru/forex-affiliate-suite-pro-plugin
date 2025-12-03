<?php
/**
 * Unit tests for fasp_get_payments() normalization function.
 *
 * Run with: php tests/test-fasp-get-payments.php
 */

// Mock WordPress functions for standalone testing
if (!function_exists('get_option')) {
    $GLOBALS['test_options'] = array();
    function get_option($key, $default = false) {
        return isset($GLOBALS['test_options'][$key]) ? $GLOBALS['test_options'][$key] : $default;
    }
}

// Define ABSPATH to satisfy include guard
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

// Include the helpers-core file
require_once dirname(__DIR__) . '/includes/helpers-core.php';

/**
 * Simple test framework.
 */
class FaspPaymentsTest {
    private $tests_run = 0;
    private $tests_passed = 0;
    private $tests_failed = 0;

    public function run() {
        echo "Running fasp_get_payments() normalization tests...\n\n";

        $this->test_empty_options();
        $this->test_legacy_flat_structure();
        $this->test_nested_structure();
        $this->test_mixed_structure();
        $this->test_stripe_keys_normalization();
        $this->test_mpesa_shortcode_fallback();

        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Results: {$this->tests_passed} passed, {$this->tests_failed} failed out of {$this->tests_run} tests.\n";

        return $this->tests_failed === 0 ? 0 : 1;
    }

    private function assert($condition, $message) {
        $this->tests_run++;
        if ($condition) {
            $this->tests_passed++;
            echo "✓ PASS: {$message}\n";
        } else {
            $this->tests_failed++;
            echo "✗ FAIL: {$message}\n";
        }
    }

    private function test_empty_options() {
        echo "Test: Empty options returns canonical structure\n";
        $GLOBALS['test_options'] = array();
        
        $result = fasp_get_payments();
        
        $this->assert(is_array($result), 'Result is an array');
        $this->assert(isset($result['stripe']), 'Has stripe key');
        $this->assert(isset($result['flutterwave']), 'Has flutterwave key');
        $this->assert(isset($result['paystack']), 'Has paystack key');
        $this->assert(isset($result['mpesa']), 'Has mpesa key');
        $this->assert(isset($result['paypal']), 'Has paypal key');
        $this->assert(isset($result['webhooks']), 'Has webhooks key');
        $this->assert(isset($result['crypto']), 'Has crypto key');
        $this->assert(isset($result['bank']), 'Has bank key');
        echo "\n";
    }

    private function test_legacy_flat_structure() {
        echo "Test: Legacy flat structure normalization\n";
        $GLOBALS['test_options'] = array(
            'fasp_payments' => array(
                'stripe_enable' => 1,
                'stripe_pk' => 'pk_test_123',
                'stripe_sk' => 'sk_test_456',
                'stripe_whsec' => 'whsec_789',
                'fw_enable' => 1,
                'fw_public' => 'FLWPUBK_TEST',
                'fw_secret' => 'FLWSECK_TEST',
                'ps_enable' => 1,
                'ps_public' => 'pk_test_ps',
                'ps_secret' => 'sk_test_ps',
                'mpesa_enable' => 1,
                'mpesa_mode' => 'till',
                'mpesa_till' => '123456',
                'mpesa_ck' => 'consumer_key',
                'mpesa_cs' => 'consumer_secret',
                'paypal_enable' => 1,
                'paypal_email' => 'test@example.com',
                'wh_enable' => 1,
                'wh_secret' => 'webhook_secret',
                'cr_enable' => 1,
                'cr_chain' => 'trc20',
                'cr_trc20' => 'Taddr123',
                'bank_enable' => 1,
                'bank_name' => 'Test Bank',
            ),
        );
        
        $result = fasp_get_payments();
        
        $this->assert($result['stripe']['enabled'] === true, 'Stripe enabled is boolean true');
        $this->assert($result['stripe']['pk'] === 'pk_test_123', 'Stripe pk normalized');
        $this->assert($result['stripe']['sk'] === 'sk_test_456', 'Stripe sk normalized');
        $this->assert($result['stripe']['webhook_secret'] === 'whsec_789', 'Stripe webhook_secret normalized');
        $this->assert($result['flutterwave']['enabled'] === true, 'Flutterwave enabled');
        $this->assert($result['flutterwave']['pk'] === 'FLWPUBK_TEST', 'Flutterwave pk normalized');
        $this->assert($result['paystack']['enabled'] === true, 'Paystack enabled');
        $this->assert($result['mpesa']['enabled'] === true, 'M-Pesa enabled');
        $this->assert($result['mpesa']['mode'] === 'till', 'M-Pesa mode normalized');
        $this->assert($result['mpesa']['consumer_key'] === 'consumer_key', 'M-Pesa consumer_key normalized');
        $this->assert($result['paypal']['enabled'] === true, 'PayPal enabled');
        $this->assert($result['paypal']['email'] === 'test@example.com', 'PayPal email normalized');
        $this->assert($result['webhooks']['enabled'] === true, 'Webhooks enabled');
        $this->assert($result['crypto']['enabled'] === true, 'Crypto enabled');
        $this->assert($result['crypto']['chain'] === 'trc20', 'Crypto chain normalized');
        $this->assert($result['bank']['enabled'] === true, 'Bank enabled');
        echo "\n";
    }

    private function test_nested_structure() {
        echo "Test: Already nested structure passes through\n";
        $GLOBALS['test_options'] = array(
            'fasp_payments' => array(
                'stripe' => array(
                    'enabled' => true,
                    'pk' => 'pk_live_abc',
                    'sk' => 'sk_live_xyz',
                    'webhook_secret' => 'whsec_live',
                ),
                'flutterwave' => array(
                    'enabled' => false,
                    'pk' => '',
                    'sk' => '',
                ),
            ),
        );
        
        $result = fasp_get_payments();
        
        $this->assert($result['stripe']['enabled'] === true, 'Nested stripe enabled preserved');
        $this->assert($result['stripe']['pk'] === 'pk_live_abc', 'Nested stripe pk preserved');
        $this->assert($result['stripe']['webhook_secret'] === 'whsec_live', 'Nested webhook_secret preserved');
        $this->assert($result['flutterwave']['enabled'] === false, 'Nested flutterwave disabled preserved');
        echo "\n";
    }

    private function test_mixed_structure() {
        echo "Test: Mixed structure handling\n";
        $GLOBALS['test_options'] = array(
            'fasp_payments' => array(
                'stripe' => array(
                    'enabled' => true,
                    'pk' => 'pk_mixed',
                ),
                // Legacy keys alongside nested
                'fw_enable' => 1,
            ),
        );
        
        $result = fasp_get_payments();
        
        // When stripe key exists and is array, treat as nested
        $this->assert(isset($result['stripe']), 'Stripe key exists in mixed');
        $this->assert($result['stripe']['pk'] === 'pk_mixed', 'Mixed stripe pk preserved');
        echo "\n";
    }

    private function test_stripe_keys_normalization() {
        echo "Test: Stripe keys accessed correctly by fasp_stripe_keys()\n";
        $GLOBALS['test_options'] = array(
            'fasp_payments' => array(
                'stripe_enable' => 1,
                'stripe_pk' => 'pk_stripe_test',
                'stripe_sk' => 'sk_stripe_test',
            ),
        );
        
        $result = fasp_get_payments();
        
        $this->assert($result['stripe']['pk'] === 'pk_stripe_test', 'Stripe pk accessible');
        $this->assert($result['stripe']['sk'] === 'sk_stripe_test', 'Stripe sk accessible');
        echo "\n";
    }

    private function test_mpesa_shortcode_fallback() {
        echo "Test: M-Pesa shortcode fallback from till\n";
        $GLOBALS['test_options'] = array(
            'fasp_payments' => array(
                'mpesa_mode' => 'till',
                'mpesa_till' => '654321',
            ),
        );
        
        $result = fasp_get_payments();
        
        $this->assert($result['mpesa']['shortcode'] === '654321', 'Shortcode falls back to till');
        echo "\n";
    }
}

// Run tests
$test = new FaspPaymentsTest();
exit($test->run());
