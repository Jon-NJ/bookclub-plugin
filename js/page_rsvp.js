/*
 * JavaScript used for editing rsvp page.
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

jQuery('#button_help').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_rsvp_help', {
        'nonce': jQuery('#nonce').val()
    }, json => {
        jQuery('#htmlhelp').html(json['html']);
        jQuery(".bc_help").show();
    });
});

jQuery('#button_send').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_rsvp_resend', {
        'eid': jQuery('#eid').val(),
        'personid': jQuery('#personid').val(),
        'webkey': jQuery('#webkey').val(),
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

function bc_rsvp(e) {
    e.preventDefault();
    t = e.target;
    if ('img' === t.localName) {
        t = t.parentElement;
    }
    let status;
    let name = t.id;
    if ('button_yes' === name) {
        status = 'yes';
    } else if ('button_no' === name) {
        status = 'no';
    } else if ('button_maybe' === name) {
        status = 'maybe';
    }
    ajax_call('bc_rsvp_update', {
        'eid': jQuery('#eid').val(),
        'personid': jQuery('#personid').val(),
        'webkey': jQuery('#webkey').val(),
        'comment': jQuery('#comment').val(),
        'nonce': jQuery('#nonce').val(),
        'status': status,
        'referer': jQuery('#referer').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
}

jQuery('#button_no').on('click', bc_rsvp);
jQuery('#button_yes').on('click', bc_rsvp);
jQuery('#button_maybe').on('click', bc_rsvp);

jQuery('#button_ical').on('click', function (e) {
    e.preventDefault();
    t = e.target;
    if ('img' === t.localName) {
        t = t.parentElement;
    }
    let url = t.getAttribute('href');
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
        let ht = data['docHeight'];
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

jQuery(document).ready(function () {
    jQuery('#get_iframe').submit();
});

function refreshRSVP() {
    ajax_call('bc_rsvp_heartbeat', {
        'nonce': jQuery('#nonce').val(),
        'eid': jQuery('#eid').val(),
        'personid': jQuery('#personid').val(),
        'webkey': jQuery('#webkey').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
        jQuery('#who').html(json['html']);
        jQuery('#nonce').val(json['nonce']);
    });
}

setInterval(refreshRSVP, 60000);
