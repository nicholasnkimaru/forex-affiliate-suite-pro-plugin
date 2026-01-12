<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcodes — r14.8 clean
 * - [fasp_checkout], [fasp_stk_status], [fasp_receipt], [fasp_usdt_wallet]
 */

function fasp_payopt(){
    // Use canonical accessor if available for runtime normalization
    if (function_exists('fasp_get_payments')) {
        return fasp_get_payments();
    }
    return get_option('fasp_payments', []);
}

add_shortcode('fasp_checkout', function($atts){
    $a = shortcode_atts([
        'amount' => isset($_GET['amount']) ? sanitize_text_field($_GET['amount']) : '',
        'currency' => isset($_GET['currency']) ? sanitize_text_field($_GET['currency']) : '',
    ], $atts);
    $opt = fasp_payopt();
    $visible = isset($opt['visible']) ? (array)$opt['visible'] : [];

    ob_start();
    ?>
    <div class="fasp-checkout" data-amount="<?php echo esc_attr($a['amount']); ?>" data-currency="<?php echo esc_attr($a['currency']); ?>">
        <?php if (isset($visible['paypal'])): ?><p><a class="button" href="#" onclick="return faspCheckout('paypal')">Pay with PayPal</a></p><?php endif; ?>
        <?php if (isset($visible['stripe'])): ?><p><a class="button" href="#" onclick="return faspCheckout('stripe')">Pay with Card (Stripe)</a></p><?php endif; ?>
        <?php if (isset($visible['flutterwave'])): ?><p><a class="button" href="#" onclick="return faspCheckout('flutterwave')">Pay with Flutterwave</a></p><?php endif; ?>
        <?php if (isset($visible['paystack'])): ?><p><a class="button" href="#" onclick="return faspCheckout('paystack')">Pay with Paystack</a></p><?php endif; ?>
        <?php $country = apply_filters('fasp_geo_country', 'KE'); if (isset($visible['mpesa']) && $country === 'KE'): ?>
        <div class="fasp-mpesa">
            <h4>M-Pesa</h4>
            <label>Phone (07XXXXXXXX or 2547XXXXXXXX)</label><br/>
            <input type="text" id="fasp_msisdn" placeholder="07XXXXXXXX" />
            <p style="margin-top:8px">
                <a class="button button-primary" href="#" onclick="return faspMpesaAjax('till');">Pay via Till</a>
                <a class="button" href="#" onclick="return faspMpesaAjax('paybill');">Pay via Paybill</a>
            </p>
        </div>
        <?php endif; ?>
        <div id="fasp-stk-status" style="display:none;margin-top:10px;padding:12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff">
            <strong>Payment Status:</strong> <span id="fasp-stk-text">Waiting to start...</span>
            <div id="fasp-stk-receipt" style="margin-top:8px;display:none"></div>
        </div>
    </div>
    <script>
    (function(){
        function post(url, data){
            return fetch(url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams(data)}).then(r=>r.json());
        }
        function act(method, amount, currency){
            const url = '/wp-admin/admin-ajax.php';
            const base = window.location.href.split('#')[0];
            const sep = base.indexOf('?')>-1?'&':'?';
            const success = base + sep + 'fasp=success';
            const cancel = base + sep + 'fasp=cancel';
            return post(url, { action:'fasp_create_checkout', method, amount, currency, success_url: success, cancel_url: cancel });
        }
        window.faspCheckout = function(method){
            var root = document.querySelector('.fasp-checkout');
            var amount = root && root.dataset.amount ? root.dataset.amount : (new URLSearchParams(location.search).get('amount') || '10');
            var currency = root && root.dataset.currency ? root.dataset.currency : (new URLSearchParams(location.search).get('currency') || 'USD');
            act(method, amount, currency).then(function(res){
                if(res && res.ok && res.redirect){ window.location = res.redirect; }
                else{ alert('Payment error: ' + (res && (res.error || JSON.stringify(res)))); }
            }).catch(function(e){ alert('Network error: '+ e); });
            return false;
        };

        var FASP_POLL = null;
        function pollQuery(checkout){
            var url = '/wp-admin/admin-ajax.php';
            var txt = document.getElementById('fasp-stk-text');
            var rcpt = document.getElementById('fasp-stk-receipt');
            var box = document.getElementById('fasp-stk-status');
            if (!box) return;
            box.style.display = 'block';
            txt.textContent = 'Awaiting authorization on your phone...';
            var start = Date.now();
            if (FASP_POLL) clearInterval(FASP_POLL);
            FASP_POLL = setInterval(function(){
                var elapsed = (Date.now()-start)/1000;
                if (elapsed > 120){ clearInterval(FASP_POLL); txt.textContent = 'Timed out — please try again.'; return; }
                post(url, {action:'fasp_mpesa_query', checkout_id: checkout}).then(function(res){
                    if (!res || !res.ok) return;
                    var data = res.data || {};
                    var rc = ''+(data.ResultCode!==undefined ? data.ResultCode : '');
                    if (rc === '0'){ clearInterval(FASP_POLL); txt.textContent = 'Payment successful.'; rcpt.style.display='block'; rcpt.textContent = 'Receipt: ' + (data.MpesaReceiptNumber || 'Available in callback.'); }
                    else if (rc && rc !== '0' && rc !== 'null'){ clearInterval(FASP_POLL); txt.textContent = (data.ResultDesc || 'Failed'); }
                }).catch(function(){});
            }, 4000);
        }
        window.faspMpesaAjax = function(mode){
            var root = document.querySelector('.fasp-checkout');
            var amount = root && root.dataset.amount ? root.dataset.amount : (new URLSearchParams(location.search).get('amount') || '10');
            var currency = 'KES';
            var phone = document.getElementById('fasp_msisdn').value.trim();
            var url = '/wp-admin/admin-ajax.php';
            var re = /^(?:254|\+254|0)?7\\d{8}$/;
            if(!re.test(phone)){ alert('Enter a valid Kenyan phone number.'); return false; }
            post(url, { action:'fasp_mpesa_push', amount: amount, currency: currency, phone: phone, mode: mode }).then(function(res){
                if(res && res.ok && res.checkout_id){ pollQuery(res.checkout_id); }
                else{ alert('M-Pesa error: ' + (res && (res.error || JSON.stringify(res)))); }
            }).catch(function(e){ alert('Network error: '+ e); });
            return false;
        };
    })();
    </script>
    <?php
    return ob_get_clean();
});

