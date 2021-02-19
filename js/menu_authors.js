/*
 * JavaScript used for editing authors page.
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
            'action':  'bc_authors_help',
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

function delete_author(authorid, error, message) {
    if (error) {
        handle_result(true, message);
    } else if (confirm(message)) {
        jQuery.ajax({
            type: "post",
            url:  bookclub_ajax_object.ajax_url,
            data: {
                'action':   'bc_authors_delete',
                'nonce':    jQuery('#nonce').val(),
                'authorid': authorid
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
    
}

function check_delete(authorid) {
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':   'bc_authors_book_count',
            'nonce':    jQuery('#nonce').val(),
            'authorid': authorid
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            delete_author(authorid, json['error'], json['message']);
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
        handle_result(true, error);
    });
}

jQuery('#button_search').on('click', function(e) {
    e.preventDefault();
    let parms = { action:'search' };
    let authorid = jQuery('#author_id').val();
    if ('' !== authorid) {
        parms.authorid = authorid;
    }
    let name = jQuery('#name').val();
    if ('' !== name) {
        parms.name = name;
    }
    let link = jQuery('#link').val();
    if ('' !== link) {
        parms.link = link;
    }
    let bio = jQuery('#bio').val();
    if ('' !== bio) {
        parms.bio = bio;
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
    authorid = jQuery('#author_id').val();
    count = check_delete(authorid);
});

jQuery('#button_save').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':   'bc_authors_save',
            'nonce':    jQuery('#nonce').val(),
            'referer':  jQuery('#referer').val(),
            'authorid': jQuery('#author_id').val(),
            'name':     jQuery('#name').val(),
            'link':     jQuery('#link').val(),
            'bio':      jQuery('#bio').val()
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
            'action':  'bc_authors_add',
            'nonce':   jQuery('#nonce').val(),
            'referer': jQuery('#referer').val(),
            'name':    jQuery('#name').val(),
            'link':    jQuery('#link').val(),
            'bio':     jQuery('#bio').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            let parms = {action:'edit'};
            parms.authorid = json['author_id'];
            editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
            handle_result(json['error'], json['message'], editurl);
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
});

function edit_author(authorid) {
    let parms = {action:'edit'};
    parms.authorid = authorid;
    editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = editurl;
}

jQuery('.bc_authors_id').on('click', function(e) {
    edit_author(e.target.id.substring(3));
});

jQuery('.bc_authors_author').on('click', function(e) {
    edit_author(e.target.id.substring(7));
});

function highlight_line(authorid) {
    add_highlight('id_'     + authorid, 'bc_results_highlight');
    add_highlight('author_' + authorid, 'bc_results_highlight');
}

function unhighlight_line(authorid) {
    remove_highlight('id_'     + authorid, 'bc_results_highlight');
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
