/*
 * JavaScript used for test page.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @license    https://opensource.org/licenses/MIT MIT
 */
/* global bookclub_ajax_object */

function random_int(max) {
    return Math.floor((Math.random() * max));
}

function generate_uid() {
    let hex = '0123456789ABCDEF';
    let uid = '';
    for (let i = 0; i < 16; ++i) {
        uid += hex[random_int(16)];
    }
    return uid;
}

function random_name() {
    let first = ['Anthony', 'Barbara', 'Charles', 'Deborah', 'Eric',
        'Francine', 'Gert', 'Heather', 'Ian', 'Jane', 'Karl',
        'Laura', 'Michael', 'Nancy', 'Oliver', 'Patricia', 'Quinn',
        'Rachael', 'Steven', 'Terese', 'Uriah', 'Vanessa', 'Walter',
        'Xena', 'Yves', 'Zandra'];
    let last = ['Jackson', 'Smith', 'Myers', 'Johnson', 'Williams',
        'Peterson', 'Stewart', 'Wilson', 'Taylor', 'Murphy', 'Hall',
        'Jones', 'Brown', 'Davis', 'Miller', 'Moore', 'Anderson',
        'Harris', 'Martin', 'Robinson', 'Clark', 'Lewis', 'Walker',
        'Collins', 'Edwards', 'Evans', 'Campbell', 'Phillips',
        'Turner', 'Roberts', 'Mitchell', 'Nelson', 'Adams', 'King',
        'Wright', 'Cooper', 'Watson', 'Coleman', 'Woods', 'Barnes'];
    return first[random_int(first.length)] + ' ' + last[random_int(last.length)];
}

function random_email(name) {
    let domains = ['gmx.de', 'gmail.com', 'yahoo.com', 'hotmail.com',
        'web.de', 'gmx.com', 'freenet.de', 't-online.de']
    let pos = name.indexOf(' ');
    let first = name.substring(0, pos).toLowerCase();
    let last = name.substring(pos + 1).toLowerCase();
    switch(random_int(4)) {
        case 0: base = first + '.' + last; break;
        case 1: base = first.substring(0,1) + last; break;
        case 2: base = first + last.substring(0,1); break;
        case 3: base = first + random_int(100).toString(); break;
    }
    return base + '@' + domains[random_int(domains.length)];
}

function random_comment() {
    let words = ['a', 'about', 'after', 'an', 'and', 'anger', 'at',
            'attempt', 'ballots', 'bananas', 'baseless', 'be',
            'before', 'best', 'both', 'by', 'chances', 'chaos',
            'chilling', 'cling', 'commit', 'complaint', 'conceding',
            'continuation', 'could', 'day', 'defeat', 'directly',
            'disbelief', 'during', 'early', 'election', 'even',
            'evening', 'expecting', 'fears', 'frankly', 'fresh',
            'get', 'going', 'happens', 'has', 'have', 'he', 'his',
            'house', 'if', 'in', 'instead', 'intention', 'it',
            'last', 'leading', 'loses', 'mail', 'men', 'most',
            'never', 'no', 'of', 'on', 'one', 'pandemic',
            'peaceful', 'person', 'power', 'quell', 'raising',
            'rebuking', 'referring', 'refused', 'renewing',
            'reporters', 'rid', 'rival', 'said', 'see', 'short',
            'sister', 'sometimes', 'sought', 'sparked', 'spent',
            'stark', 'stoke', 'stopped', 'sworn', 'that', 'the',
            'there', 'they', 'though', 'time', 'to', 'told',
            'transfer', 'us', 'very', 'violence', 'visited',
            'voting', 'warnings', 'was', 'weeks', 'well', 'went',
            'what', 'white', 'yet'];
    let list = [];
    count = random_int(15) + 2;
    while (count--) {
        list.push(words[random_int(words.length)]);
    }
    list[0] = list[0].charAt(0).toUpperCase() + list[0].slice(1);
    return list.join(' ') + '.';
}

function make_heading() {
    return "<div class='head line_id'>ID</div>" +
           "<div class='head line_key'>Key</div>" +
           "<div class='head line_age'>Age</div>" +
           "<div class='head line_name'>Name</div>" +
           "<div class='head line_email'>EMail</div>" +
           "<div class='head line_comment'>Comment</div>";
}

function make_line() {
    let id  = 1000 + random_int(1000);
    let age = 15 + random_int(55);
    let uid = generate_uid();
    let name = random_name();
    let email = random_email(name);
    let comment = random_comment();
    return "<div class='line line_id'>" + id + "</div>" +
           "<div class='line line_key'>" + uid + "</div>" +
           "<div class='line line_age'>" + age + "</div>" +
           "<div class='line line_name'>" + name + "</div>" +
           "<div class='line line_email'>" + email + "</div>" +
           "<div class='line line_comment'>" + comment + "</div>";
}

function make_lines(count) {
    let lines = '';
    while (count--) {
        let line = make_line();
        lines += line;
    }
    return lines;
}

function fill_scroll_area() {
    console.log('fill scroll area');
    document.getElementById('list').innerHTML = make_heading() + make_lines(150);
}

jQuery('#search_test').on('click', function(e) {
    e.preventDefault();
    fill_scroll_area();
});
