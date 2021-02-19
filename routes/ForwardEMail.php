<?php namespace bookclub;

/*
 * Cron job for forwarding EMails
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage pages
 * @license    https://opensource.org/licenses/MIT MIT
 */

class ForwardEMail extends Path
{
    /**
     * Initialize the object.
     * @return \bookclub\PageRSVP
     */
    public function __construct()
    {
        parent::__construct('bc_elist', '/cron_elist/');
    }

    /**
     * Used for register_activation_hook.
     */
    public function activate(): void
    {
        $this->schedule_cron();
        parent::activate();
    }

    /**
     * Deactivate the plugin.
     */
    public function deactivate(): void
    {
        $this->remove_cron();
        parent::deactivate();
    }

    /**
     * Writes a cron file and schedules it.
     * @param string $schedule optional schedule string (e.g. * * * * *)
     */
    public function schedule_cron(string $schedule = '*/5 * * * *'): void
    {
        //$path = BOOKCLUBPATH.DS.'bookclub.cron';
        //$url  = url_site() . "/cron_elist/";
        //file_put_contents($path, "MAILTO=\"\"\n$schedule /usr/bin/wget --no-check-certificate -qO /dev/null $url\n");
        //exec("crontab $path");
    }

    /**
     * Removes the scheduled cron job.
     */
    public function remove_cron(): void
    {
        //$path = BOOKCLUBPATH.DS.'bookclub.cron';
        //exec("crontab -r");
        //unlink($path);
    }

    /**
     * Check for the debug flag. If set, save a .eml file of the received email.
     * @param resource $ch IMAP handle
     * @param int $msgno message number
     */
    private function log_email($ch, int $msgno): void
    {
        $debug  = $this->logger->isDebugEnabled();
        if ($debug) {
            $headers = imap_fetchheader($ch, $msgno);
            $body    = imap_body($ch, $msgno);
            write_debug_file('debug_email', BOOKCLUBLOGS . DS .
                    "bookclub_$msgno.eml", $headers . $body);
        }
    }

    /**
     * Add the email to the database if it doesn't already exist.
     * @param resource $ch IMAP handle
     * @param int $msgno message number
     * @param string $mailbox mailbox name of the list server (e.g. listserver)
     * @param string $host listserver host name (e.g. myhost.org)
     */
    private function register($ch, int $msgno, string $mailbox, string $host): void
    {
        // fetch header and message identifier (required)
        $info = imap_headerinfo($ch, $msgno);
        $uid  = imap_uid($ch, $msgno);
        if ((false === $info) || (false == $uid)) {
            $this->error();
            return;
        }
        $message_id = $info->message_id;
        $this->log_debug("Found: $msgno - $message_id");
        if (!$message_id) {
            $this->log_error("IMAP missing header ID $msgno/$message_id");
            return;
        }

        // check if already registered
        $imap = TableIMap::findByID($message_id);
        if (!$imap) {
            $this->log_email($ch, $msgno);

            // There may be multiple "to" fields. Find the one for listserver.
            $to_me = false;
            foreach ($info->to as $to) {
                if ($to->mailbox === $mailbox && $to->host === $host) {
                    $to_me = $to;
                    break;
                }
            }
            if (!$to_me) {
                $this->log_error("I am not a recipient! $msgno/$message_id");
            }
            $target = $to_me ? imap_utf8($to_me->personal) : '';

            // Determine the recipient(s) based on the to name
            $group_id    = $this->find_group($target);
            $receiver_id = 0;
            if (!$group_id) {
                $receiver_id = $this->find_user($target);
            }

            // Who sent this to me? (Compose the email address and find the
            // WordPress user ID.)
            $from   = $info->from[0];
            $email  = $from->mailbox . '@' . $from->host;
            $sender = \get_user_by('email', $email);

            // Register the email in the database
            $dt     = new \DateTimeImmutable($info->date);
            $imap   = new TableIMap();
            $imap->message_id   = $message_id;
            $imap->subject      = $info->subject;
            $imap->uid          = $uid;
            $imap->processed    = 0;
            $imap->status       = BC_IMAP_NEW;
            $imap->wordpress_id = $sender ? $sender->id : 0;
            $imap->target       = $target;
            $imap->timestamp    = gmdate('Y-m-d H:i:s', $dt->getTimestamp());
            $imap->target_type  = $group_id ? BC_IMAP_TARGET_GROUP :
                    ($receiver_id ? BC_IMAP_TARGET_USER : BC_IMAP_TARGET_NONE);
            $imap->target_id    = $group_id ? $group_id : $receiver_id;
            $imap->insert();
        }
    }

