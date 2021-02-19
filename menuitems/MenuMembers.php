<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_members' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuMembers extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuMembers
     */
    public function __construct()
    {
        parent::__construct('bc_menu_members',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Member List',
                'menu_name'   => 'Members',
                'menu_rank'   => RANK_MEMBERS,
                'capability'  => 'edit_bc_members',
                'slug'        => 'bc_members',
                'help'        => 'menu_members',
                'script'      => 'menu_members.js',
                'style'       => 'menu_members.css',
                'nonce'       => 'members_nonce',
                'actions'     => [[
                        'key' => 'wp_ajax_bc_members_add',
                        'function' => 'members_add'
                    ],[
                        'key' => 'wp_ajax_bc_members_email',
                        'function' => 'members_email'
                    ],[
                        'key' => 'wp_ajax_bc_members_save',
                        'function' => 'members_save'
                    ],[
                        'key' => 'wp_ajax_bc_members_delete',
                        'function' => 'members_delete'
                    ],[
                        'key' => 'wp_ajax_bc_members_key',
                        'function' => 'reset_key'
                    ]]
            ]);
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @global string|null $_GET['action'] 'edit', 'search' or empty
     * @return string HTML content
     */
    public function render(): string
    {
        if (!parent::enqueue()) {
            return '';
        }
        $action = input_get('action');
        if ('search' === $action) {
            $json = $this->executeSearch();
        } elseif ('edit' === $action) {
            $json = $this->executeEdit();
        } else {
            $json = $this->executeStart();
        }
        return twig_render('menu_members', $json);
    }

    /**
     * Search database with the provided parameters.
     * @param string|null $id optional exact member identifier
     * @param string|null $wpid optional exact WordPress identifier or * for
     * all, but only WP users
     * @param string|null $login optional partial WordPress login
     * @param string|null $pkey optional exact member web key
     * @param string|null $name optional partial member name
     * @param string|null $email optional partial member email
     * @param int         $group 0 for all groups or specific group id
     * @param bool        $exclude 0, 1 to exclude members of specified group
     * @param string|null $active 0, 1 or - active flag is neutral, yes, no
     * @param string|null $last optional number of months to check for last hit
     * @param string|null $ltgt 0 to check older than, 1 to check younger than
     * @return array JSON search results
     */
    private function search(?string $id, ?string $wpid, ?string $login,
            ?string $pkey, ?string $name, ?string $email, int $group,
            bool $exclude, ?string $active, ?string $last, ?string $ltgt): array
    {
        $results = [];
        $iterator = new JoinMembersUsers();
        $iterator->loopSearch($id, $wpid, $login, $pkey, $name, $email, $group,
                $exclude, $active, $last, $ltgt);
        while ($iterator->fetch()) {
            $first = '';
            $last  = '';
            if ($iterator->wordpress_id) {
                $user = \get_userdata($iterator->wordpress_id);
                $first = $user->first_name ?: '(First name missing)';
                $last  = $user->last_name ?: '(Last name missing)';
            }
            $results[] = [
                'id'      => $iterator->member_id,
                'wpid'    => $iterator->wordpress_id,
                'login'   => $iterator->user_login,
                'pkey'    => $iterator->web_key,
                'name'    => $iterator->wordpress_id ? "$first $last" :
                    $iterator->name,
                'email'   => $iterator->wordpress_id ?
                    $iterator->user_email : $iterator->email,
                'active'  => $iterator->active,
                'hittime' => $iterator->hittime
            ];
        }
        return $results;
    }

    /**
     * Remap request value to '0'/'1'/'-' usually +/- or empty.
     * @global string|null $_GET['id'] optional exact member identifier
     * @return string 0-ignore,1-must be in group,-- must not be in group
     */
    private function remap_group(?string $id): string
    {
        $val = input_request($id);
        if (is_null($val)) {
            return '0';
        } elseif ('-' === $val) {
            return '-';
        }
        return '1';
    }

    /**
     * Fetch JSON used for the search state.
     * @global string|null $_GET['id'] optional exact member identifier
     * @global string|null $_GET['wpid'] optional exact WordPress identifier
     * @global string|null $_GET['login'] optional partial WordPress login
     * @global string|null $_GET['pkey'] optional exact member web key
     * @global string|null $_GET['name'] optional partial member name
     * @global string|null $_GET['email'] optional partial email address
     * @global string|null $_GET['last'] optional last visit in months
     * @global string|null $_GET['ltgt'] less than/greater than list visit
     * @global string      $_GET['group'] group to match or zero for all
     * @global string|null $_GET['exclude'] exclude flag, true if given
     * @global string      $_GET['active'] active flag
     * @return array JSON for TWIG rendering
     */
    private function executeSearch(): array
    {
        $id      = input_request('id');
        $wpid    = input_request('wpid');
        $login   = input_request('login');
        $pkey    = input_request('pkey');
        $name    = input_request('name');
        $email   = input_request('email');
        $group   = input_request('group');
        $exclude = !is_null(input_request('exclude'));
        $active  = $this->remap_group('active');
        $last    = input_request('last');
        $ltgt    = input_request('ltgt');
        $nonce   = $this->create_nonce();
        $json    = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_members'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'search',
            'id'        => $id,
            'wpid'      => $wpid,
            'login'     => $login,
            'pkey'      => $pkey,
            'name'      => $name,
            'email'     => $email,
            'groups'    => array_merge(getGroups(BC_GROUP_CLUB),
                            getGroups(BC_GROUP_SELECT)),
            'group'     => $group,
            'exclude'   => $exclude ? '1' : '0',
            'active'    => $active,
            'last'      => $last,
            'ltgt'      => $ltgt
            ];
        $json['found'] = $this->search($id, $wpid, $login, $pkey, $name, $email,
                $group, $exclude, $active, $last, $ltgt);
        return $json;
    }

    /**
     * Fetch JSON used for the edit state.
     * @global string $_GET['id'] unique member identifier
     * @return array JSON for TWIG rendering
     */
    private function executeEdit(): array
    {
        $id     = input_request('id');
        $nonce  = $this->create_nonce();
        $member = TableMembers::findByID($id);
        $groups = [];
        $giterator = new JoinGroupsGroupMembers();
        $giterator->loopForMember($id);
        while ($giterator->fetch()) {
            $all[$giterator->group_id] = $giterator->group_id;
            if ($giterator->inGroup()) {
                $list[$giterator->group_id] = $giterator->group_id;
            }
            $groups[] = [
                'id'          => $giterator->group_id,
                'tag'         => $giterator->tag,
                'description' => $giterator->description,
                'flag'        => $giterator->inGroup()
            ];
        }
        if ($member->wordpress_id) {
            $giterator = new JoinGroupsGroupUsers();
            $giterator->loopForUser($member->wordpress_id);
            while ($giterator->fetch()) {
                $all[$giterator->group_id] = $giterator->group_id;
                if ($giterator->inGroup()) {
                    $list[$giterator->group_id] = $giterator->group_id;
                }
                $groups[] = [
                    'id'          => $giterator->group_id,
                    'tag'         => $giterator->tag,
                    'description' => $giterator->description,
                    'flag'        => $giterator->inGroup()
                ];
            }
        }
        $json   = [
            'nonce'          => $nonce,
            'admin_url'      => url_admin_post(),
            'referer'        => url_menu('bc_members'),
            'title'          => \get_admin_page_title(),
            'images'         => url_images(),
            'mode'           => 'edit',
            'id'             => $id,
            'pkey'           => $member->web_key,
            'name'           => $member->name,
            'email'          => $member->email,
            'groups'         => $groups,
            'active'         => $member->active,
            'noemail'        => $member->noemail,
            'format'         => $member->format,
            'ical'           => $member->ical,
            'public_email'   => $member->public_email,
            'receive_others' => 0 //$member->receive_others
            ];
        $last_signup_log = TableLogs::findLastBySelectors(
                ['SIGNUP', null, $member->member_id]);
        if ($last_signup_log) {
            $json['signup'] = [
                'timestamp' => $last_signup_log->timestamp,
                'type'      => $last_signup_log->param1,
                'message'   => $last_signup_log->message
            ];
        }
        if ($member->wordpress_id) {
            $user  = \get_userdata($member->wordpress_id);
            $first = $user->first_name ?: '(First name missing)';
            $last  = $user->last_name ?: '(Last name missing)';
            $json['wordpress_id']   = $member->wordpress_id;
            $json['login']          = $user->user_login;
            $json['name']           = "$first $last";
            $json['email']          = $user->user_email;
            $json['profile_url']    = url_profile_user($member->wordpress_id);
            $json['role']           = $user->roles[0];
            $json['receive_others'] = \get_user_meta($member->wordpress_id,
                    'bc_receive_others', true);
        } else {
            $json['signup_url']   = url_signup($member);
        }
        return $json;
    }

    /**
     * Fetch JSON used for start state.
     * @return array JSON for TWIG rendering
     */
    private function executeStart(): array
    {
        $nonce = $this->create_nonce();

        $json = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_members'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'start',
            'id'        => '',
            'wpid'      => '',
            'login'     => '',
            'pkey'      => '',
            'name'      => '',
            'email'     => '',
            'groups'    => array_merge(getGroups(BC_GROUP_CLUB),
                            getGroups(BC_GROUP_SELECT)),
            'group'     => 0,
            'exclude'   => 0,
            'active'    => 0,
            'last'      => '',
            'ltgt'      => 0
            ];
        return $json;
    }

    /** AJAX handling */

    /**
     * Send a sign-up email to the specified member, generate a JSON response.
     * @global string $_REQUEST['id'] unique member identifier
     */
    public function members_email(): array
    {
        $response = $this->check_request('Send SignUp EMail');
        if (!$response) {
            $id = input_request('id');
            $sender = new EMail();
            if ($sender->sendSignUp($id)) {
                $response = $this->get_response(false, 'EMail sent');
                $this->log_info("SignUp EMail sent member id $id");
            } else {
                $response = $this->get_response(false, $sender->getLastError());
            }
            $last_signup_log = TableLogs::findLastBySelectors(
                    ['SIGNUP', null, $member->member_id]);
            $response['text'] = "Sent " . substr($last_signup_log->timestamp, 0, 16) .
                    " - " . $last_signup_log->message;
        }
        exit(json_encode($response));
    }

    /**
     * Add a member based on the submitted entries, generate a JSON response.
     * @global string $_REQUEST['name'] member name
     * @global string $_REQUEST['email'] member email address
     * @global string $_REQUEST['group'] group to add to if specified
     */
    public function members_add(): array
    {
        $response = $this->check_request('Add member');
        if (!$response) {
            $newid = TableMembers::getNextID();
            $member = new TableMembers();
            $member->member_id       = $newid;
            $member->web_key         = generate_key();
            $member->active          = true;
            $member->noemail         = false;
            $member->name            = input_request('name');
            $member->email           = input_request('email');
            $member->format          = 1;
            $member->ical            = 1;
            $member->noemail         = 0;
            $member->wordpress_id    = 0;
            $member->hittime         = null;
            $member->public_email    = 0;
            //$member->receive_others  = 0;
            $member->insert();
            $group                   = input_request('group');
            if ($group) {
                TableGroupMembers::addMember($group, $newid);
            }
            $response = $this->get_response(false, 'Member added');
            $response['id'] = $newid;
            $this->log_info("Add member id $newid");
        }
        exit(json_encode($response));
    }

    /**
     * Update member information. Generate a JSON response.
     * @global string $_REQUEST['id'] unique member identifier
     * @global string $_REQUEST['pkey'] unique member web key
     * @global string $_REQUEST['name'] member name
     * @global string $_REQUEST['email'] member email address
     * @global string $_REQUEST['group'*] true if included in given group
     * @global string $_REQUEST['active'] 0/1 if member active
     * @global string $_REQUEST['noemail'] 0/1 to block email
     * @global string $_REQUEST['format'] email format 0-text only/1-HTML
     * @global string $_REQUEST['ical'] email 0-no iCAL/1-iCAL
     */
    public function members_save(): array
    {
        $response = $this->check_request('Save member info');
        if (!$response) {
            $id = input_request('id');
            $member = TableMembers::findByID($id);
            if (!$member->wordpress_id) {
                $member->name            = input_request('name');
            } else {
                \update_user_meta($member->wordpress_id, 'bc_receive_others',
                        input_request('receive'));
            }
            $member->active         = input_request('active');
            $member->web_key        = input_request('pkey');
            $member->email          = input_request('email');
            $member->format         = input_request('format');
            $member->ical           = input_request('ical');
            $member->noemail        = input_request('noemail');
            $member->public_email   = input_request('public_email');
            //$member->receive_others = input_request('receive');
            $member->update();
            // bookclub groups
            $giterator = new JoinGroupsGroupMembers();
            $giterator->loopForMember($id);
            while ($giterator->fetch()) {
                if (($giterator->inGroup()) &&
                        !('true' === input_request('group' . $giterator->group_id))) {
                    TableGroupMembers::removeMember($giterator->group_id, $id);
                } elseif (!($giterator->inGroup()) &&
                        ('true' === input_request('group' . $giterator->group_id))) {
                    TableGroupMembers::addMember($giterator->group_id, $id);
                }
            }
            // wordpress groups
            if ($member->wordpress_id) {
                $giterator = new JoinGroupsGroupUsers();
                $giterator->loopForUser($member->wordpress_id);
                while ($giterator->fetch()) {
                    if (($giterator->inGroup()) &&
                            !('true' === input_request('group' . $giterator->group_id))) {
                        TableGroupUsers::removeUser($giterator->group_id, $member->wordpress_id);
                    } elseif (!($giterator->inGroup()) &&
                            ('true' === input_request('group' . $giterator->group_id))) {
                        TableGroupUsers::addUser($giterator->group_id, $member->wordpress_id);
                    }
                }
            }
            $response = $this->get_response(false, 'Member updated');
            $this->log_info("Member updated $id");
        }
        exit(json_encode($response));
    }

    /**
     * Delete a member from the database. Generate a JSON response. The
     * WordPress account is NOT deleted.
     * @global string $_REQUEST['id'] unique member identifier
     */
    public function members_delete(): array
    {
        $response = $this->check_request('Delete member');
        if (!$response) {
            $memberid = input_request('id');
            $this->log_info("Delete member id $memberid");
            TableRecipients::deleteByID($memberid);
            TableParticipants::deleteByID($memberid);
            TableGroupMembers::deleteByMember($memberid);
            TableMembers::deleteByID($memberid);
            TableLogs::changeTypeBySelectors('NOSIGNUP',
                    ['SIGNUP', null, $memberid]);
            $response = $this->get_response(false, 'Member deleted');
        }
        exit(json_encode($response));
    }

    /**
     * Change web key for selected user.
     */
    public function reset_key()
    {
        $response = $this->check_request('Changing web key');
        if (!$response) {
            $wpid = \get_current_user_id();
            $person = TableMembers::findByWordpressID($wpid);
            $newkey = generate_key();
            $this->log_info("Change key for $wpid: " . $person->web_key .
                    " => $newkey");
            $response = $this->get_response(false, "New web key generated");
            $response['newkey'] = $newkey;
        }
        exit(json_encode($response));
    }
}

new MenuMembers();
