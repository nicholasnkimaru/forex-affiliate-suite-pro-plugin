<?php if (!defined("ABSPATH")) { exit; } wp_enqueue_style("fasp-front");

$current_user   = wp_get_current_user();
$platforms_raw  = function_exists("fasp_get_platforms") ? fasp_get_platforms() : [];
$platforms      = function_exists("fasp_filter_platforms_for_user") ? fasp_filter_platforms_for_user($platforms_raw) : $platforms_raw;
$clicks_opt     = get_option("fasp_clicks", []);
$is_admin       = current_user_can("manage_options");
$is_preview     = function_exists("fasp_is_preview_user_mode") && fasp_is_preview_user_mode();
$myaccount_url  = function_exists("wc_get_page_permalink") ? wc_get_page_permalink("myaccount") : home_url("/my-account/");
$preview_url_on = add_query_arg("fasp_preview_user","1",$myaccount_url . "forex-dashboard/");
$preview_url_off= remove_query_arg("fasp_preview_user",$myaccount_url . "forex-dashboard/");
$deriv_app_id   = function_exists("fasp_get_option") ? fasp_get_option("deriv_app_id","") : "";
$callback       = add_query_arg("fasp_deriv_callback","1", home_url("/"));
$deriv_url      = $deriv_app_id ? ("https://oauth.deriv.com/oauth2/authorize?app_id=".rawurlencode($deriv_app_id)."&scope=read&redirect_uri=".rawurlencode($callback)) : "";
?>
<div class="fasp-wrap">
  <div class="fasp-hero">
    <h1>Forex Dashboard</h1>
    <p class="fasp-sub">Welcome, <?php echo esc_html($current_user->display_name ?: $current_user->user_login); ?>.</p>
    <div class="fasp-toolbar">
      <?php if ($is_admin): ?>
        <?php if (!$is_preview): ?>
          <a class="button" href="<?php echo esc_url($preview_url_on); ?>">Preview as User</a>
          <span class="fasp-admin-pill">Admin View</span>
        <?php else: ?>
          <a class="button" href="<?php echo esc_url($preview_url_off); ?>">Exit Preview</a>
          <span class="fasp-admin-pill">Preview Mode</span>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (!get_user_meta(get_current_user_id(), "_fasp_deriv_verified", true) && $deriv_url): ?>
        <a class="button" href="<?php echo esc_url($deriv_url); ?>">Connect Deriv</a>
      <?php endif; ?>
    </div>
    <div class="fasp-kpi">
      <div class="k">Deriv status:
        <?php echo get_user_meta(get_current_user_id(), "_fasp_deriv_verified", true) ? "<span class=\"fasp-badge ok\">Verified</span>" : "<span class=\"fasp-badge muted\">Not verified</span>"; ?>
      </div>
      <div class="k">Platforms visible: <strong><?php echo is_array($platforms) ? count($platforms) : 0; ?></strong></div>
    </div>
  </div>

  <div class="fasp-grid">
    <div class="fasp-card">
      <h2>Platforms</h2>
      <?php if (empty($platforms)): ?>
        <div class="fasp-note">No platforms available. Add them in <em>Forex Trading → Setup</em> and set visibility.</div>
      <?php else: ?>
        <table class="fasp-table">
          <thead><tr><th>Name</th><th>Link</th><th>Clicks</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach($platforms as $p):
            $k = $p["key"] ?? ($p["name"] ?? "");
            $count = intval($clicks_opt[$k] ?? 0);
            $showClicks = !empty($p["show_clicks_to_users"]) || $is_admin;
          ?>
            <tr>
              <td><strong><?php echo esc_html($p["name"] ?? $k); ?></strong></td>
              <td><code><?php echo esc_html($p["link"] ?? ""); ?></code></td>
              <td><?php echo $showClicks ? esc_html($count) : "—"; ?></td>
              <td class="fasp-actions"><a class="button" href="<?php echo esc_url(add_query_arg("fasp_click", $k, home_url("/"))); ?>">Open</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="fasp-card">
      <h2>Coaches</h2>
      <?php
        $cls = get_posts(["post_type"=>"fasp_coach_event","numberposts"=>6,"post_status"=>"publish"]);
        if (empty($cls)) { echo "<div class=\"fasp-note\">No coaches yet. Add some under <em>Forex Coaches</em>.</div>"; }
        else { echo '<div class="fasp-coaches">';
          foreach($cls as $c){
            $name = get_post_meta($c->ID,"_fasp_coach_name",true) ?: get_the_title($c);
            $role = get_post_meta($c->ID,"_fasp_coach_role",true);
            $intro= get_post_meta($c->ID,"_fasp_coach_intro",true);
            $live = get_post_meta($c->ID,"_fasp_coach_live",true);
            $aff  = get_post_meta($c->ID,"_fasp_coach_affiliate",true);
            $pid  = intval(get_post_meta($c->ID,"_fasp_coach_photo_id",true));
            $img  = $pid ? wp_get_attachment_image_url($pid,"medium") : get_the_post_thumbnail_url($c,"medium");
            $initial = strtoupper(mb_substr(wp_strip_all_tags($name),0,1));

            $turl = ($aff || $live) ? add_query_arg(["fasp_aff_click"=>"coach","id"=>$c->ID], home_url("/")) : "";
            echo '<div class="fasp-coach">';
            if ($img){ echo '<img src="'.esc_url($img).'" alt="'.esc_attr($name).'">'; }
            else { echo '<div class="ph" aria-hidden="true">'.esc_html($initial).'</div>'; }
            echo '<div class="meta"><h3>'.esc_html($name).($role ? ' <span class="role">— '.esc_html($role).'</span>' : '').'</h3>';
            if ($intro){ echo '<div class="intro">'.wp_kses_post($intro).'</div>'; }
            echo '<div class="actions">';
            if ($turl){ echo '<a class="button" target="_blank" rel="noopener nofollow" href="'.esc_url($turl).'">'.($aff?"Join Coaching":"Join Live").'</a> '; }
            echo '<a class="button" href="'.esc_url(get_permalink($c)).'">Profile</a>';
            echo '</div></div></div>';
          }
          echo '</div>';
        }
      ?>
    </div>

    <div class="fasp-card">
      <h2>Resources</h2>
      <?php
        $res = get_posts(["post_type"=>"fasp_resource","numberposts"=>8,"post_status"=>"publish"]);
        if (empty($res)) { echo "<div class=\"fasp-note\">No resources yet.</div>"; }
        else { echo '<div class="fasp-resources">';
          foreach($res as $r){
            $type = get_post_meta($r->ID,"_fasp_type",true) ?: "n/a";
            $mon  = get_post_meta($r->ID,"_fasp_monetization",true) ?: "free";
            $ext  = get_post_meta($r->ID,"_fasp_external_url",true);
            $aff  = get_post_meta($r->ID,"_fasp_affiliate_url",true);
            $prim = get_post_meta($r->ID,"_fasp_primary_url",true);
            $reqd = get_post_meta($r->ID,"_fasp_require_deriv",true) ? true : false;
            $cid  = intval(get_post_meta($r->ID,"_fasp_cover_id",true));
            $img  = $cid ? wp_get_attachment_image_url($cid,"medium") : get_the_post_thumbnail_url($r,"medium");
            $initial = strtoupper(mb_substr(wp_strip_all_tags(get_the_title($r)),0,1));
            $intro = get_the_excerpt($r) ?: wp_trim_words(wp_strip_all_tags(get_post_field("post_content",$r)), 22, "…");

            $cta_url = get_permalink($r);
            $cta_txt = "View";
            $target  = "";

            if ($mon === "external"){
              $cta_url = add_query_arg(["fasp_aff_click"=>"resource","id"=>$r->ID], home_url("/"));
              $cta_txt = "Open";
              $target  = ' target="_blank" rel="noopener nofollow"';
            } elseif ($mon === "woo"){
              $uid = get_current_user_id();
              $has = function_exists("fasp_user_has_access_to_resource") ? fasp_user_has_access_to_resource($r->ID, $uid) : true;
              $products  = function_exists("fasp_get_products_for_resource") ? fasp_get_products_for_resource($r->ID) : [];
              if ($has){ $cta_url = $prim ?: get_permalink($r); $cta_txt = "Open"; }
              else { $cta_url = !empty($products) ? get_permalink($products[0]) : get_permalink($r); $cta_txt = "Buy"; }
            } else {
              if ($prim){ $cta_url = $prim; $cta_txt = "Open"; $target=' target="_blank" rel="noopener"'; }
            }

            echo '<div class="fasp-res">';
            if ($img){ echo '<img src="'.esc_url($img).'" alt="'.esc_attr(get_the_title($r)).'">'; }
            else { echo '<div class="ph" aria-hidden="true">'.esc_html($initial).'</div>'; }
            echo '<div class="meta">';
            echo '<h3>'.esc_html(get_the_title($r)).'</h3>';
            echo '<div class="badges">';
            echo '<span class="b">'.esc_html(ucfirst($type)).'</span>';
            if ($mon === "free") echo '<span class="b ok">Free</span>';
            if ($mon === "woo")  echo '<span class="b">Woo</span>';
            if ($mon === "external") echo '<span class="b">External</span>';
            if ($reqd) echo '<span class="b warn">Deriv required</span>';
            echo '</div>';
            if ($intro){ echo '<div class="intro">'.esc_html($intro).'</div>'; }
            echo '<div class="actions"><a class="button"'.$target.' href="'.esc_url($cta_url).'">'.$cta_txt.'</a></div>';
            echo '</div></div>';
          }
          echo '</div>';
        }
      ?>
    </div>

    <div class="fasp-card">
      <h2>Licensing</h2>
      <p class="fasp-sub">After purchase, license keys can appear here (stub).</p>
    </div>
  </div>
</div>