<?php
if (!defined('ABSPATH')) exit;

/**
 * FOREX AFFILIATE SUITE PRO
 * Payments — Unified Admin Screen
 * Single source of truth in option: fasp_payments
 */

const FASP_PAY_OPT = 'fasp_payments';

function fasp_pay_get() {
    $opt = get_option(FASP_PAY_OPT, []);
    return is_array($opt) ? $opt : [];
}

function fasp_pay_update($data) {
    if (!is_array($data)) $data = [];
    // basic sanitize (keep keys stable)
    $clean = [];

    // PayPal
    $clean['paypal_enable']  = !empty($data['paypal_enable']) ? 1 : 0;
    $clean['paypal_email']   = isset($data['paypal_email']) ? sanitize_text_field($data['paypal_email']) : '';
    $clean['paypal_client']  = isset($data['paypal_client']) ? sanitize_text_field($data['paypal_client']) : '';
    $clean['paypal_secret']  = isset($data['paypal_secret']) ? sanitize_text_field($data['paypal_secret']) : '';

    // Stripe
    $clean['stripe_enable']  = !empty($data['stripe_enable']) ? 1 : 0;
    $clean['stripe_pk']      = isset($data['stripe_pk']) ? sanitize_text_field($data['stripe_pk']) : '';
    $clean['stripe_sk']      = isset($data['stripe_sk']) ? sanitize_text_field($data['stripe_sk']) : '';

    // Flutterwave
    $clean['fw_enable']      = !empty($data['fw_enable']) ? 1 : 0;
    $clean['fw_public']      = isset($data['fw_public']) ? sanitize_text_field($data['fw_public']) : '';
    $clean['fw_secret']      = isset($data['fw_secret']) ? sanitize_text_field($data['fw_secret']) : '';

    // Paystack
    $clean['ps_enable']      = !empty($data['ps_enable']) ? 1 : 0;
    $clean['ps_public']      = isset($data['ps_public']) ? sanitize_text_field($data['ps_public']) : '';
    $clean['ps_secret']      = isset($data['ps_secret']) ? sanitize_text_field($data['ps_secret']) : '';

    // M-Pesa
    $clean['mpesa_enable']   = !empty($data['mpesa_enable']) ? 1 : 0;
    $mode                     = isset($data['mpesa_mode']) ? sanitize_text_field($data['mpesa_mode']) : 'till';
    $clean['mpesa_mode']     = in_array($mode, ['till','paybill','both'], true) ? $mode : 'till';
    $clean['mpesa_till']     = isset($data['mpesa_till']) ? sanitize_text_field($data['mpesa_till']) : '';
    $clean['mpesa_paybill']  = isset($data['mpesa_paybill']) ? sanitize_text_field($data['mpesa_paybill']) : '';
    $clean['mpesa_account']  = isset($data['mpesa_account']) ? sanitize_text_field($data['mpesa_account']) : '';
    $clean['mpesa_ck']       = isset($data['mpesa_ck']) ? sanitize_text_field($data['mpesa_ck']) : '';
    $clean['mpesa_cs']       = isset($data['mpesa_cs']) ? sanitize_text_field($data['mpesa_cs']) : '';
    $clean['mpesa_passkey']  = isset($data['mpesa_passkey']) ? sanitize_text_field($data['mpesa_passkey']) : '';
    $clean['mpesa_initiator']= isset($data['mpesa_initiator']) ? sanitize_text_field($data['mpesa_initiator']) : '';
    $clean['mpesa_ipass']    = isset($data['mpesa_ipass']) ? sanitize_text_field($data['mpesa_ipass']) : '';
    // M-Pesa PEM certificate: use sanitize_textarea_field to preserve PEM format
    $mpesa_cert_raw          = isset($data['mpesa_cert']) ? sanitize_textarea_field($data['mpesa_cert']) : '';
    $clean['mpesa_cert']     = '';
    $clean['mpesa_cert_valid'] = 0;
    if (!empty($mpesa_cert_raw)) {
        // Validate PEM format: must have BEGIN and END markers
        if (strpos($mpesa_cert_raw, '-----BEGIN') !== false && strpos($mpesa_cert_raw, '-----END') !== false) {
            $clean['mpesa_cert'] = $mpesa_cert_raw;
            $clean['mpesa_cert_valid'] = 1;
        } else {
            // Invalid PEM format - store but mark as invalid for admin notice
            $clean['mpesa_cert'] = $mpesa_cert_raw;
            $clean['mpesa_cert_valid'] = 0;
            add_settings_error('fasp_payments', 'mpesa_cert_invalid', 
                'M-Pesa Certificate appears invalid. PEM certificates must contain -----BEGIN and -----END markers.', 
                'error');
        }
    }
    $env                      = isset($data['mpesa_env']) ? sanitize_text_field($data['mpesa_env']) : 'sandbox';
    $clean['mpesa_env']      = in_array($env, ['sandbox','live'], true) ? $env : 'sandbox';

    // Webhooks (fixed: single enable checkbox)
    $clean['wh_enable']      = !empty($data['wh_enable']) ? 1 : 0;
    $clean['wh_secret']      = isset($data['wh_secret']) ? sanitize_text_field($data['wh_secret']) : '';
    $clean['wh_url']         = isset($data['wh_url']) ? esc_url_raw($data['wh_url']) : '';

    // Crypto (USDT)
    $clean['cr_enable']      = !empty($data['cr_enable']) ? 1 : 0;
    $chain                    = isset($data['cr_chain']) ? sanitize_text_field($data['cr_chain']) : 'trc20';
    $clean['cr_chain']       = in_array($chain, ['trc20','erc20','bep20'], true) ? $chain : 'trc20';
    $clean['cr_trc20']       = isset($data['cr_trc20']) ? sanitize_text_field($data['cr_trc20']) : '';
    $clean['cr_erc20']       = isset($data['cr_erc20']) ? sanitize_text_field($data['cr_erc20']) : '';
    $clean['cr_bep20']       = isset($data['cr_bep20']) ? sanitize_text_field($data['cr_bep20']) : '';

    // Bank Transfer (add real inputs)
    $clean['bank_enable']    = !empty($data['bank_enable']) ? 1 : 0;
    $clean['bank_name']      = isset($data['bank_name']) ? sanitize_text_field($data['bank_name']) : '';
    $clean['bank_account']   = isset($data['bank_account']) ? sanitize_text_field($data['bank_account']) : '';
    $clean['bank_beneficiary']=isset($data['bank_beneficiary']) ? sanitize_text_field($data['bank_beneficiary']) : '';
    $clean['bank_iban']      = isset($data['bank_iban']) ? sanitize_text_field($data['bank_iban']) : '';
    $clean['bank_swift']     = isset($data['bank_swift']) ? sanitize_text_field($data['bank_swift']) : '';
    $clean['bank_instructions']= isset($data['bank_instructions']) ? wp_kses_post($data['bank_instructions']) : '';

    update_option(FASP_PAY_OPT, $clean);
    return $clean;
}

