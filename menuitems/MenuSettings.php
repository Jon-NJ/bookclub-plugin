<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_settings' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuSettings extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuSettings
     */
    public function __construct()
    {
        parent::__construct('bc_menu_settings',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Book Club Plugin Settings',
                'menu_name'   => 'Settings',
                'menu_rank'   => RANK_SETTINGS,
                'capability'  => 'manage_options',
                'slug'        => 'bc_settings',
                'help'        => 'menu_settings',
                'script'      => 'menu_settings.js',
                'style'       => 'menu_settings.css',
                'nonce'       => 'settings_nonce',
                'actions'     => [[
                        'key' => 'wp_ajax_bc_settings_save',
                        'function' => 'settings_submit'
                    ],[
                        'key' => 'wp_ajax_bc_settings_test',
                        'function' => 'settings_test'
                    ],[
                        'key' => 'wp_ajax_bc_forwarder_test',
                        'function' => 'forwarder_test'
                    ]],
                'filters'   => [[
                        'name'     => 'plugin_action_links',
                        'function' => 'settings_link',
                        'args'     => 2
                    ]]
            ]);
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @return string HTML content
     */
    public function render(): string
    {
        if (!parent::enqueue()) {
            return '';
        }
        updateTimeZone();
        $json = $this->showSettings();
        return twig_render('menu_settings', $json);
    }

    /**
     * Fetch JSON used for the settings configuration.
     * @return array JSON for TWIG rendering
     */
    private function showSettings(): array
    {
        $defines          = splitOption('defines');
        $email_headers    = splitOption('email_headers');
        $email_params     = splitOption('email_params');
        $email_password   = getOption('email_password');
        $forward_headers  = splitOption('forward_headers');
        $forward_params   = splitOption('forward_params');
        $forward_password = getOption('forward_password');
        $json = [
            'nonce'            => $this->create_nonce(),
            'admin_url'        => url_admin_post(),
            'referer'          => url_menu('bc_settings'),
            'title'            => \get_admin_page_title(),
            'images'           => url_images(),
            'folder_covers'    => getOption('folder_covers'),
            'defines'          => $defines,
            'email_backend'    => getOption('email_backend', 'smtp'),
            'email_params'     => $email_params,
            'email_headers'    => $email_headers,
            'email_password'   => str_pad(' ', strlen($email_password)),
            'email_sleep'      => getOption('email_sleep'),
            'error_sender'     => getOption('error_sender'),
            'error_recipient'  => getOption('error_recipient'),
            'forward_imap'     => getOption('forward_imap'),
            'forward_user'     => getOption('forward_user'),
            'forward_password' => str_pad(' ', strlen($forward_password)),
            'forward_backend'  => getOption('forward_backend', 'smtp'),
            'forward_params'   => $forward_params,
            'forward_headers'  => $forward_headers,
            'pages'            => \get_pages(),
            'permalink'        => \get_option('permalink_structure'),
            'page_book'        => getOption('page_book'),
            'page_forthcoming' => getOption('page_forthcoming'),
            'page_previous'    => getOption('page_previous'),
            'page_rsvp'        => getOption('page_rsvp'),
            'page_signup'      => getOption('page_signup')
        ];
        return $json;
    }

    /**
     * For the given option name, the request is fetched and then set in
     * the database.
     * @param string $option_name option name and also request name
     */
    private function setOption(string $option_name): void
    {
        setOption($option_name, input_request($option_name));
    }

    /**
     * Save settings.
     * @global string $_REQUEST['defines'] macro defines
     * @global string $_REQUEST['email_backend'] one of mail, smtp or sendmail
     * @global string $_REQUEST['email_headers'] email headers with macros
     * @global string $_REQUEST['email_params'] email parameters
     * @global string $_REQUEST['email_password'] password for email address
     * @global string $_REQUEST['email_sleep'] optional microseconds to wait
     * before sending the next email
     * @global string $_REQUEST['error_sender'] email address for sending errors
     * @global string $_REQUEST['error_recipient'] recipient email address for errors
     * @global string $_REQUEST['forward_imap'] IMAP connection for forwarder
     * @global string $_REQUEST['forward_user'] user account for IMAP
     * @global string $_REQUEST['forward_password'] password for IMAP account
     * @global string $_REQUEST['forward_backend'] one of mail, smtp or sendmail
     * @global string $_REQUEST['forward_headers'] email headers with macros
     * @global string $_REQUEST['forward_params'] email parameters
     * @global string $_REQUEST['page_book'] partial URL for book page
     * @global string $_REQUEST['page_forthcoming'] partial URL for page with
     * upcoming books
     * @global string $_REQUEST['page_previous'] partial URL for page with
     * previous books
     * @global string $_REQUEST['page_rsvp'] partial URL for page to RSVP on
     * without signing in
     * @global string $_REQUEST['page_signup'] partial URL for page to create
     * a WordPress account
     */
    public function settings_submit()
    {
        $response = $this->check_request('Settings submit');
        if (!$response) {
            setOption('defines', input_request('defines'));
            $this->setOption('email_backend');
            $this->setOption('email_params');
            $this->setOption('email_headers');
            $password = input_request('email_password');
            if (!preg_match('/^ *$/', $password)) {
                setOption('email_password', $password);
            }
            $this->setOption('email_sleep');
            $this->setOption('error_sender');
            $this->setOption('error_recipient');
            $imap     = input_request('forward_imap');
            $iuser    = input_request('forward_user');
            if ((getOption('forward_imap') !== $imap) ||
                    (getOption('forward_user') !== $iuser)) {
                // account change, reset last checked time
                setOption('forward_time', '');
                setOption('forward_imap', $imap);
                setOption('forward_user', $iuser);
            }
            $password = input_request('forward_password');
            if (!preg_match('/^ *$/', $password)) {
                setOption('forward_password', $password);
            }
            $this->setOption('forward_backend');
            $this->setOption('forward_params');
            $this->setOption('forward_headers');
            $this->setOption('page_book');
            $this->setOption('page_forthcoming');
            $this->setOption('page_previous');
            $this->setOption('page_rsvp');
            $this->setOption('page_signup');
            // TODO folder covers
            //$folder_covers    = input_request('folder_covers');
            $response = $this->get_response(false, 'Settings updated');
        }
        exit(json_encode($response));
    }

    /**
     * Send test email using the normal account.
     */
    public function settings_test()
    {
        $response = $this->check_request('Settings test email');
        if (!$response) {
            $wpid = \get_current_user_id();
            $person = TableMembers::findByWordpressID($wpid);
            if (!$person) {
                $response = $this->get_response(true, 'Make sure you have a bookclub profile');
            } else {
                $this->log_debug("Sending to " . $person->member_id);
                $sender = new EMail();
                if ($sender->sendTest($person->member_id)) {
                    $response = $this->get_response(false, $sender->getLastError());
                } else {
                    $response = $this->get_response(true, $sender->getLastError());
                }
            }
        }
        exit(json_encode($response));
    }

    /**
     * Send test email using the forwarder account.
     */
    public function forwarder_test()
    {
        $response = $this->check_request('Forwarder test email');
        if (!$response) {
            $wpid = \get_current_user_id();
            $person = TableMembers::findByWordpressID($wpid);
            if (!$person) {
                $response = $this->get_response(true, 'Make sure you have a bookclub profile');
            } else {
                $this->log_debug("Sending to " . $person->member_id);
                $sender = new EMail('forward');
                if ($sender->sendTest($person->member_id)) {
                    $response = $this->get_response(false, $sender->getLastError());
                } else {
                    $response = $this->get_response(true, $sender->getLastError());
                }
            }
        }
        exit(json_encode($response));
    }

    /**
     * Add settings links for the plugin.
     * @param array $links collection of settings
     * @param string $filename the file to match to see whether to add the links
     * @return array collection of links
     */
    public function settings_link(array $links, string $file): array
    {
	if (plugin_basename(dirname(dirname(__FILE__))) . '/bookclub.php' === $file ) {
            $in = '<a href="' . url_menu('bc_settings') . '">Settings</a>';
            array_unshift($links, $in);
	}
	return $links;
    }
}

new MenuSettings();
