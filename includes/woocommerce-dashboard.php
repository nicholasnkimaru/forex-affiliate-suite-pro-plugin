<?php
if (!defined('ABSPATH')) { exit; }
add_action('init', function(){ add_rewrite_endpoint('forex-affiliate', EP_ROOT|EP_PAGES); });
add_filter('query_vars', function($v){ $v[]='forex-affiliate'; return $v; });
add_filter('woocommerce_account_menu_items', function($items){ $new=[]; foreach($items as $k=>$v){ $new[$k]=$v; if($k==='dashboard'){ $new['forex-affiliate']='Forex Trading'; }} if(!isset($new['forex-affiliate'])) $new['forex-affiliate']='Forex Trading'; return $new; });
add_action('woocommerce_account_forex-affiliate_endpoint','fasp_wc_dashboard');
function fasp_wc_dashboard(){
    $uid = get_current_user_id(); $plats = function_exists('fasp_get_platforms')? fasp_get_platforms():[]; ?>
    <div class="fasp-wrap">
        <div class="fasp-grid">
            <div class="fasp-card">
                <h2>Welcome back</h2>
                <p class="fasp-muted">Here’s your affiliate status and quick actions.</p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
                    <span class="fasp-pill">User #<?php echo intval($uid); ?></span>
                </div>
                <p style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
                <?php foreach($plats as $slug=>$pl): if (($pl['show_in_dashboard']??'1')!=='1' || ($pl['enabled']??'1')!=='1') continue; $ok = get_user_meta($uid,'_fasp_verified_'.$slug,true)==='1'; ?>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <?php if(!empty($pl['logo_url'])): ?><img src="<?php echo esc_url($pl['logo_url']); ?>" alt="<?php echo esc_attr($pl['name']); ?>" style="height:24px;width:auto;border-radius:4px;"><?php endif; ?>
                        <a class="fasp-button" href="<?php echo esc_url(home_url('/fasp-go/'.$slug.'?dest=signup')); ?>">Join <?php echo esc_html($pl['name']); ?></a>
                        <span class="fasp-pill"><?php echo esc_html($pl['name']); ?> <?php echo $ok?'✓ Verified':'— not verified'; ?></span>
                    </div>
                <?php endforeach; ?>
                </p>
            </div>
            <div class="fasp-card">
                <h2>Your progress</h2>
                <canvas id="faspChart" width="520" height="260"></canvas>
                <p class="fasp-muted">Placeholder chart (clicks over time).</p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>(function(){var c=document.getElementById('faspChart'); if(!c) return; new Chart(c,{type:'line',data:{labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],datasets:[{label:'Clicks',data:[0,5,9,3,12,7,10]}]},options:{responsive:true,plugins:{legend:{display:false}}}});})();</script>
    <?php
}


function fasp_progress_strip($uid){
  $steps = [
    ['key'=>'verified','label'=>'Verify Deriv'],
    ['key'=>'downloaded','label'=>'Download eBook'],
    ['key'=>'booked','label'=>'Book 15‑min coach'],
    ['key'=>'deposit','label'=>'First deposit'],
    ['key'=>'trade','label'=>'First trade']
  ];
  $ok = [
    'verified' => (get_user_meta($uid,'_fasp_verified_deriv',true)==='1'),
    'downloaded' => (get_user_meta($uid,'_fasp_downloaded',true)==='1'),
    'booked' => (get_user_meta($uid,'_fasp_booked',true)==='1'),
    'deposit' => (get_user_meta($uid,'_fasp_deposit',true)==='1'),
    'trade' => (get_user_meta($uid,'_fasp_trade',true)==='1'),
  ];
  echo '<div class="fasp-card" style="margin-top:12px;"><h3>Getting started</h3><div style="display:flex;gap:8px;flex-wrap:wrap;">';
  foreach($steps as $s){
    $done = !empty($ok[$s['key']]);
    echo '<span class="fasp-pill" style="'.($done?'background:#dcfce7;border-color:#86efac;':'').'">'.esc_html($s['label']).($done?' ✓':'').'</span>';
  }
  echo '</div></div>';
}

