/*
 * JavaScript used for editing settings page.
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

jQuery('#help_settings').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_settings_help',
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

function copy_value(source, destination) {
    let value = jQuery('#' + source).val();
    if (value) {
        jQuery('#' + destination).val(value);
    }
}

jQuery('#book_list').on('change', function(e) {
    copy_value(e.target.id, 'page_book');
});

jQuery('#forthcoming_list').on('change', function(e) {
    copy_value(e.target.id, 'page_forthcoming');
});

jQuery('#previous_list').on('change', function(e) {
    copy_value(e.target.id, 'page_previous');
});

jQuery('#rsvp_list').on('change', function(e) {
    copy_value(e.target.id, 'page_rsvp');
});

jQuery('#signup_list').on('change', function(e) {
    copy_value(e.target.id, 'page_signup');
});

jQuery('#save_settings').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':          'bc_settings_save',
            'nonce':            jQuery('#nonce').val(),
            'referer':          jQuery('#referer').val(),
            'defines':          jQuery('#defines').val(),
            'email_backend':    jQuery('[name="email_backend"]:checked').val(),
            'email_params':     jQuery('#email_params').val(),
            'email_headers':    jQuery('#email_headers').val(),
            'email_password':   jQuery('#email_password').val(),
            'email_sleep':      jQuery('#email_sleep').val(),
            'error_sender':     jQuery('#error_sender').val(),
            'error_recipient':  jQuery('#error_recipient').val(),
            'forward_imap':     jQuery('#forward_imap').val(),
            'forward_user':     jQuery('#forward_user').val(),
            'forward_password': jQuery('#forward_password').val(),
            'forward_backend':  jQuery('[name="forward_backend"]:checked').val(),
            'forward_params':   jQuery('#forward_params').val(),
            'forward_headers':  jQuery('#forward_headers').val(),
            'page_book':        jQuery('#page_book').val(),
            'page_forthcoming': jQuery('#page_forthcoming').val(),
            'page_previous':    jQuery('#page_previous').val(),
            'page_rsvp':        jQuery('#page_rsvp').val(),
            'page_signup':      jQuery('#page_signup').val()
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
        handle_result(true, error);
    });
});

jQuery('#settings_test').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':          'bc_settings_test',
            'nonce':            jQuery('#nonce').val(),
            'referer':          jQuery('#referer').val()
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
        handle_result(true, error);
    });
});

jQuery('#forwarder_test').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':          'bc_forwarder_test',
            'nonce':            jQuery('#nonce').val(),
            'referer':          jQuery('#referer').val()
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
        handle_result(true, error);
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

jQuery('.bc_tab').on('click', function(e) {
    select_tab(e.target.id.substring(3));
});
