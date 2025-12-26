jQuery(document).ready(function($){
  $('.fasp-geo-multiselect, .fasp-geo-regions').select2({ width: 'resolve', placeholder: '' });

  $('.fasp-geo-regions').on('change', function(){
    var selectedRegions = $(this).val() || [];
    if (!window.fasp_geo_data || !window.fasp_geo_data.regions) return;

    var codes = [];
    selectedRegions.forEach(function(r){
      var regionCodes = window.fasp_geo_data.regions[r] || [];
      regionCodes.forEach(function(c){
        if (codes.indexOf(c) === -1) codes.push(c);
      });
    });

    if (codes.length) {
      var $allow = $('#fasp-geo-allow');
      codes.forEach(function(code){
        var opt = $allow.find('option[value="' + code + '"]');
        if (opt.length && !opt.prop('selected')) {
          opt.prop('selected', true);
        }
      });
      $allow.trigger('change.select2');
    }
  });
});
