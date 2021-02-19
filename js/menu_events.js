/*
 * JavaScript used for editing events page.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @license    https://opensource.org/licenses/MIT MIT
 */
/* global bookclub_ajax_object, Symbol */

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
            'action':  'bc_events_help',
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

jQuery('#button_search').on('click', function(e) {
    e.preventDefault();
    let parms = { action:'search' };
    let eventid = jQuery('#eventid').val();
    if ('' !== eventid) {
        parms.eventid = eventid;
    }
    let datetime = jQuery('#datetime').val();
    if ('' !== datetime) {
        parms.datetime = datetime;
    }
    let age = jQuery('#age').val();
    if ('' !== age) {
        parms.age = age;
    }
    let what = jQuery('#what').val();
    if ('' !== what) {
        parms.what = what;
    }
    let where = jQuery('#where').val();
    if ('' !== where) {
        parms.where = where;
    }
    let body = jQuery('#body').val();
    if ('' !== body) {
        parms.body = body;
    }
    let map = jQuery('#map').val();
    if ('' !== map) {
        parms.map = map;
    }
    searchurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = searchurl;
});

function edit_event(eventid) {
    let parms = {action:'edit'};
    let id = jQuery('#id_' + eventid).text();
    parms.eventid  = id;
    editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = editurl;
}

jQuery('.bc_events_id').on('click', function(e) {
    edit_event(e.target.id.substring(3));
});

jQuery('.bc_events_private').on('click', function(e) {
    t = e.target;
    if ('img' === t.localName) {
        t = t.parentElement;
    }
    edit_event(t.id.substring(8));
});

jQuery('.bc_events_priority').on('click', function(e) {
    edit_event(e.target.id.substring(9));
});

jQuery('.bc_events_start').on('click', function(e) {
    edit_event(e.target.id.substring(6));
});

jQuery('.bc_events_subject').on('click', function(e) {
    edit_event(e.target.id.substring(8));
});

jQuery('#button_add').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_events_add',
            'nonce':    jQuery('#nonce').val(),
            'referer':  jQuery('#referer').val(),
            'eventid':  jQuery('#eventid').val(),
            'datetime': jQuery('#datetime').val(),
            'what':     jQuery('#what').val(),
            'where':    jQuery('#where').val(),
            'map':      jQuery('#map').val(),
            'body':     jQuery('#body').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json    = jQuery.parseJSON(data);
            let error   = json['error'];
            let editurl = '';
            if (!error) {
                let parms     = {action:'edit'};
                parms.eventid = json['eventid'];
                editurl       = jQuery('#referer').val() + '&' +
                        jQuery.param(parms);
            }
            handle_result(error, json['message'], editurl);
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
        handle_result(true, error);
    });
});

function highlight_line(id) {
    add_highlight('id_'       + id, 'bc_results_highlight');
    add_highlight('private_'  + id, 'bc_results_highlight');
    add_highlight('priority_' + id, 'bc_results_highlight');
    add_highlight('start_'    + id, 'bc_results_highlight');
    add_highlight('subject_'  + id, 'bc_results_highlight');
}

function unhighlight_line(id) {
    remove_highlight('id_'       + id, 'bc_results_highlight');
    remove_highlight('private_'  + id, 'bc_results_highlight');
    remove_highlight('priority_' + id, 'bc_results_highlight');
    remove_highlight('start_'    + id, 'bc_results_highlight');
    remove_highlight('subject_'  + id, 'bc_results_highlight');
}

jQuery('.bc_events_id').hover(function (e) {
    highlight_line(e.target.id.substring(3));
},
function (e) {
    unhighlight_line(e.target.id.substring(3));
});

jQuery('.bc_events_private').hover(function (e) {
    t = e.target;
    if ('img' === t.localName) {
        t = t.parentElement;
    }
    highlight_line(t.id.substring(8));
},
function (e) {
    t = e.target;
    if ('img' === t.localName) {
        t = t.parentElement;
    }
    unhighlight_line(t.id.substring(8));
});

jQuery('.bc_events_priority').hover(function (e) {
    highlight_line(e.target.id.substring(9));
},
function (e) {
    unhighlight_line(e.target.id.substring(9));
});

