jQuery(function(){
    jQuery.each( tinypostcode.fields, function( selector, fields ){
        var xhr;
        var ac = new autoComplete({
            'cache'     : 0,
            'selector'  : selector,
            'source'    : function(term, response){
                var data = tinypostcode.data;
                data['q'] = term;
                var code = term.match(/[0-9]{5}/ );
                if ( null !== code ) {
                    code = code[0];
                    data['code'] = code;
                }
                // var house = term.match( /[0-9]{1,3}[a-zA-Z]*/ );
                // if ( null !== house ) {
                //     house = house[0];
                //     data['house'] = house;
                // }
                xhr = jQuery.getJSON(
                    'https://api.aru.lt/json/postcode/v1/search',
                    data,
                    function(result){
                        response(result);
                    }
                ).fail(function(data){
                    if ( 429 == data.status ) {
                        jQuery.get(
                            tinypostcode.ajaxuri,
                            { 'action': 'tpc_limiter' },
                            function(response){}
                        );
                    }
                    response([]);
                });
            },
            renderItem: function (item, search){
                search = search.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                var re = new RegExp("(" + search.split(' ').join('|') + ")", "gi");
                return '<div class="autocomplete-suggestion" data-val="' + item.address + '" data-obj=\''+JSON.stringify(item)+'\'>' + item.address.replace(re, "<b>$1</b>") + '</div>';
            },
            onSelect: function( e, term, item ){
                e.preventDefault();
                var data = jQuery( item ).data('obj');
                jQuery.each( fields, function( field_selector, field_template ) {
                    field_template = field_template.replace( /%street%/g, data.street );
                    field_template = field_template.replace( /%house%/g, data.house );
                    field_template = field_template.replace( /%city%/g, data.city );
                    field_template = field_template.replace( /%region%/g, data.region );
                    field_template = field_template.replace( /%code%/g, data.code );
                    jQuery( field_selector ).val( field_template );
                });
            },
        });
    });
});
