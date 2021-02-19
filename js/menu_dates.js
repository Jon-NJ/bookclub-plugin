/*
 * JavaScript used for editing dates page.
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
            'action':  'bc_dates_help',
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

jQuery('#group').on('change', function(e) {
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
        let add  = jQuery('#button_add');
        if (valid) {
            add.removeAttr('disabled');
        } else {
            add.attr('disabled', '');
        }
    }
}

function validate_author() {
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action': 'bc_dates_lookup_author',
            'nonce':  jQuery('#nonce').val(),
            'author': jQuery('#author').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            let author_id = jQuery('#author_id');
            if (json['error']) {
                author_id.attr('value', '');
            } else {
                author_id.attr('value', json['author_id']);
            }
            validate_form();
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
}

function validate_book() {
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action': 'bc_dates_lookup_book',
            'nonce':  jQuery('#nonce').val(),
            'book':   jQuery('#book').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
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
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
}

function validate_place() {
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action': 'bc_dates_lookup_place',
            'nonce':  jQuery('#nonce').val(),
            'place':  jQuery('#place').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            let place_id = jQuery('#place_id');
            if (json['error']) {
                place_id.attr('value', '');
            } else {
                place_id.attr('value', json['place_id']);
            }
            validate_form();
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
    });
}

jQuery('#author').on('input', function(e) {
    set_modified();
    validate_author();
});

jQuery('#book').on('input', function(e) {
    set_modified();
    validate_book();
});

jQuery('#place').on('input', function(e) {
    set_modified();
    validate_place();
});

jQuery('#priority').on('input', function(e) {
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

jQuery('#date').on('input', function(e) {
    refresh_date_selection();
    set_modified();
    validate_form();
});

jQuery(document).ready(function() {
    validate_author();
    validate_book();
    validate_place();
    generate_calendar();
});

jQuery('#button_search').on('click', function(e) {
    e.preventDefault();
    let parms = { action:'search' };
    let groupid = jQuery('#groupid').val();
    if (0 !== groupid) {
        parms.groupid = groupid;
    }
    let date = jQuery('#date').val();
    if ('' !== date) {
        parms.date = date;
    }
    let age = jQuery('#age').val();
    if ('' !== age) {
        parms.age = age;
    }
    let book = jQuery('#book').val();
    if ('' !== book) {
        parms.book = book;
    }
    let author = jQuery('#author').val();
    if ('' !== author) {
        parms.author = author;
    }
    let place = jQuery('#place').val();
    if ('' !== place) {
        parms.place = place;
    }
    let calmonth = jQuery('#calmonth').val();
    parms.calmonth = calmonth;
    searchurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = searchurl;
});

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
                'action':  'bc_dates_delete',
                'nonce':   jQuery('#nonce').val(),
                'groupid': jQuery('#original_groupid').val(),
                'bookid':  jQuery('#original_bookid').val(),
                'date':    jQuery('#original_date').val()
            }
        })
        .done(function(data) {
            if (data) {
                let json = jQuery.parseJSON(data);
                if (json['error']) {
                    handle_result(json['error'], json['message']);
                } else {
                    handle_result(json['error'], json['message'],
                        window.location = jQuery('#referer').val());
                }
            }
        })
        .fail(function(jqXHR, text, error) {
            console.log(error);
            handle_result(true, error);
        });
    }
});

function set_modified() {
    let make = jQuery('#make_event');
    if (make) {
        make.attr('disabled', '');
    }
}

jQuery('#button_save').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':          'bc_dates_save',
            'nonce':            jQuery('#nonce').val(),
            'referer':          jQuery('#referer').val(),
            'groupid':          jQuery('#groupid').val(),
            'original_groupid': jQuery('#original_groupid').val(),
            'date':             jQuery('#date').val(),
            'original_date':    jQuery('#original_date').val(),
            'original_bookid':  jQuery('#original_bookid').val(),
            'bookid':           jQuery('#book_id').val(),
            'book':             jQuery('#book').val(),
            'placeid':          jQuery('#place_id').val(),
            'hideflag':         jQuery('#hideflag').prop('checked') ? 1 : 0,
            'private':          jQuery('#private').prop('checked') ? 1 : 0,
            'priority':         jQuery('#priority').val()
        }
    })
    .done(function(data) {
        if (data) {
            let json = jQuery.parseJSON(data);
            let error = json['error'];
            handle_result(error, json['message'], json['redirect']);
            if (!error) {
                let make = jQuery('#make_event');
                if (make) {
                    make.removeAttr('disabled');
                }
            }
        }
    })
    .fail(function(jqXHR, text, error) {
        console.log(error);
        handle_result(true, error);
    });
});

jQuery('#make_event').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':    'bc_dates_event',
            'nonce':     jQuery('#nonce').val(),
            'referer':   jQuery('#referer').val(),
            'bc_events': jQuery('#bc_events').val(),
            'groupid':   jQuery('#groupid').val(),
            'date':      jQuery('#date').val(),
            'bookid':    jQuery('#book_id').val()
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
        handle_result(true, error);
    });
});

jQuery('#button_add').on('click', function(e) {
    e.preventDefault();
    jQuery.ajax({
        type: "post",
        url:  bookclub_ajax_object.ajax_url,
        data: {
            'action':   'bc_dates_add',
            'nonce':    jQuery('#nonce').val(),
            'referer':  jQuery('#referer').val(),
            'groupid':  jQuery('#groupid').val(),
            'date':     jQuery('#date').val(),
            'bookid':   jQuery('#book_id').val(),
            'placeid':  jQuery('#place_id').val(),
            'hideflag': 0,
            'private':  0
        }
    })
    .done(function(data) {
        if (data) {
            let json    = jQuery.parseJSON(data);
            let error   = json['error'];
            let editurl = '';
            if (!error) {
                let parms   = {action:'edit'};
                parms.date  = json['date'];
                parms.group = json['group'];
                parms.book  = json['book'];
                editurl     = jQuery('#referer').val() + '&' +
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

function edit_date(line) {
    let parms = {action:'edit'};
    let date = jQuery('#date_' + line).text();
    let group = jQuery('#group_' + line).text();
    let book = jQuery('#bookid_' + line).text();
    parms.date  = date;
    parms.group = group;
    parms.book  = book;
    editurl = jQuery('#referer').val() + '&' + jQuery.param(parms);
    window.location = editurl;
}

jQuery('.bc_dates_hidden').on('click', function(e) {
    t = e.target;
    if ('img' === t.localName) {
        t = t.parentElement;
    }
    edit_date(t.id.substring(7));
});

jQuery('.bc_dates_private').on('click', function(e) {
    t = e.target;
    if ('img' === t.localName) {
        t = t.parentElement;
    }
    edit_date(t.id.substring(8));
});

jQuery('.bc_dates_priority').on('click', function(e) {
    edit_date(e.target.id.substring(9));
});

jQuery('.bc_dates_day').on('click', function(e) {
    edit_date(e.target.id.substring(5));
});

jQuery('.bc_dates_group_id').on('click', function(e) {
    edit_date(e.target.id.substring(6));
});

jQuery('.bc_dates_place').on('click', function(e) {
    edit_date(e.target.id.substring(6));
});

jQuery('.bc_dates_book').on('click', function(e) {
    edit_date(e.target.id.substring(5));
});

jQuery('.bc_dates_author').on('click', function(e) {
    edit_date(e.target.id.substring(7));
});

function highlight_line(line) {
    add_highlight('hidden_'   + line, 'bc_results_highlight');
    add_highlight('private_'  + line, 'bc_results_highlight');
    add_highlight('priority_' + line, 'bc_results_highlight');
    add_highlight('date_'     + line, 'bc_results_highlight');
    add_highlight('group_'    + line, 'bc_results_highlight');
    add_highlight('place_'    + line, 'bc_results_highlight');
    add_highlight('book_'     + line, 'bc_results_highlight');
    add_highlight('author_'   + line, 'bc_results_highlight');
}

function unhighlight_line(line) {
    remove_highlight('hidden_'   + line, 'bc_results_highlight');
    remove_highlight('private_'  + line, 'bc_results_highlight');
    remove_highlight('priority_' + line, 'bc_results_highlight');
    remove_highlight('date_'     + line, 'bc_results_highlight');
    remove_highlight('group_'    + line, 'bc_results_highlight');
    remove_highlight('place_'    + line, 'bc_results_highlight');
    remove_highlight('book_'     + line, 'bc_results_highlight');
    remove_highlight('author_'   + line, 'bc_results_highlight');
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
    let months = [ "January",   "February", "March",    "April",
                   "May",       "June",     "July",     "August",
                   "September", "October",  "November", "December"];
    let dows =   [ "Su", "Mo", "Tu", "We", "Th", "Fr", "Sa" ];

    let calmonth = jQuery('#calmonth').val();
    let sow = Number(jQuery('#start_of_week').val());
    let dt = new Date(calmonth);
    let year = dt.getFullYear();
    let month = dt.getMonth();
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
    for (i = 0; i < days; ) {
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
    for (i = 0; i < 36; ) {
        jQuery('#day_' + ix).hide();
        ++ix; ++i;
    }
}

function fetch_date(day, madj, yadj) {
    let calmonth = jQuery('#calmonth').val();
    let dt = new Date(calmonth);
    let year = dt.getFullYear() + yadj;
    let month = dt.getMonth() + 1 + madj;
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

jQuery('.calendar_date').on('click', function(e) {
    e.preventDefault();
    if (!e.target.hasAttribute('disabled')) {
        jQuery('#date').val(fetch_date(e.target.innerHTML, 0, 0));
        refresh_date_selection();
        set_modified();
        validate_form();
    }
});

jQuery('#calendar_ym1').on('click', function(e) {
    e.preventDefault();
    jQuery('#calmonth').val(fetch_date(1, 0, -1));
    generate_calendar();
});

jQuery('#calendar_mm1').on('click', function(e) {
    e.preventDefault();
    jQuery('#calmonth').val(fetch_date(1, -1, 0));
    generate_calendar();
});

jQuery('#calendar_yp1').on('click', function(e) {
    e.preventDefault();
    jQuery('#calmonth').val(fetch_date(1, 0, 1));
    generate_calendar();
});

jQuery('#calendar_mp1').on('click', function(e) {
    e.preventDefault();
    jQuery('#calmonth').val(fetch_date(1, 1, 0));
    generate_calendar();
});

jQuery('#calendar_month').on('click', function(e) {
    e.preventDefault();
    let today = new Date();
    let year = today.getFullYear();
    let month = ('0' + (today.getMonth() + 1)).slice(month.length - 2);
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