jQuery('.bc_events_start').hover(function (e) {
    highlight_line(e.target.id.substring(6));
},
function (e) {
    unhighlight_line(e.target.id.substring(6));
});

jQuery('.bc_events_subject').hover(function (e) {
    highlight_line(e.target.id.substring(8));
},
function (e) {
    unhighlight_line(e.target.id.substring(8));
});

// Edit mode functionality

function update_priority() {
    let priority = jQuery('#priority');
    let dt       = Date.parse(jQuery('#datetime').val());
    let hours    = parseInt(priority.val());
    if (dt !== dt || hours !== hours || 0 == hours) {
        add_hide('prior_line');
    } else {
        dt = new Date(dt - (hours * 3600000) -
                (new Date().getTimezoneOffset() * 60000));
        jQuery('#prior').html(dt.toISOString().slice(0, 19).replace('T', ' '));
        remove_hide('prior_line');
    }
}

jQuery('#datetime').on('change', function(e) {
    e.preventDefault();
    update_priority();
});

jQuery('#priority').on('change', function(e) {
    e.preventDefault();
    update_priority();
});

function get_selected(source) {
    let list = [];
    jQuery('#' + source + ' option:selected').each(function() {
        list.push(this.value);
    });
    return list.join(',');
}

function move_ids(source, source_list, dest_list) {
    let from      = jQuery('#' + source_list);
    let to        = jQuery('#' + dest_list);
    let from_list = from.val().split(',');
    let to_list   = to.val().split(',');
    jQuery('#' + source + ' option:selected').each(function() {
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

jQuery('#eventid').on('input', function(e) {
    if ('edit' === jQuery('#mode').val()) {
        set_modified();
    }
});

jQuery('#max').on('input', function(e) {
    set_modified();
});

jQuery('#datetime').on('input', function(e) {
    if ('edit' === jQuery('#mode').val()) {
        set_modified();
    }
});

jQuery('#endtime').on('input', function(e) {
    set_modified();
});

jQuery('#what').on('input', function(e) {
    if ('edit' === jQuery('#mode').val()) {
        set_modified();
    }
});

jQuery('#where').on('input', function(e) {
    if ('edit' === jQuery('#mode').val()) {
        set_modified();
    }
});

jQuery('#private').on('click', function(e) {
    if ('edit' === jQuery('#mode').val()) {
        set_modified();
    }
});

jQuery('#priority').on('input', function(e) {
    if ('edit' === jQuery('#mode').val()) {
        set_modified();
    }
});

function set_map_link(href) {
    let link = jQuery('#map_link');
    link.attr("href", href);
    if (('http://' === href.substring(0, 7)) ||
        ('https://' === href.substring(0, 8))) {
        link.removeAttr('onclick');
    } else {
        link.attr('onclick', 'return false');
    }
}

jQuery('#map').on('input', function(e) {
    let href = jQuery('#map').val();
    if (href.trim() !== href) {
        href = href.trim();
        jQuery('#map').val(href);
    }
    set_map_link(href);
    if ('edit' === jQuery('#mode').val()) {
        set_modified();
    }
});

jQuery('#body').on('input', function(e) {
    if ('edit' === jQuery('#mode').val()) {
        set_modified();
    }
});

// Actions

jQuery('#button_reset').on('click', function(e) {
    e.preventDefault();
    window.location = jQuery('#referer').val();
});

jQuery('#button_delete').on('click', function(e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        jQuery.ajax({
            type: "post",
            url:  bookclub_ajax_object.ajax_url,
            data: {
                'action':  'bc_events_delete',
                'nonce':   jQuery('#nonce').val(),
                'eventid': jQuery('#eventid').val()
            }
        })
        .done(function(data) {
            if (data) {
                let json = jQuery.parseJSON(data);
                if (json['error']) {
                    handle_result(json['error'], json['message']);
                } else {
                    handle_result(json['error'], json['message'],
                        jQuery('#referer').val());
                }
            }
        })
        .fail(function(jqXHR, text, error) {
            console.log(error);
            handle_result(true, error);
        });
    }
});

jQuery('#button_save').on('click', function(e) {
    e.preventDefault();
    if (('0' === jQuery('#sent').val()) ||
        (jQuery('#original_eventid').val() === jQuery('#eventid').val()) ||
        confirm(jQuery('#idwarning_text').val()))
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':      'bc_events_save',
            'nonce':       jQuery('#nonce').val(),
            'referer':     jQuery('#referer').val(),
            'eventid':     jQuery('#original_eventid').val(),
            'new_eventid': jQuery('#eventid').val(),
            'max':         jQuery('#max').val(),
            'yes':         jQuery('#yes_data').val(),
            'no':          jQuery('#no_data').val(),
            'starttime':   jQuery('#datetime').val(),
            'endtime':     jQuery('#endtime').val(),
            'private':     jQuery('[name="private"]:checked').val(),
            'priority':    jQuery('#priority').val(),
            'what':        jQuery('#what').val(),
            'where':       jQuery('#where').val(),
            'map':         jQuery('#map').val(),
            'body':        jQuery('#body').val()
        }
    })
    .done(function(data) {
        let json    = jQuery.parseJSON(data);
        let error   = json['error'];
        let url     = json['redirect'];
        if (!error) {
            jQuery('#button_save').attr('disabled', '');
        }
        handle_result(error, json['message'], url);
        if (!url) {
            refresh_views(get_active_view());
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
        handle_result(true, error);
    });
});

