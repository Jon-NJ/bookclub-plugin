/*
 * JavaScript used for editing news page.
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

jQuery('#close_help').on('click', function (e) {
    e.preventDefault();
    jQuery(".bc_help").hide();
});

jQuery('#button_help').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_news_help', {
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

jQuery('#button_search').on('click', function (e) {
    e.preventDefault();
    window.location = create_url(jQuery('#referer').val(), {
        action: 'search',
        datetime: jQuery('#datetime').val(),
        poster: jQuery('#poster').val(),
        news: jQuery('#news').val(),
        age: jQuery('#age').val()
    });
});

jQuery('#button_reset').on('click', function (e) {
    e.preventDefault();
    window.location = jQuery('#referer').val();
});

jQuery('#button_delete').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        ajax_call('bc_news_delete', {
            'nonce': jQuery('#nonce').val(),
            'datetime': jQuery('#datetime').val()
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
    ajax_call('bc_news_save', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'datetime': jQuery('#datetime').val(),
        'poster': jQuery('#poster').val(),
        'news': jQuery('#news').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

jQuery('#button_add').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_news_add', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'datetime': jQuery('#datetime').val(),
        'poster': jQuery('#poster').val(),
        'news': jQuery('#news').val()
    }, json => {
        let error = json['error'];
        let editurl = '';
        if (!error) {
            let parms = { action: 'edit' };
            parms.datetime = json['datetime'];
            editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
        }
        handle_result(json['error'], json['message'], editurl);
    });
});

function edit_news(line) {
    let parms = { action: 'edit' };
    parms.datetime = jQuery('#dt_' + line).text();
    editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = editurl;
}

jQuery('.bc_news_date').on('click', function (e) {
    edit_news(e.target.id.substring(5));
});

jQuery('.bc_news_poster').on('click', function (e) {
    edit_news(e.target.id.substring(7));
});

jQuery('.bc_news_news').on('click', function (e) {
    edit_news(e.target.id.substring(5));
});

function highlight_line(placeid) {
    add_highlight('date_' + placeid, 'bc_results_highlight');
    add_highlight('poster_' + placeid, 'bc_results_highlight');
    add_highlight('news_' + placeid, 'bc_results_highlight');
}

function unhighlight_line(placeid) {
    remove_highlight('date_' + placeid, 'bc_results_highlight');
    remove_highlight('poster_' + placeid, 'bc_results_highlight');
    remove_highlight('news_' + placeid, 'bc_results_highlight');
}

jQuery('.bc_news_date').hover(function (e) {
    highlight_line(e.target.id.substring(5));
},
    function (e) {
        unhighlight_line(e.target.id.substring(5));
    });

jQuery('.bc_news_poster').hover(function (e) {
    highlight_line(e.target.id.substring(7));
},
    function (e) {
        unhighlight_line(e.target.id.substring(7));
    });

jQuery('.bc_news_news').hover(function (e) {
    highlight_line(e.target.id.substring(5));
},
    function (e) {
        unhighlight_line(e.target.id.substring(5));
    });
