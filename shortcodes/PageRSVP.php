<?php namespace bookclub;

/*
 * Class wraps code used to generate the type='rsvp' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage shortcodes
 * @license    https://opensource.org/licenses/MIT MIT
 */

class PageRSVP extends ShortCode
{
    /**
     * Initialize the object.
     * @return \bookclub\PageRSVP
     */
    public function __construct()
    {
        parent::__construct('bc_rsvp',
            [
                'shorttype' => 'rsvp',
                'style'     => 'page_rsvp.css',
                'script'    => 'page_rsvp.js',
                'help'      => 'page_rsvp',
                'nonce'     => 'rsvp_nonce',
                'actions'   => [[
                        'key' => 'wp_ajax_bc_rsvp_update',
                        'function' => 'rsvp_update'
                    ],[
                        'key' => 'wp_ajax_nopriv_bc_rsvp_update',
                        'function' => 'rsvp_update'
                    ],[
                        'key' => 'wp_ajax_bc_rsvp_resend',
                        'function' => 'rsvp_resend'
                    ],[
                        'key' => 'wp_ajax_nopriv_bc_rsvp_resend',
                        'function' => 'rsvp_resend'
                    ],[
                        'key' => 'admin_post_nopriv_bc_rsvp_body',
                        'function' => 'rsvp_body'
                    ],[
                        'key' => 'admin_post_bc_rsvp_body',
                        'function' => 'rsvp_body'
                    ],[
                        'key' => 'wp_ajax_bc_rsvp_help',
                        'function' => 'get_help'
                    ],[
                        'key' => 'wp_ajax_nopriv_bc_rsvp_help',
                        'function' => 'get_help'
                    ],[
                        'key' => 'wp_ajax_bc_rsvp_heartbeat',
                        'function' => 'rsvp_heartbeat'
                    ],[
                        'key' => 'wp_ajax_nopriv_bc_rsvp_heartbeat',
                        'function' => 'rsvp_heartbeat'
                    ]]
            ]);
    }