    /**
     * Reload the given email corresponding the the IMap record.
     * @param resource $ch IMAP handle
     * @param \bookclub\TableIMap $imap the database record
     * @return array empty if error, otherwise 'msgno', 'info', 'header', 'body'
     */
    private function refetch($ch, TableIMap $imap): array
    {
        $result = [];
        // unable to fetch by UID, so it must be converted to a message number
        $msgno = imap_msgno($ch, $imap->uid);
        if (false === $msgno) {
            $this->log_debug("Fetch UID");
            $this->error($imap);
            return $result;
        }

        // The UID is normally a good identifier but it is not perfect
        // The Message-ID is guaranteed to be unique. Let's check that
        // we have the correct email. If not, we have to find it.
        $info = imap_headerinfo($ch, $msgno);
        if (false === $info) {
            $this->log_debug("Fetch info");
            $this->error($imap);
            return $result;
        }

        // If the saved message ID does not match, find the message we fetched
        if ($imap->message_id !== $info->message_id) {
            $this->log_info("UID changed for Message-ID " . $imap->message_id);
            $this->log_debug('Original ' . $info->message_id);
            $list = imap_search($ch, "TEXT \"" . $imap->message_id . "\"");
            if (false === $list) {
                $this->error($imap);
                return $result;
            }
            // get new message number, UID and header info
            $msgno = $list[0];
            $imap->uid = imap_uid($ch, $msgno);
            $info = imap_headerinfo($ch, $msgno);
            if (false === $info) {
                $this->log_debug("Fetch info");
                $this->error($imap);
                return $result;
            }
        }

        // recover full header as a text block
        $header = imap_fetchheader($ch, $msgno);
        if (false === $header) {
            $this->log_debug("Fetch header");
            $this->error($imap);
            return $result;
        }

        // recover full body as a text block
        $body = imap_body($ch, $msgno);
        if (false === $body) {
            $this->log_debug("Fetch body");
            $this->error($imap);
            return $result;
        }

        // get structure and then find text/html parts
        $struct = imap_fetchstructure($ch, $msgno);

        $message = [
            'msgno'   => $msgno,
            'info'    => $info,
            'header'  => $header,
            'body'    => $body,
            'struct'  => $struct,
            'target'  => $imap->target,
            'type'    => $imap->target_type
        ];
        if (BC_IMAP_TARGET_GROUP == $imap->target_type) {
            $message['group'] = TableGroups::findByID($imap->target_id);
        }

        // find text and html parts
        if ($struct->parts) {
            foreach ($struct->parts as $index => $part) {
                if ("PLAIN" === $part->subtype) {
                    $message['text'] = imap_fetchbody($ch, $msgno, $index + 1);
                } elseif ("HTML" === $part->subtype) {
                    $message['html'] = imap_fetchbody($ch, $msgno, $index + 1);
                }
            }
        }

        return $message;
    }

    /**
     * Report an error, set the IMAP record to error.
     * @param \bookclub\TableIMap $imap current IMAP database record
     * @param string $error error message
     */
    private function prepare_error(TableIMap $imap, string $error): void
    {
        $this->log_error($error);
        $imap->status    = BC_IMAP_ERROR;
        $imap->processed = 1;
    }

    /**
     * Send a bounce message to the original sender.
     * @param resource $ch IMAP handle
     * @param \bookclub\TableIMap $imap database record of current email
     * @param string $error error message for the user
     */
    private function bounce_error($ch, TableIMap $imap, string $error): void
    {
        $imap->status    = BC_IMAP_BOUNCE;
        $imap->processed = 1;
        $email           = new EMail('forward');
        $message         = $this->refetch($ch, $imap);
        if (!$message) {
            return;
        }
        $email->bounce(\get_userdata($imap->wordpress_id), $message, $error);
    }

    /**
     * Search for the group with the given tag.
     * @param string $target email target, search for group tag
     * @return int identifier of the group if found, otherwise zero
     */
    private function find_group(string $target): int
    {
        $group = TableGroups::findByTag($target);
        return $group ? $group->group_id : 0;
    }

    /**
     * Search for the user with the given name or login.
     * @param string $target user first+last name, login, display or nice name
     * @return int identifier of the user if found, otherwise zero.
     */
    private function find_user(string $target): int
    {
        $users = new \WP_User_Query([
            'search' => $target,
            'search_fields' => [
                'user_login', 'user_nicename', 'display_name'
            ]]);
        if (count($users->results)) {
            return $users->results[0]->ID;
        }
        $tobj = JoinUsers::findByName($target);
        return $tobj ? $tobj->ID : 0;
    }

