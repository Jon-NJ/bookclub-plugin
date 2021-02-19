/*
 * JavaScript used for editing news page.
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
            'action':  'bc_news_help',
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

jQuery('#button_search').on('click', function(e) {
    e.preventDefault();
    let parms = { action:'search' };
    let datetime = jQuery('#datetime').val();
    if ('' !== datetime) {
        parms.datetime = datetime;
    }
    let poster = jQuery('#poster').val();
    if ('' !== poster) {
        parms.poster = poster;
    }
    let news = jQuery('#news').val();
    if ('' !== news) {
        parms.news = news;
    }
    let age = jQuery('#age').val();
    if ('' !== age) {
        parms.age = age;
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
                'action':   'bc_news_delete',
                'nonce':    jQuery('#nonce').val(),
                'datetime': jQuery('#datetime').val()
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
            'action':   'bc_news_save',
            'nonce':    jQuery('#nonce').val(),
            'referer':  jQuery('#referer').val(),
            'datetime': jQuery('#datetime').val(),
            'poster':   jQuery('#poster').val(),
            'news':     jQuery('#news').val()
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
            'action':   'bc_news_add',
            'nonce':    jQuery('#nonce').val(),
            'referer':  jQuery('#referer').val(),
            'datetime': jQuery('#datetime').val(),
            'poster':   jQuery('#poster').val(),
            'news':     jQuery('#news').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            let error   = json['error'];
            let editurl = '';
            if (!error) {
                let parms = {action:'edit'};
                parms.datetime = json['datetime'];
                editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
            }
            handle_result(json['error'], json['message'], editurl);
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
});

function edit_news(line) {
    let parms = {action:'edit'};
    parms.datetime = jQuery('#dt_' + line).text();
    editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = editurl;
}

jQuery('.bc_news_date').on('click', function(e) {
    edit_news(e.target.id.substring(5));
});

jQuery('.bc_news_poster').on('click', function(e) {
    edit_news(e.target.id.substring(7));
});

jQuery('.bc_news_news').on('click', function(e) {
    edit_news(e.target.id.substring(5));
});

function highlight_line(placeid) {
    add_highlight('date_'   + placeid, 'bc_results_highlight');
    add_highlight('poster_' + placeid, 'bc_results_highlight');
    add_highlight('news_'   + placeid, 'bc_results_highlight');
}

function unhighlight_line(placeid) {
    remove_highlight('date_'   + placeid, 'bc_results_highlight');
    remove_highlight('poster_' + placeid, 'bc_results_highlight');
    remove_highlight('news_'   + placeid, 'bc_results_highlight');
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
