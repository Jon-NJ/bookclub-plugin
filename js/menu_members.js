/*
 * JavaScript used for editing members.
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
    ajax_call('bc_members_help', {
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
    return group.attr('class').indexOf('bc_members_group_selected') >= 0;
}

function group_is_voided(id) {
    let group = jQuery('#' + id);
    return group.attr('class').indexOf('bc_members_group_voided') >= 0;
}

function throggle_group(e) {
    if ('edit' === jQuery('#mode').val()) {
        toggle_group(e);
    } else {
        let id = e.target.name;
        if (group_is_selected(id)) {
            remove_highlight(id, 'bc_members_group_selected');
            add_highlight(id, 'bc_members_group_voided');
        } else if (group_is_voided(id)) {
            remove_highlight(id, 'bc_members_group_voided');
        } else {
            add_highlight(id, 'bc_members_group_selected');
        }
    }
}

function toggle_group(e) {
    e.preventDefault();
    let id = e.target.name;
    if (group_is_selected(id)) {
        remove_highlight(id, 'bc_members_group_selected');
    } else {
        add_highlight(id, 'bc_members_group_selected');
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

jQuery('#button_search').on('click', function (e) {
    e.preventDefault();
    let group = jQuery('#group').val();
    window.location = create_url(jQuery('#referer').val(), {
        action: 'search',
        group: jQuery('#group').val(),
        id: jQuery('#memberid').val(),
        wpid: jQuery('#wpid').val(),
        login: jQuery('#login').val(),
        pkey: jQuery('#pkey').val(),
        name: jQuery('#name').val(),
        email: jQuery('#email').val(),
        exclude: group_is_selected('exclude') ? 1 : '',
        active: group_is_selected('active') ? 1 : ((group_is_voided('active')) ? '-' : ''),
        last: jQuery('#last').val(),
        ltgt: ('1' === jQuery('[name="ltgt"]:checked').val()) ? 1 : ''
    });
});

jQuery('#button_reset').on('click', function (e) {
    e.preventDefault();
    window.location = jQuery('#referer').val();
});

jQuery('#button_delete').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        ajax_call('bc_members_delete', {
            'nonce': jQuery('#nonce').val(),
            'id': jQuery('#memberid').val()
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

jQuery('#copy_url').on('click', function (e) {
    e.preventDefault();
    let signup = document.getElementById("signup");
    signup.select();
    signup.setSelectionRange(0, 99999); /*For mobile devices*/
    document.execCommand("copy");
    signup.blur();
    handle_result(false, 'URL copied to clipboard: ' + signup.value);
});

jQuery('#sendemail').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_members_email', {
        'nonce': jQuery('#nonce').val(),
        'id': jQuery('#memberid').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
        jQuery('#sent_text').html(json['text']);
    });
});

jQuery('#button_save').on('click', function (e) {
    e.preventDefault();
    let data = {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'id': jQuery('#memberid').val(),
        'name': jQuery('#name').val(),
        'email': jQuery('#email').val(),
        'pkey': jQuery('#pkey').val(),
        'active': jQuery('[name="active"]:checked').val(),
        'noemail': jQuery('[name="noemail"]:checked').val(),
        'format': jQuery('[name="format"]:checked').val(),
        'ical': jQuery('[name="ics"]:checked').val(),
        'public_email': jQuery('[name="public_email"]:checked').val(),
        'receive': jQuery('[name="receive"]:checked').val()
    };
    jQuery('.groupbox').each(function () {
        data[this.id] = jQuery(this).prop("checked");
    });
    ajax_call('bc_members_save', data,
        json => {
            handle_result(json['error'], json['message'], json['redirect']);
        });
});

jQuery('#button_add').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_members_add', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'name': jQuery('#name').val(),
        'email': jQuery('#email').val(),
        'group': jQuery('#group').val()
    }, json => {
        let error = json['error'];
        let editurl = '';
        if (!error) {
            let parms = { action: 'edit' };
            parms.id = json['id'];
            editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
        }
        handle_result(json['error'], json['message'], editurl);
    });
});

function edit_members(memberid) {
    let parms = { action: 'edit' };
    parms.id = memberid;
    editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = editurl;
}

jQuery('.bc_members_id').on('click', function (e) {
    edit_members(e.target.id.substring(3));
});

jQuery('.bc_members_pkey').on('click', function (e) {
    edit_members(e.target.id.substring(5));
});

jQuery('.bc_members_wpid').on('click', function (e) {
    edit_members(e.target.id.substring(5));
});

jQuery('.bc_members_login').on('click', function (e) {
    edit_members(e.target.id.substring(6));
});

jQuery('.bc_members_name').on('click', function (e) {
    edit_members(e.target.id.substring(5));
});

jQuery('.bc_members_email').on('click', function (e) {
    edit_members(e.target.id.substring(6));
});

jQuery('.bc_members_hittime').on('click', function (e) {
    edit_members(e.target.id.substring(3));
});

function highlight_line(memberid) {
    add_highlight('id_' + memberid, 'bc_results_highlight');
    add_highlight('pkey_' + memberid, 'bc_results_highlight');
    add_highlight('wpid_' + memberid, 'bc_results_highlight');
    add_highlight('login_' + memberid, 'bc_results_highlight');
    add_highlight('name_' + memberid, 'bc_results_highlight');
    add_highlight('email_' + memberid, 'bc_results_highlight');
    add_highlight('ht_' + memberid, 'bc_results_highlight');
}

function unhighlight_line(memberid) {
    remove_highlight('id_' + memberid, 'bc_results_highlight');
    remove_highlight('pkey_' + memberid, 'bc_results_highlight');
    remove_highlight('wpid_' + memberid, 'bc_results_highlight');
    remove_highlight('login_' + memberid, 'bc_results_highlight');
    remove_highlight('name_' + memberid, 'bc_results_highlight');
    remove_highlight('email_' + memberid, 'bc_results_highlight');
    remove_highlight('ht_' + memberid, 'bc_results_highlight');
}

jQuery('.bc_members_id').hover(function (e) {
    highlight_line(e.target.id.substring(3));
},
    function (e) {
        unhighlight_line(e.target.id.substring(3));
    });

jQuery('.bc_members_pkey').hover(function (e) {
    highlight_line(e.target.id.substring(5));
},
    function (e) {
        unhighlight_line(e.target.id.substring(5));
    });

jQuery('.bc_members_wpid').hover(function (e) {
    highlight_line(e.target.id.substring(5));
},
    function (e) {
        unhighlight_line(e.target.id.substring(5));
    });

jQuery('.bc_members_login').hover(function (e) {
    highlight_line(e.target.id.substring(6));
},
    function (e) {
        unhighlight_line(e.target.id.substring(6));
    });

jQuery('.bc_members_name').hover(function (e) {
    highlight_line(e.target.id.substring(5));
},
    function (e) {
        unhighlight_line(e.target.id.substring(5));
    });

jQuery('.bc_members_email').hover(function (e) {
    highlight_line(e.target.id.substring(6));
},
    function (e) {
        unhighlight_line(e.target.id.substring(6));
    });

jQuery('.bc_members_hittime').hover(function (e) {
    highlight_line(e.target.id.substring(3));
},
    function (e) {
        unhighlight_line(e.target.id.substring(3));
    });

jQuery('#button_newkey').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#reset_text').val())) {
        ajax_call('bc_members_key', {
            'nonce': jQuery('#nonce').val()
        }, json => {
            if (!json['error']) {
                jQuery('#pkey').val(json['newkey']);
            }
            handle_result(json['error'], json['message'], json['redirect']);
        });
    }
});
