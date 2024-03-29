/*
 * JavaScript used for editing dates page.
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
    ajax_call('bc_dates_help', {
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

jQuery('#group').on('change', function (e) {
    e.preventDefault();
    let group = jQuery('#group').val();
    let groupid = jQuery('#groupid');
    groupid.val(group);
    set_modified();
    validate_form();
});

function validate_form() {
    let valid = true;
    if ('0' === jQuery('#groupid').val()) {
        valid = false;
    }
    if ('' === jQuery('#book_id').val()) {
        valid = false;
    }
    if ('' === jQuery('#author_id').val()) {
        valid = false;
    }
    if ('' === jQuery('#place_id').val()) {
        valid = false;
    }
    if (jQuery('#bookauthorid').val() !== jQuery('#author_id').val()) {
        valid = false;
    }
    /* quick and dirty date validation, should actually check if it is a real date */
    if ('' === jQuery('#date').val()) {
        valid = false;
    }
    if ('edit' === jQuery('#mode').val()) {
        let save = jQuery('#button_save');
        if (valid) {
            save.removeAttr('disabled');
        } else {
            save.attr('disabled', '');
        }
    } else {
        let add = jQuery('#button_add');
        if (valid) {
            add.removeAttr('disabled');
        } else {
            add.attr('disabled', '');
        }
    }
}

function validate_author() {
    ajax_call('bc_dates_lookup_author', {
        'nonce': jQuery('#nonce').val(),
        'author': jQuery('#author').val()
    }, json => {
        let author_id = jQuery('#author_id');
        if (json['error']) {
            author_id.attr('value', '');
        } else {
            author_id.attr('value', json['author_id']);
        }
        validate_form();
    });
}

function validate_book() {
    ajax_call('bc_dates_lookup_book', {
        'nonce': jQuery('#nonce').val(),
        'book': jQuery('#book').val()
    }, json => {
        let book_id = jQuery('#book_id');
        let author_id = jQuery('#bookauthorid');
        let author = jQuery('#author');
        if (json['error']) {
            book_id.attr('value', '');
            author_id.attr('value', '');
        } else {
            book_id.attr('value', json['book_id']);
            author_id.attr('value', json['author_id']);
            author.attr('value', json['author']);
            validate_author();
        }
        validate_form();
    });
}

function validate_place() {
    ajax_call('bc_dates_lookup_place', {
        'nonce': jQuery('#nonce').val(),
        'place': jQuery('#place').val()
    }, json => {
        let place_id = jQuery('#place_id');
        if (json['error']) {
            place_id.attr('value', '');
        } else {
            place_id.attr('value', json['place_id']);
        }
        validate_form();
    });
}

jQuery('#author').on('input', function (e) {
    set_modified();
    validate_author();
});

jQuery('#book').on('input', function (e) {
    set_modified();
    validate_book();
});

jQuery('#place').on('input', function (e) {
    set_modified();
    validate_place();
});

jQuery('#priority').on('input', function (e) {
    set_modified();
});

function refresh_date_selection() {
    let calmonth = jQuery('#calmonth');
    let date = jQuery('#date');
    let newmonth = date.val();
    if ((newmonth.length === 10) && (newmonth.slice(0, 8) !== calmonth.val().slice(0, 8))) {
        calmonth.val(newmonth.slice(0, 8) + '01');
        generate_calendar();
    } else {
        let selected_date = jQuery('#selected_date');
        if (selected_date.val()) {
            remove_highlight('day_' + selected_date.val(), 'calendar_enter_date');
            selected_date.val('');
        }
        if (newmonth.slice(0, 8) === calmonth.val().slice(0, 8)) {
            let newdate = Number(jQuery('#pad').val()) + Number(newmonth.slice(8)) - 1;
            selected_date.val(newdate);
            add_highlight('day_' + newdate, 'calendar_enter_date');
        }
    }
}

jQuery('#date').on('input', function (e) {
    refresh_date_selection();
    set_modified();
    validate_form();
});

jQuery(document).ready(function () {
    validate_author();
    validate_book();
    validate_place();
    generate_calendar();
});

jQuery('#button_search').on('click', function (e) {
    e.preventDefault();
    let groupid = jQuery('#groupid').val();
    window.location = create_url(jQuery('#referer').val(), {
        action: 'search',
        groupid: (0 != groupid) ? groupid : '',
        date: jQuery('#date').val(),
        age: jQuery('#age').val(),
        book: jQuery('#book').val(),
        author: jQuery('#author').val(),
        place: jQuery('#place').val(),
        calmonth: jQuery('#calmonth').val()
    });
});

jQuery('#button_reset').on('click', function (e) {
    e.preventDefault();
    window.location = jQuery('#referer').val();
});

