/*
 * JavaScript used for editing places page.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @license    https://opensource.org/licenses/MIT MIT
 */
/* global bookclub_ajax_object */

function handle_result(flag, message, redirect) {
    jQuery('#bc_message').text(message);
    let notice = jQuery('#bc_notice');
    let status = flag ? 'notice-error' : 'notice-success';
    notice.addClass(status);
    notice.removeClass('hide');
    setTimeout(function () {
        notice.addClass('hide');
        notice.removeClass(status);
        if (redirect) {
            window.location = redirect;
        }
    }, 3000);
}

function ajax_call(action, data, success) {
    data.action = action;
    jQuery.ajax({ type: 'post', url: bookclub_ajax_object.ajax_url, data })
        .done(data => {
            {
                try {
                    let json = jQuery.parseJSON(data);
                    success(json);
                } catch (e) {
                    console.log(`${action} exception ${e.message}`);
                }
            }
        })
        .fail(((jqXHR, text, error) => {
            console.log(`bc_authors_book_count ${text} ${error}`);
            handle_result(true, error);
        }));
}

jQuery('#close_help').on('click', function (e) {
    e.preventDefault();
    jQuery(".bc_help").hide();
});

jQuery('#button_help').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_places_help', {
        'nonce': jQuery('#nonce').val()
    }, json => {
        jQuery('#htmlhelp').html(json['html']);
        jQuery(".bc_help").show();
    });
});

function create_url(base, args) {
    let parms = {};
    for (let key in args) {
        if (args[key]) {
            parms[key] = args[key];
        }
    }
    return base + '&' + jQuery.param(parms)
}

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

jQuery('#button_search').on('click', function (e) {
    e.preventDefault();
    window.location = create_url(jQuery('#referer').val(), {
        action: 'search',
        placeid: jQuery('#place_id').val(),
        place: jQuery('#place').val(),
        address: jQuery('#address').val(),
        map: jQuery('#map').val(),
        directions: jQuery('#directions').val()
    });
});

jQuery('#button_reset').on('click', function (e) {
    e.preventDefault();
    window.location = jQuery('#referer').val();
});

jQuery('#button_delete').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        ajax_call('bc_places_delete', {
            'nonce': jQuery('#nonce').val(),
            'placeid': jQuery('#place_id').val()
        }, json => {
            if (json['error']) {
                handle_result(json['error'], json['message'], json['redirect']);
            } else {
                handle_result(json['error'], json['message'],
                    window.location = jQuery('#referer').val());
            }
        });
    }
});

jQuery('#button_save').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_places_save', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'placeid': jQuery('#place_id').val(),
        'place': jQuery('#place').val(),
        'address': jQuery('#address').val(),
        'map': jQuery('#map').val(),
        'directions': jQuery('#directions').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

jQuery('#button_add').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_places_add', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'placeid': jQuery('#place_id').val(),
        'place': jQuery('#place').val(),
        'address': jQuery('#address').val(),
        'map': jQuery('#map').val(),
        'directions': jQuery('#directions').val()
    }, json => {
        let parms = { action: 'edit' };
        parms.placeid = json['place_id'];
        editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
        handle_result(json['error'], json['message'], editurl);
    });
});

function edit_place(placeid) {
    let parms = { action: 'edit' };
    parms.placeid = placeid;
    editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = editurl;
}

jQuery('.bc_places_id').on('click', function (e) {
    edit_place(e.target.id.substring(3));
});

jQuery('.bc_places_place').on('click', function (e) {
    edit_place(e.target.id.substring(6));
});

jQuery('.bc_places_address').on('click', function (e) {
    edit_place(e.target.id.substring(4));
});

function highlight_line(placeid) {
    add_highlight('id_' + placeid, 'bc_results_highlight');
    add_highlight('place_' + placeid, 'bc_results_highlight');
    add_highlight('adr_' + placeid, 'bc_results_highlight');
}

function unhighlight_line(placeid) {
    remove_highlight('id_' + placeid, 'bc_results_highlight');
    remove_highlight('place_' + placeid, 'bc_results_highlight');
    remove_highlight('adr_' + placeid, 'bc_results_highlight');
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
