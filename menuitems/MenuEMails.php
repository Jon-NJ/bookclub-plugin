<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_email' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuEMails extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuEMails
     */
    public function __construct()
    {
        parent::__construct('bc_menu_email',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'EMail to Bookclub Members',
                'menu_name'   => 'EMail',
                'menu_rank'   => RANK_EMAILS,
                'capability'  => 'edit_bc_emails',
                'slug'        => 'bc_email',
                'script'      => 'menu_emails.js',
                'style'       => 'menu_emails.css',
                'help'        => 'menu_emails',
                'nonce'       => 'emails_nonce',
                'actions'     => [[
                        'key' => 'admin_post_bc_emails_body',
                        'function' => 'emails_body'
                    ],[
                        'key' => 'wp_ajax_bc_emails_add',
                        'function' => 'emails_add'
                    ],[
                        'key' => 'wp_ajax_bc_emails_save',
                        'function' => 'emails_save'
                    ],[
                        'key' => 'wp_ajax_bc_emails_delete',
                        'function' => 'emails_delete'
                    ],[
                        'key'      => 'wp_ajax_bc_emails_lookup_author',
                        'function' => 'emails_lookup_author'
                    ],[
                        'key'      => 'wp_ajax_bc_emails_status',
                        'function' => 'get_status'
                    ],[
                        'key'      => 'wp_ajax_bc_emails_select',
                        'function' => 'emails_select'
                    ],[
                        'key'      => 'wp_ajax_bc_emails_send_job',
                        'function' => 'send_job'
                    ],[
                        'key'      => 'wp_ajax_bc_emails_clear',
                        'function' => 'clear_all_recipients'
                    ],[
                        'key'      => 'wp_ajax_bc_events_clear_recipients',
                        'function' => 'clear_recipients'
                    ],[
                        'key'      => 'bc_emails_start_send',
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
        return twig_render('menu_emails', $json);
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
            'referer'   => url_menu('bc_email'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'start',
            'authors'   => getAdministrators(),
            'age'       => 12,
            'author'    => '',
            'subject'   => '',
            'body'      => ''
            ];
        return $json;
    }

    /**
     * Fetch JSON used for the edit state.
     * @global string $_GET['created'] unique timestamp for the email
     * @return array JSON for TWIG rendering
     */
    private function executeEdit(): array
    {
        $created = input_request('created');
        $no      = [];
        $yes     = [];
        $join = new JoinMembersUsersRecipients();
        $join->loopRecipientForEmail($created);
        while ($join->fetch()) {
            if ($join->isRecipient()) {
                $yes[] = $join->member_id;
            } else {
                $no[]  = $join->member_id;
            }
        }
        $join    = JoinEMailsMembersUsers::findByCreateDate($created);
        $user = \get_userdata($join->wordpress_id);
        $first = $user->first_name ?: '(First name missing)';
        $last  = $user->last_name ?: '(Last name missing)';
        $nonce   = $this->create_nonce();
        $json    = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_email'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'edit',
            'authors'   => getAdministrators(),
            'groups'    => array_merge(getGroups(BC_GROUP_CLUB),
                            getGroups(BC_GROUP_SELECT)),
            'created'   => $created,
            'author'    => "$first $last",
            'authorid'  => $join->member_id,
            'subject'   => $join->subject,
            'body'      => $join->html,
            'no'        => $no,
            'yes'       => $yes
            ];
        return $json;
    }

    /**
     * Search database with the provided parameters.
     * @param string|null $age optional maximum age in months
     * @param string|null $author optional partial email creator
     * @param string|null $subject optional partial email subject
     * @param string|null $body optional partial email body
     * @return array JSON search results
     */
    private function search(?string $age, ?string $author, ?string $subject,
            ?string $body): array
    {
        $results = [];
        $iterator = new JoinEMailsMembersUsers();
        $iterator->loopSearch($age, $author, $subject, $body);
        $line = 0;
        while ($iterator->fetch()) {
            $results[] = [
                'line'    => $line++,
                'created' => $iterator->create_dt,
                'author'  => $iterator->fullname,
                'subject' => $iterator->subject,
                'body'    => $iterator->html
            ];
        }
        return $results;
    }

    /**
     * Fetch JSON used for the search state.
     * @global string|null $_GET['age'] optional maximum age in months
     * @global string|null $_GET['author'] optional partial email creator
     * @global string|null $_GET['subject'] optional partial email subject
     * @global string|null $_GET['body'] optional partial email body
     * @return array JSON for TWIG rendering
     */
    private function executeSearch(): array
    {
        $age     = input_request('age');
        $author  = input_request('author');
        $subject = input_request('subject');
        $body    = input_request('body');
        $nonce   = $this->create_nonce();
        $json = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_email'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'search',
            'authors'   => getAdministrators(),
            'age'       => $age,
            'author'    => $author,
            'subject'   => $subject,
            'body'      => $body
            ];
        $json['found'] = $this->search($age, $author, $subject, $body);
        return $json;
    }

    /** AJAX functions */

    /**
     * Find the author of the email, fetch member identifier. Generate a JSON
     * response.
     * @global string $_REQUEST['author'] email author name
     */
    public function emails_lookup_author(): void
    {
        $response = $this->check_request('Lookup email author');
        if (!$response) {
            $name   = input_request('author');
            $member = JoinMembersUsers::findByName($name);
            if ($member) {
                $response = $this->get_response(false, '');
                $response['authorid'] = $member->member_id;
            } else {
                $response = $this->get_response(true,
                        "EMail author not found $name");
            }
        }
        exit(json_encode($response));
    }

    /**
     * Add an email to the database. Generate a JSON response.
     * @global string $_REQUEST['author'] name of the member writing the email
     * @global string $_REQUEST['subject'] email subject
     * @global string $_REQUEST['body'] email body in HTML
     */
    public function emails_add(): void
    {
        $response = $this->check_request('Add email');
        if (!$response) {
            $author  = input_request('author');
            $subject = input_request('subject');
            $body    = input_request('body');
            $member  = null;
            if ($author) {
                $member  = JoinMembersUsers::findByName($author);
                if (is_null($member)) {
                    $this->log_error("EMail author not found $author");
                    $response = $this->get_response(true, 'Author not found');
                }
            } else {
                $wpid = \get_current_user_id();
                $member = TableMembers::findByWordpressID($wpid);
                if (is_null($member)) {
                    $this->log_error("EMail author not found $wpid");
                    $response = $this->get_response(true,
                            'You are not a bookclub member');
                }
            }
            if ($member) {
                $created = date('Y-m-d H:i:s');
                $email = new TableEMails();
                $email->create_dt      = $created;
                $email->member_id      = $member->member_id;
                $email->subject        = $subject;
                $email->html           = $body;
                $email->insert();
                $this->log_info("EMail added $created");
                $response = $this->get_response(false, 'Email added');
                $response['created']  = $created;
            }
        }
        exit(json_encode($response));
    }

    /**
     * Update email information. Generate a JSON response.
     * @global string $_REQUEST['created'] email creation timestamp
     * @global string $_REQUEST['author'] email author
     * @global string $_REQUEST['subject'] email subject
     * @global string $_REQUEST['body'] email body
     * @global string $_REQUEST['yes'] comma separated list of recipients
     * @global string $_REQUEST['no'] comma separated list of non-recipients
     */
    public function emails_save(): void
    {
        $response = $this->check_request('Save email');
        if (!$response) {
            $created        = input_request('created');
            $author         = input_request('author');
            $subject        = input_request('subject');
            $body           = input_request('body');
            //$yesdata        = preg_split('/,/', input_request('yes'));
            $nodata         = preg_split('/,/', input_request('no'));
            $email          = TableEMails::findByCreateDate($created);
            $email->subject = $subject;
            $email->html    = $body;
            if ($author) {
                $member  = JoinMembersUsers::findByName($author);
                if ($member) {
                    $email->member_id = $member->member_id;
                }
            }
            $email->update();
            $join = new JoinMembersUsersRecipients();
            $join->loopRecipientForEmail($created);
            while ($join->fetch()) {
                if (in_array($join->member_id, $nodata)) {
                    if ($join->isRecipient()) {
                        TableRecipients::deleteRecipient($created,
                                $join->member_id);
                    }
                } else {    //in_array($join->member_id, $yesdata)
                    if (!$join->isRecipient()) {
                        TableRecipients::addRecipient($created,
                                $join->member_id);
                    }
                }
            }
            $this->log_info("EMail updated $created");
            $response = $this->get_response(false, 'EMail updated');
        }
        exit(json_encode($response));
    }

    /**
     * Delete an email from the database. Generate a JSON response.
     * @global string $_REQUEST['created'] email creation timestamp
     */
    public function emails_delete(): void
    {
        $response = $this->check_request('Delete email');
        if (!$response) {
            $created = input_request('created');
            TableEMails::deleteByTimestamp($created);
            TableRecipients::deleteByTimestamp($created);
            $this->log_info("Delete email $created");
            $response = $this->get_response(false, 'EMail deleted');
        }
        exit(json_encode($response));
    }

    /**
     * Render the raw email body using twig to HTML.
     * @global string $_REQUEST['body'] raw HTML for the email
     */
    public function emails_body(): void
    {
        $response = $this->check_request('Fetch HTML');
        if (!$response) {
            $html     = input_request('body');
            $member   = TableMembers::findByWordpressID(\get_current_user_id());
            $user     = \get_userdata(\get_current_user_id());
            $json     = twig_macro_fields([$member, $user]);
            $template = twig_template($html);
            echo twig_render($template, $json);
        }
        die();
    }

    /**
     * Render the raw HTML in text form.
     * @param string $created email creation timestamp
     * @param string $body raw HTML for the email
     * @return string email rendered in text form
     */
    private function get_text(string $created, string $body): string
    {
        $wpid   = \get_current_user_id();
        $member = TableMembers::findByWordpressID($wpid);
        $user   = \get_userdata($wpid);
        $json   = twig_macro_fields([$member, $user]);
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
     * @param string $created email creation timestamp
     * @param int $sent count of emails sent
     * @param int $unsent count of emails not yet sent
     * @return string rendered HTML
     */
    private function get_log(string $created, int $sent, int $unsent): string
    {
        $lockkey    = $this->get_lock_key($created);
        $recipients = [];
        $join       = new JoinMembersUsersRecipients();
        $join->loopSent($created);
        while ($join->fetch()) {
            $recipients[] = [
                'name'  => $join->fullname,
                'email' => $join->user_email ?: $join->email,
                'sent'  => $join->email_sent
            ];
        }
        $json = [
            'recipients' => $recipients,
            'sent'       => $sent,
            'unsent'     => $unsent,
            'done'       => !is_lock($lockkey)
            ];
        return twig_render('email_log', $json);
    }

    /**
     * Loop through participants, set included and sent status in JSON object.
     * @param array $response JSON response object
     * @param string $created date email created
     * @param array $yesdata collection of currently included participants
     * @param array $nodata collection of participants currently not included
     */
    private function recipient_data(array &$response, string $created,
            array $yesdata, array $nodata): void
    {
        $no         = [];
        $yes        = [];
        $join = new JoinMembersUsersRecipients();
        $join->loopRecipientForEmail($created);
        while ($join->fetch()) {
            $item = [
                'id'     => $join->member_id,
                'name'   => $join->fullname,
                'active' => $join->active
            ];
            if ($join->isRecipient()) {
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
        $response['no']  = twig_render('select_recipients',
                 ['set' => 'no', 'members' => $no]);
        $response['yes'] = twig_render('select_recipients',
                 ['set' => 'yes', 'members' => $yes]);
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
        $iterator->loopSearch(null, null, null, null, null, $group, $exclude,
                $active, null, null);
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
    public function emails_select(): void
    {
        $response = $this->check_request('Get selections');
        if (!$response) {
            //$created  = input_request('created');
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
     * Generate a JSON response containing general status information and data
     * specific to the current view.
     * @global string $_REQUEST['created'] datetime the email was created
     * @global string $_REQUEST['view'] active view (text,recipients,log,etc.)
     * @global string $_REQUEST['body'] raw HTML email body
     * @global string $_REQUEST['yes'] comma separated list of recipient ids
     * @global string $_REQUEST['no'] comma separated list of non-recipients
     */
    public function get_status(): void
    {
        $response = $this->check_request('Get status');
        if (!$response) {
            $created  = input_request('created');
            $view     = input_request('view');
            $unsent   = TableRecipients::getUnsentCount($created);
            $sent     = TableRecipients::getSentCount($created);
            $lockkey  = $this->get_lock_key($created);
            $running  = is_lock($lockkey);
            $response = $this->get_response(false,
                    "Get status ($running, $sent, $unsent)");
            if ('text' == $view) {
                $response['text'] = $this->get_text(
                        $created, input_request('body'));
            } else if ('recipients' == $view) {
                $yesdata    = preg_split('/,/', input_request('yes'));
                $nodata     = preg_split('/,/', input_request('no'));
                $this->recipient_data($response, $created, $yesdata, $nodata);
            } else if ('log' == $view) {
                $response['log']    = $this->get_log($created, $sent, $unsent);
            }
            $response['running'] = $running;
            $response['sent']    = $sent;
            $response['unsent']  = $unsent;
        }
        exit(json_encode($response));
    }

    /**
     * Generate a string to use for locking an email send job.
     * @param string $created timestamp of email creation
     * @return string a string used for locking the send job
     */
    public function get_lock_key(string $created): string
    {
        return 'send_' . preg_replace('~[ :-]~', '', $created);
    }

    /**
     * Create a lock, start an email send job, generate a JSON response.
     * @global string $_REQUEST['created'] datetime the email was created
     * @global string $_REQUEST['list'] 'all' or comma separated ids of
     * recipients
     */
    public function send_job(): void
    {
        $response = $this->check_request('Start sending emails');
        if (!$response) {
            $created  = input_request('created');
            $list     = input_request('list');
            $lockkey = $this->get_lock_key($created);
            if (!create_lock($lockkey)) {
                $response = $this->get_response(true,
                        'Cannot start job - it was already started');
                $this->log_error("Error locking job $created");
            } else {
//                $this->start_send($created, $list);
//                $result = true;
                $result = \wp_schedule_single_event(time(),
                        'bc_emails_start_send', [$created, $list]);
                if ($result) {
                    $response = $this->get_response(false,
                            'Scheduled job to send emails');
                    $this->log_info("Job scheduled $created");
                } else {
                    free_lock($lockkey);
                    $response = $this->get_response(true,
                            'Error starting job - possible duplicate');
                    $this->log_error("Error scheduling job $created");
                }
            }
        }
        exit(json_encode($response));
    }

    /**
     * EMail send job, sends emails to designated recipients, clears lock.
     * @param string $created datetime the email was created
     * @param string $list 'all' or comma separated ids of recipients
     */
    public function start_send(string $created, string $list): void
    {
        $this->log_info("Start sending EMails $created");
        $lockkey = $this->get_lock_key($created);
        claim_lock($lockkey);
        $sender = new EMail();
        $sleep  = getOption('email_sleep');
        $this->log_debug("Sleep $sleep");
        $email   = TableEMails::findByCreateDate($created);
        if ('all' === $list) {
            $join    = new JoinMembersUsersRecipients();
            $join->loopUnsent($created);
            while ($join->fetch()) {
                $this->log_debug("Sending to " . $join->member_id);
                if ($sender->sendEMail($join->member_id, $email)) {
                    TableRecipients::setSent($created, $join->member_id);
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
            foreach (preg_split('/,/', $list) as $id) {
                $this->log_debug("Sending to $id");
                if ($sender->sendEMail($id, $email)) {
                    TableRecipients::setSent($created, $id);
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
     * Clear the sent flag for all recipients of an email. Generate a JSON
     * response.
     * @global string $_REQUEST['created'] datetime the email was created
     */
    public function clear_all_recipients(): void
    {
        $response = $this->check_request('Clear all recipients');
        if (!$response) {
            $created  = input_request('created');
            TableRecipients::clearByTimestamp($created);
            $response = $this->get_response(false, "Recipients cleared");
        }
        exit(json_encode($response));
    }

    /**
     * Clear the sent flag for some recipients of an email. Generate a JSON
     * response.
     * @global string $_REQUEST['created'] datetime the email was created
     * @global string $_REQUEST['list'] comma separated ids of recipients
     */
    public function clear_recipients(): void
    {
        $response = $this->check_request('Clear recipients');
        if (!$response) {
            $created  = input_request('created');
            $list = preg_split('/,/', input_request('list'));
            foreach ($list as $recipient) {
                TableRecipients::clearRecipient($created, $recipient);
                $this->log_debug("Clear $recipient");
            }
            $response = $this->get_response(false, "Recipients cleared");
        }
        exit(json_encode($response));
    }
}

new MenuEMails();
