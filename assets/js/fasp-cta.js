(function($){
  $(document).on('click', '.fasp-cta', function(){
    var $a=$(this);
    $.post(FASP_CTA.ajax,{action:'fasp_cta_click',nonce:FASP_CTA.nonce,rid:$a.data('rid'),bucket:$a.data('bucket')});
  });
})(jQuery);