function do_send(list) {
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_events_send_job',
            'nonce':   jQuery('#nonce').val(),
            'eventid': jQuery('#eventid').val(),
            'list':    list
        }
    })
    .done(function(data) {
        refresh_views('log_view', 'log_email');
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
        handle_result(true, error);
    });
}

jQuery('#button_send').on('click', function(e) {
    e.preventDefault();
    do_send('all');
});

jQuery('#button_clear').on('click', function(e) {
    e.preventDefault();
    if (confirm(jQuery('#clear_text').val())) {
        jQuery.ajax({
            type: "post",
            url:  bookclub_ajax_object.ajax_url,
            data: {
                'action':  'bc_events_clear',
                'nonce':   jQuery('#nonce').val(),
                'eventid': jQuery('#eventid').val()
            }
        })
        .done(function(data) {
            refresh_views(get_active_view());
        })
        .fail(function(jqXHR, text, error) {
            console.log(error);
            handle_result(true, error);
        });
    }
});

// Selection

jQuery('#non_participants').on('change', function(e) {
    e.preventDefault();
    if (jQuery('#non_participants option:selected').length > 0) {
        jQuery('#add_participant').removeAttr('disabled');
    } else {
        jQuery('#add_participant').attr('disabled', '');
    }
});

jQuery('#oui_participants').on('change', function(e) {
    e.preventDefault();
    if (jQuery('#oui_participants option:selected').length > 0) {
        jQuery('#remove_participant').removeAttr('disabled');
        jQuery('#send_participant').removeAttr('disabled');
        jQuery('#clear_participant').removeAttr('disabled');
    } else {
        jQuery('#remove_participant').attr('disabled', '');
        jQuery('#send_participant').attr('disabled', '');
        jQuery('#clear_participant').attr('disabled', '');
    }
});

jQuery('#add_participant').on('click', function(e) {
    e.preventDefault();
    move_ids('non_participants', 'no_data', 'yes_data');
    move_selected('non_participants', 'oui_participants');
    set_modified();
});

jQuery('#remove_participant').on('click', function(e) {
    e.preventDefault();
    move_ids('oui_participants', 'yes_data', 'no_data');
    move_selected('oui_participants', 'non_participants');
    set_modified();
});

jQuery('#send_participant').on('click', function(e) {
    e.preventDefault();
    do_send(get_selected('oui_participants'));
    jQuery('#remove_participant').attr('disabled', '');
    jQuery('#send_participant').attr('disabled', '');
    jQuery('#clear_participant').attr('disabled', '');
});

