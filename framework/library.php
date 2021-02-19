<?php namespace bookclub;

/*
 * This is the root file for the general library. It also links in all other
 * global classes and libraries. Most of the functions defined here are for
 * access to the environment such as server, get, request definitions but
 * also urls and file locations. The autoloader for the Symphony Composer
 * package is also included.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @license    https://opensource.org/licenses/MIT MIT
 */

// use composer
require_once 'vendor/autoload.php';

require_once(BOOKCLUBPATH.DS.'framework/database.php');
require_once(BOOKCLUBPATH.DS.'framework/logging.php');
require_once(BOOKCLUBPATH.DS.'framework/layouter.php');
require_once(BOOKCLUBPATH.DS.'framework/EMail.php');
require_once(BOOKCLUBPATH.DS.'framework/Manager.php');
require_once(BOOKCLUBPATH.DS.'framework/Page.php');
require_once(BOOKCLUBPATH.DS.'framework/MenuItem.php');
require_once(BOOKCLUBPATH.DS.'framework/ShortCode.php');
require_once(BOOKCLUBPATH.DS.'framework/REST.php');
require_once(BOOKCLUBPATH.DS.'framework/Path.php');
require_once(BOOKCLUBPATH.DS.'framework/pages.php');

/* generate keys, encode, decode */

/**
 * Generate a random identifier.
 * @return string 16 digit hexadecimal string
 */
function generate_key(): string
{
    $keyset = "0123456789ABCDEF";
    do {
        $key = "";
        for ($i = 0; $i < 16; $i++) {
            $key .= substr($keyset, rand(0, strlen($keyset)-1), 1);
        }
    } while (substr($key, 0, 1) == "0");
    return $key;
}

/** functions based on server data, get and request parameters, etc.

/**
 * Fetch a locale language descriptor from the server if available.
 * @return string locale string (default to en_US)
 */
function get_locale(): string
{
    $locale = \Locale::acceptFromHttp(input_server('HTTP_ACCEPT_LANGUAGE'));
    if (!$locale) {
        $locale = 'en_US';
    }
    return $locale;
}

/**
 * Fetch an offset for the start of the week based on the user locale.
 * @return int 0 to 6 for SUNDAY to SATURDAY
 */
function get_start_of_week(): int
{
    $cal = \IntlCalendar::createInstance(null, get_locale());
    //return \get_option('start_of_week');
    return $cal->getFirstDayOfWeek() - 1;
}

/**
 * Same as file_get_contents(url) but also works if allow_url_fopen not On.
 * @param string $url Web URL to fetch
 * @return string contents at web URL
 */
