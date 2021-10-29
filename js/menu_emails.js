/*
 * JavaScript used for editing emails page.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @license    https://opensource.org/licenses/MIT MIT
 */
/* global bookclub_ajax_object, Symbol */

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
    ajax_call('bc_email_help', {
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

// Search functionality

jQuery('#button_search').on('click', function (e) {
    e.preventDefault();
    window.location = create_url(jQuery('#referer').val(), {
        action: 'search',
        age: jQuery('#age').val(),
        author: jQuery('#author').val(),
        subject: jQuery('#subject').val(),
        body: jQuery('#body').val()
    });
});

jQuery('#button_add').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_emails_add', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'author': jQuery('#author').val(),
        'subject': jQuery('#subject').val(),
        'body': jQuery('#body').val()
    }, json => {
        let error = json['error'];
        let editurl = '';
        if (!error) {
            let parms = { action: 'edit' };
            parms.created = json['created'];
            editurl = jQuery('#referer').val() + '&' +
                jQuery.param(parms);
        }
        handle_result(error, json['message'], editurl);
    });
});

function highlight_line(line) {
    add_highlight('ts_' + line, 'bc_results_highlight');
    add_highlight('author_' + line, 'bc_results_highlight');
    add_highlight('subject_' + line, 'bc_results_highlight');
}

function unhighlight_line(line) {
    remove_highlight('ts_' + line, 'bc_results_highlight');
    remove_highlight('author_' + line, 'bc_results_highlight');
    remove_highlight('subject_' + line, 'bc_results_highlight');
}

jQuery('.bc_emails_timestamp').hover(function (e) {
    highlight_line(e.target.id.substring(3));
},
    function (e) {
        unhighlight_line(e.target.id.substring(3));
    });

jQuery('.bc_emails_author').hover(function (e) {
    highlight_line(e.target.id.substring(7));
},
    function (e) {
        unhighlight_line(e.target.id.substring(7));
    });

jQuery('.bc_emails_subject').hover(function (e) {
    highlight_line(e.target.id.substring(8));
},
    function (e) {
        unhighlight_line(e.target.id.substring(8));
    });

// Edit mode functionality

function get_selected(source) {
    let list = [];
    jQuery('#' + source + ' option:selected').each(function () {
        list.push(this.value);
    });
    return list.join(',');
}

function move_ids(source, source_list, dest_list) {
    let from = jQuery('#' + source_list);
    let to = jQuery('#' + dest_list);
    let from_list = from.val().split(',');
    let to_list = to.val().split(',');
    jQuery('#' + source + ' option:selected').each(function () {
        from_list.splice(from_list.indexOf(this.value), 1);
        to_list.push(this.value);
    });
    from.val(from_list.join(','));
    to.val(to_list.join(','));
}

function move_selected(source, dest) {
    let target = jQuery('#' + dest);
    let merge = jQuery('#' + source + ' option:selected').remove();
    let iterator = target.children()[Symbol.iterator]();
    let result = iterator.next();
    for (let item of merge) {
        while (!result.done && (result.value.text.toUpperCase().localeCompare(
            item.text.toUpperCase()) < 0)) {
            result = iterator.next();
        }
        if (result.done) {
            target.append(item);
        } else {
            result.value.before(item);
        }
    }
    target.change();
    jQuery('#' + source).change();
}

function set_modified() {
    jQuery('#button_save').removeAttr('disabled');
    jQuery('#button_send').attr('disabled', '');
    jQuery('#button_clear').attr('disabled', '');
}

jQuery('#author').on('input', function (e) {
    if ('edit' === jQuery('#mode').val()) {
        set_modified();
        ajax_call('bc_emails_lookup_author', {
            'nonce': jQuery('#nonce').val(),
            'author': jQuery('#author').val()
        }, json => {
            let author_id = jQuery('#authorid');
            if (json['error']) {
                author_id.attr('value', '');
            } else {
                author_id.attr('value', json['authorid']);
            }
        });
    }
});

