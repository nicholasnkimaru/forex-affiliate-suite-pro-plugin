<?php
if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . '/helpers.php';

add_action('admin_menu', function(){
    add_submenu_page('fasp_hub','Platform Setup','Platform Setup','manage_options','fasp_platforms','fasp_platforms_page');
});

function fasp_platforms_page(){
    if (!current_user_can('manage_options')) return;
    $p = fasp_get_platforms();

    // Defaults used everywhere to avoid undefined-index notices
    $defaults = [
        'slug'=>'','name'=>'','affiliate_url'=>'','signup_url'=>'','method'=>'none',
        'app_id'=>'','client_secret'=>'','logo_url'=>'','regions'=>'','oauth_scopes'=>'',
        'oauth_redirect'=>'','webhook_url'=>'','webhook_auth'=>'','kyc_required'=>'0',
        'primary'=>'0','enabled'=>'1','show_in_dashboard'=>'1'
    ];

    $bulk_columns = [
        '__slug'        => ['label'=>'Platform','type'=>'text'],
        'method'        => ['label'=>'Method','type'=>'select','options'=>['none'=>'None','oauth'=>'OAuth','link'=>'Direct Link']],
        'app_id'        => ['label'=>'App ID','type'=>'text'],
        'client_secret' => ['label'=>'Client Secret','type'=>'text'],
        'webhook_url'   => ['label'=>'Webhook URL','type'=>'url'],
        'webhook_auth'  => ['label'=>'Webhook Auth','type'=>'text'],
    ];
    if (has_filter('fasp_platform_bulk_columns')){ $bulk_columns = apply_filters('fasp_platform_bulk_columns',$bulk_columns); }

    // Handle inline bulk save
    if (isset($_POST['fasp_plat_bulk']) && isset($_POST['fasp_plat_nonce']) && wp_verify_nonce($_POST['fasp_plat_nonce'],'fasp_plat_nonce')){
        $rows = isset($_POST['rows']) ? (array) $_POST['rows'] : [];
        $newp = $p;
        foreach($rows as $slug=>$r){
            $slug_s   = sanitize_title($slug);
            $r        = wp_unslash($r);
            $new_slug = sanitize_title($r['__slug'] ?? $slug_s);
            if (!$new_slug) continue;
            $rec = isset($newp[$slug_s]) ? (array)$newp[$slug_s] : ['slug'=>$new_slug];
            $rec = array_merge($defaults, $rec);
            foreach($bulk_columns as $key=>$schema){
                if ($key==='__slug') continue;
                $t = $schema['type'] ?? 'text';
                $val = $r[$key] ?? '';
                if ($t==='checkbox') $rec[$key] = !empty($val)?'1':'0';
                elseif ($t==='url')  $rec[$key] = esc_url_raw($val);
                else                 $rec[$key] = sanitize_text_field($val ?? '');
            }
            $rec['slug']   = $new_slug;
            $newp[$new_slug] = $rec;
            if ($new_slug !== $slug_s) unset($newp[$slug_s]);
        }
        fasp_save_platforms($newp);
        echo '<div class="updated"><p>Saved settings.</p></div>';
        $p = fasp_get_platforms();
    }

    // Handle add/edit/delete
    if (isset($_POST['fasp_plat_action']) && isset($_POST['fasp_plat_nonce']) && wp_verify_nonce($_POST['fasp_plat_nonce'],'fasp_plat_nonce')){
        $act = sanitize_text_field($_POST['fasp_plat_action']);
        if ($act==='save'){
            $slug = sanitize_title($_POST['slug'] ?? ''); if (!$slug) $slug = 'platform-'.time();
            $rec = array_merge($defaults, [
                'slug'=>$slug,
                'name'=>sanitize_text_field($_POST['name'] ?? ''),
                'affiliate_url'=>esc_url_raw($_POST['affiliate_url'] ?? ''),
                'signup_url'=>esc_url_raw($_POST['signup_url'] ?? ''),
                'method'=>sanitize_text_field($_POST['method'] ?? 'none'),
                'app_id'=>sanitize_text_field($_POST['app_id'] ?? ''),
                'client_secret'=>sanitize_text_field($_POST['client_secret'] ?? ''),
                'logo_url'=>esc_url_raw($_POST['logo_url'] ?? ''),
                'regions'=>sanitize_text_field($_POST['regions'] ?? ''),
                'oauth_scopes'=>sanitize_text_field($_POST['oauth_scopes'] ?? ''),
                'oauth_redirect'=>esc_url_raw($_POST['oauth_redirect'] ?? site_url('/wp-json/fasp/v1/deriv/callback')),
                'webhook_url'=>esc_url_raw($_POST['webhook_url'] ?? ''),
                'webhook_auth'=>sanitize_text_field($_POST['webhook_auth'] ?? ''),
                'kyc_required'=>isset($_POST['kyc_required'])?'1':'0',
                'primary'=>isset($_POST['primary'])?'1':'0',
                'enabled'=>isset($_POST['enabled'])?'1':'0',
                'show_in_dashboard'=>isset($_POST['show_in_dashboard'])?'1':'0',
            ]);
            $p[$slug] = $rec; fasp_save_platforms($p);
            echo '<div class="updated"><p>Saved platform.</p></div>';
            $p = fasp_get_platforms();
        } elseif ($act==='delete'){
            $slug = sanitize_title($_POST['slug'] ?? '');
            if ($slug && isset($p[$slug])){ unset($p[$slug]); fasp_save_platforms($p); echo '<div class="updated"><p>Deleted platform.</p></div>'; }
        }
    }

    ?>
    <style>.fasp-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}.fasp-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px}.fasp-muted{color:#6b7280}</style>
    <div class="wrap fasp-admin">
      <h1>Platform Setup</h1>
      <div class="fasp-wrap fasp-card">
        <h3>Quick inline edit</h3>
        <form method="post"><?php wp_nonce_field('fasp_plat_nonce','fasp_plat_nonce'); ?><input type="hidden" name="fasp_plat_bulk" value="1">
          <table class="widefat fasp-table">
            <thead><tr><th>Slug</th><?php foreach($bulk_columns as $k=>$s){ echo '<th>'.esc_html($s['label'] ?? $k).'</th>'; } ?></tr></thead>
            <tbody>
              <?php foreach($p as $slug=>$row):
                    $row = array_merge($defaults, (array) $row);
                ?><tr><td><code><?php echo esc_html($row['slug'] ?: $slug); ?></code></td>
                <?php foreach($bulk_columns as $key=>$schema):
                    $t=$schema['type']??'text';
                    $val= ($key==='__slug') ? ($row['slug'] ?: $slug) : ($row[$key] ?? '');
                ?>
                  <td>
                    <?php if ($t==='checkbox'): ?>
                      <input type="checkbox" name="rows[<?php echo esc_attr($slug); ?>][<?php echo esc_attr($key); ?>]" value="1" <?php checked($val,'1'); ?>>
                    <?php elseif ($t==='select'): $opts=$schema['options']??[]; ?>
                      <select name="rows[<?php echo esc_attr($slug); ?>][<?php echo esc_attr($key); ?>]">
                        <?php foreach($opts as $ov=>$ol): ?><option value="<?php echo esc_attr($ov); ?>" <?php selected($val,$ov); ?>><?php echo esc_html($ol); ?></option><?php endforeach; ?>
                      </select>
                    <?php else: ?>
                      <input type="<?php echo $t==='url'?'url':'text'; ?>" class="regular-text" name="rows[<?php echo esc_attr($slug); ?>][<?php echo esc_attr($key); ?>]" value="<?php echo $t==='url'? esc_url($val) : esc_attr($val); ?>">
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr><?php endforeach; ?>
            </tbody>
          </table>
          <p><button class="button button-primary">Save settings</button></p>
        </form>
      </div>

      <div class="fasp-grid">
        <div class="fasp-card">
          <h3>Add / Edit Platform</h3>
          <form id="fasp_form" method="post"><?php wp_nonce_field('fasp_plat_nonce','fasp_plat_nonce'); ?>
            <p><label><strong>Slug</strong><br><input name="slug" class="regular-text"></label></p>
            <p><label><strong>Name</strong><br><input name="name" class="regular-text"></label></p>
            <p><label><strong>Affiliate / Signup URL</strong><br><input name="affiliate_url" class="regular-text"></label></p>
            <p><label><strong>Alt Signup URL</strong><br><input name="signup_url" class="regular-text"></label></p>
            <p><label><strong>Method</strong><br><select name="method"><option value="none">None</option><option value="oauth">OAuth</option><option value="link">Direct Link</option></select></label></p>
            <p><label><strong>App ID</strong><br><input name="app_id" class="regular-text"></label></p>
            <p><label><strong>Client Secret</strong><br><input name="client_secret" class="regular-text"></label></p>
            <p><label><strong>Logo URL</strong><br><input name="logo_url" class="regular-text"></label></p>
            <p><label><strong>Regions</strong><br><input name="regions" class="regular-text"></label></p>
            <p><label><strong>OAuth Scopes</strong><br><input name="oauth_scopes" class="regular-text"></label></p>
            <p><label><strong>OAuth Redirect URI</strong><br><input name="oauth_redirect" class="regular-text" value="<?php echo esc_attr(site_url('/wp-json/fasp/v1/deriv/callback')); ?>"></label></p>
            <p><label><strong>Webhook URL</strong><br><input name="webhook_url" class="regular-text"></label></p>
            <p><label><strong>Webhook Auth</strong><br><input name="webhook_auth" class="regular-text"></label></p>
            <p><label><input type="checkbox" name="kyc_required" value="1"> KYC required</label></p>
            <p><label><input type="checkbox" name="primary" value="1"> Primary</label> &nbsp; <label><input type="checkbox" name="enabled" value="1" checked> Enabled</label> &nbsp; <label><input type="checkbox" name="show_in_dashboard" value="1" checked> Show in dashboard</label></p>
            <p><input type="hidden" name="fasp_plat_action" value="save"><button class="button button-primary">Save Platform</button></p>
          </form>
        </div>

        <div class="fasp-card">
          <h3>Existing Platforms</h3>
          <table class="widefat fasp-table"><thead><tr><th>Slug</th><th>Name</th><th>Primary</th><th>Enabled</th><th>Show</th><th>Actions</th></tr></thead><tbody>
          <?php foreach($p as $k=>$v): $d = array_merge($defaults, (array) $v); $slug = $d['slug'] ?: sanitize_title($k); ?>
            <tr>
              <td><?php echo esc_html($slug); ?></td>
              <td><?php echo esc_html($d['name']); ?></td>
              <td><?php echo ($d['primary']==='1'?'Yes':'No'); ?></td>
              <td><?php echo ($d['enabled']==='1'?'Yes':'No'); ?></td>
              <td><?php echo ($d['show_in_dashboard']==='1'?'Yes':'No'); ?></td>
              <td>
                <button type="button" class="button fasp-edit-platform"
                  data-slug="<?php echo esc_attr($slug); ?>"
                  data-name="<?php echo esc_attr($d['name']); ?>"
                  data-affiliate_url="<?php echo esc_url($d['affiliate_url']); ?>"
                  data-signup_url="<?php echo esc_url($d['signup_url']); ?>"
                  data-method="<?php echo esc_attr($d['method']); ?>"
                  data-app_id="<?php echo esc_attr($d['app_id']); ?>"
                  data-client_secret="<?php echo esc_attr($d['client_secret']); ?>"
                  data-logo_url="<?php echo esc_url($d['logo_url']); ?>"
                  data-regions="<?php echo esc_attr($d['regions']); ?>"
                  data-oauth_scopes="<?php echo esc_attr($d['oauth_scopes']); ?>"
                  data-oauth_redirect="<?php echo esc_url($d['oauth_redirect']); ?>"
                  data-webhook_url="<?php echo esc_url($d['webhook_url']); ?>"
                  data-webhook_auth="<?php echo esc_attr($d['webhook_auth']); ?>"
                  data-kyc_required="<?php echo esc_attr($d['kyc_required']); ?>"
                  data-primary="<?php echo esc_attr($d['primary']); ?>"
                  data-enabled="<?php echo esc_attr($d['enabled']); ?>"
                  data-show_in_dashboard="<?php echo esc_attr($d['show_in_dashboard']); ?>"
                >Edit</button>
                <form method="post" style="display:inline-block;margin-left:6px;"><?php wp_nonce_field('fasp_plat_nonce','fasp_plat_nonce'); ?><input type="hidden" name="fasp_plat_action" value="delete"><input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>"><button class="button button-secondary" onclick="return confirm('Delete platform <?php echo esc_js($slug); ?>?');">Delete</button></form>
              </td>
            </tr>
          <?php endforeach; ?></tbody></table>
          <p class="fasp-muted">Click <em>Edit</em> to load a row into the Add/Edit form.</p>
        </div>
      </div>
    </div>
    <script>
      jQuery(function($){
        function setChk(n,v){ $('#fasp_form [name="'+n+'"]').prop('checked', String(v)==='1'); }
        $(document).on('click','.fasp-edit-platform',function(e){
          e.preventDefault();
          var d=$(this).data(), f=$('#fasp_form');
          f.find('[name=slug]').val(d.slug); f.find('[name=name]').val(d.name);
          f.find('[name=affiliate_url]').val(d.affiliate_url); f.find('[name=signup_url]').val(d.signup_url);
          f.find('[name=method]').val(d.method); f.find('[name=app_id]').val(d.app_id); f.find('[name=client_secret]').val(d.client_secret);
          f.find('[name=logo_url]').val(d.logo_url); f.find('[name=regions]').val(d.regions); f.find('[name=oauth_scopes]').val(d.oauth_scopes);
          f.find('[name=oauth_redirect]').val(d.oauth_redirect); f.find('[name=webhook_url]').val(d.webhook_url); f.find('[name=webhook_auth]').val(d.webhook_auth);
          setChk('kyc_required', d.kyc_required); setChk('primary', d.primary); setChk('enabled', d.enabled); setChk('show_in_dashboard', d.show_in_dashboard);
          f[0].scrollIntoView({behavior:'smooth', block:'start'});
        });
      });
    </script>
<?php }
