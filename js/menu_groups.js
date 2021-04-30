/*
 * JavaScript used for editing groups page.
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
    ajax_call('bc_groups_help', {
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

function group_is_selected(id) {
    let group = jQuery('#' + id);
    return group.attr('class').indexOf('bc_select_group_selected') >= 0;
}

function group_is_voided(id) {
    let group = jQuery('#' + id);
    return group.attr('class').indexOf('bc_select_group_voided') >= 0;
}

function enable_add(flag) {
    if (flag) {
        jQuery('#button_add').removeAttr('disabled');
    } else {
        jQuery('#button_add').attr('disabled', '');
    }
}

// Selection

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

jQuery('#non_members').on('change', function (e) {
    e.preventDefault();
    if (jQuery('#non_members option:selected').length > 0) {
        jQuery('#add_members').removeAttr('disabled');
    } else {
        jQuery('#add_members').attr('disabled', '');
    }
});

jQuery('#oui_members').on('change', function (e) {
    e.preventDefault();
    if (jQuery('#oui_members option:selected').length > 0) {
        jQuery('#remove_members').removeAttr('disabled');
    } else {
        jQuery('#remove_members').attr('disabled', '');
    }
});

jQuery('#add_members').on('click', function (e) {
    e.preventDefault();
    move_ids('non_members', 'no_data', 'yes_data');
    move_selected('non_members', 'oui_members');
});

jQuery('#remove_members').on('click', function (e) {
    e.preventDefault();
    move_ids('oui_members', 'yes_data', 'no_data');
    move_selected('oui_members', 'non_members');
});

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
    ajax_call('bc_groups_select', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'eventid': jQuery('#eventid').val(),
        'group': jQuery('#group').val(),
        'exclude': group_is_selected('exclude'),
        'active': get_state('active')
    }, json => {
        let error = json['error'];
        if (error) {
            handle_result(error, json['message'], json['redirect']);
        } else {
            jQuery('#' + id).val(json['select']).attr('selected', true);
            jQuery('#' + id).change();
        }
    });
}

jQuery('#left').on('click', function (e) {
    e.preventDefault();
    fetch_select('non_members');
});

jQuery('#right').on('click', function (e) {
    e.preventDefault();
    fetch_select('oui_members');
});

jQuery('.bc_group_button').on('click', function (e) {
    e.preventDefault();
    let editmode = jQuery('#mode').val() === 'edit';
    if (!editmode) {
        let was_selected = group_is_selected(e.target.id);
        jQuery('.bc_group_button').removeClass('bc_select_group_selected');
        if (!was_selected) {
            add_highlight(e.target.id, 'bc_select_group_selected');
            enable_add(true);
        } else {
            enable_add(false);
        }
    }
});

// Search functionality

jQuery('#button_search').on('click', function (e) {
    e.preventDefault();
    let type = '';
    if (group_is_selected('type_club')) {
        type = 1;
    } else if (group_is_selected('type_email')) {
        type = 2;
    } else if (group_is_selected('type_wordpress')) {
        type = 3;
    } else if (group_is_selected('type_announcements')) {
        type = 4;
    }
    window.location = create_url(jQuery('#referer').val(), {
        action: 'search',
        groupid: jQuery('#group_id').val(),
        type: type,
        tag: jQuery('#tag').val(),
        desc: jQuery('#desc').val()
    });
});

function edit_group(groupid) {
    let parms = { action: 'edit' };
    parms.groupid = groupid;
    editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = editurl;
}

jQuery('.bc_groups_id').on('click', function (e) {
    edit_group(e.target.id.substring(3));
});

jQuery('.bc_groups_type').on('click', function (e) {
    edit_group(e.target.id.substring(5));
});

jQuery('.bc_groups_tag').on('click', function (e) {
    edit_group(e.target.id.substring(4));
});

jQuery('.bc_groups_desc').on('click', function (e) {
    edit_group(e.target.id.substring(5));
});

jQuery('#button_add').on('click', function (e) {
    e.preventDefault();
    let type;
    if (group_is_selected('type_club')) {
        type = 1;
    } else if (group_is_selected('type_email')) {
        type = 2;
    } else if (group_is_selected('type_wordpress')) {
        type = 3;
    } else { // group_is_selected('type_announcements')
        type = 4;
    }
    ajax_call('bc_groups_add', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'groupid': jQuery('#group_id').val(),
        'type': type,
        'tag': jQuery('#tag').val(),
        'desc': jQuery('#desc').val()
    }, json => {
        let error = json['error'];
        let editurl = '';
        if (!error) {
            let parms = { action: 'edit' };
            parms.groupid = json['group_id'];
            editurl = jQuery('#referer').val() + '&' +
                jQuery.param(parms);
        }
        handle_result(error, json['message'], editurl);
    });
});

function highlight_line(id) {
    add_highlight('id_' + id, 'bc_results_highlight');
    add_highlight('type_' + id, 'bc_results_highlight');
    add_highlight('tag_' + id, 'bc_results_highlight');
    add_highlight('desc_' + id, 'bc_results_highlight');
}

function unhighlight_line(id) {
    remove_highlight('id_' + id, 'bc_results_highlight');
    remove_highlight('type_' + id, 'bc_results_highlight');
    remove_highlight('tag_' + id, 'bc_results_highlight');
    remove_highlight('desc_' + id, 'bc_results_highlight');
}

jQuery('.bc_groups_id').hover(function (e) {
    highlight_line(e.target.id.substring(3));
},
    function (e) {
        unhighlight_line(e.target.id.substring(3));
    });

jQuery('.bc_groups_type').hover(function (e) {
    highlight_line(e.target.id.substring(5));
},
    function (e) {
        unhighlight_line(e.target.id.substring(5));
    });

jQuery('.bc_groups_tag').hover(function (e) {
    highlight_line(e.target.id.substring(4));
},
    function (e) {
        unhighlight_line(e.target.id.substring(4));
    });

jQuery('.bc_groups_desc').hover(function (e) {
    highlight_line(e.target.id.substring(5));
},
    function (e) {
        unhighlight_line(e.target.id.substring(5));
    });

// Edit mode functionality

// Actions

jQuery('#button_reset').on('click', function (e) {
    e.preventDefault();
    window.location = jQuery('#referer').val();
});

jQuery('#button_delete').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        ajax_call('bc_groups_delete', {
            'nonce': jQuery('#nonce').val(),
            'groupid': jQuery('#group_id').val()
        }, json => {
            if (json['error']) {
                handle_result(json['error'], json['message'], json['redirect']);
            } else {
                handle_result(json['error'], json['message'],
                    jQuery('#referer').val());
            }
        });
    }
});

jQuery('#button_save').on('click', function (e) {
    e.preventDefault();
    let type = jQuery('#type').val();
    if (1 == type) { // book club
        data = {
            'nonce': jQuery('#nonce').val(),
            'referer': jQuery('#referer').val(),
            'groupid': jQuery('#group_id').val(),
            'tag': jQuery('#tag').val(),
            'desc': jQuery('#desc').val(),
            'url': jQuery('#url').val(),
            'event_id': jQuery('#event_id').val(),
            'max': jQuery('#max').val(),
            'include': jQuery('#include').val(),
            'starttime': jQuery('#starttime').val(),
            'endtime': jQuery('#endtime').val(),
            'what': jQuery('#what').val(),
            'body': jQuery('#body').val()
        };
    } else if (2 == type) { // email list
        data = {
            'nonce': jQuery('#nonce').val(),
            'referer': jQuery('#referer').val(),
            'groupid': jQuery('#group_id').val(),
            'tag': jQuery('#tag').val(),
            'desc': jQuery('#desc').val(),
            'yes': jQuery('#yes_data').val(),
            'no': jQuery('#no_data').val()
        };
    } else { // wordpress list or announcments
        data = {
            'nonce': jQuery('#nonce').val(),
            'referer': jQuery('#referer').val(),
            'groupid': jQuery('#group_id').val(),
            'tag': jQuery('#tag').val(),
            'desc': jQuery('#desc').val(),
            'yes': jQuery('#yes_data').val(),
            'no': jQuery('#no_data').val()
        };
    }
    ajax_call('bc_groups_save', data,
        json => {
            handle_result(json['error'], json['message'], json['redirect']);
        });
});