jQuery('#subject').on('input', function (e) {
    if ('edit' === jQuery('#mode').val()) {
        set_modified();
    }
});

jQuery('#body').on('input', function (e) {
    if ('edit' === jQuery('#mode').val()) {
        set_modified();
    }
});

// Actions

jQuery('#button_reset').on('click', function (e) {
    e.preventDefault();
    window.location = jQuery('#referer').val();
});

jQuery('#button_delete').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        ajax_call('bc_emails_delete', {
            'nonce': jQuery('#nonce').val(),
            'created': jQuery('#created').val()
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
    ajax_call('bc_emails_save', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'created': jQuery('#created').val(),
        'author': jQuery('#author').val(),
        'subject': jQuery('#subject').val(),
        'body': jQuery('#body').val(),
        'yes': jQuery('#yes_data').val(),
        'no': jQuery('#no_data').val()
    }, json => {
        let error = json['error'];
        let url = json['redirect'];
        if (!error) {
            jQuery('#button_save').attr('disabled', '');
        }
        handle_result(error, json['message'], url);
        if (!url) {
            refresh_views(get_active_view());
        }
    });
});

function do_send(list) {
    ajax_call('bc_emails_send_job', {
        'nonce': jQuery('#nonce').val(),
        'created': jQuery('#created').val(),
        'list': list
    }, json => {
        refresh_views('log_view');
    });
}

jQuery('#button_send').on('click', function (e) {
    e.preventDefault();
    do_send('all');
});

jQuery('#button_cancel').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_emails_cancel_job', {
        'nonce': jQuery('#nonce').val(),
        'created': jQuery('#created').val()
    }, json => {
        refresh_views('log_view');
    });
});

jQuery('#button_clear').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#clear_text').val())) {
        ajax_call('bc_emails_clear', {
            'nonce': jQuery('#nonce').val(),
            'created': jQuery('#created').val()
        }, json => {
            refresh_views(get_active_view());
        });
    }
});

// Selection

jQuery('#non_recipients').on('change', function (e) {
    e.preventDefault();
    if (jQuery('#non_recipients option:selected').length > 0) {
        jQuery('#add_recipient').removeAttr('disabled');
    } else {
        jQuery('#add_recipient').attr('disabled', '');
    }
});

jQuery('#oui_recipients').on('change', function (e) {
    e.preventDefault();
    if (jQuery('#oui_recipients option:selected').length > 0) {
        jQuery('#remove_recipient').removeAttr('disabled');
        jQuery('#send_recipient').removeAttr('disabled');
        jQuery('#clear_recipient').removeAttr('disabled');
    } else {
        jQuery('#remove_recipient').attr('disabled', '');
        jQuery('#send_recipient').attr('disabled', '');
        jQuery('#clear_recipient').attr('disabled', '');
    }
});

jQuery('#add_recipient').on('click', function (e) {
    e.preventDefault();
    move_ids('non_recipients', 'no_data', 'yes_data');
    move_selected('non_recipients', 'oui_recipients');
    set_modified();
});

jQuery('#remove_recipient').on('click', function (e) {
    e.preventDefault();
    move_ids('oui_recipients', 'yes_data', 'no_data');
    move_selected('oui_recipients', 'non_recipients');
    set_modified();
});

jQuery('#send_recipient').on('click', function (e) {
    e.preventDefault();
    do_send(get_selected('oui_recipients'));
    jQuery('#remove_recipient').attr('disabled', '');
    jQuery('#send_recipient').attr('disabled', '');
    jQuery('#clear_recipient').attr('disabled', '');
});

jQuery('#clear_recipient').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_events_clear_recipients', {
        'nonce': jQuery('#nonce').val(),
        'created': jQuery('#created').val(),
        'list': get_selected('oui_recipients')
    }, json => {
        refresh_views(get_active_view());
        jQuery('#remove_recipient').attr('disabled', '');
        jQuery('#send_recipient').attr('disabled', '');
        jQuery('#clear_recipient').attr('disabled', '');
    });
});

