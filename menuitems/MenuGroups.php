<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_groups' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuGroups extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuGroups
     */
    public function __construct()
    {
        parent::__construct('bc_menu_groups',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Edit Groups',
                'menu_name'   => 'Groups',
                'menu_rank'   => RANK_GROUPS,
                'capability'  => 'edit_bc_groups',
                'slug'        => 'bc_groups',
                'help'        => 'menu_groups',
                'script'      => 'menu_groups.js',
                'style'       => 'menu_groups.css',
                'nonce'       => 'groups_nonce',
                'actions'     => [[
                        'key' => 'wp_ajax_bc_groups_add',
                        'function' => 'groups_add'
                    ],[
                        'key' => 'wp_ajax_bc_groups_save',
                        'function' => 'groups_save'
                    ],[
                        'key' => 'wp_ajax_bc_groups_delete',
                        'function' => 'groups_delete'
                    ],[
                        'key'      => 'wp_ajax_bc_groups_select',
                        'function' => 'groups_select'
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
        return twig_render('menu_groups', $json);
    }

    /**
     * Fetch JSON used for start state.
     * @return array JSON for TWIG rendering
     */
    private function executeStart(): array
    {
        $nonce = $this->create_nonce();
        $json = [
            'nonce'       => $nonce,
            'admin_url'   => url_admin_post(),
            'referer'     => url_menu('bc_groups'),
            'title'       => \get_admin_page_title(),
            'images'      => url_images(),
            'mode'        => 'start',
            'group_id'    => '',
            'type'        => '',
            'tag'         => '',
            'description' => ''
            ];
        return $json;
    }

    /**
     * Fetch JSON used for the edit state.
     * @global string $_GET['groupid'] group identifier
     * @return array JSON for TWIG rendering
     */
    private function executeEdit(): array
    {
        $groupid = input_get('groupid');
        $nonce   = $this->create_nonce();
        $tobj    = TableGroups::findByID($groupid);
        $no      = [];
        $yes     = [];
        $nodata  = [];
        $yesdata = [];
        if (BC_GROUP_SELECT == $tobj->type) {
            $jobj = new JoinMembersUsersGroupMembers();
            $jobj->loopMembersForGroup($groupid);
            while ($jobj->fetch()) {
                if ($jobj->isMember()) {
                    $yes[] = [
                        'id'     => $jobj->member_id,
                        'name'   => $jobj->fullname,
                        'active' => $jobj->active
                        ];
                    $yesdata[]   = $jobj->member_id;
                } else {
                    $no[] = [
                        'id'     => $jobj->member_id,
                        'name'   => $jobj->fullname,
                        'active' => $jobj->active
                        ];
                    $nodata[]    = $jobj->member_id;
                }
            }
        }
        if ((BC_GROUP_WORDPRESS == $tobj->type) ||
                (BC_GROUP_ANNOUNCEMENTS == $tobj->type)) {
            $users = \get_users();
            foreach ($users as $user) {
                $item = [
                    'id'     => $user->id,
                    'name'   => $user->first_name . ' ' . $user->last_name,
                    'active' => true
                ];
                if (!trim($item['name'])) {
                    $item['name'] = $user->user_login;
                }
                if (TableGroupUsers::isUser($groupid, $user->id)) {
                    $yes[] = $item;
                    $yesdata[] = $user->id;
                } else {
                    $no[] = $item;
                    $nodata[]  = $user->id;
                }
            }
        }
        $json = [
            'nonce'         => $nonce,
            'admin_url'     => url_admin_post(),
            'referer'       => url_menu('bc_groups'),
            'title'         => \get_admin_page_title(),
            'images'        => url_images(),
            'mode'          => 'edit',
            'group_id'      => $groupid,
            'groups'        => getGroups(),
            'yes'           => $yes,
            'no'            => $no,
            'yes_data'      => join(',', $yesdata),
            'no_data'       => join(',', $nodata),
            'tag'           => $tobj->tag,
            'type'          => $tobj->type,
            'description'   => $tobj->description,
            'url'           => $tobj->url,
            't_event_id'    => $tobj->t_event_id,
            't_max_attend'  => $tobj->t_max_attend,
            't_starttime'   => $tobj->t_starttime,
            't_endtime'     => $tobj->t_endtime,
            't_summary'     => $tobj->t_summary,
            't_description' => $tobj->t_description,
            't_include'     => $tobj->t_include
            ];
        return $json;
    }

    /**
     * Search database with the provided parameters.
     * @param string|null $groupid optional group identifier
     * @param string|null $type optional type of group, BC_GROUP_CLUB,
     * BC_GROUP_SELECT, BC_GROUP_WORDPRESS or BC_GROUP_ANNOUNCEMENTS
     * @param string|null $tag optional group short description
     * @param string|null $description optional description
     * @return array JSON search results
     */
    private function search(?string $groupid, ?string $type, ?string $tag,
            ?string $description): array
    {
        $results = [];
        $tobj = new TableGroups();
        $tobj->loopSearch($groupid, $type, $tag, $description);
        while ($tobj->fetch()) {
            $results[] = [
                'group_id'    => $tobj->group_id,
                'tag'         => $tobj->tag,
                'type'        => $tobj->type,
                'description' => $tobj->description
            ];
        }
        return $results;
    }

    /**
     * Fetch JSON used for the search state.
     * @global string|null $_REQUEST['groupid'] optional exact group identifier
     * @global string|null $_REQUEST['type'] optional exact group type,
     * BC_GROUP_CLUB or BC_GROUP_SELECT
     * @global string|null $_REQUEST['tag'] optional short description
     * @global string|null $_REQUEST['desc'] optional group $description
     * @return array JSON for TWIG rendering
     */
    private function executeSearch(): array
    {
        $groupid = input_request('groupid');
        $type    = input_request('type');
        $tag     = input_request('tag');
        $desc    = input_request('desc');
        $nonce   = $this->create_nonce();
        $json = [
            'nonce'       => $nonce,
            'admin_url'   => url_admin_post(),
            'referer'     => url_menu('bc_groups'),
            'title'       => \get_admin_page_title(),
            'images'      => url_images(),
            'mode'        => 'search',
            'group_id'    => $groupid,
            'type'        => $type,
            'tag'         => $tag,
            'description' => $desc
            ];
        $json['found'] = $this->search($groupid, $type, $tag, $desc);
        return $json;
    }

    /**
     * Add an group to the database.
     * @param string|null $groupid new group identifier if specified
     * @param string $type BC_GROUP_CLUB, BC_GROUP_SELECT, BC_GROUP_WORDPRESS or
     * BC_GROUP_ANNOUNCEMENTS
     * @param string $tag|null optional short description
     * @param string $description group description
     * @return int group unique identifier
     */
    private function insert(?string $groupid, string $type, ?string $tag,
            string $description): int
    {
        $group = new TableGroups();
        if (!$groupid) {
            $groupid = TableGroups::getNextID($type);
        }
        if (!$tag) {
            $tag = "Group $groupid";
        }
        if (!$description) {
            $description = "Group $groupid";
        }
        $group->group_id      = $groupid;
        $group->type          = $type;
        $group->tag           = $tag;
        $group->description   = $description;
        $group->url           = '';
        $group->t_event_id    = '';
        $group->t_max_attend  = '';
        $group->t_starttime   = '';
        $group->t_endtime     = '';
        $group->t_summary     = '';
        $group->t_description = '';
        $group->t_include     = 0;
        $group->insert();
        return $groupid;
    }

    /**
     * Add an group to the database. Generate a JSON response.
     * @global string $_REQUEST['groupid']|null optional new group identifier
     * @global string $_REQUEST['type'] BC_GROUP_CLUB, BC_GROUP_SELECT,
     * BC_GROUP_WORDPRESS or BC_GROUP_ANNOUNCEMENTS
     * @global string $_REQUEST['tag'] short identifier
     * @global string $_REQUEST['desc'] description
     */
    public function groups_add(): void
    {
        $response = $this->check_request('Add group');
        if (!$response) {
            $groupid = input_request('groupid');
            $type    = input_request('type');
            if ($groupid) {
                if (!is_numeric($groupid)) {
                    $response = $this->get_response(true, 'Bad group number');
                } else {
                    $group = TableGroups::findByID($groupid);
                    if ($group) {
                        $response = $this->get_response(true, 'Group exists');
                    } elseif ((BC_GROUP_CLUB == $type) && ($groupid >= 1000)) {
                        $response = $this->get_response(true, 'Group must be in the range 1 to 999');
                    } elseif ((BC_GROUP_SELECT == $type) && ($groupid < 1000)) {
                        $response = $this->get_response(true, 'Group must be in the range 1000 to 1999');
                    } elseif ((BC_GROUP_WORDPRESS == $type) && ($groupid < 2000)) {
                        $response = $this->get_response(true, 'Group must be in the range 2000 to 2999');
                    } elseif ((BC_GROUP_ANNOUNCEMENTS == $type) && ($groupid < 3000)) {
                        $response = $this->get_response(true, 'Group must be above 2999');
                    }
                }
            }
            if (!$response) {
                $groupid = $this->insert($groupid, $type, input_request('tag'),
                        input_request('desc'));
                $this->log_info("Group added $groupid");
                $response = $this->get_response(false, 'Group added');
                $response['group_id'] = $groupid;
            }
        }
        exit(json_encode($response));
    }

    /**
     * Delete an group from the database. Generate a JSON response.
     * @global string $_REQUEST['groupid'] group identifier
     */
    public function groups_delete(): void
    {
        $response = $this->check_request('Delete group');
        if (!$response) {
            $groupid = input_request('groupid');
            $group   = TableGroups::findByID($groupid);
            $this->log_info("Delete group id $groupid");
            if (BC_GROUP_CLUB == $group->type || BC_GROUP_SELECT == $group->type) {
                TableGroupMembers::deleteByID($groupid);
            } else { // BC_GROUP_WORDPRESS == $group->type || BC_GROUP_ANNOUNCEMENTS == $group->type
                TableGroupUsers::deleteByID($groupid);
            }
            TableGroups::deleteByID($groupid);
            $response = $this->get_response(false, 'Group deleted');
        }
        exit(json_encode($response));
    }

    /**
     * Update database information about the given group.
     * @param int $groupid group identifier
     * @param string $tag short description
     * @param string $desc
     * @param string|null $url book club group URL
     * @param string|null $event_id event id template
     * @param string|null $max_attend template max attend
     * @param string|null $include template include group
     * @param string|null $starttime template start time
     * @param string|null $endtime template end time
     * @param string|null $what template summary
     * @param string|null $body template body
     * @param string|null $yes comma separated group members
     * @param string|null $no comma separated non-group members
     */
    private function update(int $groupid, string $tag, string $desc,
            ?string $url, ?string $event_id, ?string $max_attend,
            ?string $include, ?string $starttime, ?string $endtime,
            ?string $what, ?string $body, ?string $yes, ?string $no): void
    {
        $group              = TableGroups::findByID($groupid);
        $group->tag         = $tag;
        $group->description = $desc;
        if (BC_GROUP_CLUB == $group->type) {
            $group->url           = $url;
            $group->t_event_id    = $event_id;
            $group->t_max_attend  = $max_attend;
            $group->t_include     = $include;
            $group->t_starttime   = $starttime;
            $group->t_endtime     = $endtime;
            $group->t_summary     = $what;
            $group->t_description = $body;
        } elseif (BC_GROUP_SELECT == $group->type) {
            //$yesdata = preg_split('/,/', $yes);
            $nodata  = preg_split('/,/', $no);
            $tobj = new JoinMembersUsersGroupMembers();
            $tobj->loopMembersForGroup($groupid);
            while ($tobj->fetch()) {
                if (in_array($tobj->member_id, $nodata)) {
                    if ($tobj->isMember()) {
                        TableGroupMembers::removeMember($groupid, $tobj->member_id);
                    }
                } else {    //in_array($join->member_id, $yesdata)
                    if (!$tobj->isMember()) {
                        TableGroupMembers::addMember($groupid, $tobj->member_id);
                    }
                }
            }
        } else { // BC_GROUP_WORDPRESS == $group->type ||
                 // BC_GROUP_ANNOUNCEMENTS == $group->type
            $yesdata = preg_split('/,/', $yes);
            $users = \get_users();
            foreach ($users as $user) {
                $isUser = TableGroupUsers::isUser($groupid, $user->id);
                if (in_array($user->id, $yesdata)) {
                    if (!$isUser) {
                        TableGroupUsers::addUser($groupid, $user->id);
                    }
                } else {
                    if ($isUser) {
                        TableGroupUsers::removeUser($groupid, $user->id);
                    }
                }
            }
        }
        $group->update();
    }

    /**
     * Update information about the given group. Generate a JSON response.
     * @global string $_REQUEST['groupid'] group identifier
     * @global string $_REQUEST['tag'] short identifier
     * @global string $_REQUEST['desc'] description
     * @global string $_REQUEST['url']|null group URL
     * @global string $_REQUEST['event_id']|null event_id template
     * @global string $_REQUEST['max']|null max attend template
     * @global string $_REQUEST['include']|null include group template
     * @global string $_REQUEST['starttime']|null starttime template
     * @global string $_REQUEST['endtime']|null endtime template
     * @global string $_REQUEST['what']|null summary template
     * @global string $_REQUEST['body']|null description template
     * @global string $_REQUEST['yes'] comma separated list of group members
     * @global string $_REQUEST['no'] comma separated list of non group members
     */
    public function groups_save(): void
    {
        $response = $this->check_request('Save group');
        if (!$response) {
            $this->update(input_request('groupid'), input_request('tag'),
                    input_request('desc'), input_request('url'),
                    input_request('event_id'), input_request('max'),
                    input_request('include'), input_request('starttime'),
                    input_request('endtime'), input_request('what'),
                    input_request('body'), input_request('yes'),
                    input_request('no'));
            $response = $this->get_response(false, 'Group updated');
        }
        exit(json_encode($response));
    }

    /**
     * Find participants matching the criteria.
     * @param int    $group 0 for all groups or specific group id
     * @param bool   $exclude 0, 1 to exclude members of specified group
     * @param string $active active flag
     * @return array list of participants
     */
    private function select_search(int $group, bool $exclude,
            string $active): array
    {
        $results = [];
        $iterator = new TableMembers();
        $iterator->loopSearch(null, null, null, null, null,
                $group, $exclude, $active, null, null);
        while ($iterator->fetch()) {
            $results[] = $iterator->member_id;
        }
        return $results;
    }

    /**
     * Convert request value to flag used for search.
     * @param string $value original value from request, +, - or anything else
     * @return string original value
     */
    private function get_flag(string $value): string
    {
        return ('+' == $value) ? '1' : (('-' == $value) ? '-' : '0');
    }

    /**
     * Check participants inclusion for selected group and active where the
     * flag + means included, - means excluded, otherwise neutral. Generate
     * a JSON response.
     * @global string $_REQUEST['group'] group to match or zero for all
     * @global string $_REQUEST['exclude'] exclude flag, true if given
     * @global string $_REQUEST['active'] inclusion/exclusion active flag
     */
    public function groups_select(): void
    {
        $response = $this->check_request('Get selections');
        if (!$response) {
            $group   = input_request('group');
            $exclude = "true" === input_request('exclude');
            $active   = $this->get_flag(input_request('active'));
            $response = $this->get_response(false, 'Get selection');
            $response['select'] = $this->select_search(
                    $group, $exclude, $active);
        }
        exit(json_encode($response));
    }
}

new MenuGroups();
