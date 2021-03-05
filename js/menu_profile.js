/*
 * JavaScript used for editing profiles page.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @license    https://opensource.org/licenses/MIT MIT
 */
/* global bookclub_ajax_object, epid */

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
    }, 1500);
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
    ajax_call('bc_profile_help', {
        'nonce': jQuery('#nonce').val()
    }, json => {
        jQuery('#htmlhelp').html(json['html']);
        jQuery(".bc_help").show();
    });
});

jQuery('#button_send').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_email_test', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

jQuery('#button_newkey').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#reset_text').val())) {
        ajax_call('bc_reset_key', {
            'nonce': jQuery('#nonce').val()
        }, json => {
            handle_result(json['error'], json['message'], json['redirect']);
            jQuery('#pkey').val(json['newkey']);
        });
    }
});

jQuery('#button_delete').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        ajax_call('bc_profile_delete', {
            'nonce': jQuery('#nonce').val(),
        }, json => {
            handle_result(json['error'], json['message'], json['redirect']);
        });
    }
});

function update_profile(e) {
    let data = {
        'nonce': jQuery('#nonce').val(),
        'pkey': jQuery('#pkey').val(),
        'active': jQuery('[name="active"]:checked').val(),
        'noemail': jQuery('[name="noemail"]:checked').val(),
        'format': jQuery('[name="format"]:checked').val(),
        'ics': jQuery('[name="ics"]:checked').val(),
        'public_email': jQuery('[name="public_email"]:checked').val(),
        'receive': jQuery('[name="receive"]:checked').val()
    };
    jQuery('.groupbox').each(function () {
        data[this.id] = jQuery(this).prop("checked") ? '1' : '';
    });
    ajax_call('bc_profile_update', data,
        json => {
            if (json['upcoming']) {
                jQuery('#upcoming_books').html(json['upcoming']);
            }
            handle_result(json['error'], json['message'], json['redirect']);
        });
}

function update_profile_check(e) {
    let pkey = jQuery('#pkey');
    if (pkey.length) {
        update_profile(e);
    } else {
        let data = {
            'nonce': jQuery('#nonce').val(),
            'receive': jQuery('[name="receive"]:checked').val()
        };
        jQuery('.groupbox').each(function () {
            data[this.id] = jQuery(this).prop("checked") ? '1' : '';
        });
        ajax_call('bc_wordpress_update', {
            'nonce': jQuery('#nonce').val(),
            'receive': jQuery('[name="receive"]:checked').val()
        }, json => {

        });
    }
}

jQuery('[name="active"]').on('change', update_profile);
jQuery('[name="noemail"]').on('change', update_profile);
jQuery('[name="format"]').on('change', update_profile);
jQuery('[name="ics"]').on('change', update_profile);
jQuery('[name="public_email"]').on('change', update_profile);
jQuery('[name="receive"]').on('change', update_profile_check);
jQuery('.groupbox').on('change', update_profile_check);
