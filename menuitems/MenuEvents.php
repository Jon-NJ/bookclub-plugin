<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_events' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuEvents extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuEvents
     */
    public function __construct()
    {
        parent::__construct('bc_menu_events',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Bookclub Events',
                'menu_name'   => 'Events',
                'capability'  => 'edit_bc_events',
                'slug'        => 'bc_events',
                'menu_rank'   => RANK_EVENTS,
                'script'      => 'menu_events.js',
                'style'       => 'menu_events.css',
                'help'        => 'menu_events',
                'nonce'       => 'events_nonce',
                'actions'     => [[
                        'key' => 'admin_post_bc_events_body',
                        'function' => 'events_body'
                    ],[
                        'key' => 'wp_ajax_bc_events_add',
                        'function' => 'events_add'
                    ],[
                        'key' => 'wp_ajax_bc_events_save',
                        'function' => 'events_save'
                    ],[
                        'key' => 'wp_ajax_bc_events_delete',
                        'function' => 'events_delete'
                    ],[
                        'key'      => 'wp_ajax_bc_events_status',
                        'function' => 'get_status'
                    ],[
                        'key'      => 'wp_ajax_bc_events_select',
                        'function' => 'events_select'
                    ],[
                        'key'      => 'wp_ajax_bc_events_update_rsvp',
                        'function' => 'update_rsvp'
                    ],[
                        'key'      => 'wp_ajax_bc_events_send_job',
                        'function' => 'send_job'
                    ],[
                        'key'      => 'wp_ajax_bc_events_clear',
                        'function' => 'clear_all_participants'
                    ],[
                        'key'      => 'wp_ajax_bc_events_clear_participants',
                        'function' => 'clear_participants'
                    ],[
                        'key'      => 'bc_events_start_send',
                        'function' => 'start_send',
                        'args'     => 2
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
        return twig_render('menu_events', $json);
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
            'referer'   => url_menu('bc_events'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'start',
            'eventid'   => '',
            'datetime'  => '',
            'age'       => 6,
            'what'      => '',
            'where'     => '',
            'body'      => ''
            ];
        return $json;
    }

    /**
     * Fetch JSON used for the edit state.
     * @global string $_GET['eventid'] unique event identifier
     * @return array JSON for TWIG rendering
     */
    private function executeEdit(): array
    {
        $eventid = input_request('eventid');
        $no      = [];
        $yes     = [];
        $join = new JoinMembersUsersParticipants();
        $join->loopParticipantForEvent($eventid);
        while ($join->fetch()) {
            if ($join->isParticipant()) {
                $yes[] = $join->member_id;
            } else {
                $no[]  = $join->member_id;
            }
        }
        $event    = TableEvents::findByID($eventid);
        $datetime = $event->starttime;
        $endtime  = $event->endtime;
        if ('00' === substr($datetime, 17)) {
            $datetime = substr($datetime, 0, 16);
        }
        if ('00' === substr($endtime, 17)) {
            $endtime = substr($endtime, 0, 16);
        }
        if (substr($datetime, 0, 10) === substr($endtime, 0, 10)) {
            $endtime = substr($endtime, 11);
        }
        $nonce   = $this->create_nonce();
        $json    = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_events'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'edit',
            'groups'    => array_merge(getGroups(BC_GROUP_CLUB),
                            getGroups(BC_GROUP_SELECT)),
            'eventid'   => $eventid,
            'max'       => $event->max_attend,
            'sent'      => TableParticipants::getSentCount($eventid),
            'datetime'  => $datetime,
            'endtime'   => $endtime,
            'private'   => $event->private,
            'priority'  => $event->priority,
            'what'      => $event->summary,
            'where'     => $event->location,
            'map'       => $event->map,
            'max'       => $event->max_attend,
            'body'      => $event->description,
            'no'        => $no,
            'yes'       => $yes
            ];
        if ($event->priority) {
            $ahead = new \DateTime($datetime);
            $ahead->sub(new \DateInterval('PT' . $event->priority . 'H'));
            $json['priortime'] = $ahead;
        }
        return $json;
    }

    /**
     * Search database with the provided parameters.
     * @param string|null $eventid optional exact event identifier
     * @param string|null $datetime optional day of the event
     * @param string|null $age optional maximum age of the event in months
     * @param string|null $what optional partial event short description
     * @param string|null $where optional partial street address
     * @param string|null $map optional partial map URL
     * @param string|null $body optional partial email full description
     * @return array JSON search results
     */
    private function search(?string $eventid, ?string $datetime, ?string $age,
            ?string $what, ?string $where, ?string $map, ?string $body): array
    {
        $results = [];
        $today = strtotime(date('Y-m-d'));
        $iterator = new TableEvents();
        $iterator->loopSearch($eventid, $datetime, $age, $what, $where, $map, $body);
        while ($iterator->fetch()) {
            $results[] = [
                'id'       => $iterator->event_id,
                'start'    => $iterator->starttime,
                'subject'  => $iterator->summary,
                'location' => $iterator->location,
                'private'  => $iterator->private,
                'priority' => $iterator->priority,
                'past'     => strtotime($iterator->starttime) < $today
            ];
        }
        return $results;
    }

    /**
     * Fetch JSON used for the search state.
     * @global string|null $_GET['eventid'] optional exact event identifier
     * @global string|null $_GET['datetime'] optional day of the event
     * @global string|null $_GET['age'] optional maximum age of the event in
     * months
     * @global string|null $_GET['what'] optional event short description
     * @global string|null $_GET['where'] optional partial street address
     * @global string|null $_GET['map'] optional partial map URL
     * @global string|null $_GET['body'] optional partial event full description
     * @return array JSON for TWIG rendering
     */
    private function executeSearch(): array
    {
        $eventid  = input_request('eventid');
        $datetime = input_request('datetime');
        $age      = input_request('age');
        $what     = input_request('what');
        $where    = input_request('where');
        $map      = input_request('map');
        $body     = input_request('body');
        $nonce    = $this->create_nonce();
        $json     = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_events'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'search',
            'eventid'   => $eventid,
            'datetime'  => $datetime,
            'age'       => $age,
            'what'      => $what,
            'where'     => $where,
            'map'       => $map,
            'body'      => $body
            ];
        $json['found'] = $this->search($eventid, $datetime, $age, $what, $where,
                $map, $body);
        return $json;
    }

    /** AJAX functions */

    /**
     * Render the raw event body using twig to HTML.
     * @global string $_REQUEST['body'] raw HTML for the email
     * @global string $_REQUEST['eventid'] unique event identifier
     */
    public function events_body(): void
    {
        $response = $this->check_request('Fetch HTML');
        if (!$response) {
            $html    = input_request('body');
            $eventid = input_request('eventid');
            $event   = TableEvents::findByID($eventid);
            $member  = TableMembers::findByWordpressID(\get_current_user_id());
            $user    = \get_userdata(\get_current_user_id());
            $json    = twig_macro_fields([$member, $event, $user]);
            $template = twig_template($html);
            echo twig_render($template, $json);
        }
        die();
    }

    /**
     * Render the raw HTML in text form.
     * @param \bookclub\TableEvents $event event database record
     * @param string $body raw HTML for the event
     * @return string rendered HTML
     */
    private function get_text(TableEvents $event, string $body): string
    {
        $wpid   = \get_current_user_id();
        $member = TableMembers::findByWordpressID($wpid);
        $user   = \get_userdata($wpid);
        $json   = twig_macro_fields([$member, $event, $user]);
        $json['html'] = twig_template($body);
        $source = "{% autoescape false %}\n" .
                  "{% apply html_to_text %}" .
                  "{% include html %}" .
                  "{% endapply %}\n" .
                  "{% endautoescape %}\n";
        $template = twig_template($source);
        return twig_render($template, $json);
    }

    /**
     * Generate a log list of emails being sent.
     * @param string $eventid unique event identifier
     * @param int $sent count of emails sent
     * @param int $unsent count of emails not yet sent
     * @return string rendered HTML
     */
    private function get_log(string $eventid, int $sent, int $unsent): string
    {
        $lockkey    = $this->get_lock_key($eventid);
        $participants = [];
        $join       = new JoinMembersUsersParticipants();
        $join->loopSent($eventid);
        while ($join->fetch()) {
            $participants[] = [
                'name'  => $join->fullname,
                'email' => $join->user_email ?: $join->email,
                'sent'  => $join->email_sent
            ];
        }
        $json = [
            'participants' => $participants,
            'sent'         => $sent,
            'unsent'       => $unsent,
            'done'         => !is_lock($lockkey)
            ];
        return twig_render('event_log', $json);
    }

    /**
     * Generate a log of changes to RSVP status.
     * @param string $eventid unique event identifier
     * @return string rendered HTML
     */
    private function get_history(string $eventid): string
    {
        $join = new JoinRSVPsMembersUsers();
        $join->loopRecentOptionalEvent($eventid);
        $participants = [];
        while ($join->fetch()) {
            $participants[] = [
                'name'  => $join->fullname,
                'time'  => $join->modtime,
                'rsvp'  => $join->rsvp
            ];
        }
        return twig_render('event_history', [
            'participants' => $participants,
            'images'       => url_images()]);
    }

    /**
     * Generate the RSVP view for the given event.
     * @param string $eventid unique event identifier
     * @return string rendered HTML
     */
    private function get_rsvp(string $eventid): string
    {
        $join = new JoinMembersUsersParticipants();
        $join->loopEvent($eventid);
        $yes    = [];
        $no     = [];
        $maybe  = [];
        $wyes   = [];
        $wmaybe = [];
        $rest   = [];
        while ($join->fetch()) {
            $person = [
                'name'    => $join->fullname,
                'id'      => $join->member_id,
                'sent'    => $join->isEMailSent(),
                'rsvp'    => $join->rsvp,
                'waiting' => $join->waiting
                    ];
            if ($join->waiting) {
                if (BC_RSVP_ATTENDING == $join->rsvp) {
                    $wyes[] = $person;
                } else { // BC_RSVP_MAYBE
                    $wmaybe[] = $person;
                }
            } else if (BC_RSVP_ATTENDING == $join->rsvp) {
                $yes[] = $person;
            } else if (BC_RSVP_MAYBE == $join->rsvp) {
                $maybe[] = $person;
            } else if (BC_RSVP_NOTATTENDING == $join->rsvp) {
                $no[] = $person;
            } else {
                $rest[] = $person;
            }
        }
        return twig_render('event_rsvp', [
            'yes'        => $yes,
            'maybe'      => $maybe,
            'wait_yes'   => $wyes,
            'wait_maybe' => $wmaybe,
            'no'         => $no,
            'rest'       => $rest]);
    }

    /**
     * Generate waiting list for RSVP view.
     * @param string $eventid unique event identifier
     * @return string rendered HTML
     */
    private function get_waiting_list(string $eventid): string
    {
        $participants = [];
        $join = new JoinMembersUsersParticipants();
        $join->loopWaitingList($eventid);
        while ($join->fetch()) {
            $participants[] = $join->fullname;
        }
        return twig_render('event_waiting', ['participants'  => $participants]);
    }

    /**
     * Add member inclusion/sent status data to a response object.
     * @param array $response JSON response object
     * @param string $eventid unique event identifier
     * @param array $yesdata list of included member ids
     * @param array $nodata list of member ids not included
     */
    private function participant_data(array &$response, string $eventid,
            array $yesdata, array $nodata): void
    {
        $no         = [];
        $yes        = [];
        $join = new JoinMembersUsersParticipants();
        $join->loopParticipantForEvent($eventid);
        while ($join->fetch()) {
            $item = [
                'id'     => $join->member_id,
                'name'   => $join->fullname,
                'active' => $join->active
            ];
            if ($join->isParticipant()) {
                $item['included'] = true;
            }
            if ($join->isEMailSent()) {
                $item['sent'] = true;
            }
            if (in_array($join->member_id, $nodata)) {
                $no[]  = $item;
            } else {    //in_array($join->member_id, $yesdata)
                $yes[] = $item;
            }
        }
        $response['no']  = twig_render('select_participants',
                 ['set'  => 'no', 'members' => $no]);
        $response['yes'] = twig_render('select_participants',
                 ['set'  => 'yes', 'members' => $yes]);
    }

    /**
     * Check that the event identifier can be used (is not already used and
     * has a non-zero length), return a response object if not.
     * @param string $eventid unique event identifier
     * @return array response object if there is an error
     */
    private  function validate_eventid(string $eventid): array
    {
        if (0 == strlen(trim($eventid))) {
            return $this->get_response(true, 'Invalid event ID');
        }
        $event = TableEvents::findByID($eventid);
        if (!is_null($event)) {
            return $this->get_response(true, 'Event ID already exists');
        }
        return [];
    }

    /**
     * Checks and amends the user entered start datetime. The time, if omitted,
     * will be set to 19:30:00. The seconds (:00) will be appended if omitted.
     * @param string $datetime start datetime
     * @return array response object if there is an error
     */
    private  function validate_start(string &$datetime): array
    {
        if (preg_match("/^\d\d\d\d-\d\d-\d\d$/", $datetime)) {
            $datetime .= ' 19:30:00';
        } elseif (preg_match("/^\d\d\d\d-\d\d-\d\d \d\d:\d\d$/", $datetime)) {
            $datetime .= ':00';
        }
        $d = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        if ($d && $d->format('Y-m-d H:i:s') === $datetime) {
            return [];
        }
        return $this->get_response(true, 'Invalid start time');
    }

    /**
     * Checks and amends the user entered end datetime. The date if omitted is
     * prepended from the start datetime. The seconds (:00) will be appended
     * if omitted.
     * @param string $start start datetime
     * @param string $end end datetime
     * @return bool true if format is valid
     */
    private function validate_end(string $start, string &$end): bool
    {
        if (!preg_match("/^\d\d\d\d-\d\d-\d\d /", $end)) {
            $end = substr($start, 0, 11) . $end;
        }
        if (preg_match("/ \d\d:\d\d$/", $end)) {
            $end .= ':00';
        }
        $d = \DateTime::createFromFormat('Y-m-d H:i:s', $end);
        return $d && $d->format('Y-m-d H:i:s') === $end;
    }

    /**
     * Add an event based on the user submitted entries, generate a JSON
     * response.
     * @global string $_REQUEST['eventid'] unique event identifier
     * @global string $_REQUEST['datetime'] start date or datetime
     * @global string $_REQUEST['what'] event short description
     * @global string $_REQUEST['where'] street address of the event
     * @global string $_REQUEST['map'] map URL
     * @global string $_REQUEST['body'] event raw HTML description
     */
    public function events_add(): void
    {
        $response = $this->check_request('Add event');
        if (!$response) {
            $eventid  = input_request('eventid');
            $datetime = input_request('datetime');
            $response = $this->validate_eventid($eventid);
            if (!$response) {
                $response = $this->validate_start($datetime);
            }
            if (!$response) {
                $person = TableMembers::findByWordpressID(\get_current_user_id());
                $event = new TableEvents();
                $event->event_id    = $eventid;
                $event->starttime   = $datetime;
                $event->endtime     = substr($datetime, 0, 10) . ' 22:00:00';
                $event->summary     = input_request('what');
                $event->location    = input_request('where');
                $event->map         = input_request('map');
                $event->description = input_request('body');
                $event->private     = 0;
                $event->priority    = 0;
                $event->max_attend  = 0;
                $event->rsvp_attend = 0;
                $event->modtime     = date('Y-m-d H:i:s');
                $event->organiser   = $person ? $person->member_id : 0;
                $event->insert();
                $this->log_info("Event added $eventid");
                $response = $this->get_response(false, 'Event added');
                $response['eventid'] = $eventid;
            }
        }
        exit(json_encode($response));
    }

    /**
     * Update event information. Generate a JSON response.
     * @global string $_REQUEST['eventid'] unique event identifier
     * @global string $_REQUEST['new_eventid'] new event identifier (or same)
     * @global string $_REQUEST['starttime'] start date or datetime
     * @global string $_REQUEST['endtime'] end time or end datetime
     * @global string $_REQUEST['private'] one if event private
     * @global string $_REQUEST['priority'] non-zero for hours prior to meeting
     * where it is public
     * @global string $_REQUEST['max'] maximum number of participants
     * @global string $_REQUEST['what'] event short description
     * @global string $_REQUEST['where'] street address of the event
     * @global string $_REQUEST['map'] map URL
     * @global string $_REQUEST['body'] event raw HTML description
     * @global string $_REQUEST['yes'] comma separated list of participants
     * @global string $_REQUEST['no'] comma separated list of non-participants
     * @global string $_REQUEST['referer'] original referer URL
     */
    public function events_save(): void
    {
        $response = $this->check_request('Save event');
        if (!$response) {
            $eventid = input_request('eventid');
            $newid   = input_request('new_eventid');
            $datetime = input_request('starttime');
            if ($eventid !== $newid) {
                $response = $this->validate_eventid($newid);
            }
            if (!$response) {
                $response = $this->validate_start($datetime);
            }
            if (!$response) {
                if ($eventid !== $newid) {
                    $this->log_info("Event moved $eventid => $newid");
                    TableEvents::updateID($eventid, $newid);
                    TableParticipants::updateID($eventid, $newid);
                    TableRSVPs::updateID($eventid, $newid);
                }
                $endtime = input_request('endtime');
                $event = TableEvents::findByID($newid);
                $event->max_attend  = input_request('max');
                $event->rsvp_attend = TableParticipants::getAttendingCount($eventid);
                $event->starttime   = $datetime;
                if ($this->validate_end($datetime, $endtime)) {
                    $event->endtime = $endtime;
                }
                $event->summary     = input_request('what');
                $event->location    = input_request('where');
                $event->map         = input_request('map');
                $event->description = input_request('body');
                $event->private     = input_request('private');
                $event->priority    = input_request('priority');
                $event->modtime     = date('Y-m-d H:i:s');
                $event->update();
                //$yesdata = preg_split('/,/', input_request('yes'));
                $nodata  = preg_split('/,/', input_request('no'));
                $join = new JoinMembersUsersParticipants();
                $join->loopParticipantForEvent($newid);
                while ($join->fetch()) {
                    if (in_array($join->member_id, $nodata)) {
                        if ($join->isParticipant()) {
                            TableParticipants::deleteParticipant($newid,
                                    $join->member_id);
                        }
                    } else {    //in_array($join->member_id, $yesdata)
                        if (!$join->isParticipant()) {
                            TableParticipants::addParticipant($newid,
                                    $join->member_id);
                        }
                    }
                }
                $this->log_info("Event updated $eventid");
                $url = '';
                if ($eventid !== $newid) {
                    $url = input_request('referer') .
                            "&action=edit&eventid=$newid";
                }
                $response = $this->get_response(false, 'Event updated', $url);
            }
        }
        exit(json_encode($response));
    }

    /**
     * Delete an event from the database. Generate a JSON response.
     * @global string $_REQUEST['eventid'] unique event identifier
     */
    public function events_delete(): void
    {
        $response = $this->check_request('Delete event');
        if (!$response) {
            $eventid = input_request('eventid');
            TableEvents::deleteByID($eventid);
            TableParticipants::deleteByEvent($eventid);
            TableRSVPs::deleteByEvent($eventid);
            $this->log_info("Delete event $eventid");
            $response = $this->get_response(false, 'Event deleted');
        }
        exit(json_encode($response));
    }

    /**
     * Generate a string to use for locking an event send job.
     * @param string $eventid unique event identifier
     * @return string a string used for locking the send job
     */
    public function get_lock_key(string $eventid): string
    {
        return 'invite_' . preg_replace('~[ :-]~', '', $eventid);
    }

    /**
     * Generate a JSON response containing general status information and data
     * specific to the current view.
     * @global string $_REQUEST['eventid'] unique event identifier
     * @global string $_REQUEST['view'] active view (text,participants,log,etc.)
     * @global string $_REQUEST['body'] raw HTML event body
     * @global string $_REQUEST['yes'] comma separated list of participant ids
     * @global string $_REQUEST['no'] comma separated list of non-participants
     */
    public function get_status(): void
    {
        $response = $this->check_request('Get status');
        if (!$response) {
            $eventid  = input_request('eventid');
            $view     = input_request('view');
            $event    = TableEvents::findByID($eventid);
            $unsent   = TableParticipants::getUnsentCount($eventid);
            $sent     = TableParticipants::getSentCount($eventid);
            $lockkey  = $this->get_lock_key($eventid);
            $running  = is_lock($lockkey);
            $response = $this->get_response(false,
                    "Get status ($running, $sent, $unsent)");
            if ('text' == $view) {
                $response['text'] = $this->get_text(
                        $event, input_request('body'));
            } else if ('participants' == $view) {
                $yesdata    = preg_split('/,/', input_request('yes'));
                $nodata     = preg_split('/,/', input_request('no'));
                $this->participant_data($response, $eventid, $yesdata, $nodata);
            } else if ('log' == $view) {
                $response['log']  = $this->get_log($eventid, $sent, $unsent);
                $response['rsvp'] = $this->get_history($eventid);
            } else if ('rsvp' == $view) {
                $response['rsvp'] = $this->get_rsvp($eventid);
                $response['waiting'] = $this->get_waiting_list($eventid);
                $response['wait_count'] = TableParticipants::getWaitingCount($eventid);
            }
            $response['running'] = $running;
            $response['sent']    = $sent;
            $response['unsent']  = $unsent;
        }
        exit(json_encode($response));
    }

    /**
     * Update the RSVP status of the specified participant. Generate a JSON
     * response. If a seat is freed or next is chosen, an email will be sent
     * to the next person on the waiting list.
     * @global string $_REQUEST['eventid'] unique event identifier
     * @global string $_REQUEST['member'] unique member identifier
     * @global string $_REQUEST['status'] status change 'wait','unwait','next',
     * 'yes','no','maybe'
     */
    public function update_rsvp(): void
    {
        $response = $this->check_request('Update rsvp');
        if (!$response) {
            $lucky_person = null;
            $eventid = input_request('eventid');
            $member  =  input_request('member');
            $status  = input_request('status');
            $event   = TableEvents::findByID($eventid);
            $rsvp = TableParticipants::findByEventAndPerson($eventid, $member);
            if ($status === 'wait') {
                $rsvp->waiting = 1;
                $rsvp->update();
            } elseif ($status === 'unwait') {
                $rsvp->waiting = 0;
                $rsvp->update();
            } elseif ($status === 'next') {
                $lucky_guy = TableParticipants::getNextWaiting($eventid);
                $lucky_person = JoinMembersUsers::findByID($lucky_guy);
                $sender = new EMail();
                $sender->sendNotification($lucky_guy, $event);
            } else {
                $st = BC_RSVP_NORESPONSE;
                if ($status === 'no') {
                    $st = BC_RSVP_NOTATTENDING;
                } elseif ($status === 'yes') {
                    $st = BC_RSVP_ATTENDING;
                } elseif ($status === 'maybe') {
                    $st = BC_RSVP_MAYBE;
                }
                $lucky_person = updateRSVP($event, $rsvp, $st, $rsvp->comment,
                        [new EMail(), 'sendNotification']);
            }
            $event->rsvp_attend = TableParticipants::getAttendingCount($eventid);
            $event->update();
            $this->log_info("Update rsvp ($eventid, $member, $status)");
            if ($status === 'next') {
                $response = $this->get_response(false,
                        "Removed from waiting list - " . $lucky_person->fullname);
            } elseif ($lucky_person) {
                $response = $this->get_response(false, "RSVP updated, " .
                        $lucky_person->fullname . " removed from waiting list.");
            } else {
                $response = $this->get_response(false, "RSVP updated");
            }
            $response['rsvp'] = $this->get_rsvp($eventid);
            $response['waiting'] = $this->get_waiting_list($eventid);
            $response['wait_count'] = TableParticipants::getWaitingCount($eventid);
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
    public function events_select(): void
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

    /**
     * Create a lock, start an email send job, generate a JSON response.
     * @global string $_REQUEST['eventid'] unique event identifier
     * @global string $_REQUEST['list'] 'all' or comma separated ids of
     * recipients
     */
    public function send_job(): void
    {
        $response = $this->check_request('Start sending emails');
        if (!$response) {
            $eventid  = input_request('eventid');
            $list     = input_request('list');
            $lockkey = $this->get_lock_key($eventid);
            if (!create_lock($lockkey)) {
                $response = $this->get_response(true,
                        'Cannot start job - it was already started');
                $this->log_error("Error locking job $eventid");
            } else {
//                $this->start_send($eventid, $list);
                $result = \wp_schedule_single_event(time(),
                        'bc_events_start_send', [$eventid, $list]);
                if ($result) {
                    $response = $this->get_response(false,
                            'Scheduled job to send emails');
                    $this->log_info("Job scheduled $eventid");
                } else {
                    free_lock($lockkey);
                    $response = $this->get_response(true,
                            'Error starting job - possible duplicate');
                    $this->log_error("Error scheduling job $eventid");
                }
            }
        }
        exit(json_encode($response));
    }

    /**
     * Clear the sent flag for all participants of an event. Generate a JSON
     * response.
     * @global string $_REQUEST['eventid'] unique event identifier
     */
    public function clear_all_participants(): void
    {
        $response = $this->check_request('Clear all participants');
        if (!$response) {
            $eventid  = input_request('eventid');
            TableParticipants::clearByID($eventid);
            $response = $this->get_response(false, "Participants cleared");
        }
        exit(json_encode($response));
    }

    /**
     * EMail send job, sends invitation to designated participants, clears lock.
     * @param string $eventid unique event identifier
     * @param string $list 'all' or comma separated ids of recipients
     */
    public function start_send(string $eventid, string $list): void
    {
        $this->log_info("Start sending invitations for $eventid");
        $lockkey = $this->get_lock_key($eventid);
        claim_lock($lockkey);
        $sender = new EMail();
        $sleep  = getOption('email_sleep');
        $this->log_debug("Sleep $sleep");
        if ('all' === $list) {
            $join    = new JoinMembersUsersParticipants();
            $join->loopUnsent($eventid);
            while ($join->fetch()) {
                $this->log_debug("Sending to " . $join->member_id);
                if ($sender->sendInvitation($join->member_id, $eventid)) {
                    TableParticipants::setSent($eventid, $join->member_id);
                }
                if (!is_lock($lockkey)) {
                    $this->log_error("Job interrupted");
                    break;
                }
                if ($sleep) {
                    usleep($sleep);
                }
            }
        } else {
            foreach (preg_split('/,/', $list) as $personid) {
                $this->log_debug("Sending to $personid");
                if ($sender->sendInvitation($personid, $eventid)) {
                    TableParticipants::setSent($eventid, $personid);
                }
                if (!is_lock($lockkey)) {
                    $this->log_error("Job interrupted");
                    break;
                }
                if ($sleep) {
                    usleep($sleep);
                }
            }
        }
        free_lock($lockkey);
    }

    /**
     * Clear the sent flag for some participants of an event. Generate a JSON
     * response.
     * @global string $_REQUEST['eventid'] unique event identifier
     * @global string $_REQUEST['list'] comma separated ids of participants
     */
    public function clear_participants(): void
    {
        $response = $this->check_request('Clear participants');
        if (!$response) {
            $eventid  = input_request('eventid');
            $list = preg_split('/,/', input_request('list'));
            foreach ($list as $participant) {
                TableParticipants::clearParticipant($eventid, $participant);
                $this->log_debug("Clear $participant");
            }
            $response = $this->get_response(false, "Participants cleared");
        }
        exit(json_encode($response));
    }
}

new MenuEvents();