function group_is_selected(id) {
    let group = jQuery('#' + id);
    return group.attr('class').indexOf('bc_select_group_selected') >= 0;
}

function group_is_voided(id) {
    let group = jQuery('#' + id);
    return group.attr('class').indexOf('bc_select_group_voided') >= 0;
}

function throggle_group(e) {
    e.preventDefault();
    let id = e.target.id;
    if (group_is_selected(id)) {
        remove_highlight(id, 'bc_select_group_selected');
        add_highlight(id, 'bc_select_group_voided');
    } else if (group_is_voided(id)) {
        remove_highlight(id, 'bc_select_group_voided');
    } else {
        add_highlight(id, 'bc_select_group_selected');
    }
}

function toggle_group(e) {
    e.preventDefault();
    let id = e.target.id;
    if (group_is_selected(id)) {
        remove_highlight(id, 'bc_select_group_selected');
    } else {
        add_highlight(id, 'bc_select_group_selected');
    }
}

jQuery('#group').on('change', function (e) {
    e.preventDefault();
    let group = jQuery('#group').val();
    let exclude = jQuery('#exclude');
    if (0 != group) {
        exclude.removeAttr('disabled');
    } else {
        exclude.attr('disabled', '');
    }
});

jQuery('#exclude').on('click', toggle_group);
jQuery('#active').on('click', throggle_group);

function get_state(id) {
    let state = ' ';
    if (group_is_selected(id)) {
        state = '+';
    } else if (group_is_voided(id)) {
        state = '-';
    }
    return state;
}

function fetch_select(id) {
    ajax_call('bc_emails_select', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'created': jQuery('#created').val(),
        'group': jQuery('#group').val(),
        'exclude': group_is_selected('exclude'),
        'active': get_state('active')
    }, json => {
        let error = json['error'];
        if (error) {
            handle_result(error, json['message'], json['redirect']);
        } else {
            jQuery('#' + id).val(json['select']).
                attr('selected', true);
            jQuery('#' + id).change();
        }
    });
}

jQuery('#left').on('click', function (e) {
    e.preventDefault();
    fetch_select('non_recipients');
});

jQuery('#right').on('click', function (e) {
    e.preventDefault();
    fetch_select('oui_recipients');
});

// Views

function view_is_active(view) {
    let elem = jQuery('#' + view);
    let atr = elem.attr('class');
    return atr.indexOf('bc_emails_view_selected') >= 0;
}

function get_active_view() {
    if (view_is_active('raw_view')) {
        return 'raw_view';
    } else if (view_is_active('html_view')) {
        return 'html_view';
    } else if (view_is_active('text_view')) {
        return 'text_view';
    } else if (view_is_active('recipients_view')) {
        return 'recipients_view';
    } // else view_is_active('log_view')
    return 'log_view';
}

jQuery(document).ready(function () {
    if ('edit' === jQuery('#mode').val()) {
        refresh_views('raw_view');
    }
});

