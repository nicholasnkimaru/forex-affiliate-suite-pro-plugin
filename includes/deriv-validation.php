<?php if (!defined("ABSPATH")) { exit; }

/**
 * Returns array ["ok"=>bool, "data"=>mixed]
 * Strategy:
 *  - If Webhook URL set: POST JSON {code, token, app_id, client_secret, site}. Expect {"ok":true}.
 *  - Else if token present and fail_open enabled: ok=true (soft trust).
 *  - Else: ok=false.
 */
function fasp_deriv_verify_token($code, $token){
  $app_id  = fasp_get_option("deriv_app_id","");
  $secret  = fasp_get_option("deriv_client_secret","");
  $webhook = fasp_get_option("deriv_webhook_url","");
  $auth    = fasp_get_option("deriv_webhook_auth","");
  $fail    = fasp_get_option("deriv_fail_open",0);

  if ($webhook){
    $args = [
      "headers" => [
        "Content-Type" => "application/json",
      ],
      "body"    => wp_json_encode([
        "code"          => $code,
        "token"         => $token,
        "app_id"        => $app_id,
        "client_secret" => $secret,
        "site"          => home_url("/"),
      ]),
      "timeout" => 20,
    ];
    if ($auth){ $args["headers"]["Authorization"] = $auth; }
    $res = wp_remote_post($webhook, $args);
    if (!is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200){
      $j = json_decode(wp_remote_retrieve_body($res), true);
      if (is_array($j) && !empty($j["ok"])) return ["ok"=>true, "data"=>$j];
    }
    if ($fail) return ["ok"=>true, "data"=>["fallback"=>"webhook_failed_failopen"]];
    return ["ok"=>false, "data"=>"webhook_failed"];
  }

  if (!empty($token) && $fail){
    return ["ok"=>true, "data"=>["fallback"=>"token_present_failopen"]];
  }

  return ["ok"=>false, "data"=>"no_validation_configured"];
}