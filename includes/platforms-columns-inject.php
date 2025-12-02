<?php if (!defined("ABSPATH")) { exit; }
/* Safe placeholder: does nothing unless theme/plugin uses the provided filter. */
add_filter("fasp_platform_rows_mutate", function($rows){ return $rows; }, 10, 1);