// admin page
function fasp_admin_payments_screen() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['fasp_payments_submit']) && check_admin_referer('fasp_payments_save','fasp_payments_nonce')) {
        $saved = fasp_pay_update( wp_unslash( $_POST['fasp'] ?? [] ) );
        echo '<div class="updated"><p>Payments settings saved.</p></div>';
        // Display any settings errors (e.g., invalid M-Pesa cert)
        settings_errors('fasp_payments');
    }
    $opt = fasp_pay_get();

    $mpesa_callback = add_query_arg('fasp_mpesa_callback', '1', home_url('/'));
    ?>
    <div class="wrap fasp-admin">
      <h1>Payments (Unified)</h1>

      <h2 class="nav-tab-wrapper">
        <a href="#tab-paypal"      class="nav-tab nav-tab-active">PayPal</a>
        <a href="#tab-stripe"      class="nav-tab">Stripe</a>
        <a href="#tab-flutterwave" class="nav-tab">Flutterwave</a>
        <a href="#tab-paystack"    class="nav-tab">Paystack</a>
        <a href="#tab-mpesa"       class="nav-tab">M-Pesa</a>
        <a href="#tab-crypto"      class="nav-tab">Crypto</a>
        <a href="#tab-webhooks"    class="nav-tab">Webhooks</a>
        <a href="#tab-bank"        class="nav-tab">Bank Transfer</a>
      </h2>

      <form method="post" action="">
        <?php wp_nonce_field('fasp_payments_save','fasp_payments_nonce'); ?>

        <!-- PayPal -->
        <div id="tab-paypal" class="fasp-tab active">
          <table class="form-table">
            <tr><th scope="row"><label><input type="checkbox" name="fasp[paypal_enable]" <?php checked(1, intval($opt['paypal_enable'] ?? 0)); ?>> Enable PayPal</label></th><td></td></tr>
            <tr><th scope="row"><label>PayPal Email</label></th><td><input type="text" class="regular-text" name="fasp[paypal_email]" value="<?php echo esc_attr($opt['paypal_email'] ?? ''); ?>"></td></tr>
            <tr><th scope="row"><label>Client ID</label></th><td><input type="text" class="regular-text" name="fasp[paypal_client]" value="<?php echo esc_attr($opt['paypal_client'] ?? ''); ?>"></td></tr>
            <tr><th scope="row"><label>Secret</label></th><td><input type="text" class="regular-text" name="fasp[paypal_secret]" value="<?php echo esc_attr($opt['paypal_secret'] ?? ''); ?>"></td></tr>
          </table>
        </div>

        <!-- Stripe -->
        <div id="tab-stripe" class="fasp-tab">
          <table class="form-table">
            <tr><th scope="row"><label><input type="checkbox" name="fasp[stripe_enable]" <?php checked(1, intval($opt['stripe_enable'] ?? 0)); ?>> Enable Stripe</label></th><td></td></tr>
            <tr><th scope="row"><label>Publishable Key</label></th><td><input type="text" class="regular-text" name="fasp[stripe_pk]" value="<?php echo esc_attr($opt['stripe_pk'] ?? ''); ?>"></td></tr>
            <tr><th scope="row"><label>Secret Key</label></th><td><input type="text" class="regular-text" name="fasp[stripe_sk]" value="<?php echo esc_attr($opt['stripe_sk'] ?? ''); ?>"></td></tr>
          </table>
        </div>

        <!-- Flutterwave -->
        <div id="tab-flutterwave" class="fasp-tab">
          <table class="form-table">
            <tr><th scope="row"><label><input type="checkbox" name="fasp[fw_enable]" <?php checked(1, intval($opt['fw_enable'] ?? 0)); ?>> Enable Flutterwave</label></th><td></td></tr>
            <tr><th scope="row"><label>Public Key</label></th><td><input type="text" class="regular-text" name="fasp[fw_public]" value="<?php echo esc_attr($opt['fw_public'] ?? ''); ?>"></td></tr>
            <tr><th scope="row"><label>Secret Key</label></th><td><input type="text" class="regular-text" name="fasp[fw_secret]" value="<?php echo esc_attr($opt['fw_secret'] ?? ''); ?>"></td></tr>
          </table>
        </div>

        <!-- Paystack -->
        <div id="tab-paystack" class="fasp-tab">
          <table class="form-table">
            <tr><th scope="row"><label><input type="checkbox" name="fasp[ps_enable]" <?php checked(1, intval($opt['ps_enable'] ?? 0)); ?>> Enable Paystack</label></th><td></td></tr>
            <tr><th scope="row"><label>Public Key</label></th><td><input type="text" class="regular-text" name="fasp[ps_public]" value="<?php echo esc_attr($opt['ps_public'] ?? ''); ?>"></td></tr>
            <tr><th scope="row"><label>Secret Key</label></th><td><input type="text" class="regular-text" name="fasp[ps_secret]" value="<?php echo esc_attr($opt['ps_secret'] ?? ''); ?>"></td></tr>
          </table>
        </div>

        <!-- M-Pesa -->
        <div id="tab-mpesa" class="fasp-tab">
          <table class="form-table">
            <tr><th scope="row"><label><input type="checkbox" name="fasp[mpesa_enable]" <?php checked(1, intval($opt['mpesa_enable'] ?? 0)); ?>> Enable M-Pesa</label></th><td></td></tr>
            <tr><th scope="row">Mode</th><td>
              <select name="fasp[mpesa_mode]">
                <option value="till"   <?php selected(($opt['mpesa_mode'] ?? 'till'), 'till'); ?>>Till</option>
                <option value="paybill"<?php selected(($opt['mpesa_mode'] ?? 'till'), 'paybill'); ?>>Paybill</option>
                <option value="both"   <?php selected(($opt['mpesa_mode'] ?? 'till'), 'both'); ?>>Both</option>
              </select>
            </td></tr>
            <tr><th scope="row">Till Number</th><td><input type="text" class="regular-text" name="fasp[mpesa_till]" value="<?php echo esc_attr($opt['mpesa_till'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Paybill Shortcode</th><td><input type="text" class="regular-text" name="fasp[mpesa_paybill]" value="<?php echo esc_attr($opt['mpesa_paybill'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Paybill Account Number</th><td><input type="text" class="regular-text" name="fasp[mpesa_account]" value="<?php echo esc_attr($opt['mpesa_account'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Consumer Key</th><td><input type="text" class="regular-text" name="fasp[mpesa_ck]" value="<?php echo esc_attr($opt['mpesa_ck'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Consumer Secret</th><td><input type="text" class="regular-text" name="fasp[mpesa_cs]" value="<?php echo esc_attr($opt['mpesa_cs'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Passkey</th><td><input type="text" class="regular-text" name="fasp[mpesa_passkey]" value="<?php echo esc_attr($opt['mpesa_passkey'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Initiator Username</th><td><input type="text" class="regular-text" name="fasp[mpesa_initiator]" value="<?php echo esc_attr($opt['mpesa_initiator'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Initiator Password</th><td><input type="text" class="regular-text" name="fasp[mpesa_ipass]" value="<?php echo esc_attr($opt['mpesa_ipass'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Safaricom Public Certificate (PEM)</th><td><textarea class="large-text code" rows="6" name="fasp[mpesa_cert]"><?php echo esc_textarea($opt['mpesa_cert'] ?? ''); ?></textarea></td></tr>
            <tr><th scope="row">Environment</th><td>
              <select name="fasp[mpesa_env]">
                <option value="sandbox" <?php selected(($opt['mpesa_env'] ?? 'sandbox'), 'sandbox'); ?>>Sandbox</option>
                <option value="live"    <?php selected(($opt['mpesa_env'] ?? 'sandbox'), 'live'); ?>>Live</option>
              </select>
            </td></tr>
            <tr><th scope="row">Callback URL</th><td><input type="text" class="regular-text" value="<?php echo esc_attr($mpesa_callback); ?>" readonly></td></tr>
          </table>
        </div>

        <!-- Crypto -->
        <div id="tab-crypto" class="fasp-tab">
          <table class="form-table">
            <tr><th scope="row"><label><input type="checkbox" name="fasp[cr_enable]" <?php checked(1, intval($opt['cr_enable'] ?? 0)); ?>> Enable Crypto</label></th><td></td></tr>
            <tr><th scope="row">Preferred Chain</th><td>
              <select name="fasp[cr_chain]">
                <option value="trc20" <?php selected(($opt['cr_chain'] ?? 'trc20'),'trc20'); ?>>USDT TRC20</option>
                <option value="erc20" <?php selected(($opt['cr_chain'] ?? 'trc20'),'erc20'); ?>>USDT ERC20</option>
                <option value="bep20" <?php selected(($opt['cr_chain'] ?? 'trc20'),'bep20'); ?>>USDT BEP20</option>
              </select>
            </td></tr>
            <tr><th scope="row">USDT (TRC20)</th><td><input type="text" class="regular-text" name="fasp[cr_trc20]" value="<?php echo esc_attr($opt['cr_trc20'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">USDT (ERC20)</th><td><input type="text" class="regular-text" name="fasp[cr_erc20]" value="<?php echo esc_attr($opt['cr_erc20'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">USDT (BEP20)</th><td><input type="text" class="regular-text" name="fasp[cr_bep20]" value="<?php echo esc_attr($opt['cr_bep20'] ?? ''); ?>"></td></tr>
          </table>
        </div>

        <!-- Webhooks (no duplicate checkbox) -->
        <div id="tab-webhooks" class="fasp-tab">
          <table class="form-table">
            <tr><th scope="row"><label><input type="checkbox" name="fasp[wh_enable]" <?php checked(1, intval($opt['wh_enable'] ?? 0)); ?>> Enable Webhooks</label></th><td></td></tr>
            <tr><th scope="row">Webhook Secret</th><td><input type="text" class="regular-text" name="fasp[wh_secret]" value="<?php echo esc_attr($opt['wh_secret'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Webhook Endpoint URL</th><td><input type="url" class="regular-text" name="fasp[wh_url]" value="<?php echo esc_attr($opt['wh_url'] ?? ''); ?>"></td></tr>
          </table>
        </div>

        <!-- Bank Transfer -->
        <div id="tab-bank" class="fasp-tab">
          <table class="form-table">
            <tr><th scope="row"><label><input type="checkbox" name="fasp[bank_enable]" <?php checked(1, intval($opt['bank_enable'] ?? 0)); ?>> Enable Bank Transfer</label></th><td></td></tr>
            <tr><th scope="row">Bank Name</th><td><input type="text" class="regular-text" name="fasp[bank_name]" value="<?php echo esc_attr($opt['bank_name'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Beneficiary / Account Name</th><td><input type="text" class="regular-text" name="fasp[bank_beneficiary]" value="<?php echo esc_attr($opt['bank_beneficiary'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Account Number</th><td><input type="text" class="regular-text" name="fasp[bank_account]" value="<?php echo esc_attr($opt['bank_account'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">IBAN</th><td><input type="text" class="regular-text" name="fasp[bank_iban]" value="<?php echo esc_attr($opt['bank_iban'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">SWIFT/BIC</th><td><input type="text" class="regular-text" name="fasp[bank_swift]" value="<?php echo esc_attr($opt['bank_swift'] ?? ''); ?>"></td></tr>
            <tr><th scope="row">Instructions</th><td><textarea class="large-text code" rows="5" name="fasp[bank_instructions]"><?php echo esc_textarea($opt['bank_instructions'] ?? ''); ?></textarea></td></tr>
          </table>
        </div>

        <p><button type="submit" class="button button-primary" name="fasp_payments_submit" value="1">Save Settings</button></p>
      </form>
    </div>

    <style>
      .fasp-tab{display:none;margin-top:15px}
      .fasp-tab.active{display:block}
      .nav-tab-wrapper .nav-tab{cursor:pointer}
    </style>
    <script>
      (function(){
        const tabs = document.querySelectorAll('.nav-tab-wrapper .nav-tab');
        const panes = document.querySelectorAll('.fasp-tab');
        function activate(id){
          tabs.forEach(t=>t.classList.remove('nav-tab-active'));
          panes.forEach(p=>p.classList.remove('active'));
          document.querySelector('.nav-tab[href="'+id+'"]').classList.add('nav-tab-active');
          document.querySelector(id).classList.add('active');
          history.replaceState(null,'',id);
        }
        tabs.forEach(t=>t.addEventListener('click',function(e){e.preventDefault();activate(this.getAttribute('href'));}));
        if(location.hash && document.querySelector(location.hash)){ activate(location.hash); }
      })();
    </script>
    <?php
}

/**
 * Admin menu — keep ONLY the unified page under “Forex Affiliate”
 * Slug: fasp_payments
 */
add_action('admin_menu', function(){
    // Parent slug must match your main hub; adjust if different:
    $parent = 'forex_affiliate_root';
    // If your parent is registered under a different slug, set it here.
    if (!menu_page_url($parent, false)) {
        // Fallback: try the first-level FASP root if known
        $parent = 'forex_affiliate';
    }

    add_submenu_page(
        $parent,
        'Payments & Gateways',
        'Payments & Gateways',
        'manage_options',
        'fasp_payments',
        'fasp_admin_payments_screen',
        10
    );
}, 40);


// === FASP Bridge: keep legacy & unified payment settings in sync ===
if (!function_exists('fasp_bridge_sync_payments')) {
    function fasp_bridge_sync_payments($option, $old_value, $value) {
        // Normalize arrays
        $new = is_array($value) ? $value : array();
        // Legacy keys we mirror from unified
        $legacy = array();

        // PayPal
        if (isset($new['paypal_email'])) {
            $legacy['paypal_email'] = $new['paypal_email'];
        }
        if (isset($new['paypal_client_id'])) {
            $legacy['paypal_client_id'] = $new['paypal_client_id'];
        }
        if (isset($new['paypal_secret'])) {
            $legacy['paypal_secret'] = $new['paypal_secret'];
        }

        // Stripe
        if (isset($new['stripe_publishable_key'])) {
            $legacy['stripe_publishable_key'] = $new['stripe_publishable_key'];
        }
        if (isset($new['stripe_secret_key'])) {
            $legacy['stripe_secret_key'] = $new['stripe_secret_key'];
        }

        // M-Pesa
        foreach (array('mpesa_mode','mpesa_till','mpesa_paybill','mpesa_account','mpesa_consumer_key','mpesa_consumer_secret','mpesa_passkey','mpesa_initiator_username','mpesa_initiator_password','mpesa_cert_pem','mpesa_security_credential','mpesa_env','mpesa_callback') as $k) {
            if (isset($new[$k])) $legacy[$k] = $new[$k];
        }

        // Webhooks
        foreach (array('webhooks_enabled','webhook_secret','webhook_endpoint') as $k) {
            if (isset($new[$k])) $legacy[$k] = $new[$k];
        }

        // Crypto
        foreach (array('crypto_enabled','crypto_pref','usdt_trc20','usdt_erc20','usdt_bep20') as $k) {
            if (isset($new[$k])) $legacy[$k] = $new[$k];
        }

        // Bank transfer
        foreach (array('bank_enabled','bank_instructions') as $k) {
            if (isset($new[$k])) $legacy[$k] = $new[$k];
        }

        // Write legacy mirrors (single options commonly used by older code)
        if (!empty($legacy)) {
            foreach ($legacy as $lk => $lv) {
                update_option('fasp_' . $lk, $lv);
            }
        }
    }
    // Hook when the unified option is updated
    add_action('updated_option', function($option, $old_value, $value){
        if ($option === 'fasp_payments') {
            fasp_bridge_sync_payments($option, $old_value, $value);
        }
    }, 10, 3);
    
    
    
}
// === End Bridge ===