add_shortcode('fasp_stk_status', function($atts){
    $a = shortcode_atts(['checkout_id' => ''], $atts);
    if (empty($a['checkout_id']) && isset($_GET['checkout_id'])) $a['checkout_id'] = sanitize_text_field($_GET['checkout_id']);
    ob_start(); ?>
    <div id="fasp-stk-status" style="margin-top:10px;padding:12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff">
        <strong>Payment Status:</strong> <span id="fasp-stk-text">Waiting...</span>
        <div id="fasp-stk-receipt" style="margin-top:8px;display:none"></div>
    </div>
    <script>
    (function(){
        var checkout = <?php echo json_encode($a['checkout_id']); ?>;
        function post(url, data){
            return fetch(url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams(data)}).then(r=>r.json());
        }
        function poll(){
            if(!checkout){ document.getElementById('fasp-stk-text').textContent = 'Missing checkout_id.'; return; }
            post('/wp-admin/admin-ajax.php', {action:'fasp_mpesa_query', checkout_id: checkout}).then(function(res){
                var txt = document.getElementById('fasp-stk-text');
                var rcpt = document.getElementById('fasp-stk-receipt');
                if (res && res.ok){
                    var data = res.data || {};
                    var rc = ''+(data.ResultCode!==undefined ? data.ResultCode : '');
                    if (rc === '0'){ txt.textContent = 'Payment successful.'; rcpt.style.display='block'; rcpt.textContent = 'Receipt: ' + (data.MpesaReceiptNumber || 'Will arrive via callback'); }
                    else if (rc && rc !== '0' && rc !== 'null'){ txt.textContent = (data.ResultDesc || 'Failed'); }
                    else { setTimeout(poll, 4000); txt.textContent = 'Awaiting authorization...'; }
                } else { setTimeout(poll, 6000); }
            }).catch(function(){ setTimeout(poll, 6000); });
        }
        poll();
    })();
    </script>
    <?php return ob_get_clean();
});

