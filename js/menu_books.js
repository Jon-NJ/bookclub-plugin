/*
 * JavaScript used for editing books page.
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
            'action':  'bc_books_help',
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

function validate_form() {
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action': 'bc_books_lookup_author',
            'nonce':  jQuery('#nonce').val(),
            'author': jQuery('#author_name').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            let editmode = jQuery('#mode').val() === 'edit';
            let author_id = jQuery('#author_id');
            if (json['error']) {
                author_id.attr('value', '');
                if (editmode) {
                    let save_button = jQuery('#button_save');
                    save_button.attr('disabled', '');
                } else {
                    let add_button = jQuery('#button_add');
                    add_button.attr('disabled', '');
                }
            } else {
                author_id.attr('value', json['author_id']);
                if (editmode) {
                    let save_button = jQuery('#button_save');
                    save_button.removeAttr('disabled');
                } else {
                    let add_button = jQuery('#button_add');
                    add_button.removeAttr('disabled');
                }
            }
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
}

jQuery('#author_name').on('input', function(e) {
    validate_form();
});

jQuery(document).ready(function() {
    validate_form();
});

jQuery('#button_search').on('click', function(e) {
    e.preventDefault();
    let parms = { action:'search' };
    let bookid = jQuery('#book_id').val();
    if (('' !== bookid) && ('0' !== bookid)) {
        parms.bookid = bookid;
    }
    let title = jQuery('#title').val();
    if ('' !== title) {
        parms.title = title;
    }
    let cover = jQuery('#cover_url').val();
    if ('' !== cover) {
        parms.cover = cover;
    }
    let authorname = jQuery('#author_name').val();
    if ('' !== authorname) {
        parms.authorname = authorname;
    }
    let summary = jQuery('#summary').val();
    if ('' !== summary) {
        parms.summary = summary;
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
                'action': 'bc_books_delete',
                'nonce':  jQuery('#nonce').val(),
                'bookid': jQuery('#book_id').val()
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
            'action':  'bc_books_save',
            'nonce':    jQuery('#nonce').val(),
            'referer':  jQuery('#referer').val(),
            'bookid':   jQuery('#book_id').val(),
            'title':    jQuery('#title').val(),
            'cover':    jQuery('#cover_url').val(),
            'authorid': jQuery('#author_id').val(),
            'author':   jQuery('#author_name').val(),
            'blurb':    jQuery('#summary').val()
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
            'action':  'bc_books_add',
            'nonce':    jQuery('#nonce').val(),
            'referer':  jQuery('#referer').val(),
            'title':    jQuery('#title').val(),
            'cover':    jQuery('#cover_url').val(),
            'authorid': jQuery('#author_id').val(),
            'author':   jQuery('#author_name').val(),
            'blurb':    jQuery('#summary').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            let parms = {action:'edit'};
            parms.bookid = json['book_id'];
            editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
            handle_result(json['error'], json['message'], editurl);
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
});

function edit_book(bookid) {
    let parms = {action:'edit'};
    parms.bookid = bookid;
    editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = editurl;
}

jQuery('.bc_books_bookid').on('click', function(e) {
    edit_book(e.target.id.substring(4));
});

jQuery('.bc_books_title').on('click', function(e) {
    edit_book(e.target.id.substring(6));
});

jQuery('.bc_books_author').on('click', function(e) {
    edit_book(e.target.id.substring(7));
});

function highlight_line(bookid) {
    add_highlight('bid_'    + bookid, 'bc_results_highlight');
    add_highlight('title_'  + bookid, 'bc_results_highlight');
    add_highlight('author_' + bookid, 'bc_results_highlight');
}

function unhighlight_line(bookid) {
    remove_highlight('bid_'    + bookid, 'bc_results_highlight');
    remove_highlight('title_'  + bookid, 'bc_results_highlight');
    remove_highlight('author_' + bookid, 'bc_results_highlight');
}

jQuery('.bc_books_bookid').hover(function (e) {
    highlight_line(e.target.id.substring(4));
}, 
function (e) {
    unhighlight_line(e.target.id.substring(4));
});

jQuery('.bc_books_title').hover(function (e) {
    highlight_line(e.target.id.substring(6));
}, 
function (e) {
    unhighlight_line(e.target.id.substring(6));
});

jQuery('.bc_books_author').hover(function (e) {
    highlight_line(e.target.id.substring(7));
}, 
function (e) {
    unhighlight_line(e.target.id.substring(7));
});