function refresh_views(view) {
    let data;

    let oldjobid = jQuery('#jobid').val();
    if (oldjobid) {
        clearTimeout(oldjobid);
        jQuery('#jobid').val('');
    }
    if ('raw_view' === view) {
        data = {
            'nonce': jQuery('#nonce').val(),
            'created': jQuery('#created').val(),
            'view': 'raw'
        };
    } else if ('html_view' === view) {
        jQuery('#get_iframe').submit();
        data = {
            'nonce': jQuery('#nonce').val(),
            'created': jQuery('#created').val(),
            'view': 'html'
        };
    } else if ('text_view' === view) {
        data = {
            'nonce': jQuery('#nonce').val(),
            'created': jQuery('#created').val(),
            'referer': jQuery('#referer').val(),
            'body': jQuery('#body').val(),
            'view': 'text'
        };
    } else if ('recipients_view' === view) {
        data = {
            'nonce': jQuery('#nonce').val(),
            'referer': jQuery('#referer').val(),
            'created': jQuery('#created').val(),
            'yes': jQuery('#yes_data').val(),
            'no': jQuery('#no_data').val(),
            'view': 'recipients'
        };
    } else {    // view_is_active('log_view')
        data = {
            'nonce': jQuery('#nonce').val(),
            'referer': jQuery('#referer').val(),
            'created': jQuery('#created').val(),
            'view': 'log'
        };
    }
    ajax_call('bc_emails_status', data, json => {
        let running = json['running'];
        let sent = json['sent'];
        let unsent = json['unsent'];
        let error = json['error'];
        if (error) {
            handle_result(error, json['message'], json['redirect']);
        }
        if (!running) {
            jQuery('#button_send').removeClass('hide');
            jQuery('#button_cancel').addClass('hide');
        }
        if (running) {
            jQuery('#button_send').attr('disabled', '');
            jQuery('#button_send').addClass('hide');
            jQuery('#button_cancel').removeClass('hide');
            jQuery('#button_clear').attr('disabled', '');
            let jobid = setTimeout(function () {
                refresh_views(get_active_view());
            }, 1000);
            jQuery('#jobid').val(jobid);
        } else if (!jQuery('#button_save').attr('disabled')) {
            jQuery('#button_send').attr('disabled', '');
            jQuery('#button_clear').attr('disabled', '');
        } else {
            if (unsent > 0) {
                jQuery('#button_send').removeAttr('disabled');
            } else {
                jQuery('#button_send').attr('disabled', '');
            }
            if (sent > 0) {
                jQuery('#button_clear').removeAttr('disabled');
            } else {
                jQuery('#button_clear').attr('disabled', '');
            }
        }
        select_view(view);
        if ('raw_view' === view) {
            remove_hide('body');
        } else if ('html_view' === view) {
            remove_hide('showhtml');
        } else if ('text_view' === view) {
            let text = jQuery('#showtext');
            text.text(json['text']);
            remove_hide('showtext');
        } else if ('recipients_view' === view) {
            let not = jQuery('#non_recipients');
            not.html(json['no']);
            let yes = jQuery('#oui_recipients');
            yes.html(json['yes']);
            select_view('recipients_view');
            remove_hide('recipients');
            remove_hide('emails_selection');
        } else {    // view_is_active('log_view')
            let log = jQuery('#showlog');
            log.html(json['log']);
            remove_hide('showlog');
            if (oldjobid) {
                let content = document.getElementById("end_marker");
                content.scrollIntoView(false);
            }
            let saved = jQuery('#button_save').attr('disabled');
            if (saved && json['unsent'] > 0) {
                jQuery('#button_send').removeAttr('disabled');
            }
        }
        jQuery('#oui_recipients').change();
    });
}

function select_view(view) {
    if (view_is_active('raw_view')) {
        add_hide('body');
        remove_highlight('raw_view', 'bc_emails_view_selected');
    } else if (view_is_active('html_view')) {
        add_hide('showhtml');
        remove_highlight('html_view', 'bc_emails_view_selected');
    } else if (view_is_active('text_view')) {
        add_hide('showtext');
        remove_highlight('text_view', 'bc_emails_view_selected');
    } else if (view_is_active('recipients_view')) {
        add_hide('recipients');
        remove_highlight('recipients_view', 'bc_emails_view_selected');
        add_hide('emails_selection');
    } else {    // view_is_active('log_view')
        add_hide('showlog');
        remove_highlight('log_view', 'bc_emails_view_selected');
    }
    add_highlight(view, 'bc_emails_view_selected');
}

jQuery('#raw_view').on('click', function (e) {
    e.preventDefault();
    refresh_views(e.target.id);
});

jQuery('#html_view').on('click', function (e) {
    e.preventDefault();
    refresh_views(e.target.id);
});

jQuery('#text_view').on('click', function (e) {
    e.preventDefault();
    refresh_views(e.target.id);
});

jQuery('#recipients_view').on('click', function (e) {
    e.preventDefault();
    refresh_views(e.target.id);
});

jQuery('#log_view').on('click', function (e) {
    e.preventDefault();
    refresh_views(e.target.id);
});