jQuery('#button_delete').on('click', function (e) {
    e.preventDefault();
    if (confirm(jQuery('#delete_text').val())) {
        ajax_call('bc_dates_delete', {
            'nonce': jQuery('#nonce').val(),
            'groupid': jQuery('#original_groupid').val(),
            'bookid': jQuery('#original_bookid').val(),
            'date': jQuery('#original_date').val()
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

function set_modified() {
    let make = jQuery('#make_event');
    if (make) {
        make.attr('disabled', '');
    }
}

jQuery('#button_save').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_dates_save', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'groupid': jQuery('#groupid').val(),
        'original_groupid': jQuery('#original_groupid').val(),
        'date': jQuery('#date').val(),
        'original_date': jQuery('#original_date').val(),
        'original_bookid': jQuery('#original_bookid').val(),
        'bookid': jQuery('#book_id').val(),
        'book': jQuery('#book').val(),
        'placeid': jQuery('#place_id').val(),
        'hideflag': jQuery('#hideflag').prop('checked') ? 1 : 0,
        'private': jQuery('#private').prop('checked') ? 1 : 0,
        'priority': jQuery('#priority').val()
    }, json => {
        let error = json['error'];
        handle_result(error, json['message'], json['redirect']);
        if (!error) {
            let make = jQuery('#make_event');
            if (make) {
                make.removeAttr('disabled');
            }
        }
    });
});

jQuery('#make_event').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_dates_event', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'bc_events': jQuery('#bc_events').val(),
        'groupid': jQuery('#groupid').val(),
        'date': jQuery('#date').val(),
        'bookid': jQuery('#book_id').val()
    }, json => {
        handle_result(json['error'], json['message'], json['redirect']);
    });
});

jQuery('#button_add').on('click', function (e) {
    e.preventDefault();
    ajax_call('bc_dates_add', {
        'nonce': jQuery('#nonce').val(),
        'referer': jQuery('#referer').val(),
        'groupid': jQuery('#groupid').val(),
        'date': jQuery('#date').val(),
        'bookid': jQuery('#book_id').val(),
        'placeid': jQuery('#place_id').val(),
        'hideflag': 0,
        'private': 0
    }, json => {
        let error = json['error'];
        let editurl = '';
        if (!error) {
            let parms = { action: 'edit' };
            parms.date = json['date'];
            parms.group = json['group'];
            parms.book = json['book'];
            editurl = jQuery('#referer').val() + '&' +
                jQuery.param(parms);
        }
        handle_result(error, json['message'], editurl);
    });
});

function highlight_line(line) {
    add_highlight('hidden_' + line, 'bc_results_highlight');
    add_highlight('private_' + line, 'bc_results_highlight');
    add_highlight('priority_' + line, 'bc_results_highlight');
    add_highlight('date_' + line, 'bc_results_highlight');
    add_highlight('group_' + line, 'bc_results_highlight');
    add_highlight('place_' + line, 'bc_results_highlight');
    add_highlight('book_' + line, 'bc_results_highlight');
    add_highlight('author_' + line, 'bc_results_highlight');
}

function unhighlight_line(line) {
    remove_highlight('hidden_' + line, 'bc_results_highlight');
    remove_highlight('private_' + line, 'bc_results_highlight');
    remove_highlight('priority_' + line, 'bc_results_highlight');
    remove_highlight('date_' + line, 'bc_results_highlight');
    remove_highlight('group_' + line, 'bc_results_highlight');
    remove_highlight('place_' + line, 'bc_results_highlight');
    remove_highlight('book_' + line, 'bc_results_highlight');
    remove_highlight('author_' + line, 'bc_results_highlight');
}

jQuery('.bc_dates_hidden').hover(function (e) {
    t = e.target;
    if ('img' === t.localName) {
        t = t.parentElement;
    }
    highlight_line(t.id.substring(7));
},
    function (e) {
        t = e.target;
        if ('img' === t.localName) {
            t = t.parentElement;
        }
        unhighlight_line(t.id.substring(7));
    });