    /**
     * Generate an HTML/JS script for redirecting to the RSVP page without the
     * status flag (and possibly different or no web key).
     * @param \bookclub\TableEvents $event event object
     * @param string $pkey web key for user that is not logged in (or empty)
     * @return string HTML snippet
     */
    private function redirect(TableEvents $event, string $pkey): string
    {
        return
            "\n" .
            "<script type='text/javascript'>\n" .
            "  window.onload = function() {\n" .
            "    window.location.href = '" .
                    url_rsvp($event, $pkey) . "';\n" .
            "  }\n" .
            "</script>\n";
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @global string $_GET['eid'] unique event identifier
     * @global string|null $_GET['pkey'] member web key (if not logged in)
     * @global string|null $_GET['status'] new RSVP status
     * @return string HTML content
     */
    public function render(): string
    {
        parent::enqueue();
        $wpid   = \get_current_user_id();
        $eid    = input_get('eid');
        $pkey   = input_get('pkey');
        $status = input_get('status');
        $this->log_info("RSVP eid: $eid, pkey $pkey, status: $status");

        // get member
        if ($wpid) {
            // logged in user takes precedence over web key
            $member = JoinMembersUsers::findByWordpressID($wpid);
            if ($pkey && $pkey != $member->web_key) {
                $this->log_info("RSVP incorrect member");
            }
        } else {
            $member = JoinMembersUsers::findByKey($pkey);
        }

        // get event and rsvp
        $event  = TableEvents::findByID($eid);
        if ($member && $event) {
            $rsvp   = TableParticipants::findByEventAndPerson(
                        $eid, $member->member_id);

            // person not invited and event is not private
            if (!$rsvp && !$event->private) {
                // invite the person
                TableParticipants::addParticipant($event->event_id,
                        $member->member_id);
                $rsvp = TableParticipants::findByEventAndPerson(
                        $event->event_id, $member->member_id);
                // change status to attending
//                updateRSVP($event, $rsvp, BC_RSVP_ATTENDING, null,
//                        [new EMail(), 'sendNotification']);
            }
        }

        // missing information - quit
        if (!$member || !$event || !$rsvp) {
            return '<div class=\'bc_rsvp_error\'>RSVP - Bad Link</div>';
        }

        // set new status
        if ($status) {
            updateRSVP($event, $rsvp, $status, null,
                    [new EMail(), 'sendNotification']);
        }

        // we need a new URL if the web key does not match or if there was a status change
        if ($status || ($pkey && $pkey != $member->web_key)) {
            return $this->redirect($event, $wpid ? '' : $pkey);
        }

        // register member participation
        TableMembers::findByKey($member->web_key)->hit();

        // render the page
        $json = $this->jsonRSVP($event, $member, $rsvp, $wpid != 0);
        return twig_render('page_rsvp', $json);
    }

    /**
     * Fetch JSON for participants with given parameters
     * @param string $eventid unique event identifier
     * @param int $rsvp RSVP status to select for
     * @param int $waiting waiting status, 1 if waiting
     * @param int $me current user identifier
     * @param int $loggedin true if the user is logged in
     * @return array JSON for parameters
     */
    private function showForClass(string $eventid, int $rsvp, int $waiting,
            int $me, bool $loggedin): array
    {
        $json = [];
        $participant = new JoinMembersUsersParticipants();
        $participant->loopEventForRSVPStatus($eventid, $rsvp,
                $waiting, time() - (91*24*60*60));
        while ($participant->fetch()) {
            if (!$loggedin && ($participant->member_id != $me)) {
                $json[] = [
                    'name'    => '-- undisclosed --',
                    'comment' => ''
                    ];
            } else {
                $json[] = [
                    'name'    => $participant->fullname,
                    'comment' => $participant->comment,
                    'email'   => $participant->public_email ? $participant->user_email : ''
                    ];
            }
        }
        return $json;
    }

    /**
     * Fetch JSON for the RSVP page.
     * @param \bookclub\TableEvents $event database event object
     * @param \bookclub\JoinMembersUsers $member database member object
     * @param \bookclub\TableParticipants $rsvp database RSVP object
     * @param bool $loggedin true if user logged in
     * @return array JSON for TWIG rendering
     */
    private function jsonRSVP(TableEvents $event, JoinMembersUsers $member,
            TableParticipants $rsvp, bool $loggedin): array
    {
        $id       = $member->member_id;
        $eid      = $event->event_id;
        $nonce    = $this->create_nonce();
        $json     = [
            'admin_url'     => url_admin_post(),
            'nonce'         => $nonce,
            'referer'       => url_request(),
            'images'        => url_images(),
            'event_id'      => $event->event_id,
            'person_id'     => $member->member_id,
            'webkey'        => $member->web_key,
            'wpid'          => $member->wordpress_id,
            'loggedin'      => $loggedin,
            'login_page'    => url_login(url_rsvp($event)),
            'ical_url'      => url_ical($member->web_key, $eid),
            'summary'       => $event->summary,
            'max_attend'    => $event->max_attend,
            'map'           => $event->map,
            'location'      => $event->location,
            'start'         => $event->starttime,
            'end'           => $event->endtime,
            'attending'     => $this->showForClass($eid, BC_RSVP_ATTENDING, 0, $id, $loggedin),
            'maybe'         => $this->showForClass($eid, BC_RSVP_MAYBE, 0, $id, $loggedin),
            'waiting'       => $this->showForClass($eid, BC_RSVP_ATTENDING, 1, $id, $loggedin),
            'waiting_maybe' => $this->showForClass($eid, BC_RSVP_MAYBE, 1, $id, $loggedin),
            'not_attending' => $this->showForClass($eid, BC_RSVP_NOTATTENDING, 0, $id, $loggedin),
            'no_response'   => $this->showForClass($eid, BC_RSVP_NORESPONSE, 0, $id, $loggedin),
            'name'          => $member->fullname,
            'rsvp'          => $rsvp->rsvp,
            'comment'       => $rsvp->comment,
            'signup'        => url_signup($member)
        ];
        return $json;
    }

    /** Actions */

    /**
     * Checks if REQUEST is correct for this page. Overrides parent function to
     * use the webkey instead of the nonce for validation.
     * @param string $error additional string for error message
     * @return array JSON response or empty array if no error
     */
    protected function check_request(string $error): array
    {
        $response = [];
        if (!is_request()) {
            $this->log_error("Not a reqest - $error (" . input_referer() . ")");
            $response = $this->get_response(true, 'Bad request');
        } else {
            $nonce  = get_nonce();
            $result = \wp_verify_nonce($nonce, $this->data['nonce']);
            if (!$result) {
                $webkey = input_request('webkey');
                $member = TableMembers::findByKey($webkey);
                if (!$member) {
//                    $response = $this->get_response(true,
//                            'Bad member key');
                    $eid   = input_request('eid');
                    $event = TableEvents::findByID($eid);
                    $this->log_info("Redirecting - $eid $webkey");
                    return $this->redirect($event, $webkey);
                } else {
                    $this->log_info("Nonce expired ($nonce), validated with web key $webkey");
                }
            }
        }
        return $response;
    }

    /**
     * Update the RSVP status of the specified member. Generate a JSON response.
     * If a seat is freed or next is chosen, an email will be sent to the next
     * person on the waiting list.
     * @global string $_REQUEST['personid'] unique member identifier
     * @global string $_REQUEST['eid'] unique event identifier
     * @global string $_REQUEST['status'] status change 'yes','no','maybe'
     * @global string $_REQUEST['st'] status change if status not specified
     * @global string $_REQUEST['comment'] optional comment
     */
    public function rsvp_update(): void
    {
        $response = $this->check_request('RSVP submit');
        if (!$response) {
            $wpid     = \get_current_user_id();
            $webkey   = input_request('webkey');
            $member   = TableMembers::findByKey($webkey);
            $eid      = input_request('eid');
            $event    = TableEvents::findByID($eid);
            $rsvp     = TableParticipants::findByEventAndPerson($eid,
                            $member->member_id);
            $status   = input_request('status');
            if (!$status) {
                $status = input_request('st');
            }
            $st = BC_RSVP_NORESPONSE;
            if ($status == 'no') {
                $st = BC_RSVP_NOTATTENDING;
            } elseif ($status == 'yes') {
                $st = BC_RSVP_ATTENDING;
            } elseif ($status == 'maybe') {
                $st = BC_RSVP_MAYBE;
            }
            $comment  = input_request('comment');
            updateRSVP($event, $rsvp, $st, $comment,
                    [new EMail(), 'sendNotification']);
            $response = $this->get_response(false, 'RSVP updated',
                    url_rsvp($event, $wpid ? '' : $member->web_key));
        }
        exit(json_encode($response));
    }

    /**
     * Resent RSVP invitation. Generate a JSON response.
     * @global string $_REQUEST['personid'] unique member identifier
     * @global string $_REQUEST['eid'] unique event identifier
     */
    public function rsvp_resend(): void
    {
        $response = $this->check_request('RSVP resend');
        if (!$response) {
            $eid      = input_request('eid');
            $webkey   = input_request('webkey');
            $member   = TableMembers::findByKey($webkey);
            $sender   = new EMail();
            $ok = $sender->sendInvitation($member->member_id, $eid);
            if ($ok) {
                TableParticipants::setSent($eid, $member->member_id);
            }
            $response = $this->get_response(!$ok, $sender->getLastError());
        }
        exit(json_encode($response));
    }

    /**
     * Generate HTML for given event and person.
     * @global string $_REQUEST['webkey'] unique member webkey
     * @global string $_REQUEST['eid'] unique event identifier
     */
    public function rsvp_body(): void
    {
        $this->log_debug("Get rsvp body");
        $eventid  = input_request('eid');
        $response = $this->check_request("Fetch invitation description $eid");
        if (!$response) {
            $webkey       = input_request('webkey');
            $member       = TableMembers::findByKey($webkey);
            $event        = TableEvents::findByID($eventid);
            $user         = $member->wordpress_id ?
                    \get_userdata($member->wordpress_id) : null;
            $json         = twig_macro_fields([$member, $event, $user]);
            $json['body'] = twig_template($event->description);
            echo twig_render('event_body', $json);
        }
        die();
    }

    /**
     * Update the "who" portion of the RSVP and possibly the nonce.
     * @global string $_REQUEST['personid'] unique member identifier
     * @global string $_REQUEST['eid'] unique event identifier
     */
    public function rsvp_heartbeat(): void
    {
        $response = $this->check_request('RSVP heartbeat');
        if (!$response) {
            $this->log_debug("RSVP heartbeat refresh");
            $eid      = input_request('eid');
            $event    = TableEvents::findByID($eid);
            $id       = input_request('personid');
            $wpid     = \get_current_user_id();
            $loggedin = $wpid != 0;
            $nonce    = $this->create_nonce();
            if ($nonce != input_request('nonce')) {
                $this->log_info("Update nonce " . input_request('nonce') .
                        " => $nonce");
            }
            $response          = $this->get_response(false, '');
            $response['nonce'] = $nonce;
            $json = [
                'max_attend'    => $event->max_attend,
                'attending'     => $this->showForClass($eid, BC_RSVP_ATTENDING, 0, $id, $loggedin),
                'maybe'         => $this->showForClass($eid, BC_RSVP_MAYBE, 0, $id, $loggedin),
                'waiting'       => $this->showForClass($eid, BC_RSVP_ATTENDING, 1, $id, $loggedin),
                'waiting_maybe' => $this->showForClass($eid, BC_RSVP_MAYBE, 1, $id, $loggedin),
                'not_attending' => $this->showForClass($eid, BC_RSVP_NOTATTENDING, 0, $id, $loggedin),
                'no_response'   => $this->showForClass($eid, BC_RSVP_NORESPONSE, 0, $id, $loggedin)
                    ];
            $response['html']   = twig_render('rsvp_who', $json);
        }
        exit(json_encode($response));
    }
}

new PageRSVP();