    /**
     * Validate sender and set receiver(s) for the given email. Bounce if any
     * problems.
     * @param resource $ch IMAP handle
     * @param \bookclub\TableIMap $imap database record of current email
     */
    private function prepare($ch, TableIMap $imap): void
    {
        // sender is not a user, ignore it
        if (!$imap->wordpress_id) {
            $imap->status    = BC_IMAP_IGNORE;
            $imap->processed = 1;
            $this->log_info('Ignore (not a user) ' . $imap->message_id);
            return;
        }

        // find this user
        $user = \get_userdata($imap->wordpress_id);
        if (!$user) {
            $this->prepare_error($imap, "Problem fetching user " . $imap->wordpress_id);
            return;
        }

        // ensure the sender has enabled access to the forwarder
        // in the future there may be an exception for admins
        if (!\get_user_meta($imap->wordpress_id, 'bc_receive_others', true)) {
            $this->bounce_error($ch, $imap,
                    "The forwarder may only be used if you activate 'Receive email from others' on your bookclub profile page.");
            return;
        }

        // ensure the sender has first/last names
        //if (!$user->first_name || !$user->last_name) {
        //    $this->bounce_error($ch, $imap,
        //            "First and/or Last name missing in profile. For someone to respond your email, they need your name.");
        //    return;
        //}

        $target = $imap->target;
        // no target specified
        if (!$target) {
            $this->bounce_error($ch, $imap, "No target specified.");
            return;
        }
        if (BC_IMAP_TARGET_NONE == $imap->target_type) {
            $this->bounce_error($ch, $imap,
                    "User (or group) \"$target\" was not found.");
            return;
        }

        if (BC_IMAP_TARGET_GROUP == $imap->target_type) {
            // send to all users of a group
            $group      = TableGroups::findByID($imap->target_id);
            $group_type = $group->type;

            // find this bookclub member
            $member = TableMembers::findByWordpressID($imap->wordpress_id);

            // check if this (non-admin) user can email to the given group
            if (!$user->has_cap('edit_bc_members')) {
                switch($group_type) {
                    case BC_GROUP_CLUB:
                        if (!$member) {
                            $this->bounce_error($ch, $imap, "Your are not in the bookclub so you can't send to a bookclub group (" . $imap->wordpress_id . ")");
                            return;
                        }
                        if (!TableGroupMembers::isMember($imap->target_id, $member->member_id)) {
                            $this->bounce_error($ch, $imap,
                                    "You are not in the group named '$target'. You can only send emails to your own groups.");
                            return;
                        }
                        break;

                    case BC_GROUP_SELECT:
                        $this->bounce_error($ch, $imap,
                                "Sorry, you can't send to this group.");
                        return;

                    case BC_GROUP_WORDPRESS:
                        if (!TableGroupUsers::isUser($imap->target_id, $imap->wordpress_id)) {
                            $this->bounce_error($ch, $imap,
                                    "You are not in the group named '$target'. You can only send emails to your own groups.");
                            return;
                        }
                        break;

                    case BC_GROUP_ANNOUNCEMENTS:
                        $this->bounce_error($ch, $imap,
                                "Sorry, this is an announcement group. You can't send to it.");
                        return;

                    default:
                        $this->log_error("Group \"$target\" unknown type.");
                        $this->bounce_error($ch, $imap,
                                "Group \"$target\" unknown type.");
                        return;
                }
            }

            if (BC_GROUP_CLUB == $group_type || BC_GROUP_SELECT == $group_type) {
                $tobj = new JoinMembersUsersGroupMembers();
                $tobj->loopMembersForGroup($imap->target_id);
                while ($tobj->fetch()) {
                    if (($tobj->isMember()) &&
                            ($tobj->wordpress_id) &&
                            (\get_user_meta($tobj->wordpress_id,
                                    'bc_receive_others', true))) {
                        TableForwards::target($imap->message_id,
                                $tobj->wordpress_id);
                    }
                }
            } else {
                $tobj = new TableGroupUsers();
                $tobj->loopForGroup($imap->target_id);
                while ($tobj->fetch()) {
                    TableForwards::target($imap->message_id, $tobj->wordpress_id);
                }
            }
            $imap->status = BC_IMAP_ACTIVE;
        } else {
            // send to a specific user
            // ensure the user wants to receive email
            if (!\get_user_meta($imap->target_id, 'bc_receive_others', true)) {
                $this->bounce_error($ch, $imap,
                    "User '$target' has not enabled direct messages.");
                return;
            }
            TableForwards::target($imap->message_id, $imap->target_id);
            $imap->status = BC_IMAP_ACTIVE;
        }
    }

