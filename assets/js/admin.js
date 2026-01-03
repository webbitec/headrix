/**
 * Headrix Admin Scripts (Tabbed UI)
 */
(function($){
  'use strict';
  $(function(){
    var $tabs   = $('#headrix-settings .hdrx-tab');
    var $panels = $('#headrix-settings .hdrx-panel');

    function activateTab(id){
      $tabs.removeClass('active');
      $panels.removeClass('active');
      $('#tab-' + id).addClass('active');
      $('#panel-' + id).addClass('active');
      window.location.hash = id;
    }

    $tabs.on('click', function(){
      var id = $(this).data('id');
      activateTab(id);
    });

    // init
    var hash = window.location.hash.replace('#','') || 'general';
    activateTab(hash);
  });
})(jQuery);
