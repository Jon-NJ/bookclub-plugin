/*
 * JavaScript used for creating WordPress account on the signup page.
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
    }, 5000);
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
    ajax_call('bc_signup_help', {
        'nonce': jQuery('#nonce').val()
    }, json => {
        jQuery('#htmlhelp').html(json['html']);
        jQuery(".bc_help").show();
    });
});

jQuery('#button_create').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_signup_submit', {
        'nonce': jQuery('#nonce').val(),
        'pkey': jQuery('#pkey').val(),
        'login': jQuery('#user_login').val(),
        'first': jQuery('#first').val(),
        'last': jQuery('#last').val(),
        'pass': jQuery('#user_pass').val(),
        'confirm': jQuery('#user_pass2').val(),
        'email': jQuery('#user_email').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

jQuery('#link_account').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_signup_link', {
        'nonce': jQuery('#nonce').val(),
        'uid': jQuery('#uid').val(),
        'wpid': jQuery('#wpid').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

jQuery('#button_delete').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        ajax_call('bc_signup_delete', {
            'nonce': jQuery('#nonce').val(),
            'pkey': jQuery('#pkey').val()
        }, json => {
            handle_result(json['error'], json['message'], json['redirect']);
        });
    }
});
