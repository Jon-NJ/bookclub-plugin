<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_books' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuChat extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuChat
     */
    public function __construct()
    {
        parent::__construct('bc_menu_chat',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Chat Menu',
                'menu_name'   => 'Chat',
                'menu_rank'   => RANK_CHAT,
                'capability'  => 'read',
                'slug'        => 'bc_chat',
                'help'        => 'menu_chat',
                'script'      => 'menu_chat.js',
                'style'       => 'menu_chat.css',
                'nonce'       => 'chat_nonce',
                'actions'     => [[
                        'key' => 'wp_ajax_bc_chat_refresh',
                        'function' => 'chat_refresh'
                    ],[
                        'key' => 'admin_post_bc_event_body',
                        'function' => 'event_body'
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
        $wpid = \get_current_user_id();
        $member = TableMembers::findByWordpressID($wpid);
        $dm = input_get('dm');
        $gid = input_get('gid');
        $eid = input_get('eid');
        $bid = input_get('bid');
        $json = [];
        if (!is_null($dm)) {
            $json = $this->direct_message($wpid, $dm);
        } elseif (!is_null($gid)) {
            $json = $this->group_chat($wpid, $gid);
        } elseif (!is_null($eid)) {
            $json = $this->event_chat($wpid, $eid, $member);
        } elseif (!is_null($bid)) {
            $json = $this->book_chat($wpid, $bid);
        }
        if (!$json) {
            $json = $this->main_chat($wpid, $member);
            $this->log_info("Start chat ($wpid)");
        } else {
            $this->log_info("Start chat ($wpid," . $json['type'] . "," . $json['target'] . ")");
        }
        return twig_render('menu_chat', $json);
    }

    /**
     * Fetch JSON data for direct messages with given chat partner.
     * @param int $first WordPress ID of primary chat partner
     * @param int $second WordPress ID of other chat partner
     * @return array|null JSON data for chat with given partner (if exists)
     */
    private function direct_message(int $first, int $second): ?array
    {
        $user = \get_userdata($second);
        $json = null;
        if ($user) {
            $json = $this->get_dms($first, $second, 0);
            $json['title'] = 'Direct Messages';
            $json['display_name'] = $user->display_name;
            $json['avatar'] = \get_avatar_url($second);
            $json['first_name'] = $user->first_name;
            $json['last_name'] = $user->last_name;
        }
        return $json;
    }

    /**
     * Fetch JSON data for group chats.
     * @param int $wpid WordPress ID of current user
     * @param int $group_id group identifier
     * @return array|null JSON data for chat with given group (if exists)
     */
    private function group_chat(int $wpid, int $group_id): ?array
    {
        $json = null;
        $group = TableGroups::findByID($group_id);
        if ($group) {
            $json = $this->get_chats($wpid, BC_CHAT_TARGET_GROUP, $group_id, 0);
            $json['title'] = 'Group Chat';
            $json['groupid'] = $group_id;
            $json['tag'] = $group->tag;
            $json['description'] = $group->description;
        }
        return $json;
    }

    /**
     * Fetch JSON for event chats.
     * @param int $wpid WordPress ID of current user
     * @param string $event_id unique event identifier
     * @param TableMembers $member member data of current user
     * @return array|null JSON data for chat with given group (if exists)
     */
    private function event_chat(int $wpid, string $event_id, TableMembers $member): ?array
    {
        $json = null;
        $event = TableEvents::findByID($event_id);
        if ($event) {
            $json = $this->get_chats($wpid, BC_CHAT_TARGET_EVENT, $event_id, 0);
            $json['title']   = 'Event discussion';
            $json['summary'] = $event->summary;
            $json['webkey']  = $member->web_key;
            $json['start']   = $event->starttime;
            $json['end']     = $event->endtime;
        }
        return $json;
    }

    /**
     * Fetch JSON for book chats.
     * @param int $wpid WordPress ID of current user
     * @param int $bookid book identifier
     * @return array|null JSON data for chat about given book (if exists)
     */
    private function book_chat(int $wpid, int $bookid): ?array
    {
        $json = null;
        $book = JoinBooksAuthors::findBookByID($bookid);
        if ($book) {
            $json = $this->get_chats($wpid, BC_CHAT_TARGET_BOOK, $bookid, 0);
            $json['title'] = $book->title . ' by ' . $book->name;
            $json['book_cover'] = url_cover($book->cover_url);
            $json['book_title'] = $book->title;
            $json['book_author'] = $book->name;
            $json['book_link'] = $book->link;
            $json['book_summary'] = $book->summary;

            $dates = [];
            /* dates for this book */
            $tobj = new JoinDatesGroups();
            $tobj->loopDatesForBook($bookid);
            while ($tobj->fetch()) {
                $child = [
                        'group' => $tobj->group_id,
                        'tag'   => $tobj->tag,
                        'url'   => $tobj->url,
                        'date'  => $tobj->day
                    ];
                $dates[] = $child;
            }
            $json['groups'] = $dates;
        }
        return $json;
    }

    /**
     * Fetch JSON for main chat page.
     * @param int $wpid WordPress ID of current user
     * @param TableMembers|null $member bookclub member data (if in book club)
     * @return array JSON data for the main chat window
     */
    private function main_chat(int $wpid, ?TableMembers $member): array
    {
        // construct users
        $users = [];
        foreach (\get_users() as $user) {
            $users[] = [
                'id' => $user->ID,
                'url' => url_chat(BC_CHAT_TARGET_USER, $user->ID),
                'display_name' => $user->display_name
            ];
        }
        // construct books
        $books = getBooks();
        foreach ($books as &$book) {
            $book['url'] = url_chat(BC_CHAT_TARGET_BOOK, $book['bookid']);
        }
        // construct events
        $events = [];
        if ($member) {
            $today = strtotime(date('Y-m-d'));
            $tobj = new JoinEventsParticipants();
            $tobj->loopRecentForMember($member->member_id,
                    date('Y-m-d', strtotime("-3 month")));
            while ($tobj->fetch()) {
                $events[] = [
                    'id'      => $tobj->event_id,
                    'time'    => $tobj->starttime,
                    'summary' => $tobj->summary,
                    'url'     => url_chat(BC_CHAT_TARGET_EVENT, $tobj->event_id),
                    'past'    => strtotime($tobj->starttime) < $today
                ];
            }
        }
        // construct groups
        $groups = [];
        if ($member) {
            $tobj = new JoinGroupsGroupMembers();
            $tobj->loopForMember($member->member_id, BC_GROUP_CLUB);
            while ($tobj->fetch()) {
                if ($tobj->inGroup()) {
                    $groups[] = [
                        'id'          => $tobj->group_id,
                        'tag'         => $tobj->tag,
                        'description' => $tobj->description,
                        'url'         => url_chat(BC_CHAT_TARGET_GROUP, $tobj->group_id),
                        'type'        => $tobj->type
                    ];
                }
            }
        }
        $user = \get_userdata($wpid);
        return [
            'users'        => $users,
            'display_name' => $user->display_name,
            'avatar'       => \get_avatar_url($wpid),
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'profile'      => url_wordpress_profile(),
            'books'        => $books,
            'groups'       => $groups,
            'events'       => $events,
            'title'        => \get_admin_page_title(),
            'images'       => url_images(),
            'type'         => 0
        ];
    }

    /**
     * 
     * @param int $wpid WordPress ID of current user
     * @param int $type one of BC_CHAT_TARGET_GROUP, BC_CHAT_TARGET_BOOK,
     * BC_CHAT_TARGET_EVENT
     * @param string $target target identifier - group, book or event
     * @param int $start start chat ID
     * @return array JSON for new chats other than direct messages
     */
    private function get_chats(int $wpid, int $type, string $target, int $start): array
    {
        $records = [];
        $tobj = new JoinChatsUsers();
        $tobj->loopForTarget($type, $target, $start);
        $newitems = false;
        while ($tobj->fetch()) {
            $records[] = [
                'id' => $tobj->chat_id,
                'deleted' => $tobj->deleted_by != 0,
                'wordpress_id' => $tobj->ID ?: 0,
                'login' => $tobj->ID ? $tobj->user_login : 'DELETED',
                'nice' => $tobj->ID ? $tobj->user_nicename : 'DELETED',
                'display' => $tobj->ID ? $tobj->display_name : 'DELETED',
                'name' => $tobj->ID ? $tobj->fullname : 'DELETED',
                'datetime' => $tobj->timestamp,
                'message' => $tobj->message,
                'is_me' => $tobj->ID == $wpid,
                'deletable' => ($tobj->ID == $wpid) && (0 == $tobj->deleted_by)
            ];
            $start = $tobj->chat_id;
            $newitems = true;
        }
        $deleted = [];
        $tobj = new TableChats();
        $tobj->loopForDeleted($start);
        while ($tobj->fetch()) {
            $id = $tobj->chat_id;
            $deleted[] = $tobj->target_id;
            if ((int) $id > (int) $start) {
                $start = $id;
            }
        }
        return [
            'admin_url' => url_admin_post(),
            'nonce'     => $this->create_nonce(),
            'records'   => $records,
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'records'   => $records,
            'deleted'   => $deleted,
            'timeout'   => 1500,
            'last'      => $start,
            'new'       => $newitems,
            'type'      => $type,
            'target'    => $target
        ];
    }

    /**
     * 
     * @param int $first WordPress ID of current user
     * @param int $second WordPress ID of chat partner
     * @param int $start start chat ID
     * @return array JSON for new direct messages
     */
    private function get_dms(int $first, int $second, int $start): array
    {
        $records = [];
        $tobj = new JoinChatsUsers();
        $tobj->loopForDirectMessages($first, $second, $start);
        $newitems = false;
        while ($tobj->fetch()) {
            $records[] = [
                'id' => $tobj->chat_id,
                'deleted' => $tobj->deleted_by != 0,
                'wordpress_id' => $tobj->ID,
                'login' => $tobj->user_login,
                'nice' => $tobj->user_nicename,
                'display' => $tobj->display_name,
                'name' => $tobj->fullname,
                'datetime' => $tobj->timestamp,
                'message' => $tobj->message,
                'is_me' => $tobj->ID == $first,
                'deletable' => ($tobj->ID == $first) && (0 == $tobj->deleted_by)
            ];
            $start = $tobj->chat_id;
            $newitems = true;
        }
        $deleted = [];
        $tobj = new TableChats();
        $tobj->loopForDeleted($start);
        while ($tobj->fetch()) {
            $id = $tobj->chat_id;
            $deleted[] = $tobj->target_id;
            if ((int) $id > (int) $start) {
                $start = $id;
            }
        }
        return [
            'admin_url' => url_admin_post(),
            'nonce'     => $this->create_nonce(),
            'records' => $records,
            'title'   => \get_admin_page_title(),
            'images'  => url_images(),
            'records' => $records,
            'deleted' => $deleted,
            'timeout' => 1500,
            'last'    => $start,
            'new'       => $newitems,
            'type'    => BC_CHAT_TARGET_USER,
            'target'  => $second
        ];
    }

    /** POST functions */

    /**
     * Generate HTML for given event and person.
     * @global string $_REQUEST['webkey'] unique member webkey
     * @global string $_REQUEST['eid'] unique event identifier
     */
    public function event_body(): void
    {
        $this->log_debug("Get event body");
        $eventid  = input_request('eid');
        $response = $this->check_request("Fetch invitation description $eventid");
        if (!$response) {
            $webkey       = input_request('webkey');
            $member       = TableMembers::findByKey($webkey);
            $event        = TableEvents::findByID($eventid);
            $user         = \get_userdata($member->wordpress_id);
            $json         = twig_macro_fields([$member, $event, $user]);
            $json['body'] = twig_template($event->description);
            echo twig_render('event_body', $json);
        }
        die();
    }

    /** AJAX functions */

    /**
     * Fetch new chat messages since last time. Add new chat message if given.
     * @global string $_REQUEST['type'] one of BC_CHAT_TARGET_USER,
     * BC_CHAT_TARGET_GROUP, BC_CHAT_TARGET_BOOK, BC_CHAT_TARGET_EVENT
     * @global string $_REQUEST['target'] chat target identifier
     * @global string $_REQUEST['last'] last chat message identifier
     * @global string|null $_REQUEST['message'] message optional new message
     * @global string|null $_REQUEST['delete_id'] id of chat to delete if given
     */
    public function chat_refresh(): void
    {
        $wpid = \get_current_user_id();
        $response = $this->get_response(false, 'Chat update');
        $type = input_request('type');
        $target = input_request('target');
        $last = input_request('last');

        $message = input_request('message');
        if ($message) {
            $this->log_debug("Add chat ($wpid," . $type . "," . $target . "): $message");
            $chat = new TableChats();
            $chat->wordpress_id = $wpid;
            $chat->message      = $message;
            $chat->target_type  = $type;
            $chat->target_id    = $target;
            $chat->insert();
        }

        $chatid = input_request('delete_id');
        if ($chatid) {
            $this->log_debug("Delete chat ($wpid,$chatid)");
            TableChats::markDeletedByID($chatid, $wpid);
        }

        $this->log_debug("Refresh chat ($wpid,$type,$target)");
        if (BC_CHAT_TARGET_USER == $type) {
            $json = $this->get_dms($wpid, $target, $last);
        } else {
            $json = $this->get_chats($wpid, $type, $target, $last);
        }
        $response['html'] = twig_render('chat_lines', $json);
        $response['last'] = $json['last'];
        $response['timeout'] = $json['timeout'];
        $response['deleted'] = $json['deleted'];
        $response['new']     = $json['new'];
        exit(json_encode($response));
    }
}

new MenuChat();
