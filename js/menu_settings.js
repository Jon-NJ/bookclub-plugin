/*
 * JavaScript used for editing settings page.
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

jQuery('#help_settings').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_settings_help', {
        'nonce': jQuery('#nonce').val()
    }, json => {
        jQuery('#htmlhelp').html(json['html']);
        jQuery(".bc_help").show();
    });
});

function copy_value(source, destination) {
    let value = jQuery('#' + source).val();
    if (value) {
        jQuery('#' + destination).val(value);
    }
}

jQuery('#book_list').on('change', function (e) {
    copy_value(e.target.id, 'page_book');
});

jQuery('#forthcoming_list').on('change', function (e) {
    copy_value(e.target.id, 'page_forthcoming');
});

jQuery('#previous_list').on('change', function (e) {
    copy_value(e.target.id, 'page_previous');
});

jQuery('#rsvp_list').on('change', function (e) {
    copy_value(e.target.id, 'page_rsvp');
});

jQuery('#signup_list').on('change', function (e) {
    copy_value(e.target.id, 'page_signup');
});

jQuery('#save_settings').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_settings_save', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'defines': jQuery('#defines').val(),
        'email_backend': jQuery('[name="email_backend"]:checked').val(),
        'email_params': jQuery('#email_params').val(),
        'email_headers': jQuery('#email_headers').val(),
        'email_password': jQuery('#email_password').val(),
        'email_sleep': jQuery('#email_sleep').val(),
        'error_sender': jQuery('#error_sender').val(),
        'error_recipient': jQuery('#error_recipient').val(),
        'forward_imap': jQuery('#forward_imap').val(),
        'forward_user': jQuery('#forward_user').val(),
        'forward_password': jQuery('#forward_password').val(),
        'forward_backend': jQuery('[name="forward_backend"]:checked').val(),
        'forward_params': jQuery('#forward_params').val(),
        'forward_headers': jQuery('#forward_headers').val(),
        'page_book': jQuery('#page_book').val(),
        'page_forthcoming': jQuery('#page_forthcoming').val(),
        'page_previous': jQuery('#page_previous').val(),
        'page_rsvp': jQuery('#page_rsvp').val(),
        'page_signup': jQuery('#page_signup').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

jQuery('#settings_test').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_settings_test', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

jQuery('#forwarder_test').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_forwarder_test', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

function select_tab(newid) {
    let current = jQuery('.bc_tab.bc_selected').attr('id').substring(3);
    if (newid != current) {
        let newtab = jQuery('#tab' + newid);
        newtab.removeClass('bc_unselected');
        newtab.addClass('bc_selected');
        let oldtab = jQuery('#tab' + current);
        oldtab.removeClass('bc_selected');
        oldtab.addClass('bc_unselected');
        let newcontent = jQuery('#content' + newid);
        newcontent.removeClass('hide');
        let oldcontent = jQuery('#content' + current);
        oldcontent.addClass('hide');
    }
}

jQuery('.bc_tab').on('click', function (e) {
    select_tab(e.target.id.substring(3));
});