jQuery('.bc_dates_private').hover(function (e) {
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

jQuery('.bc_dates_priority').hover(function (e) {
    highlight_line(e.target.id.substring(9));
},
    function (e) {
        unhighlight_line(e.target.id.substring(9));
    });

jQuery('.bc_dates_day').hover(function (e) {
    highlight_line(e.target.id.substring(5));
},
    function (e) {
        unhighlight_line(e.target.id.substring(5));
    });

jQuery('.bc_dates_group_id').hover(function (e) {
    highlight_line(e.target.id.substring(6));
},
    function (e) {
        unhighlight_line(e.target.id.substring(6));
    });

jQuery('.bc_dates_place').hover(function (e) {
    highlight_line(e.target.id.substring(6));
},
    function (e) {
        unhighlight_line(e.target.id.substring(6));
    });

jQuery('.bc_dates_book').hover(function (e) {
    highlight_line(e.target.id.substring(5));
},
    function (e) {
        unhighlight_line(e.target.id.substring(5));
    });

jQuery('.bc_dates_author').hover(function (e) {
    highlight_line(e.target.id.substring(7));
},
    function (e) {
        unhighlight_line(e.target.id.substring(7));
    });

/** calendar functions */

function generate_calendar() {
    let months = ["January", "February", "March", "April",
        "May", "June", "July", "August",
        "September", "October", "November", "December"];
    let dows = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];

    let calmonth = jQuery('#calmonth').val();
    let sow = Number(jQuery('#start_of_week').val());
    let dt = new Date(calmonth);
    let year = dt.getFullYear();
    let month = dt.getUTCMonth();
    let pad = (7 + dt.getDay() - sow) % 7;
    let days = new Date(year, month + 1, 0).getDate();
    let today = new Date();
    let edate = jQuery('#date').val();
    if (edate) {
        edate = new Date(edate);
        edate.setHours(0, 0, 0, 0);
    }
    let selected_date = jQuery('#selected_date');
    selected_date.val('');
    today.setHours(0, 0, 0, 0);
    jQuery('#pad').val(pad);
    jQuery('#calendar_month').text(months[month]);
    jQuery('#calendar_year').text(year);
    for (i = 0; i < 7; i++) {
        jQuery('#dow_' + i).text(dows[(i + sow) % 7]);
    }
    let ix = 0;
    for (i = 0; i < pad; ++i) {
        let date = jQuery('#day_' + ix);
        date.attr('disabled', '');
        date.attr('class', 'calendar_day calendar_date');
        date.text(' ');
        ++ix;
    }
    for (i = 0; i < days;) {
        let day = new Date(year, month, i + 1);
        let date = jQuery('#day_' + ix);
        if (day.getTime() === today.getTime()) {
            date.attr('class', 'calendar_day calendar_date calendar_today');
        } else {
            date.attr('class', 'calendar_day calendar_date');
        }
        if (edate && (day.getTime() === edate.getTime())) {
            date.attr('class', date.attr('class') + ' calendar_enter_date');
            selected_date.val(ix);
        }
        date.text(i + 1);
        date.removeAttr('disabled');
        date.show();
        ++ix; ++i;
    }
    for (i = 0; i < 36;) {
        jQuery('#day_' + ix).hide();
        ++ix; ++i;
    }
}

function fetch_date(day, madj, yadj) {
    let calmonth = jQuery('#calmonth').val();
    let dt = new Date(calmonth);
    var year = dt.getFullYear() + yadj;
    var month = dt.getUTCMonth() + 1 + madj;
    if (0 === month) {
        --year;
        month = 12;
    } else if (13 === month) {
        ++year;
        month = 1;
    }
    month = '0' + month;
    month = month.slice(month.length - 2);
    day = '0' + day;
    day = day.slice(day.length - 2);
    return year + '-' + month + '-' + day;
}

jQuery('.calendar_date').on('click', function (e) {
    e.preventDefault();
    if (!e.target.hasAttribute('disabled')) {
        jQuery('#date').val(fetch_date(e.target.innerHTML, 0, 0));
        refresh_date_selection();
        set_modified();
        validate_form();
    }
});

jQuery('#calendar_ym1').on('click', function (e) {
    e.preventDefault();
    jQuery('#calmonth').val(fetch_date(1, 0, -1));
    generate_calendar();
});

jQuery('#calendar_mm1').on('click', function (e) {
    e.preventDefault();
    jQuery('#calmonth').val(fetch_date(1, -1, 0));
    generate_calendar();
});

jQuery('#calendar_yp1').on('click', function (e) {
    e.preventDefault();
    jQuery('#calmonth').val(fetch_date(1, 0, 1));
    generate_calendar();
});

jQuery('#calendar_mp1').on('click', function (e) {
    e.preventDefault();
    jQuery('#calmonth').val(fetch_date(1, 1, 0));
    generate_calendar();
});

jQuery('#calendar_month').on('click', function (e) {
    e.preventDefault();
    let today = new Date();
    let year = today.getFullYear();
    var month = '0' + (today.getUTCMonth() + 1);
    month = month.slice(month.length - 2);
    day = '0' + today.getDate();
    day = day.slice(day.length - 2);
    jQuery('#calmonth').val(year + '-' + month + '-' + day);
    generate_calendar();
});

jQuery('.calendar_date').hover(function (e) {
    add_highlight(e.target.id, 'calendar_highlight');
},
    function (e) {
        remove_highlight(e.target.id, 'calendar_highlight');
    });

jQuery('.calendar_button').hover(function (e) {
    add_highlight(e.target.id, 'calendar_button_highlight');
},
    function (e) {
        remove_highlight(e.target.id, 'calendar_button_highlight');
    });

jQuery('#calendar_month').hover(function (e) {
    add_highlight('calendar_top', 'calendar_button_highlight');
},
    function (e) {
        remove_highlight('calendar_top', 'calendar_button_highlight');
    });