    /**
     * Process the given IMap record.
     * @param resource $ch IMAP handle
     * @param \bookclub\TableIMap $imap IMap record
     */
    private function process($ch, TableIMap $imap): void
    {
        $this->log_debug("Process " . $imap->uid);
        $message = $this->refetch($ch, $imap);
        if (!$message) {
            return;
        }
        $sender = \get_userdata($imap->wordpress_id);
        $email  = new EMail('forward');
        $tobj   = new TableForwards();
        $tobj->loopMail($imap->message_id);
        while ($tobj->fetch()) {
            if (!$tobj->isEMailSent()) {
                $email->forward($sender, \get_userdata($tobj->wordpress_id),
                        $message);
                TableForwards::setSent($imap->message_id, $tobj->wordpress_id);
            }
        }
        $imap->processed = 1;
    }

    /**
     * Record an imap error.
     * @param \bookclub\TableIMap $imap optional record to set as processed
     */
    private function error(TableIMap $imap = null): void
    {
        if ($imap) {
            $this->log_info("Error processing " . $imap->uid . ", " . $imap->timestamp);
            $imap->status    = BC_IMAP_ERROR;
            $imap->processed = 1;
        }
        $this->log_error(imap_last_error());
    }

    /**
     * Check imap resource for new emails and add them to the database.
     * @param resource $ch IMAP handle
     * @param string $username email account (must be found in the To field)
     */
    public function handle_register($ch, string $username): void
    {
        $pos     = strpos($username, '@');
        $mailbox = substr($username, 0, $pos);
        $host    = substr($username, $pos + 1);
        $default = date('Y-m-d', strtotime('-7 days'));
        $last    = getOption('forward_time');
        if (!$last) {
            $last = $default;
        }
        $since = date('d M Y', strtotime($last));
        $this->log_debug('Last check: ' . $last);
        $list = imap_search($ch, "SINCE \"$since\"");
        if (false !== $list) {
            foreach ($list as $item) {
                $this->register($ch, $item, $mailbox, $host);
            }
        }
        setOption('forward_time', date('Y-m-d'));
    }

    /**
     * Loop through all new emails to validate them and set the recipients.
     * @param resource $ch IMAP handle
     */
    public function handle_prepare($ch): void
    {
        $tobj = new TableIMap();
        $tobj->loopUnfinished(BC_IMAP_NEW);
        while ($tobj->fetch()) {
            // prepare an EMail object for forwarding or sending a bounce message
            $this->prepare($ch, $tobj);
            $tobj->update();
        }
    }

    /**
     * Loop through all active emails to forward them to the recipients.
     * @param resource $ch IMAP handle
     */
    public function handle_process($ch): void
    {
        $tobj = new TableIMap();
        $tobj->loopUnfinished(BC_IMAP_ACTIVE);
        while ($tobj->fetch()) {
            $this->process($ch, $tobj);
            $tobj->update();
        }
    }

    /**
     * Hook for requests starting with /cron_elist/. If recognized, run the cron
     * job for checking email forwarding. The job uses a lock on the assumption
     * that it may be called multiple times and it runs in steps on the
     * assumption that it may be interrupted. Steps:
     * - (register) check for new emails and add them to the database.
     * - (prepare)  check if the unprocessed emails qualify for forwarding and
     *              if so, create the recipient database records, otherwise
     *              send a bounce email
     * - (process)  forward to emails to all recipients
     */
    public function handle(): void
    {
        $this->log_debug('Cron EMail list forwarding');
        $imap = getOption('forward_imap');
        if (!$imap) {
            die();
        }
        $lockkey = 'elist_forward';
        if (!create_lock($lockkey)) {
            $this->log_error("Error locking job $lockkey");
            die();
        }
        claim_lock($lockkey);
        $macros   = twig_macro_fields([]);
        $mailbox  = macro_replace($imap, $macros);
        $username = macro_replace(getOption('forward_user'), $macros);
        $password = macro_replace(getOption('forward_password'), $macros);
        $ch = imap_open($mailbox, $username, $password);
        if (false === $ch) {
            $this->error();
        } else {
            $this->handle_register($ch, $username);
            $this->handle_prepare($ch);
            $this->handle_process($ch);
            imap_close($ch);
        }
        free_lock($lockkey);
        $json   = twig_macro_fields([]);
        exit(twig_render('path_forwarder', $json));
    }
}

// currently this is only stored as a global because it is used on the
// MenuTest page. Eventually it can simply be instanciated.
$GLOBALS['forwarder'] = new ForwardEMail;