add_shortcode('fasp_receipt', function($atts){
    global $wpdb;
    $a = shortcode_atts(['checkout_id'=>'', 'receipt'=>'' ], $atts);
    $table = defined('FASP_TXN_TABLE') ? FASP_TXN_TABLE : $wpdb->prefix.'fasp_transactions';
    $where = ''; $args = [];
    if (!empty($a['receipt'])){ $where = "WHERE mpesa_receipt = %s"; $args[] = $a['receipt']; }
    elseif (!empty($a['checkout_id'])){ $where = "WHERE checkout_id = %s"; $args[] = $a['checkout_id']; }
    elseif (isset($_GET['receipt'])){ $where = "WHERE mpesa_receipt = %s"; $args[] = sanitize_text_field($_GET['receipt']); }
    elseif (isset($_GET['checkout_id'])){ $where = "WHERE checkout_id = %s"; $args[] = sanitize_text_field($_GET['checkout_id']); }
    else { return '<div class="fasp-receipt">No receipt specified.</div>'; }

    $sql = "SELECT * FROM $table $where ORDER BY id DESC LIMIT 1";
    $row = !empty($args) ? $wpdb->get_row($wpdb->prepare($sql, $args), ARRAY_A) : $wpdb->get_row($sql, ARRAY_A);
    if (!$row) return '<div class="fasp-receipt">Receipt not found.</div>';

    ob_start(); ?>
    <div class="fasp-receipt" style="max-width:560px;border:1px solid #e2e8f0;border-radius:10px;padding:16px;background:#fff">
        <h3 style="margin-top:0">Payment Receipt</h3>
        <?php if (!empty($row['result_desc'])): ?>
            <p style="margin-top:10px"><em><?php echo esc_html($row['result_desc']); ?></em></p>
        <?php endif; ?>

        <?php 
        // Crypto section (preferred wallet)
        $opt = get_option('fasp_payments', []); 
        $c = isset($opt['crypto']) ? $opt['crypto'] : []; 
        $preferred = isset($c['preferred']) ? $c['preferred'] : 'trc20';
        $wallet = fasp_get_usdt_wallet($preferred);
        if (!empty($wallet)) : ?>
        <div class="fasp-receipt-crypto" style="margin-top:12px;padding-top:10px;border-top:1px dashed #e2e8f0">
            <strong>Payment Method:</strong> Crypto (USDT – <?php echo $preferred==='trc20'?'TRC20':($preferred==='erc20'?'ERC20':'BEP20'); ?>)<br/>
            <strong>Wallet Address:</strong> <span id="fasp_wallet_text"><?php echo esc_html($wallet); ?></span>
            <button type="button" onclick="(function(){try{const t=document.getElementById('fasp_wallet_text').textContent.trim(); navigator.clipboard.writeText(t).then(()=>alert('Copied'));}catch(e){alert('Copy failed');}})();" style="margin-left:8px" class="button">Copy</button>
        </div>
        <?php endif; ?>
    </div>
    <?php return ob_get_clean();
});

// Helper: get preferred/specific USDT wallet + shortcode
if (!function_exists('fasp_get_usdt_wallet')){
function fasp_get_usdt_wallet($type=''){
    // Use canonical accessor if available
    $payments = function_exists('fasp_get_payments') ? fasp_get_payments() : get_option('fasp_payments', []);
    $c = isset($payments['crypto']) && is_array($payments['crypto']) ? $payments['crypto'] : [];
    $pref = isset($c['chain']) ? $c['chain'] : (isset($c['preferred']) ? $c['preferred'] : 'trc20');
    $type = $type ? strtolower($type) : $pref;
    if ($type==='trc20') return isset($c['trc20']) ? $c['trc20'] : (isset($c['trc20_wallet']) ? $c['trc20_wallet'] : '');
    if ($type==='erc20') return isset($c['erc20']) ? $c['erc20'] : (isset($c['erc20_wallet']) ? $c['erc20_wallet'] : '');
    if ($type==='bep20') return isset($c['bep20']) ? $c['bep20'] : (isset($c['bep20_wallet']) ? $c['bep20_wallet'] : '');
    return '';
}}
add_shortcode('fasp_usdt_wallet', function($atts){
    $a = shortcode_atts(['type'=>''], $atts);
    return esc_html( fasp_get_usdt_wallet($a['type']) );
});
