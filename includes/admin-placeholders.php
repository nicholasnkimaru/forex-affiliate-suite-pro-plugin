<?php
if (!defined('ABSPATH')) exit;

// --- Promo Landings ---
function fasp_landings_page(){
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['fasp_land_create']) && check_admin_referer('fasp_land','fasp_land_nonce')){
        $title    = sanitize_text_field($_POST['title'] ?? 'Forex Landing');
        $platform = sanitize_title($_POST['platform'] ?? 'deriv');
        $hero     = sanitize_text_field($_POST['hero'] ?? 'Trade smarter with our team');
        $sub      = sanitize_text_field($_POST['sub'] ?? 'Join via our partner link to unlock tools & coaching.');

        $content = "<!-- wp:heading --><h2>{$hero}</h2><!-- /wp:heading -->\n"
                 . "<!-- wp:paragraph --><p>{$sub}</p><!-- /wp:paragraph -->\n"
                 . "<!-- wp:shortcode -->[fasp_join platform=\"{$platform}\"]<!-- /wp:shortcode -->\n"
                 . "<!-- wp:shortcode -->[fasp_resources per_page=\"12\"]<!-- /wp:shortcode -->\n"
                 . "<!-- wp:shortcode -->[fasp_coaches per_page=\"12\"]<!-- /wp:shortcode -->";

        $page_id = wp_insert_post(array(
            'post_title'   => $title,
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_content' => $content
        ));

        if (!is_wp_error($page_id)){
            echo '<div class="updated"><p>Landing created: <a href="' . esc_url(get_permalink($page_id)) . '" target="_blank" rel="noopener">View</a></p></div>';
        } else {
            echo '<div class="error"><p>Failed to create landing.</p></div>';
        }
    }
    ?>
    <div class="wrap fasp-admin">
        <h1>Promo Landings</h1>
        <div class="fasp-wrap fasp-card">
            <form method="post">
                <?php wp_nonce_field('fasp_land','fasp_land_nonce'); ?>
                <p><label><strong>Page Title</strong><br><input class="regular-text" name="title" value="Forex Landing"></label></p>
                <p><label><strong>Platform Slug</strong> (e.g., deriv)<br><input class="regular-text" name="platform" value="deriv"></label></p>
                <p><label><strong>Hero Title</strong><br><input class="regular-text" name="hero" value="Trade smarter with our team"></label></p>
                <p><label><strong>Subheading</strong><br><input class="regular-text" name="sub" value="Join via our partner link to unlock tools & coaching."></label></p>
                <p><input type="hidden" name="fasp_land_create" value="1"><button class="button button-primary">Create Landing Page</button></p>
            </form>
        </div>
    </div>
    <?php
}

// --- Visibility helper ---
function fasp_visibility_page(){
    echo '<div class="wrap fasp-admin"><h1>Platform Visibility</h1><div class="fasp-wrap fasp-card"><p class="fasp-muted">Use Platform Setup to toggle “Show in dashboard”.</p></div></div>';
}

