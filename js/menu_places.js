/*
 * JavaScript used for editing places page.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @license    https://opensource.org/licenses/MIT MIT
 */
/* global bookclub_ajax_object */

function handle_result(flag, message, redirect) {
    let msg = jQuery('#bc_message');
    let notice = jQuery('#bc_notice');
    msg.text(message);
    if (flag) {
        notice.attr('class', 'bc_notice notice notice-error');
    } else {
        notice.attr('class', 'bc_notice notice notice-success');
    }
    notice.css("visibility", "visible");
    setTimeout(function() {
        notice.css("visibility", "hidden");
        if (redirect) {
            window.location = redirect;
        }}, 3000);
}

jQuery('#close_help').on('click', function(e) {
    e.preventDefault();
    jQuery(".bc_help").hide();
});

jQuery('#button_help').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_places_help',
            'nonce':   jQuery('#nonce').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            jQuery('#htmlhelp').html(json['html']);
            jQuery(".bc_help").show();
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
        handle_result(true, error);
    });
});

function add_highlight(name, style) {
    let elem = jQuery('#' + name);
    if (!elem.attr('disabled')) {
        elem.addClass(style);
    }
}

function remove_highlight(name, style) {
    let elem = jQuery('#' + name);
    if (!elem.attr('disabled')) {
        elem.removeClass(style);
    }
}

function add_hide(name) {
    jQuery('#' + name).addClass('hide');
}

function remove_hide(name) {
    jQuery('#' + name).removeClass('hide');
}

jQuery('#button_search').on('click', function(e) {
    e.preventDefault();
    let parms = { action:'search' };
    let placeid = jQuery('#place_id').val();
    if ('' !== placeid) {
        parms.placeid = placeid;
    }
    let place = jQuery('#place').val();
    if ('' !== place) {
        parms.place = place;
    }
    let address = jQuery('#address').val();
    if ('' !== address) {
        parms.address = address;
    }
    let map = jQuery('#map').val();
    if ('' !== map) {
        parms.map = map;
    }
    let directions = jQuery('#directions').val();
    if ('' !== directions) {
        parms.directions = directions;
    }
    searchurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = searchurl;
});

jQuery('#button_reset').on('click', function(e) {
    e.preventDefault();
    window.location = jQuery('#referer').val();
});

jQuery('#button_delete').on('click', function(e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        jQuery.ajax({
            type: "post",
            url:  bookclub_ajax_object.ajax_url,
            data: {
                'action':  'bc_places_delete',
                'nonce':   jQuery('#nonce').val(),
                'placeid': jQuery('#place_id').val()
            }
        })
        .done(function(data) {
            if (data) {
                let json = jQuery.parseJSON(data);
                if (json['error']) {
                    handle_result(json['error'], json['message']);
                } else {
                    handle_result(json['error'], json['message'],
                        window.location = jQuery('#referer').val());
                }
            }
        })
        .fail(function(jqXHR, text, error) {
            console.log(error);
            handle_result(true, error);
        });
    }
});

jQuery('#button_save').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':     'bc_places_save',
            'nonce':      jQuery('#nonce').val(),
            'referer':    jQuery('#referer').val(),
            'placeid':    jQuery('#place_id').val(),
            'place':      jQuery('#place').val(),
            'address':    jQuery('#address').val(),
            'map':        jQuery('#map').val(),
            'directions': jQuery('#directions').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            handle_result(json['error'], json['message']);
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
});

jQuery('#button_add').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':     'bc_places_add',
            'nonce':      jQuery('#nonce').val(),
            'referer':    jQuery('#referer').val(),
            'placeid':    jQuery('#place_id').val(),
            'place':      jQuery('#place').val(),
            'address':    jQuery('#address').val(),
            'map':        jQuery('#map').val(),
            'directions': jQuery('#directions').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            let parms = {action:'edit'};
            parms.placeid = json['place_id'];
            editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
            handle_result(json['error'], json['message'], editurl);
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
});

function edit_place(placeid) {
    let parms = {action:'edit'};
    parms.placeid = placeid;
    editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = editurl;
}

jQuery('.bc_places_id').on('click', function(e) {
    edit_place(e.target.id.substring(3));
});

jQuery('.bc_places_place').on('click', function(e) {
    edit_place(e.target.id.substring(6));
});

jQuery('.bc_places_address').on('click', function(e) {
    edit_place(e.target.id.substring(4));
});

function highlight_line(placeid) {
    add_highlight('id_'    + placeid, 'bc_results_highlight');
    add_highlight('place_' + placeid, 'bc_results_highlight');
    add_highlight('adr_'   + placeid, 'bc_results_highlight');
}

function unhighlight_line(placeid) {
    remove_highlight('id_'    + placeid, 'bc_results_highlight');
    remove_highlight('place_' + placeid, 'bc_results_highlight');
    remove_highlight('adr_'   + placeid, 'bc_results_highlight');
}

jQuery('.bc_places_id').hover(function (e) {
    highlight_line(e.target.id.substring(3));
}, 
function (e) {
    unhighlight_line(e.target.id.substring(3));
});

jQuery('.bc_places_place').hover(function (e) {
    highlight_line(e.target.id.substring(6));
}, 
function (e) {
    unhighlight_line(e.target.id.substring(6));
});

jQuery('.bc_places_address').hover(function (e) {
    highlight_line(e.target.id.substring(4));
}, 
function (e) {
    unhighlight_line(e.target.id.substring(4));
});
