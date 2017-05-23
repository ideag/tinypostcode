jQuery(function($){
  jQuery('div[data-dismissible] button.notice-dismiss').click(function(event){
    event.preventDefault();
    action = jQuery(this).parent().data('action');
    data = {
        'action': action,
    };
    // We can also pass the url value separately from ajaxurl for front end AJAX implementations
    jQuery.post( ajaxurl, data );
  });
});