// --- Payments & Gateways ---
function fasp_payments_page(){
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['fasp_payments_save']) && check_admin_referer('fasp_payments','fasp_payments_nonce')){
        $o = array(
            'paypal_email'       => sanitize_email($_POST['paypal_email'] ?? ''),
            'paypal_return_url'  => esc_url_raw($_POST['paypal_return_url'] ?? ''),
            'paypal_cancel_url'  => esc_url_raw($_POST['paypal_cancel_url'] ?? ''),
            'stripe_pk'          => sanitize_text_field($_POST['stripe_pk'] ?? ''),
            'stripe_sk'          => sanitize_text_field($_POST['stripe_sk'] ?? ''),
            'mpesa_till'         => sanitize_text_field($_POST['mpesa_till'] ?? ''),
            'mpesa_paybill'      => sanitize_text_field($_POST['mpesa_paybill'] ?? ''),
            'mpesa_callback_url' => esc_url_raw($_POST['mpesa_callback_url'] ?? ''),
            'crypto_wallet'      => sanitize_text_field($_POST['crypto_wallet'] ?? ''),
            'bank_details'       => wp_kses_post($_POST['bank_details'] ?? ''),
        );
        update_option('fasp_payments', $o);
        echo '<div class="updated"><p>Payments saved.</p></div>';
    }

    $o = get_option('fasp_payments', array());
    $def_mpesa_cb = site_url('/?fasp_mpesa_callback=1');
    $def_pp_ret   = site_url('/?fasp_paypal_return=1');
    $def_pp_can   = site_url('/?fasp_paypal_cancel=1');
    ?>
    <div class="wrap fasp-admin">
        <h1>Payments & Gateways</h1>
        <div class="fasp-wrap fasp-card">
            <form method="post">
                <?php wp_nonce_field('fasp_payments','fasp_payments_nonce'); ?>

                <h2>M-Pesa</h2>
                <p><label>Till Number<br><input class="regular-text" name="mpesa_till" value="<?php echo esc_attr($o['mpesa_till'] ?? ''); ?>"></label></p>
                <p><label>Paybill Number<br><input class="regular-text" name="mpesa_paybill" value="<?php echo esc_attr($o['mpesa_paybill'] ?? ''); ?>"></label></p>
                <p><label>Callback URL<br><input class="large-text code" name="mpesa_callback_url" value="<?php echo esc_url($o['mpesa_callback_url'] ?? $def_mpesa_cb); ?>"></label></p>

                <h2>PayPal</h2>
                <p><label>Email<br><input class="regular-text" name="paypal_email" value="<?php echo esc_attr($o['paypal_email'] ?? ''); ?>"></label></p>
                <p><label>Return URL<br><input class="large-text code" name="paypal_return_url" value="<?php echo esc_url($o['paypal_return_url'] ?? $def_pp_ret); ?>"></label></p>
                <p><label>Cancel URL<br><input class="large-text code" name="paypal_cancel_url" value="<?php echo esc_url($o['paypal_cancel_url'] ?? $def_pp_can); ?>"></label></p>

                <h2>Stripe</h2>
                <p><label>Publishable Key<br><input class="regular-text" name="stripe_pk" value="<?php echo esc_attr($o['stripe_pk'] ?? ''); ?>"></label></p>
                <p><label>Secret Key<br><input class="regular-text" type="password" name="stripe_sk" value="<?php echo esc_attr($o['stripe_sk'] ?? ''); ?>"></label></p>

                <h2>Crypto</h2>
                <p><label>USDT/Wallet Address<br><input class="regular-text" name="crypto_wallet" value="<?php echo esc_attr($o['crypto_wallet'] ?? ''); ?>"></label></p>

                <h2>Bank Transfer</h2>
                <p><label>Bank Details / Instructions<br><textarea class="large-text code" rows="6" name="bank_details"><?php echo esc_textarea($o['bank_details'] ?? ''); ?></textarea></label></p>

                <p><input type="hidden" name="fasp_payments_save" value="1"><button class="button button-primary">Save</button></p>
            </form>
        </div>
    </div>
    <?php
}

