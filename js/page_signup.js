/*
 * JavaScript used for creating WordPress account on the signup page.
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
        notice.attr('class', "bc_notice notice notice-error");
    } else {
        notice.attr('class', "bc_notice notice notice-success");
    }
    notice.css("visibility", "visible");
    setTimeout(function() {
        notice.css("visibility", "hidden");
        if (redirect) {
            window.location = redirect;
        }}, 5000);
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
            'action':  'bc_signup_help',
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

jQuery('.signup_form').on('submit', function(e) { 
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action': 'bc_signup_submit',
            'nonce':  jQuery('#nonce').val(),
            'pkey':   jQuery('#pkey').val(),
            'login':  jQuery('#user_login').val(),
            'first':  jQuery('#first').val(),
            'last':   jQuery('#last').val(),
            'pass':   jQuery('#user_pass').val(),
            'confirm':jQuery('#user_pass2').val(),
            'email':  jQuery('#user_email').val()
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
});     

jQuery('.signup_link').on('submit', function(e) { 
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action': 'bc_signup_link',
            'nonce':  jQuery('#nonce').val(),
            'uid':    jQuery('#uid').val(),
            'wpid':   jQuery('#wpid').val()
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
});     

jQuery('#button_delete').on('click', function(e) { 
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        jQuery.ajax({
            type: "post",
            url:  bookclub_ajax_object.ajax_url,
            data: {
                'action': 'bc_signup_delete',
                'nonce':  jQuery('#nonce').val(),
                'pkey':   jQuery('#pkey').val()
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
