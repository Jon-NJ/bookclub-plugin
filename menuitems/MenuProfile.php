<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_profile' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuProfile extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuProfile
     */
    public function __construct()
    {
        parent::__construct('bc_menu_profile',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Bookclub Profile',
                'menu_name'   => 'Profile',
                'menu_rank'   => RANK_PROFILE,
                'capability'  => 'read',
                'slug'        => 'bc_profile',
                'help'        => 'menu_profile',
                'script'      => 'menu_profile.js',
                'style'       => 'menu_profile.css',
                'nonce'       => 'profile_nonce',
                'actions'     => [[
                        'key' => 'wp_ajax_bc_profile_update',
                        'function' => 'profile_update'
                    ],[
                        'key' => 'wp_ajax_bc_wordpress_update',
                        'function' => 'wordpress_update'
                    ],[
                        'key' => 'admin_post_bc_profile_create',
                        'function' => 'profile_create'
                    ],[
                        'key' => 'wp_ajax_bc_email_test',
                        'function' => 'email_test'
                    ],[
                        'key' => 'wp_ajax_bc_reset_key',
                        'function' => 'reset_key'
                    ],[
                        'key' => 'wp_ajax_bc_profile_delete',
                        'function' => 'profile_delete'
                    ],[
                        'key' => 'wp_ajax_bc_profile_heartbeat',
                        'function' => 'profile_heartbeat'
                    ]],
                'filters'   => [[
                        'name'     => 'user_profile_update_errors',
                        'args'     => 3,
                        'function' => 'precheck_profile_change'
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
        $wpid = \get_current_user_id();
        $this->log_info("User: $wpid");
        $member = TableMembers::findByWordpressID($wpid);
        if ($member) {
            $json = $this->showProfile($wpid, $member);
        } else {
            $json = $this->showNoProfile($wpid);
        }
        return twig_render('menu_profile', $json);
    }

    /**
     * Fetch JSON used for user without a bookclub account.
     * @param int $wpid wordpress identifier
     * @return array JSON for twig rendering
     */
    private function showNoProfile(int $wpid): array
    {
        // wordpress and announcment groups
        // collect $groups - list of wordpres groups and flag whether the user has joined
        $extra_groups = [];
        $tobj = new JoinGroupsGroupUsers();
        $tobj->loopForUser($wpid);
        while ($tobj->fetch()) {
            $extra_groups[] = [
                'id'          => $tobj->group_id,
                'tag'         => $tobj->tag,
                'description' => $tobj->description,
                'flag'        => $tobj->inGroup(),
                'url'         => $tobj->url,
                'type'        => $tobj->type
            ];
        }
        return [
            'no_profile'     => 'true',
            'admin_url'      => url_admin_post(),
            'images'         => url_images(),
            'support'        => email_support(),
            'forwarder'      => email_forwarder(),
            'action'         => 'bc_profile_create',
            'nonce'          => $this->create_nonce(),
            'referer'        => url_request(),
            'wordpress_id'   => $wpid,
            'admin'          => \current_user_can('editor'),
            'receive_others' => \get_user_meta($wpid, 'bc_receive_others', true),
            'extra'          => $extra_groups
        ];
    }

    /**
     * Generate the JSON containing information about future books.
     * @param array $all an array containing all group identifiers
     * @param array $list an array containing group identifiers for the given person
     * @return array JSON for rendering upcoming books TWIG
     */
    private function getBooks(array $all, array $list): array
    {
        // collect $books of future meetings
        $books = [];
        $cond = join(',', $list);
        if ($cond == '') { $cond = join(',', $all); }
        $tobj = new JoinDatesBooksAuthorsGroups();
        $tobj->loopFutureForGroups($cond);
        while ($tobj->fetch()) {
            $books[] = [
                'date'    => $tobj->day,
                'group'   => $tobj->group_id,
                'tag'     => $tobj->tag,
                'url'     => url_book($tobj->book_id),
                'title'   => $tobj->title,
                'author'  => $tobj->name,
                'private' => $tobj->private
            ];
        }
        return $books;
    }

    /**
     * Fetch JSON used for the profile.
     * @param int $wpid wordpress identifier
     * @param \bookclub\TableMembers $member the bookclub member object
     * @return array JSON for TWIG rendering
     */
    private function showProfile(int $wpid, TableMembers $member): array
    {
        $groups = [];
        $list   = [];
        $all    = [];
        // collect $groups - list of groups and flag whether the user has joined
        $tobj = new JoinGroupsGroupMembers();
        $tobj->loopForMember($member->member_id, BC_GROUP_CLUB);
        while ($tobj->fetch()) {
            $all[$tobj->group_id] = $tobj->group_id;
            if ($tobj->inGroup()) {
                $list[$tobj->group_id] = $tobj->group_id;
            }
            $groups[] = [
                'id'          => $tobj->group_id,
                'tag'         => $tobj->tag,
                'description' => $tobj->description,
                'flag'        => $tobj->inGroup(),
                'url'         => $tobj->url,
                'type'        => $tobj->type
            ];
        }
        // wordpress and announcment groups
        // collect $groups - list of wordpres groups and flag whether the user has joined
        $extra_groups = [];
        $tobj = new JoinGroupsGroupUsers();
        $tobj->loopForUser($member->wordpress_id);
        while ($tobj->fetch()) {
            $extra_groups[] = [
                'id'          => $tobj->group_id,
                'tag'         => $tobj->tag,
                'description' => $tobj->description,
                'flag'        => $tobj->inGroup(),
                'url'         => $tobj->url,
                'type'        => $tobj->type
            ];
        }

        // collect $events the member is invited to
        $myevents = [];
        $otherevents = [];
        $tobj = new JoinEventsParticipants();
        $tobj->loopFutureForMember($member->member_id);
        while ($tobj->fetch()) {
            $private = false;
            if ($tobj->priority) {
                $ahead = new \DateTime($tobj->starttime);
                $ahead->sub(new \DateInterval('PT' . ($tobj->priority + \get_option('gmt_offset')) . 'H'));
                $private = $ahead > new \DateTime(); 
            } else {
                $private = $tobj->private;
            }
            if ($tobj->member_id) {
                $myevents[] = [
                    'time'    => $tobj->starttime,
                    'url'     => url_rsvp($tobj),
                    'eventid' => $tobj->event_id,
                    'summary' => $tobj->summary,
                    'rsvp'    => $tobj->rsvp,
                    'waiting' => $tobj->waiting
                ];
            } elseif (!$private) {
                $otherevents[] = [
                    'time'    => $tobj->starttime,
                    'url'     => url_rsvp($tobj),
                    'summary' => $tobj->summary,
                    'eventid' => $tobj->event_id
                ];
            }
        }

        $user = \get_userdata($wpid);
        $first = $user->first_name ?: '(First name missing)';
        $last  = $user->last_name ?: '(Last name missing)';
        $json = [
            'admin_url'      => url_admin_post(),
            'action'         => 'bc_profile_update',
            'nonce'          => $this->create_nonce(),
            'referer'        => url_request(),
            'images'         => url_images(),
            'support'        => email_support(),
            'forwarder'      => email_forwarder(),
            'wordpress_id'   => $wpid,
            'admin'          => \current_user_can('editor'),
            'id'             => $member->member_id,
            'key'            => $member->web_key,
            'name'           => "$first $last",
            'email'          => $user->user_email,
            'active'         => $member->active,
            'noemail'        => $member->noemail,
            'format'         => $member->format,
            'ical'           => $member->ical,
            'hittime'        => $member->hittime,
            'public_email'   => $member->public_email,
            'receive_others' => \get_user_meta($wpid, 'bc_receive_others', true),
            'groups'         => $groups,
            'extra'          => $extra_groups,
            'books'          => $this->getBooks($all, $list),
            'events'         => $myevents,
            'others'         => $otherevents
        ];

        $member->hit();
        return $json;
    }

    /** Actions */

    /**
     * Handle POST for profile save button. Generate a redirect/refresh.
     * @global string $_REQUEST['group'*] 1 if included in given group
     */
    public function wordpress_update(): void
    {
        $response = $this->check_request('Wordpress update');
        if (!$response) {
            $wpid = \get_current_user_id();
            $tobj = new JoinGroupsGroupUsers();
            $tobj->loopForUser($wpid);
            while ($tobj->fetch()) {
                $all[$tobj->group_id] = $tobj->group_id;
                if (input_request('group' . $tobj->group_id)) {
                    $list[$tobj->group_id] = $tobj->group_id;
                }
                if (($tobj->inGroup()) &&
                        !(input_request('group' . $tobj->group_id))) {
                    TableGroupUsers::removeUser($tobj->group_id, $wpid);
                } elseif (!($tobj->inGroup()) &&
                        (input_request('group' . $tobj->group_id))) {
                    TableGroupUsers::addUser($tobj->group_id, $wpid);
                }
            }
            \update_user_meta(\get_current_user_id(), 'bc_receive_others',
                    input_request('receive'));
            $response = $this->get_response(false, 'WP Profile updated');
            $this->log_info("WP Profile updated");
        }
        exit(json_encode($response));
    }

    /**
     * Handle POST for profile save button. Generate a redirect/refresh.
     * @global string $_REQUEST['pkey'] member web key
     * @global string $_REQUEST['email'] email address
     * @global string $_REQUEST['active'] 1 if member active
     * @global string $_REQUEST['noemail'] 1 to turn off email
     * @global string $_REQUEST['format'] 0 text only, 1 html
     * @global string $_REQUEST['ics'] 0 no ical attachment, else 1
     * @global string $_REQUEST['public_email'] 0 email private else 1
     * @global string $_REQUEST['receive_others'] 0 email only from admins, else 1
     * @global string $_REQUEST['group'*] 1 if included in given group
     */
    public function profile_update(): void
    {
        $response = $this->check_request('Profile submit');
        if (!$response) {
            $list = [];
            $all  = [];
            $group_mod = false;
            $pkey   = input_request('pkey');
            $member = TableMembers::findByKey($pkey);
            $member->email          = input_request('email');
            $member->active         = input_request('active');
            $member->noemail        = input_request('noemail');
            $member->format         = input_request('format');
            $member->ical           = input_request('ics');
            $member->public_email   = input_request('public_email');
            //$member->receive_others = $receive_others;
            $member->update();
            \update_user_meta(\get_current_user_id(), 'bc_receive_others',
                    input_request('receive'));

            $tobj = new JoinGroupsGroupMembers();
            $tobj->loopForMember($member->member_id, BC_GROUP_CLUB);
            while ($tobj->fetch()) {
                $all[$tobj->group_id] = $tobj->group_id;
                if (input_request('group' . $tobj->group_id)) {
                    $list[$tobj->group_id] = $tobj->group_id;
                }
                if (($tobj->inGroup()) &&
                        !(input_request('group' . $tobj->group_id))) {
                    TableGroupMembers::removeMember($tobj->group_id,
                            $member->member_id);
                    $group_mod = true;
                } elseif (!($tobj->inGroup()) &&
                        (input_request('group' . $tobj->group_id))) {
                    TableGroupMembers::addMember($tobj->group_id,
                            $member->member_id);
                    $group_mod = true;
                }
            }
            $tobj = new JoinGroupsGroupUsers();
            $tobj->loopForUser($member->wordpress_id);
            while ($tobj->fetch()) {
                $all[$tobj->group_id] = $tobj->group_id;
                if (input_request('group' . $tobj->group_id)) {
                    $list[$tobj->group_id] = $tobj->group_id;
                }
                if (($tobj->inGroup()) &&
                        !(input_request('group' . $tobj->group_id))) {
                    TableGroupUsers::removeUser($tobj->group_id,
                            $member->wordpress_id);
                } elseif (!($tobj->inGroup()) &&
                        (input_request('group' . $tobj->group_id))) {
                    TableGroupUsers::addUser($tobj->group_id,
                            $member->wordpress_id);
                }
            }
            $response = $this->get_response(false, 'Profile updated');
            if ($group_mod) {
                $response['upcoming'] = twig_render('upcoming_books',
                        ['books' => $this->getBooks($all, $list)]);
            }
            $this->log_info("Profile updated");
        }
        exit(json_encode($response));
    }

    /**
     * Create a book club profile. Generate a redirect/refresh.
     */
    public function profile_create(): void
    {
        $response = $this->check_request('Profile create');
        if (!$response) {
            $user   = \wp_get_current_user();
            $member = new TableMembers();
            $member->member_id       = TableMembers::getNextID();
            $member->web_key         = generate_key();
            $member->name            =
                    $user->get('first_name') . ' ' . $user->get('last_name');
            if ('' === $member->name) {
                $member->name        = $user->nickname;
            }
            $member->email           = $user->user_email;
            $member->active          = 1;
            $member->format          = 1;
            $member->ical            = 1;
            $member->noemail         = 0;
            $member->hittime         = date('Y-m-d H:i:s', time());
            $member->wordpress_id    = input_request('wpid');
            $member->public_email    = 0;
            $member->receive_others  = 0;
            $member->insert();
            \wp_redirect(input_request('referer'), 303);
        }
        die();
    }

    /**
     * Send test email.
     */
    public function email_test()
    {
        $response = $this->check_request('Settings test email');
        if (!$response) {
            $wpid   = \get_current_user_id();
            $member = TableMembers::findByWordpressID($wpid);
            $this->log_debug("Sending to " . $member->member_id);
            $sender = new EMail();
            if ($sender->sendTest($member->member_id)) {
                $response = $this->get_response(false, $sender->getLastError());
            } else {
                $response = $this->get_response(true, $sender->getLastError());
            }
        }
        exit(json_encode($response));
    }

    /**
     * Change web key for current user.
     */
    public function reset_key()
    {
        $response = $this->check_request('Resetting web key');
        if (!$response) {
            $wpid = \get_current_user_id();
            $member = TableMembers::findByWordpressID($wpid);
            $newkey = generate_key();
            $this->log_info("Change key for $wpid: " . $member->web_key .
                    " => $newkey");
            $response = $this->get_response(false, "Web key changed");
            $response['newkey'] = $newkey;
            $member->web_key = $newkey;
            $member->update();
        }
        exit(json_encode($response));
    }

    /**
     * Deletes a WordPress and Bookclub account. Generate a JSON response.
     */
    public function profile_delete(): void
    {
        $response = $this->check_request('Profile delete');
        if (!$response) {
            $wpid = \get_current_user_id();
            $member = TableMembers::findByWordpressID($wpid);
            if ($member) {
                $this->log_info("Remove user $wpid");
                $memberid = $member->member_id;
                TableRecipients::deleteByID($memberid);
                TableParticipants::deleteByID($memberid);
                TableGroupMembers::deleteByMember($memberid);
                TableGroupUsers::deleteByUser($wpid);
                TableMembers::deleteByID($memberid);
                TableLogs::changeTypeBySelectors('NOSIGNUP',
                        ['SIGNUP', null, $memberid]);
                \wp_delete_user($wpid);
                $response = $this->get_response(false, 'WordPress account removed',
                        url_site());
            } else {
                $this->log_error("Remove user $wpid not found");
                $response = $this->get_response(true,
                        'There was some problem with the action. The account was not found');
            }
        }
        exit(json_encode($response));
    }

    /**
     * Update the nonce while on the profile page.
     */
    public function profile_heartbeat(): void
    {
        $response = $this->check_request('Profile heartbeat');
        if (!$response) {
            $this->log_debug("Profile heartbeat refresh");
            $wpid     = \get_current_user_id();
            $nonce    = $this->create_nonce();
            $message  = '';
            if ($nonce != input_request('nonce')) {
                $this->log_info("Update nonce " . input_request('nonce') .
                        " => $nonce");
                $message = 'Nonce updated';
            }
            $response          = $this->get_response(false, $message);
            $response['nonce'] = $nonce;
        }
        exit(json_encode($response));
    }

    /**
     * Ensure profile first/last name set and that they don't match anyone else.
     * @param \WP_Error $errors WP_Error object (passed by reference)
     * @param bool $update true if user update (otherwise add new user)
     * @param type $user User object (passed by reference)
     */
    public function precheck_profile_change(\WP_Error &$errors, bool $update, &$user): void
    {
        if (!$user->first_name) {
            $errors->add('first_name', 'Missing first name.');
        }
        if (!$user->last_name) {
            $errors->add('last_name', 'Missing last name.');
        }
        if ($user->first_name && $user->last_name) {
            $tobj = JoinUsers::findByName($user->first_name . ' ' . $user->last_name);
            if ($tobj && ($tobj->ID != $user->ID)) {
                //$errors->add('name', 'Another user has the same first and last name.');
                $this->log_error('Profile update: User ' . $user->ID . ' changed name to existing user ' . $tobj->ID);
            }
        } else {
            $this->log_error('Profile update: User ' . $user->ID . ' missing first or last name in update');
        }
    }
}

new MenuProfile();
