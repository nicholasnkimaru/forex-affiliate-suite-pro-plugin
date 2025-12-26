jQuery(function($){
  // Initialize Select2 for the country multi-select on the Geo Gating admin page.
  var $sel = $('#fasp_geo_allowed');
  if (!$sel.length) return;

  // If the select was rendered with a fixed size, ensure the Select2 container fills the width.
  // Select2 will be loaded as a dependency by the enqueue PHP snippet.
  $sel.select2({
    placeholder: 'Select allowed countries',
    allowClear: true,
    width: '100%',
    closeOnSelect: false
  });

  // Optional: add a "Select all" / "Clear all" helper above the select (uncomment if desired)
  // var $container = $sel.closest('p');
  // if ($container.length && !$container.find('.fasp-select2-actions').length) {
  //   $('<div class="fasp-select2-actions" style="margin-bottom:6px;"><button type="button" class="button fasp-select-all">Select all</button> <button type="button" class="button fasp-clear-all">Clear</button></div>')
  //     .prependTo($container)
  //     .on('click', '.fasp-select-all', function(){ $sel.find('option').prop('selected', true); $sel.trigger('change'); })
  //     .on('click', '.fasp-clear-all', function(){ $sel.val([]).trigger('change'); });
  // }
});
