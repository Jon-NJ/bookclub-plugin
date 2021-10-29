/*
 * JavaScript used for managing book covers page.
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

jQuery('#close_help').on('click', function (e) {
    e.preventDefault();
    jQuery(".bc_help").hide();
});

jQuery('#button_help').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_covers_help', {
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

var stuck = '';

jQuery('#button_rename').on('click', function (e) {
    e.preventDefault();
    let newname = jQuery('#cover').val();
    let original = jQuery('#filename').val();
    ajax_call('bc_covers_rename', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'original': original,
        'newname': newname
    }, json => {
        if (json['error']) {
            handle_result(json['error'], json['message'], json['redirect']);
        } else {
            let parms = { action: 'edit' };
            parms.cover = newname;
            editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
            handle_result(json['error'], json['message'], editurl);
        }
    });
});

function rename_validate() {
    let button_rename = jQuery('#button_rename');
    let undo_button = jQuery('#undo_name');
    if (('edit' === jQuery('#mode').val()) &&
        (jQuery('#cover').val() !== jQuery('#filename').val())) {
        button_rename.removeAttr('disabled');
        undo_button.removeAttr('disabled');
    } else {
        button_rename.attr('disabled', '');
        undo_button.attr('disabled', '');
    }
}

jQuery('#cover').on('input', function (e) {
    rename_validate();
});

jQuery('#undo_name').on('click', function (e) {
    e.preventDefault();
    let cover = jQuery('#cover');
    let original = jQuery('#filename').val();
    cover.val(original);
    rename_validate();
});

jQuery('#button_search').on('click', function (e) {
    e.preventDefault();
    let ounit = jQuery('[name="old_unit"]:checked').val();
    let yunit = jQuery('[name="young_unit"]:checked').val();
    window.location = create_url(jQuery('#referer').val(), {
        action: 'search',
        cover: jQuery('#cover').val(),
        older: jQuery('#older').val(),
        ounit: ('days' !== ounit) ? ounit : '',
        younger: jQuery('#younger').val(),
        yunit: ('days' !== yunit) ? yunit : ''
    });
});

jQuery('#button_reset').on('click', function (e) {
    e.preventDefault();
    window.location = jQuery('#referer').val();
});

jQuery('#file-upload').on('input', function (e) {
    let fd = new FormData();
    fd.append('file_0', e.target.files[0]);
    fd.append('length', 1);
    uploadCovers(fd);
});

jQuery('#button_add').on('click', function (e) {
    e.preventDefault();
    let upload = jQuery('#file-upload');
    upload.click();
});

jQuery('#button_delete').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        ajax_call('bc_covers_delete', {
            'nonce': jQuery('#nonce').val(),
            'cover': jQuery('#filename').val()
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

function highlight_line(coverid) {
    add_highlight('cid_' + coverid, 'bc_results_highlight');
    stuck = coverid;
}

function unhighlight_line(coverid) {
    remove_highlight('cid_' + coverid, 'bc_results_highlight');
}

jQuery('.bc_covers_item').hover(function (e) {
    if (stuck) {
        unhighlight_line(stuck);
        stuck = '';
    }
    let div = e.target;
    if ('A' !== div.nodeName) {
        div = div.parentElement;
    }
    let cid = div.id.substring(4);
    highlight_line(cid);
},
    function (e) {
        let div = e.target;
        if ('A' !== div.nodeName) {
            div = div.parentElement;
        }
        let cid = div.id.substring(4);
        unhighlight_line(cid);
    });

function uploadCovers(formdata) {
    formdata.append('action', 'bc_covers_upload');
    formdata.append('nonce', jQuery('#nonce').val());
    formdata.append('referer', jQuery('#referer').val());
    jQuery.ajax({
        type: "post",
        url: bookclub_ajax_object.ajax_url,
        data: formdata,
        processData: false,
        contentType: false
    })
        .done(function (data) {
            let json;
            try {
                json = jQuery.parseJSON(data);
            } catch (e) {
                console.log(`bc_covers_upload exception ${e.message}`);
                return;
            }
            if (json['error']) {
                handle_result(json['error'], json['message'], json['redirect']);
            } else {
                let parms = { action: 'edit' };
                parms.cover = json['cover'];
                editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
                handle_result(json['error'], json['message'], editurl);
            }
        })
        .fail(function (jqXHR, text, error) {
            console.log(`bc_covers_upload ${text} ${error}`);
            handle_result(true, error);
        });
}

jQuery('html').on('dragover dragenter', false);
jQuery('form').on('drop', false);

jQuery('.bc_covers_image').on(
    'drop',
    function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files.length) {
            let fd = new FormData();
            let files = e.originalEvent.dataTransfer.files;
            for (let i = 0; i < files.length; ++i) {
                fd.append('file_' + i, files[i], files[i].name);
            }
            fd.append('length', files.length);
            uploadCovers(fd);
        }
    }
);

jQuery('.bc_covers_image').on('dragover', false);
jQuery('.bc_covers_image').on('dragleave', false);
