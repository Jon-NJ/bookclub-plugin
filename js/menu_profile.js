/*
 * JavaScript used for editing profiles page.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @license    https://opensource.org/licenses/MIT MIT
 */
/* global bookclub_ajax_object, epid */

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
        }}, 1500);
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
            'action': 'bc_profile_help',
            'nonce':  jQuery('#nonce').val()
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

jQuery('#button_send').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_email_test',
            'nonce':   jQuery('#nonce').val(),
            'referer': jQuery('#referer').val()
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
        handle_result(true, error);
    });
});

jQuery('#button_newkey').on('click', function(e) {
    e.preventDefault();
    if (confirm(jQuery('#reset_text').val())) {
        jQuery.ajax({
            type: "post",
            url:  bookclub_ajax_object.ajax_url,
            data: {
                'action': 'bc_reset_key',
                'nonce':  jQuery('#nonce').val()
            }
        })
        .done(function(data) {
            let json = jQuery.parseJSON(data);
            handle_result(json['error'], json['message']);
            jQuery('#pkey').val(json['newkey']);
        })
        .fail(function(jqXHR, text, error) {
            console.log(error);
            handle_result(true, error);
        });
    }
});

jQuery('#button_delete').on('click', function(e) { 
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        jQuery.ajax({
            type: "post",
            url:  bookclub_ajax_object.ajax_url,
            data: {
                'action': 'bc_profile_delete',
                'nonce':  jQuery('#nonce').val(),
            }
        })
        .done(function(data) {
            if (data) {
                let json = jQuery.parseJSON(data);
                handle_result(json['error'], json['message'], json['redirect']);
            }
        })
        .fail(function(jqXHR, text, error) {
            console.log(error);
        });
    }
});     

function update_profile(e) {
    let data = {
        'action':       'bc_profile_update',
        'nonce':        jQuery('#nonce').val(),
        'pkey':         jQuery('#pkey').val(),
        'active':       jQuery('[name="active"]:checked').val(),
        'noemail':      jQuery('[name="noemail"]:checked').val(),
        'format':       jQuery('[name="format"]:checked').val(),
        'ics':          jQuery('[name="ics"]:checked').val(),
        'public_email': jQuery('[name="public_email"]:checked').val(),
        'receive':      jQuery('[name="receive"]:checked').val()
    };
    jQuery('.groupbox').each(function() {
        data[this.id] = jQuery(this).prop("checked") ? '1' : '';
    });
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: data
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            if (json['upcoming']) {
                jQuery('#upcoming_books').html(json['upcoming']);
            }
            handle_result(json['error'], json['message']);
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
}

function update_profile_check(e) {
    let pkey = jQuery('#pkey');
    if (pkey.length) {
        update_profile(e);
    } else {
        let data = {
            'action':  'bc_wordpress_update',
            'nonce':   jQuery('#nonce').val(),
            'receive': jQuery('[name="receive"]:checked').val()
        };
        jQuery('.groupbox').each(function() {
            data[this.id] = jQuery(this).prop("checked") ? '1' : '';
        });
        jQuery.ajax({
            type: "post",
            url:  bookclub_ajax_object.ajax_url,
            data: data
        })
        .done(function(data) {
            if (data) {
            }
        })
        .fail(function(jqXHR, text, error) {
            console.log(error);
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

function refreshNonce() {
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_profile_heartbeat',
            'nonce':    jQuery('#nonce').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            handle_result(json['error'], json['message']);
            jQuery('#nonce').val(json['nonce']);
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
}

setInterval(refreshNonce, 300000);
