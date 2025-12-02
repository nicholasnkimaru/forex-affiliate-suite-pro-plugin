<?php if (!defined('ABSPATH')) exit;
/** Log caller when wpdb::prepare() is used without placeholders. */
function fasp_diag_doing_it_wrong($function, $message, $version){
  if ($function === 'wpdb::prepare'){
    if (function_exists('wp_debug_backtrace_summary')){
      $trace = wp_debug_backtrace_summary(null, 0, true);
    } else {
      $trace = print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true);
    }
    error_log('FASP DIAG — bad wpdb::prepare(): '.$message.' | Trace: '.$trace);
  }
}
add_action('doing_it_wrong_run','fasp_diag_doing_it_wrong',10,3);