// --- Creative Helper ---
function fasp_creatives_page(){
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['fasp_creatives_save']) && check_admin_referer('fasp_creatives','fasp_creatives_nonce')){
        $items = (isset($_POST['items']) && is_array($_POST['items'])) ? $_POST['items'] : array();
        $out = array();
        foreach ($items as $i){
            $title = isset($i['title']) ? sanitize_text_field($i['title']) : '';
            $platform = isset($i['platform']) ? sanitize_title($i['platform']) : '';
            $image = isset($i['image']) ? esc_url_raw($i['image']) : '';
            $url = isset($i['url']) ? esc_url_raw($i['url']) : '';
            if ($title==='' && $platform==='' && $image==='' && $url==='') { continue; }
            $out[] = array('title'=>$title,'platform'=>$platform,'image'=>$image,'url'=>$url);
        }
        update_option('fasp_creatives', $out);
        echo '<div class="updated"><p>Creatives saved.</p></div>';
    }

    $items = get_option('fasp_creatives', array());
    ?>
    <div class="wrap fasp-admin">
        <h1>Creative Helper</h1>

        <div class="fasp-wrap fasp-card">
            <h2>CTA Shortcodes</h2>
            <p class="fasp-muted">Place these anywhere:</p>
            <code>[fasp_join platform="deriv"]</code> &nbsp; <code>[fasp_resources per_page="12"]</code> &nbsp; <code>[fasp_coaches per_page="12"]</code>
        </div>

        <div class="fasp-wrap fasp-card">
            <h2>UTM Link Builder</h2>
            <p>
                Base: <input class="regular-text" id="fasp_utm_base" value="<?php echo esc_attr(home_url('/fasp-go/deriv')); ?>"> &nbsp;
                Source: <input class="regular-text" id="fasp_utm_src" value="ads"> &nbsp;
                Campaign: <input class="regular-text" id="fasp_utm_cmp" value="launch">
            </p>
            <p>Result: <input class="large-text code" id="fasp_utm_out" value="" readonly></p>
            <script>
            jQuery(function($){
                function build(){
                    var a=$('#fasp_utm_base').val(),s=$('#fasp_utm_src').val(),c=$('#fasp_utm_cmp').val();
                    $('#fasp_utm_out').val(a+'?utm_source='+encodeURIComponent(s)+'&utm_campaign='+encodeURIComponent(c));
                }
                $('#fasp_utm_base,#fasp_utm_src,#fasp_utm_cmp').on('input',build); build();
            });
            </script>
        </div>

        <div class="fasp-wrap fasp-card">
            <h2>Banners / Images</h2>
            <form method="post">
                <?php wp_nonce_field('fasp_creatives','fasp_creatives_nonce'); ?>
                <table class="widefat fasp-table">
                    <thead><tr><th>Title</th><th>Platform</th><th>Image</th><th>Landing URL</th></tr></thead>
                    <tbody id="fasp_creatives_rows">
                        <?php if (!empty($items)) : foreach ($items as $i): ?>
                        <tr>
                            <td><input name="items[][title]" value="<?php echo esc_attr($i['title']); ?>" class="regular-text"></td>
                            <td><input name="items[][platform]" value="<?php echo esc_attr($i['platform']); ?>" class="regular-text"></td>
                            <td>
                                <input name="items[][image]" value="<?php echo esc_url($i['image']); ?>" class="regular-text fasp-img-input">
                                <button class="button fasp-select-img">Select</button>
                            </td>
                            <td><input name="items[][url]" value="<?php echo esc_url($i['url']); ?>" class="regular-text"></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        <tr>
                            <td><input name="items[][title]" class="regular-text" placeholder="Banner title"></td>
                            <td><input name="items[][platform]" class="regular-text" placeholder="deriv"></td>
                            <td>
                                <input name="items[][image]" class="regular-text fasp-img-input" placeholder="https://...">
                                <button class="button fasp-select-img">Select</button>
                            </td>
                            <td><input name="items[][url]" class="regular-text" placeholder="<?php echo esc_url(home_url('/fasp-go/deriv')); ?>"></td>
                        </tr>
                    </tbody>
                </table>
                <p><input type="hidden" name="fasp_creatives_save" value="1"><button class="button button-primary">Save Creatives</button></p>
            </form>
            <script>
            jQuery(function($){
                var frame;
                $(document).on('click','.fasp-select-img', function(e){
                    e.preventDefault();
                    var cell = $(this).closest('td');
                    if (frame){ frame.open(); return; }
                    frame = wp.media({ title: 'Select Banner', multiple: false, library:{type:'image'} });
                    frame.on('select', function(){
                        var att = frame.state().get('selection').first().toJSON();
                        cell.find('.fasp-img-input').val(att.url);
                    });
                    frame.open();
                });
            });
            </script>
        </div>
    </div>
    <?php
}

// --- Email/Leads placeholder ---
function fasp_leads_page(){
    echo '<div class="wrap fasp-admin"><h1>Email & Leads</h1><div class="fasp-wrap fasp-card"><p class="fasp-muted">Connect your forms to Mailchimp, Brevo, etc.</p></div></div>';
}

// --- Settings placeholder ---
function fasp_settings_page(){
    echo '<div class="wrap fasp-admin"><h1>Settings</h1><div class="fasp-wrap fasp-card"><p class="fasp-muted">General settings area. Extend as needed.</p></div></div>';
}

// --- Getting Started ---
function fasp_getting_started_page(){
    echo '<div class="wrap fasp-admin"><h1>Getting Started</h1><div class="fasp-wrap fasp-card"><ol class="fasp-muted" style="line-height:1.8;"><li>Set up Deriv in Platform Setup (App ID, Redirect URI, Affiliate link).</li><li>Create a Landing in Promo Landings.</li><li>Publish Resources and Coaches.</li><li>Users verify via Woo → My Account → Forex Affiliate.</li></ol></div></div>';
}
