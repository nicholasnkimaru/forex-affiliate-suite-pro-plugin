<?php
if (!defined('ABSPATH')) { exit; }
function fasp_user_is_verified($platform){ $uid=get_current_user_id(); if(!$uid) return false; return get_user_meta($uid,'_fasp_verified_'.$platform,true)==='1'; }
function fasp_resource_gate($content){
    if (!is_singular('fasp_resource') || !in_the_loop() || !is_main_query()) return $content;
    $required = get_post_meta(get_the_ID(),'_fasp_required_platform',true);
    $showpill = get_post_meta(get_the_ID(),'_fasp_show_platform_pill',true);
    $download = get_post_meta(get_the_ID(),'_fasp_download_url',true);
    $pill = ($showpill && $required) ? '<span class="fasp-pill" style="margin-left:8px;">Requires '.esc_html(ucfirst($required)).'</span>' : '';
    if (!$required || fasp_user_is_verified($required)){
        if ($download) return $content.$pill.'<p><a class="fasp-button" href="'.esc_url($download).'">Download</a></p>'; return $content.$pill;
    }
    $go = home_url('/fasp-go/'.$required.'?dest=signup');
    ob_start(); ?>
    <div class="fasp-card fasp-hero">
        <h3>Unlock this resource</h3>
        <p class="fasp-muted">Join via our affiliate link, then click <em>Verify</em>. Once verified, the download will appear.</p>
        <p>
            <a class="fasp-button" href="<?php echo esc_url($go); ?>">Join <?php echo esc_html(ucfirst($required)); ?></a>
            <button class="fasp-button" onclick="faspQuickVerify('<?php echo esc_js($required); ?>')" style="margin-left:10px;">Verify</button>
        </p>
        <script>
        function faspQuickVerify(p){
            fetch('/wp-json/fasp/v1/verify/'+p,{method:'POST',headers:{'X-WP-Nonce': (window.wpApiSettings? wpApiSettings.nonce : '')}})
            .then(r=>r.json()).then(_=>location.reload());
        }
        </script>
    </div>
    <?php return $pill . ob_get_clean() . $content;
}
add_filter('the_content','fasp_resource_gate');