jQuery('#clear_participant').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_events_clear_participants',
            'nonce':   jQuery('#nonce').val(),
            'eventid': jQuery('#eventid').val(),
            'list':    get_selected('oui_participants')
        }
    })
    .done(function(data) {
        refresh_views(get_active_view());
        jQuery('#remove_participant').attr('disabled', '');
        jQuery('#send_participant').attr('disabled', '');
        jQuery('#clear_participant').attr('disabled', '');
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
        handle_result(true, error);
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

jQuery('#group').on('change', function(e) {
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
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_events_select',
            'nonce':   jQuery('#nonce').val(),
            'referer': jQuery('#referer').val(),
            'eventid': jQuery('#eventid').val(),
            'group':   jQuery('#group').val(),
            'exclude': group_is_selected('exclude'),
            'active':  get_state('active')
        }
    })
    .done(function(data) {
        if (data) {
            let json    = jQuery.parseJSON(data);
            let error   = json['error'];
            if (error) {
                handle_result(error, json['message']);
            } else {
                jQuery('#' + id).val(json['select']).
                        attr('selected', true);
                jQuery('#' + id).change();
            }
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
        handle_result(true, error);
    });
}

jQuery('#left').on('click', function(e) {
    e.preventDefault();
    fetch_select('non_participants');
});

jQuery('#right').on('click', function(e) {
    e.preventDefault();
    fetch_select('oui_participants');
});

// Views

jQuery(document).ready(function() {
    let href = jQuery('#map').val();
    set_map_link(href);
    if ('edit' === jQuery('#mode').val()) {
        refresh_views('raw_view');
    }
});

function view_is_selected(view) {
    let elem = jQuery('#' + view);
    let atr = elem.attr('class');
    return atr.indexOf('bc_events_view_selected') >= 0;
}

function get_active_view() {
    if (view_is_selected('raw_view')) {
        return 'raw_view';
    } else if (view_is_selected('html_view')) {
        return 'html_view';
    } else if (view_is_selected('text_view')) {
        return 'text_view';
    } else if (view_is_selected('participants_view')) {
        return 'participants_view';
    } else if (view_is_selected('log_view')) {
        return 'log_view';
    } // else view_is_selected('rsvp_view')
    return 'rsvp_view';
}

jQuery(document).ready(function() {
    if ('edit' === jQuery('#mode').val()) {
        refresh_views('raw_view');
    }
});

function refresh_views(view, subview) {
    let data;

    let oldjobid = jQuery('#jobid').val();
    if (oldjobid) {
        clearTimeout(oldjobid);
        jQuery('#jobid').val('');
    }
    if ('raw_view' === view) {
        data = {
            'action':  'bc_events_status',
            'nonce':   jQuery('#nonce').val(),
            'eventid': jQuery('#eventid').val(),
            'view':    'raw'
        };
    } else if ('html_view' === view) {
        jQuery('#get_iframe').submit();
        data = {
            'action':  'bc_events_status',
            'nonce':   jQuery('#nonce').val(),
            'eventid': jQuery('#eventid').val(),
            'view':    'html'
        };
    } else if ('text_view' === view) {
        data = {
            'action':  'bc_events_status',
            'nonce':   jQuery('#nonce').val(),
            'eventid': jQuery('#eventid').val(),
            'referer': jQuery('#referer').val(),
            'body':    jQuery('#body').val(),
            'view':    'text'
        };
    } else if ('participants_view' === view) {
        data = {
            'action':  'bc_events_status',
            'nonce':   jQuery('#nonce').val(),
            'referer': jQuery('#referer').val(),
            'eventid': jQuery('#eventid').val(),
            'yes':     jQuery('#yes_data').val(),
            'no':      jQuery('#no_data').val(),
            'view':    'participants'
        };
    } else if ('log_view' === view) {
        data = {
            'action':  'bc_events_status',
            'nonce':   jQuery('#nonce').val(),
            'referer': jQuery('#referer').val(),
            'eventid': jQuery('#eventid').val(),
            'view':    'log'
        };
    } else { // 'rsvp_view' === view
        data = {
            'action':  'bc_events_status',
            'nonce':   jQuery('#nonce').val(),
            'referer': jQuery('#referer').val(),
            'eventid': jQuery('#eventid').val(),
            'view':    'rsvp'
        };
    }
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: data
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            let running = json['running'];
            let sent    = json['sent'];
            let unsent  = json['unsent'];
            let error   = json['error'];
            if (error) {
                handle_result(error, json['message']);
            }
            if (running) {
                jQuery('#button_send').attr('disabled', '');
                jQuery('#button_clear').attr('disabled', '');
                let jobid = setTimeout(function() {
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
                remove_hide('show_private');
            } else if ('html_view' === view) {
                remove_hide('showhtml');
                remove_hide('show_private');
            } else if ('text_view' === view) {
                let text = jQuery('#showtext');
                text.text(json['text']);
                remove_hide('showtext');
                remove_hide('show_private');
            } else if ('participants_view' === view) {
                let not = jQuery('#non_participants');
                not.html(json['no']);
                let yes = jQuery('#oui_participants');
                yes.html(json['yes']);
                add_hide('show_private');
                remove_hide('participants');
                remove_hide('events_selection');
            } else if ('log_view' === view) {
                if ('log_email' === subview) {
                    remove_highlight('log_rsvp', 'bc_select_group_selected');
                    add_highlight('log_email', 'bc_select_group_selected');
                    add_hide('rsvplog');
                }
                add_hide('show_private');
                let log = jQuery('#sendlog');
                log.html(json['log']);
                let history = jQuery('#rsvplog');
                history.html(json['rsvp']);
                if (group_is_selected('log_email')) {
                    remove_hide('sendlog');
                } else {
                    remove_hide('rsvplog');
                }
                remove_hide('log_selection');
                if (oldjobid && group_is_selected('log_email')) {
                    let content = document.getElementById("end_marker");
                    content.scrollIntoView(false);
                }
                let saved = jQuery('#button_save').attr('disabled');
                if (saved && json['unsent'] > 0) {
                    jQuery('#button_send').removeAttr('disabled');
                }
            } else {    // 'rsvp_view' === view
                let rsvp = jQuery('#invited_list');
                rsvp.html(json['rsvp']);
                let waiting = jQuery('#rsvp_waiting');
                waiting.html(json['waiting']);
                remove_hide('show_private');
                let next = jQuery('#rsvp_next');
                if (0 === json['wait_count']) {
                    next.attr('disabled', '');
                } else {
                    next.removeAttr('disabled');
                }
                remove_hide('showrsvp');
            }
            jQuery('#oui_participants').change();
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
        handle_result(true, error);
    });
}

function select_view(view) {
    if (view_is_selected('raw_view')) {
        add_hide('body');
        remove_highlight('raw_view', 'bc_events_view_selected');
    } else if (view_is_selected('html_view')) {
        add_hide('showhtml');
        remove_highlight('html_view', 'bc_events_view_selected');
    } else if (view_is_selected('text_view')) {
        add_hide('showtext');
        remove_highlight('text_view', 'bc_events_view_selected');
    } else if (view_is_selected('participants_view')) {
        add_hide('participants');
        remove_highlight('participants_view', 'bc_events_view_selected');
        add_hide('events_selection');
    } else if (view_is_selected('log_view')) {
        add_hide('sendlog');
        add_hide('rsvplog');
        add_hide('log_selection');
        remove_highlight('log_view', 'bc_events_view_selected');
    } else {    // view_is_selected('rsvp_view')
        add_hide('showrsvp');
        remove_highlight('rsvp_view', 'bc_events_view_selected');
    }
    add_highlight(view, 'bc_events_view_selected');
}

jQuery('#raw_view').on('click', function(e) {
    e.preventDefault();
    refresh_views(e.target.id);
});

jQuery('#html_view').on('click', function(e) {
    e.preventDefault();
    refresh_views(e.target.id);
});

jQuery('#text_view').on('click', function(e) {
    e.preventDefault();
    refresh_views(e.target.id);
});

jQuery('#participants_view').on('click', function(e) {
    e.preventDefault();
    refresh_views(e.target.id);
});

jQuery('#log_view').on('click', function(e) {
    e.preventDefault();
    refresh_views(e.target.id);
});

jQuery('#rsvp_view').on('click', function(e) {
    e.preventDefault();
    refresh_views(e.target.id);
});

jQuery('#log_email').on('click', function(e) {
    e.preventDefault();
    remove_highlight('log_rsvp', 'bc_select_group_selected');
    add_highlight(e.target.id, 'bc_select_group_selected');
    remove_hide('sendlog');
    add_hide('rsvplog');
});

jQuery('#log_rsvp').on('click', function(e) {
    e.preventDefault();
    remove_highlight('log_email', 'bc_select_group_selected');
    add_highlight(e.target.id, 'bc_select_group_selected');
    remove_hide('rsvplog');
    add_hide('sendlog');
});

/** RSVP view */

jQuery('#invited_list').on('change', function() {
    let item = jQuery('#invited_list option:selected');
    if (item.hasClass('bc_rsvp_yes')) {
        jQuery('#rsvp_yes').attr('disabled', '');
        jQuery('#rsvp_no').removeAttr('disabled');
        jQuery('#rsvp_maybe').removeAttr('disabled');
    } else if (item.hasClass('bc_rsvp_no')) {
        jQuery('#rsvp_yes').removeAttr('disabled');
        jQuery('#rsvp_no').attr('disabled', '');
        jQuery('#rsvp_maybe').removeAttr('disabled');
    } else if (item.hasClass('bc_rsvp_maybe')) {
        jQuery('#rsvp_yes').removeAttr('disabled');
        jQuery('#rsvp_no').removeAttr('disabled');
        jQuery('#rsvp_maybe').attr('disabled', '');
    } else if (item.hasClass('bc_rsvp_no_response')) {
        jQuery('#rsvp_yes').removeAttr('disabled');
        jQuery('#rsvp_no').removeAttr('disabled');
        jQuery('#rsvp_maybe').removeAttr('disabled');
    } else {
        jQuery('#rsvp_yes').attr('disabled', '');
        jQuery('#rsvp_no').attr('disabled', '');
        jQuery('#rsvp_maybe').attr('disabled', '');
    }
    if (item.hasClass('bc_rsvp_wait')) {
        jQuery('#rsvp_wait').attr('disabled', '');
        jQuery('#rsvp_unwait').removeAttr('disabled');
    } else if (item.hasClass('bc_rsvp_nowait')) {
        jQuery('#rsvp_wait').removeAttr('disabled');
        jQuery('#rsvp_unwait').attr('disabled', '');
    } else {
        jQuery('#rsvp_wait').attr('disabled', '');
        jQuery('#rsvp_unwait').attr('disabled', '');
    }
});

function update_rsvp(member, new_status) {
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':  'bc_events_update_rsvp',
            'nonce':   jQuery('#nonce').val(),
            'referer': jQuery('#referer').val(),
            'eventid': jQuery('#eventid').val(),
            'member':  member,
            'status':  new_status
        }
    })
    .done(function(data) {
        let json  = jQuery.parseJSON(data);
        let error = json['error'];
        handle_result(error, json['message']);
        if (!error) {
            let rsvp = jQuery('#invited_list');
            rsvp.html(json['rsvp']);
            let waiting = jQuery('#rsvp_waiting');
            waiting.html(json['waiting']);
            jQuery('#invited_list').change();
            let next = jQuery('#rsvp_next');
            if (0 === json['wait_count']) {
                next.attr('disabled', '');
            } else {
                next.removeAttr('disabled');
            }
            if (member) {
                rsvp.val(member);
                rsvp.change();
            }
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
        handle_result(true, error);
    });
}

jQuery('#rsvp_yes').on('click', function(e) {
    e.preventDefault();
    let item = jQuery('#invited_list option:selected');
    update_rsvp(item.val(), 'yes');
});

jQuery('#rsvp_no').on('click', function(e) {
    e.preventDefault();
    let item = jQuery('#invited_list option:selected');
    update_rsvp(item.val(), 'no');
});

jQuery('#rsvp_maybe').on('click', function(e) {
    e.preventDefault();
    let item = jQuery('#invited_list option:selected');
    update_rsvp(item.val(), 'maybe');
});

jQuery('#rsvp_wait').on('click', function(e) {
    e.preventDefault();
    let item = jQuery('#invited_list option:selected');
    update_rsvp(item.val(), 'wait');
});

jQuery('#rsvp_unwait').on('click', function(e) {
    e.preventDefault();
    let item = jQuery('#invited_list option:selected');
    update_rsvp(item.val(), 'unwait');
});

jQuery('#rsvp_next').on('click', function(e) {
    e.preventDefault();
    update_rsvp('', 'next');
});