function get_url_file(string $url): string
{
    $ch = curl_init();
    curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, \CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * For the given timezone, fetch the ICAL timezone definition.
 * @param string $tzone time zone name (e.g. Europe/Berlin)
 * @return string multiple lines stripping starting/ending with BEGIN/END:VTIMEZONE
 */
function get_timezone(string $tzone): string
{
    $ignore = ['BEGIN:VCALENDAR', 'PRODID:', 'VERSION:', 'END:VCALENDAR'];
//    $data = @file_get_contents("http://tzurl.org/zoneinfo-outlook/$tzone");
    $data = @get_url_file("http://tzurl.org/zoneinfo-outlook/$tzone");
    if ($data) {
        $filter = [];
        foreach (preg_split('/\r\n|\n|\r/', $data, -1, PREG_SPLIT_NO_EMPTY) as $line) {
            $include = true;
            foreach ($ignore as $prefix) {
                if ($prefix === substr($line, 0, strlen($prefix))) {
                    $include = false;
                }
            }
            if ($include) {
                $filter[] = $line;
            }
        }
        $data = join("\n", $filter);
    }
    return $data;
}

/**
 * Check if the timezone is defined and matches the cached value. If needed, a
 * web scrape is made to fetch the VTIMEZONE definition matching the time zone
 * string. It takes about a second.
 */
function updateTimeZone(): void
{
    $wp_tz         = \get_option('timezone_string'); // e.g. Europe/Berlin
    $cached_tz     = getOption('timezone_string');
    $cached_tzinfo = getOption('timezone_info'); // BEGIN:VTIMEZONE ...
    if ($wp_tz && (($wp_tz !== $cached_tz)) || !$cached_tzinfo) {
        $cached_tzinfo = get_timezone($wp_tz);
        if ($cached_tzinfo) {
            setOption('timezone_string', $wp_tz);
            setOption('timezone_info', $cached_tzinfo);
        }
    }
}

/**
 * Fetch the nonce parameter for the current request/get call.
 * @return string request/get nonce parameter
 */
function get_nonce(): ?string
{
    return is_request() ? input_request('nonce') : input_get('nonce');
}

/**
 * Fetch the specified GET parameter.
 * @param string $key parameter name
 * @return string|null GET parameter if defined
 */
function input_get(string $key): ?string
{
    return \filter_input(INPUT_GET, $key);
}

/**
 * Fetch the adjusted REQUEST_URI.
 * @return string adjusted request URI (unslashed and escaped)
 */
function input_referer(): string
{
    return \esc_attr(\wp_unslash($_SERVER['REQUEST_URI']));
}

/**
 * Fetch the specified REQUEST (POST) parameter.
 * @param string $key parameter name
 * @return string|null REQUEST parameter if defined
 */
function input_request(string $key): ?string
{
    return \array_key_exists($key, $_REQUEST) ?
            \wp_unslash($_REQUEST[$key]) : null;
}

/**
 * Fetch the specified SERVER parameter.
 * @param string $key parameter name
 * @return string|null SERVER parameter if defined
 */
function input_server(string $key): ?string
{
    return $_SERVER[$key];
}

/**
 * Determine if running in a developer context.
 * @return bool true if running on the development server
 */
function is_development(): bool
{
    return stripos(getenv('HTTP_HOST'), 'sunshine') != 0;
}

/**
 * Test if the current action was a POST (REQUEST defined).
 * @return bool true if post request
 */
function is_request(): bool
{
    return isset($_REQUEST);
}

/* configuration environment */

/**
 * Fetch the support email address.
 * @return string email address
 */
function email_support(): string
{
    $macros = [];
    foreach (splitOption('defines') as $define) {
        $pos = strpos($define, '=');
        $macros[substr($define, 0, $pos)] = substr($define, $pos + 1);
    }
//    return getOption('support_email');
    return array_key_exists('support', $macros) ? $macros['support'] : '';
}

/**
 * Fetch the forwarder email address.
 * @return string email address
 */
function email_forwarder(): string
{
    $forwarder = getOption('forward_user');
    $macros    = twig_macro_fields([]);
    return macro_replace($forwarder, $macros);
}

/**
 * During initialization the cover folder is created if it does not exist.
 */
function create_cover_folder(): void
{
    $folder = folder_covers();
    if (!file_exists($folder)) {
        mkdir($folder);
    }
}

/**
 * Fetch the absolute path to the folder containing book covers.
 * @return string absolute file path to the folder with book covers
 */
function folder_covers(): string
{
    $default = \wp_upload_dir()['basedir'] . '/covers/';
    return getOption('folder_covers', $default);
}

/**
 * Loop through all .php files in a folder and require once unless the filename
 * contains 'test' and it is not development.
 * @param string $folder path of folder
 */
function require_once_folder(string $folder): void
{
    foreach (new \DirectoryIterator($folder) as $file) {
        $filename = $file->getFilename();
        if (preg_match('/\.php$/', $filename)) {
            if (!preg_match('/test/i', $filename) || is_development()) {
                require_once $folder.DS.$filename;
            }
        }
    }
}

/**
 * Utility function to build a URL with parameters.
 * @param string $base base page such as "signup" or "?page_id=5"
 * @param string $params optional parameters
 * @return string constructed URL
 */
function url_build(string $base, string $params = ''): string
{
    if ($params) {
        $params = ((false === strpos($base, '?')) ? '?' : '&') . $params;
    }
    return \get_site_url() . '/' . $base . $params;
}

/**
 * Fetch the URL to use for AJAX requests.
 * @return string WP AJAX URL
 */
function url_admin_ajax(): string
{
    return \admin_url('admin-ajax.php');
}

/**
 * Fetch the URL to use for POSTs.
 * @return string WP POST URL
 */
function url_admin_post(): string
{
    return \admin_url('admin-post.php');
}

/**
 * Fetch the URL for a web page with information about the given book.
 * @param int $bid unique book identifier
 * @return string URL for a page that uses the shortcode for a book
 */
function url_book(int $bid): string
{
    $book = getOption('page_book', 'book');
    return url_build($book, "bid=$bid");
}

/**
 * Fetch the URL for an IMG with a book cover.
 * @param string $url filename for the book cover
 * @return string URL for a book cover
 */
function url_cover(string $url): string
{
    $default = '/wp-content/uploads/covers/';
    $covers = getOption('folder_covers', $default);
    return \get_site_url() . $covers . $url;
}

/**
 * Fetch a URL for the dashboard page to edit a given event.
 * @param string $eventid event identifier
 * @return string URL to dashboard for editing an event
 */
function url_event_edit(string $eventid): string
{
    return \admin_url("admin.php?page=bc_events&action=edit&eventid=$eventid");
}

/**
 * Fetch the URL for a web page with information about forthcoming books.
 * @param int|null $gid group identifier
 * @return string URL for a page that uses the shortcode for forthcoming books
 */
function url_forthcoming(?int $gid): string
{
    $base = getOption(
            'page_forthcoming', 'forthcoming-books');
    $params = (isset($gid) && ($gid != 0)) ? "gid=$gid" : '';
    return url_build($base, $params);
}

/**
 * Fetch a URL for downloading an ICAL file for the given user and event.
 * @param string $pkey web key for the given user
 * @param string $eventid event identifier
 * @return string URL for downloading an ical file
 */
function url_ical(string $pkey, string $eventid): string
{
    return $GLOBALS['icaldownload']->url_ical($pkey, $eventid);
}

/**
 * Fetch the base URL for the plugin icons.
 * @return string base URL for bookclub plugin icons
 */
function url_images(): string
{
    return \plugins_url("images/", BOOKCLUBFILE);
}

/**
 * Fetch a URL for logging in to WordPress.
 * @param string|null $redirect include redirect url if specified
 * @return string URL for logging in
 */
function url_login(?string $redirect): string
{
    return \wp_login_url() .
            ($redirect ? '?redirect_to=' . urlencode($redirect) : '');
}

/**
 * Fetch a URL for the given dashboard menu item.
 * @param string $slug menu item slug
 * @return string URL for dashboard menu item
 */
function url_menu(string $slug) : string
{
    return \menu_page_url($slug, false);
}

/**
 * Fetch the URL for a web page with a listing of previous books.
 * @param int|null $year optional year
 * @param int|null $gid optional group identifier
 * @return string URL for a page that uses the shortcode for previous books
 */
function url_previous(?int $year, ?int $gid): string
{
    $base = getOption('page_previous', 'previous-books');
    $params = '';
    if (isset($year)) {
        if (isset($gid)) {
            $params = "y=$year&gid=$gid";
        } else {
            $params = "y=$year";
        }
    } elseif (isset($gid)) {
        $params = "gid=$gid";
    }
    return url_build($base, $params);
}

/**
 * Fetch a URL for a web page with the dashboard profile.
 * @return string URL for the users profile page
 */
function url_profile(): string
{
    return \admin_url('admin.php?page=bc_menu');
}

/**
 * Fetch a URL for the profile page of the given WordPress user.
 * @param int $wordpress_id WordPress user id
 * @return string URL to WordPress profile for the given user
 */
function url_profile_user(int $wordpress_id): string
{
    return \admin_url("user-edit.php?user_id=$wordpress_id");
}

/**
 * Fetch the current web page request.
 * @return string URL for the current request URI
 */
function url_request(): string
{
    return url_site() . input_server('REQUEST_URI');
}

/**
 * Fetch the URL to RSVP to an event for a specified person.
 * @param object $event event record (or joined equivalent)
 * @param string $webkey web key for the person rsvp-ing or empty for logged in
 * user
 * @return string URL for a page that uses the shortcode for RSVPing
 */
function url_rsvp(object $event, string $webkey = ''): string
{
    $base = getOption('page_rsvp', 'rsvp');
    $params = 'eid=' . $event->event_id . ($webkey ? "&pkey=$webkey" : '');
    return url_build($base, $params);
}

/**
 * Fetch the URL for a web page where a user can sign up.
 * @param object $person member record
 * @return string URL for a page that uses the shortcode for signing up
 */
function url_signup($person): string
{
    $base = getOption('page_signup', 'signup');
    $params = 'pkey=' . $person->web_key;
    return url_build($base, $params);
}

/**
 * Fetch base site URL without page (e.g. http://myhost.org).
 * @return string base site URL
 */
function url_site(): string
{
    //return input_server('REQUEST_SCHEME') . '://' . input_server('SERVER_NAME');
    $site = \get_site_url();
    $pos  = strpos($site, '/', 8);
    return $pos ? substr($site, 0, $pos) : $site;
}

/* debug functions */

/**
 * Write out a file for debug use containing the given data.
 * @param string $option option name for configuration file
 * @param string $default filename to use if the option is not defined
 * @param string $data data to write to the given file
 */
function write_debug_file(string $option, string $default, string $data): void
{
    $fn = getOption($option, $default);
    if ($fn) {
        $fh = fopen($fn, 'w');
        fwrite($fh, $data);
        fclose($fh);
    }
}

/* lock functions */

/**
 * Generate a filename based on the given lock key.
 * @param string $key name of the lock
 * @return string filename of lock file
 */
function get_lock_filename(string $key): string
{
    return \get_temp_dir() . "wp_bc_$key.lock";
}

/**
 * Check if the given lock is in effect. If the lock has not been claimed it
 * only checks that the file exists and has not expired. If it was claimed it
 * also checks that the process is still active.
 * @param string $key name of the lock
 * @return bool true if lock still exists
 */
function is_lock(string $key): bool
{
    $logger = \Logger::getLogger("files.lock");
    $lockfn = get_lock_filename($key);
    // short circuit - quit if file does not exist
    if (!file_exists($lockfn)) {
        $logger->debug("Lock file does not exist: $lockfn");
        return false;
    }
    // short circuit - quit if file older than four minutes
    if (time() - filectime($lockfn) > 240) {
        $logger->debug("Lock file expired: $lockfn");
        return false;
    }
    $pid = file_get_contents($lockfn);
    if (!$pid) {
        return true;
    }
    $logger->debug("Lock file pid: $pid");
    return posix_getsid($pid);
}

/**
 * Create a (file based) lock if possible. The lock originally is not attached
 * to a process. A call to claim_lock will do this. An claimed lock will also be
 * unlocked if the process is no longer found.
 * @param string $key name of the lock
 * @return bool false if the lock already exists or cannot be created
 */
function create_lock(string $key): bool
{
    if (is_lock($key)) {
        return false;
    }
    $logger = \Logger::getLogger("files.lock");
    $lockfn = get_lock_filename($key);
    $logger->debug("Lock filename: '$lockfn'");
    if (file_exists($lockfn)) {
        unlink($lockfn);
    }
    touch($lockfn);
    $exists = file_exists($lockfn);
    if (!$exists) {
        $logger->error("Could not create lock file - $lockfn");
        return false;
    }
    return true;
}

/**
 * Claim an already created but unclaimed lock. Abort if already claimed. The
 * claim will ensure that the lock is removed if the current process dies.
 * @param string $key name of the lock
 */
function claim_lock(string $key): void
{
    $logger = \Logger::getLogger("files.lock");
    $lockfn = get_lock_filename($key);
    $logger->debug("Claim lock: '$lockfn'");
    if (!file_exists($lockfn)) {
        $logger->error("Lock file missing: $lockfn");
        die();
    }
    $pid = file_get_contents($lockfn);
    if ($pid) {
        $logger->error("Lock file already claimed: $lockfn $pid");
        die();
    }
    file_put_contents($lockfn, getmypid());
}

/**
 * Remove the (file based) lock.
 * @param string $key name of the lock
 */
function free_lock(string $key): void
{
    $logger = \Logger::getLogger("files.lock");
    $lockfn = get_lock_filename($key);
    $logger->debug("Free lock '$key'");
    if (!unlink($lockfn)) {
       $logger->error("Error unlocking $key");
    }
}
