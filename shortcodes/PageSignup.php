<?php namespace bookclub;

/*
 * Class wraps code used to generate the type='signup' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage shortcodes
 * @license    https://opensource.org/licenses/MIT MIT
 */

class PageSignup extends ShortCode
{
    /**
     * Initialize the object.
     * @return \bookclub\PageSignup
     */
    public function __construct()
    {
        parent::__construct('bc_signup',
            [
                'shorttype' => 'signup',
                'style'     => 'page_signup.css',
                'script'    => 'page_signup.js',
                'help'      => 'page_signup',
                'nonce'     => 'signup_nonce',
                'actions'   => [[
                        'key' => 'wp_ajax_nopriv_bc_signup_submit',
                        'function' => 'signup_submit'
                    ],[
                        'key' => 'wp_ajax_bc_signup_link',
                        'function' => 'signup_link'
                    ],[
                        'key' => 'wp_ajax_nopriv_bc_signup_help',
                        'function' => 'get_help'
                    ],[
                        'key' => 'wp_ajax_nopriv_bc_signup_delete',
                        'function' => 'signup_delete'
                    ]]
            ]);
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @global string $_GET['pkey'] member web key
     * @return string HTML content
     */
    public function render(): string
    {
        parent::enqueue();
        $pkey = input_get('pkey');
        $this->log_info("Signup page $pkey");
        $json = $this->signup($pkey);
        return twig_render('page_signup', $json);
    }

    /**
     * Create an error response.
     * @param int $id error identifier
     * @param string $message error message
     * @return array JSON response
     */
    private function error(int $id, string $message): array
    {
        $this->log_info(
                "Signup URL=" . input_referer() .
                ' WordPress ID=' . \get_current_user_id() .
                ' ' . $message);
        return [
            'error' => [
                'support' => email_support(),
                'id'      => $id,
                'message' => $message
            ]
        ];
    }

    /**
     * Fetch JSON data for rendering the signup link HTML.
     * @param \bookclub\TableMembers $person member database object
     * @param int $wpid wordpress user identifier
     * @return array JSON data
     */
    private function signupLink(TableMembers $person, int $wpid): array
    {
        // ask to link account
        return [
            'ajax_url' => url_admin_ajax(),
            'nonce'    => $this->create_nonce(),
            'id'       => $person->member_id,
            'wpid'     => $wpid,
            'name'     => $person->name,
            'redirect' => url_profile()
        ];
    }

    /**
     * Check various error conditions, generate JSON response or generate JSON
     * signup link data.
     * @param int $wpid wordpress user identifier
     * @param \bookclub\TableMembers|null $person member database object
     * @return array JSON data error response
     */
    private function loggedIn(int $wpid, ?TableMembers $person): array
    {
        // does this logged in user already have a bookclub account?
        $wpperson = TableMembers::findByWordpressID($wpid);
        if (!is_null($wpperson)) {
            $json = $this->error(1003,
                    'You already have a wordpress account. ' .
                    'Go to the dashboard to make any changes.');
        } elseif (is_null($person)) {
            $json = $this->error(1004,
                    'Bad signup link. ' .
                    'The correct link should be on your bookclub profile page.');
        } elseif ($person->wordpress_id != 0) {
            $json = $this->error(1005,
                    'An unexpected problem was encountered. ' .
                    'The bookclub account was already linked. ' .
                    'Check that you used the link on your profile page. ' .
                    'If it is correct, please contact support.');
            $this->log_error("Signup error 1005 MemberID " . $person->member_id);
        } else {
            $json = $this->signupLink($person, $wpid);
        }
        return $json;
    }

    /**
     * Fill in a JSON object for signing up.
     * @param \bookclub\TableMembers $person member database object
     * @return array JSON for creating a new WordPress account
     */
    private function signupCreate(TableMembers $person): array
    {
        // show create account screen with some information filled in
        $pos = strrpos($person->name, ' ');
        if ($pos) {
            $first = substr($person->name, 0, $pos);
            $last  = substr($person->name, $pos + 1);
        } else {
            $first = $person->name;
            $last  = '';
        }
        return [
            'ajax_url' => url_admin_ajax(),
            'nonce'    => $this->create_nonce(),
            'referer'  => input_referer(),
            'id'       => $person->member_id,
            'key'      => $person->web_key,
            'first'    => $first,
            'last'     => $last,
            'email'    => $person->email,
            'redirect' => url_login(\admin_url('admin.php?page=bc_menu'))
        ];
    }

    /**
     * Fill in a JSON object for signing up or an error response.
     * @param \bookclub\TableMembers $person member database object
     * @return array JSON response with error, or success information
     */
    private function signupUser(TableMembers $person): array
    {
        $json = [];
        if ($person->wordpress_id != 0) {
            // does the person already have a wordpress account?
            $user = \get_userdata($person->wordpress_id);
            if ($user) {
                $login = $user->get('user_login');
                $json = $this->error(1006,
                        'You already have a wordpress account. ' .
                        "Please login as \"$login\".");
            } else {
                $json = $this->error(1007,
                        'There is a database consistency problem. ' .
                        'Please contact support.');
                $this->log_error("Signup error 1007 MemberID " . $person->member_id);
            }
        } else {
            // is there a wordpress user with the same e-mail?
            $user = \get_user_by('email', $person->email);
            if ($user) {
                $login = $user->get('user_login');
                $json = $this->error(1008,
                        'You already have a wordpress account. ' .
                        "Please login as \"$login\".");
            } else {
                $json = $this->signupCreate($person);
            }
        }
        $json['images'] = url_images();
        return $json;
    }

    /**
     * Fill in a JSON object for signing up or an error response.
     * @param string|null $pkey unique web key for member
     * @return array JSON response with error, or success information
     */
    private function signup(?string $pkey): array
    {
        $wpid = \get_current_user_id();
        $person = $pkey ? TableMembers::findByKey($pkey) : null;
        $json = [];

        if (!$pkey) {
            $json = ['empty' => true];
        } elseif ($wpid != 0) {
            $json = $this->loggedIn($wpid, $person);
        } elseif ($person) {
            $json = $this->signupUser($person);
        } else {
            $json = $this->error(1009,
                'Bad signup link. ' .
                'Check a recent invitation or contact support.');
        }
        return $json;
    }

    /** Actions */

    /**
     * Check that the given login string only contains valid characters.
     * @param string $login proposed login string
     * @return bool true if login string only has acceptable characters
     */
    private function check_login(string $login): bool
    {
        return preg_match('/^[\p{L}\p{N}_\-.]+$/u',$login);
    }

    /**
     * Check that the given login string is allowed.
     * @param string $login proposed login string
     * @return bool true if login string is acceptable
     */
    private function check_reserved_name(string $login): bool
    {
        if (strtolower($login) === 'admin') {
            return false;
        }
        $group = TableGroups::findByTag($login);
        if ($group) {
            return false;
        }
        return true;
    }

    /**
     * Check validity of form data for creating an account.
     * @param string|null $login login name
     * @param string|null $email email address
     * @param string|null $first first name
     * @param string|null $last last name
     * @param string|null $pass password
     * @param string|null $confirm repeat password
     * @return bool|array false if no error otherwise error response
     */
    private function validate_request(?string $login, ?string $email,
            ?string $first, ?string $last, ?string $pass, ?string $confirm)
    {
        $error = false;
        if (0 == strlen($login)) {
            $error = $this->get_response(true,
                    'Missing login name.');
        } elseif (\username_exists($login)) {
            $error = $this->get_response(true,
                    'Username already exists. If this is you, please login. ' .
                    'Otherwise please pick a different login name.');
        } elseif (!$this->check_login($login)) {
            $error = $this->get_response(true,
                    'Please restrict your login name to only include ' .
                    'letters, numbers and these special characters "._-".');
        } elseif (!$this->check_reserved_name($login)) {
            $error = $this->get_response(true,
                    'This is a reserved login name. Please pick something else.');
        } elseif (!\is_email($email)) {
            $error = $this->get_response(true, 'Email address not acceptable.');
        } elseif (0 == strlen($pass)) {
            $error = $this->get_response(true, 'Password too short!');
        } elseif ($pass !== $confirm) {
            $error = $this->get_response(true, 'Passwords do not match!');
        } elseif (strlen($first) < 2) {
            $error = $this->get_response(true, 'Missing first name.');
        } elseif (strlen($last) < 4) {
            $error = $this->get_response(true, 'Missing last name.');
        } elseif (\get_user_by('email', $email)) {
            $error = $this->get_response(true, 'Email address already in use.');
        }
        return $error;
    }

    /**
     * Create new WordPress user using book club member and form data.
     * @param \bookclub\TableMembers $person book club member
     * @param string $login login name
     * @param string $first first name
     * @param string $last last name
     * @param string $pass password
     * @param string $email email address
     * @return array JSON response
     */
    private function signup_create_user(TableMembers $person, string $login,
            string $first, string $last, string $pass, string $email): array
    {
        $response = [];
        $result = \wp_create_user($login, $pass, $email);
        if (is_int($result)) {
            $person->wordpress_id = $result;
            $person->update();
            $user = \get_userdata($result);
            $user->display_name = $first . ' ' . $last;
            $user->first_name = $first;
            $user->last_name  = $last;
            $user->set_role('subscriber');
            \wp_update_user($user);
            $response = $this->get_response(false,
                    'Account created and linked. Redirecting to login page.',
                    url_login(\admin_url('admin.php?page=bc_menu')));
        } else {
            $response = $this->get_response(true, $result->get_error_message());
        }
        return $response;
    }

    /**
     * Create new WordPress user from book club member using form data.
     * @param string $pkey member unique user web key
     * @param string $login login name
     * @param string $first first name
     * @param string $last last name
     * @param string $pass password
     * @param string $email email address
     * @return array JSON response
     */
    private function signup_user(string $pkey, string $login, string $first,
            string $last, string $pass, string $email): array
    {
        $response = [];
        $person = TableMembers::findByKey($pkey);
        if ($person) {
            $response = $this->signup_create_user(
                    $person, $login, $first, $last, $pass, $email);
        } else {
            $response = $this->get_response(true, 'Member not found.');
        }
        return $response;
    }

    /**
     * [Create Account] button clicked.
     */

    /**
     * Create a WordPress account for a book club member using the submitted
     * form data. Generate a JSON response.
     * @global string $_GET['pkey'] member unique user web key
     * @global string $_GET['login'] login name
     * @global string $_GET['first'] first name
     * @global string $_GET['last'] last name
     * @global string $_GET['pass'] password
     * @global string $_GET['confirm'] confirm password
     * @global string $_GET['email'] email address
     */
    public function signup_submit(): void
    {
        $response = $this->check_request('Signup Submit');
        if (!$response) {
            $pkey     = input_request('pkey');
            $login    = input_request('login');
            $first    = input_request('first');
            $last     = input_request('last');
            $pass     = input_request('pass');
            $confirm  = input_request('confirm');
            $email    = input_request('email');
            $response = $this->validate_request(
                    $login, $email, $first, $last, $pass, $confirm);
            if (!$response) {
                $this->log_info("Signup $login: $first $last");
                $response = $this->signup_user($pkey, $login, $first, $last,
                        $pass, $email);
            } else {
                $this->log_info("Signup $login: " . $response['message']);
            }
        }
        exit(json_encode($response));
    }

    /**
     * [Link Account] button clicked.
     */

    /**
     * Link an existing WordPress account to a book club account. Generate a
     * JSON response.
     * @global string $_GET['uid'] member user identifier
     * @global string $_GET['wpid'] WordPress user identifier
     */
    public function signup_link(): void
    {
        $response = $this->check_request('Signup Link');
        if (!$response) {
            $uid   = input_request('uid');
            $wpid = input_request('wpid');
            $person = TableMembers::findByID($uid);
            $person->wordpress_id = $wpid;
            $person->update();
            $this->log_info("Link $wpid");
            $response = $this->get_response(false,
                    'Account linked. Redirecting to profile page.',
                    url_profile());
        }
        exit(json_encode($response));
    }

    /**
     * [Remove Signup request] button clicked.
     */

    /**
     * Deletes a Bookclub account. Generate a JSON response.
     * @global string $_GET['pkey'] member unique user web key
     */
    public function signup_delete(): void
    {
        $response = $this->check_request('Signup Delete');
        if (!$response) {
            $pkey     = input_request('pkey');
            $member   = TableMembers::findByKey($pkey);
            if ($member) {
                $this->log_info("Remove $pkey");
                $memberid = $member->member_id;
                TableRecipients::deleteByID($memberid);
                TableParticipants::deleteByID($memberid);
                TableGroupMembers::deleteByMember($memberid);
                TableMembers::deleteByID($memberid);
                TableLogs::changeTypeBySelectors('NOSIGNUP',
                        ['SIGNUP', null, $memberid]);
                $response = $this->get_response(false, 'Signup request removed',
                        url_site());
            } else {
                $this->log_error("Remove $pkey not found");
                $response = $this->get_response(true, 'Signup request not found');
            }
        }
        exit(json_encode($response));
    }
}

new PageSignup();
