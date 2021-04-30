/*
 * JavaScript used for chat page.
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

var timerId = null;

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
    // (almost) all ajax calls result from a one-shot timer, it must be restarted
    if ('bc_chat_refresh' === action) {
        timerId = setTimeout(refreshChat, jQuery('#timeout').val());
    }
}

jQuery('#close_help').on('click', function (e) {
    e.preventDefault();
    jQuery(".bc_help").hide();
});

jQuery('#button_help').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_chat_help', {
        'nonce': jQuery('#nonce').val()
    }, json => {
        jQuery('#htmlhelp').html(json['html']);
        jQuery(".bc_help").show();
    });
});

function isElementInViewport(el) {
    var rect = el.getBoundingClientRect();
    var windowHeight = (window.innerHeight || document.documentElement.clientHeight);
    var windowWidth = (window.innerWidth || document.documentElement.clientWidth);

    return (
        (rect.left >= 0)
        && (rect.top >= 0)
        && ((rect.left + rect.width) <= windowWidth)
        && ((rect.top + rect.height) <= windowHeight)
    );
}

function updateChat(html, last, timeout, deleted, newitem) {
    jQuery('#bc_chat_new').replaceWith(html);
    jQuery('#last').val(last);
    jQuery('#timeout').val(timeout);
    for (let item of deleted) {
        jQuery(`#msg_${item}`).html(jQuery('#delete_message').html());
        let button = jQuery(`#delete_${item}`);
        button.removeClass('delete_no');
        button.removeClass('delete_yes');
    }
    let $end = jQuery('#bc_chat_new')[0];
    if (newitem && isElementInViewport(jQuery('#chat')[0])) {
        console.log('scroll into view');
        $end.scrollIntoView(false);
    }
}

function refreshChat() {
    ajax_call('bc_chat_refresh', {
        'nonce': jQuery('#nonce').val(),
        'type': jQuery('#type').val(),
        'target': jQuery('#target').val(),
        'last': jQuery('#last').val()
    }, json => {
        if (json['error']) {
            handle_result(json['error'], json['message'], json['redirect']);
        } else {
            updateChat(json['html'], json['last'], json['timeout'], json['deleted'], json['new']);
        }
    });
}

jQuery('#book_title').on('input', function (e) {
    let title = jQuery("#book_title").val();
    let options = jQuery("#book_list option");
    let url = '';
    for (let item of options) {
        if (title === item.value) {
            url = item.getAttribute('url');
            break;
        }
    }
    if (url) {
        window.location = url;
    }
});

jQuery('#user_name').on('input', function (e) {
    let name = jQuery("#user_name").val();
    let options = jQuery("#user_list option");
    let url = '';
    for (let item of options) {
        if (name === item.value) {
            url = item.getAttribute('url');
            break;
        }
    }
    if (url) {
        window.location = url;
    }
});

jQuery('.delete_yes').on('click', function (e) {
    let t = e.target.id.substring(7);
    clearTimeout(timerId);
    ajax_call('bc_chat_refresh', {
        'nonce': jQuery('#nonce').val(),
        'type': jQuery('#type').val(),
        'target': jQuery('#target').val(),
        'last': jQuery('#last').val(),
        'delete_id': t
    }, json => {
        if (json['error']) {
            handle_result(json['error'], json['message'], json['redirect']);
        } else {
            jQuery('#chat').val('');
            updateChat(json['html'], json['last'], json['timeout'], json['deleted'], json['new']);
        }
    });
});

jQuery(document).on('keypress', function (e) {
    let keycode = (e.keyCode ? e.keyCode : e.which);
    if (13 == keycode) {
        clearTimeout(timerId);
        ajax_call('bc_chat_refresh', {
            'nonce': jQuery('#nonce').val(),
            'type': jQuery('#type').val(),
            'target': jQuery('#target').val(),
            'last': jQuery('#last').val(),
            'message': jQuery('#chat').val()
        }, json => {
            if (json['error']) {
                handle_result(json['error'], json['message'], json['redirect']);
            } else {
                jQuery('#chat').val('');
                updateChat(json['html'], json['last'], json['timeout'], json['deleted'], json['new']);
            }
        });
    }
});

function handleDocHeightMsg(e) {
    console.log('handle doc height msg');
    let origin = location.protocol + '//' + window.location.hostname;
    if (e.origin === origin) {
        let data = JSON.parse(e.data);
        let ht = data['docHeight'];
        console.log(`height ${ht}`);
        let container = document.getElementById('iframe_container');
        container.style.height = ht + 4 + "px";
    }
}

jQuery(document).ready(function () {
    let type = jQuery('#type').val();
    console.log(`Type ${type}`);
    if (0 != type) {    // not main chat
        console.log('Initial start timeout');
        timerId = setTimeout(refreshChat, jQuery('#timeout').val());
    }
    if (4 == type) {    // BC_CHAT_TARGET_EVENT
        // assign message handler
        if (window.addEventListener) {
            window.addEventListener('message', handleDocHeightMsg, false);
        } else if (window.attachEvent) { // ie8
            window.attachEvent('onmessage', handleDocHeightMsg);
        }
        jQuery('#get_iframe').submit();
    }
});
