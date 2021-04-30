/*
 * JavaScript used for editing books page.
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
    ajax_call('bc_books_help', {
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

function validate_form() {
    ajax_call('bc_books_lookup_author', {
        'nonce': jQuery('#nonce').val(),
        'author': jQuery('#author_name').val()
    }, json => {
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
    });
}

jQuery('#author_name').on('input', function (e) {
    validate_form();
});

jQuery(document).ready(function () {
    validate_form();
});

jQuery('#button_search').on('click', function (e) {
    e.preventDefault();
    window.location = create_url(jQuery('#referer').val(), {
        action: 'search',
        bookid: jQuery('#book_id').val(),
        title: jQuery('#title').val(),
        cover: jQuery('#cover_url').val(),
        authorname: jQuery('#author_name').val(),
        summary: jQuery('#summary').val()
    });
});

jQuery('#button_reset').on('click', function (e) {
    e.preventDefault();
    window.location = jQuery('#referer').val();
});

jQuery('#button_delete').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        ajax_call('bc_books_delete', {
            'nonce': jQuery('#nonce').val(),
            'bookid': jQuery('#book_id').val()
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
    ajax_call('bc_books_save', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'bookid': jQuery('#book_id').val(),
        'title': jQuery('#title').val(),
        'cover': jQuery('#cover_url').val(),
        'authorid': jQuery('#author_id').val(),
        'author': jQuery('#author_name').val(),
        'blurb': jQuery('#summary').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

jQuery('#button_add').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_books_add', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'title': jQuery('#title').val(),
        'cover': jQuery('#cover_url').val(),
        'authorid': jQuery('#author_id').val(),
        'author': jQuery('#author_name').val(),
        'blurb': jQuery('#summary').val()
    }, json => {
        let parms = { action: 'edit' };
        parms.bookid = json['book_id'];
        editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
        handle_result(json['error'], json['message'], editurl);
    });
});

function edit_book(bookid) {
    let parms = { action: 'edit' };
    parms.bookid = bookid;
    editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = editurl;
}

jQuery('.bc_books_bookid').on('click', function (e) {
    edit_book(e.target.id.substring(4));
});

jQuery('.bc_books_title').on('click', function (e) {
    edit_book(e.target.id.substring(6));
});

jQuery('.bc_books_author').on('click', function (e) {
    edit_book(e.target.id.substring(7));
});

function highlight_line(bookid) {
    add_highlight('bid_' + bookid, 'bc_results_highlight');
    add_highlight('title_' + bookid, 'bc_results_highlight');
    add_highlight('author_' + bookid, 'bc_results_highlight');
}

function unhighlight_line(bookid) {
    remove_highlight('bid_' + bookid, 'bc_results_highlight');
    remove_highlight('title_' + bookid, 'bc_results_highlight');
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
