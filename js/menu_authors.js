/*
 * JavaScript used for editing authors page.
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
            console.log(`${action} ${text} ${error}`);
            handle_result(true, error);
        }));
}

jQuery('#close_help').on('click', function (e) {
    e.preventDefault();
    jQuery(".bc_help").hide();
});

jQuery('#button_help').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_authors_help', {
        nonce: jQuery('#nonce').val()
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

function check_delete(authorid) {
    ajax_call('bc_authors_book_count', {
        'nonce': jQuery('#nonce').val(),
        'authorid': authorid
    }, json => {
        let error = json['error'];
        let message = json['message'];
        if (error) {
            handle_result(true, message, json['redirect']);
        } else if (confirm(message)) {
            ajax_call('bc_authors_delete', {
                'nonce': jQuery('#nonce').val(),
                'authorid': authorid
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
}

jQuery('#button_search').on('click', function (e) {
    e.preventDefault();
    window.location = create_url(jQuery('#referer').val(), {
        action: 'search',
        authorid: jQuery('#author_id').val(),
        name: jQuery('#name').val(),
        link: jQuery('#link').val(),
        bio: jQuery('#bio').val()
    });
});

jQuery('#button_reset').on('click', function (e) {
    e.preventDefault();
    window.location = jQuery('#referer').val();
});

jQuery('#button_delete').on('click', function (e) {
    e.preventDefault();
    authorid = jQuery('#author_id').val();
    count = check_delete(authorid);
});

jQuery('#button_save').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_authors_save', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'authorid': jQuery('#author_id').val(),
        'name': jQuery('#name').val(),
        'link': jQuery('#link').val(),
        'bio': jQuery('#bio').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

jQuery('#button_add').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_authors_add', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'name': jQuery('#name').val(),
        'link': jQuery('#link').val(),
        'bio': jQuery('#bio').val()
    }, json => {
        let parms = { action: 'edit' };
        parms.authorid = json['author_id'];
        editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
        handle_result(json['error'], json['message'], editurl);
    });
});

function highlight_line(authorid) {
    add_highlight('id_' + authorid, 'bc_results_highlight');
    add_highlight('author_' + authorid, 'bc_results_highlight');
}

function unhighlight_line(authorid) {
    remove_highlight('id_' + authorid, 'bc_results_highlight');
    remove_highlight('author_' + authorid, 'bc_results_highlight');
}

jQuery('.bc_authors_id').hover(function (e) {
    highlight_line(e.target.id.substring(3));
},
    function (e) {
        unhighlight_line(e.target.id.substring(3));
    });

jQuery('.bc_authors_author').hover(function (e) {
    highlight_line(e.target.id.substring(7));
},
    function (e) {
        unhighlight_line(e.target.id.substring(7));
    });
