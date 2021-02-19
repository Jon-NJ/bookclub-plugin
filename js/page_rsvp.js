/*
 * JavaScript used for editing rsvp page.
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
            'action':  'bc_rsvp_help',
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

jQuery('#button_send').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_rsvp_resend',
            'eid':      jQuery('#eid').val(),
            'personid': jQuery('#personid').val(),
            'webkey':   jQuery('#webkey').val(),
            'nonce':    jQuery('#nonce').val(),
            'referer':  jQuery('#referer').val()
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

function bc_rsvp(e) {
    e.preventDefault();
    let status;
    let name = e.target.id;
    if ('button_yes' === name) {
        status = 'yes';
    } else if ('button_no' === name) {
        status = 'no';
    } else {
        status = 'maybe';
    }
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_rsvp_update',
            'eid':      jQuery('#eid').val(),
            'personid': jQuery('#personid').val(),
            'webkey':   jQuery('#webkey').val(),
            'comment':  jQuery('#comment').val(),
            'nonce':    jQuery('#nonce').val(),
            'status':   status,
            'referer':  jQuery('#referer').val()
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

jQuery('#button_no').on('click', bc_rsvp);     
jQuery('#button_yes').on('click', bc_rsvp);     
jQuery('#button_maybe').on('click', bc_rsvp);

jQuery('#button_ical').on('click', function(e) {
    e.preventDefault();
    let url = e.target.getAttribute('href');
    window.location = url;
});

function setIframeHeightCO(id, ht) {
    let ifrm = document.getElementById(id);
    // some IE versions need a bit added or scrollbar appears
    ifrm.style.height = ht + 4 + "px";
}

function handleDocHeightMsg(e) {
    let origin = location.protocol + '//' + window.location.hostname;
    if (e.origin === origin) {
        let data = JSON.parse(e.data);
        let ht   = data['docHeight'];
        let container = document.getElementById('iframe_container');
        container.style.height = ht + 4 + "px";
    }
}

// assign message handler
if (window.addEventListener) {
    window.addEventListener('message', handleDocHeightMsg, false);
} else if (window.attachEvent) { // ie8
    window.attachEvent('onmessage', handleDocHeightMsg);
}

jQuery(document).ready(function() {
    console.log('get iframe');
    jQuery('#get_iframe').submit();
});

function refreshRSVP() {
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_rsvp_heartbeat',
            'nonce':    jQuery('#nonce').val(),
            'eid':      jQuery('#eid').val(),
            'personid': jQuery('#personid').val(),
            'webkey':   jQuery('#webkey').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            handle_result(json['error'], json['message']);
            jQuery('#who').html(json['html']);
            jQuery('#nonce').val(json['nonce']);
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
}

setInterval(refreshRSVP, 60000);
