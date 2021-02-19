<?php namespace bookclub;

/*
 * Class for handling e-mail.
 * @author  Jon Wolfe <jonnj@connectberlin.de>
 * @package bookclub
 * @subpackage framework
 * @license https://opensource.org/licenses/MIT MIT
 */

define('CONTENT_PLAIN', 0);
define('CONTENT_MIXED', 1);
define('CONTENT_ALT', 2);

/**
 * Class used for sending emails, invitations and notifications.
 */
class EMail
{
    /**
     * @var bool true if sending email should be simulated
     */
    private static $simulate = false;

    /**
     * @var string text describing last email send success of error
     */
    private $lasterror = '';

    /**
     * @var string option prefix - 'email_' or 'forward_'
     */
    private $prefix;

    /**
     * @var array collection of macro definitions
     */
    private $macros;

    /**
     * @var array collection of passwords
     */
    private $passwords;

    /**
     * @var object SMTP object for sending email
     */
    private $smtp;

    /**
     * @var object Logging object
     */
    private $logger;

    /**
     * @var int member identifier
     */

    private $memberid = 0;

    /**
     * @var TableMembers member database record
     */
    private $member = null;

    /**
     * @var \WP_User WordPress user information or null if not user
     */
    private $user = null;

    /**
     * @var string recipient name
     */
    private $name = '';

    /**
     * @var string recipient email address
     */
    private $address = '';

    /**
     * Initialize the object. Create an SMTP mailer.
     * @param string $prefix option prefix - 'email' or 'forward'
     * @return \bookclub\EMail
     */
    public function __construct(string $prefix = 'email')
    {
        $this->prefix    = $prefix . '_';
        $this->macros    = twig_macro_fields([]);
        $this->passwords = [
            'email_password'   => getOption('email_password'),
            'forward_password' => getOption('forward_password')
        ];
        $this->setSMTP();
        $this->logger = \Logger::getLogger("email.$prefix");
    }

    /**
     * Using the current macro definitions, apply them to the given string.
     * @param string $source original source string
     * @return string same string with the macros replaced
     */
    private function macro_replace(string $source): string
    {
        return macro_replace($source, $this->macros);
    }

    /**
     * Set a single macro value.
     * @param string $key name of the macro field
     * @param string|array $value value of the macro field
     */
    private function setMacro(string $key, $value): void
    {
        $this->macros[$key] = $value;
    }

    /**
     * Prepare the macros used for sending an email
     * @param array $donors array of values to use for macros (usually
     * TableEMail or TableEvent)
     */
    private function setMacros(array $donors = []): void
    {
        $this->macros = twig_macro_fields(
                array_merge([$this->member, $this->user], $donors));        
    }

    /**
     * Fetch an SMTP object which will be used for sending email. The server
     * configuration will be fetched from the database.
     */
    private function setSMTP(): void
    {
        // parameters to the factory function can be found here:
        // https://pear.php.net/manual/en/package.mail.mail.factory.php
        $backend    = getOption($this->prefix . 'backend', 'smtp');
        $params     = splitOption($this->prefix . 'params');
        $parameters = [];
        foreach ($params as $param) {
            $pos    = strpos($param, '=');
            $name   = substr($param, 0, $pos);
            $value  = substr($param, $pos + 1);
            if ($name === 'password') {
                $value = macro_replace($value, $this->passwords);
            } else {
                $value = $this->macro_replace($value);
            }
            if ('true' === strtolower($value)) {
                $value = true;
            } elseif ('false' === strtolower($value)) {
                $value = false;
            }
            $pos    = strpos($name, '.');
            if (false !== $pos) {
                $base = substr($name, 0, $pos);
                $name = substr($name, $pos + 1);
                $parameters['socket_options'][$base][$name] = $value;
            } else {
                $parameters[$name] = $value;
            }
        }
	$this->smtp = \Mail::factory($backend, $parameters);
    }

    /**
     * Create a content-type string for email and store it in the macros.
     * @param int $type CONTENT_PLAIN, CONTENT_MIXED or CONTENT_ALT
     */
    private function setContentType(int $type = CONTENT_PLAIN): void
    {
        switch($type) {
            case CONTENT_PLAIN:
                $content = 'text/plain';
                break;
            case CONTENT_MIXED:
                $content = 'multipart/mixed; boundary=MIME-mixed-{{hash}}';
                break;
            case CONTENT_ALT:
                $content = 'multipart/alternative; boundary=MIME-alt-{{hash}}';
                break;
        }
        $this->setMacro('content-type', $this->macro_replace($content));
    }

    /**
     * Prepare the recipient of the email.
     * @param int|\WP_User $receiver member identifier or WP user
     */
    private function setRecipient($receiver): void
    {
        if (is_int($receiver)) {
            $member  = TableMembers::findByID($receiver);
            $user    = $member->wordpress_id ?
                        \get_userdata($member->wordpress_id) : null;
            if (false === $this->user) {
                $user = null;
            }
        } else {
            $member = null;
            $user   = $receiver;
        }
        $name    = $user ? ($user->first_name . ' ' . $user->last_name) :
            $member->name;
        $address = $user ? $user->user_email : $member->email;

        $this->memberid = $receiver;
        $this->member   = $member;
        $this->user     = $user;
        $this->name     = $name;
        $this->address  = $address;
    }

    /**
     * Prepare subject and body for the email.
     * @param string $subject email subject line
     * @param string $template TWIG for rendering the body
     */
    private function setBody(string $subject, string $template): void
    {
        $this->setMacro('subject', $subject);
        $this->body = twig_render($template, $this->macros);
    }

    /**
     * Fetch headers from the database, apply the stored macros.
     * @return array list of headers for email
     */
    private function getHeaders(): array
    {
        $result = [];
        $is_list = array_key_exists('list', $this->macros);
        $headers = splitOption($this->prefix . 'headers');
        foreach ($headers as $header) {
            $pos           = strpos($header, '=');
            $name          = substr($header, 0, $pos);
            if ('@' === substr($name, 0, 1)) {
                if ($is_list) {
                    $result[substr($name,1)] =
                            $this->macro_replace(substr($header, $pos + 1));
                }
            } else {
                $result[$name] = $this->macro_replace(substr($header, $pos + 1));
            }
        }
        return $result;
    }

    private function create_Simulation(array $headers): string
    {
        $email = '';
        foreach ($headers as $key => $header) {
            $email .= "$key: $header\n";
        }
        return $email . "\n" . $this->body;
    }

    /**
     * Generic routine to send email. Several prepare functions need to be
     * called prior to it - setRecipient, setMacros, setContentType and setBody.
     * @param string $tag one of 'EMAIL','TEST','NOTIFY', 'INVITE' or 'SIGNUP'
     * @param string $log a string to use when logging the action
     * @param string|null $extra an optional string used for the database log
     * @return bool true if sending was successful, also check getLastError
     */
    private function genericSend(string $tag, string $log,
            ?string $extra = null): bool
    {
        $result  = true;
        $headers = $this->getHeaders();

        if ($this->member && $this->member->noemail) {
            $this->logger->info("No EMail ($log)");
            $message = 'EMail disabled';
            $type    = 'NONE';
        } elseif (self::$simulate) {
            $this->logger->info("Simulate email ($log)");
            write_debug_file('debug_email', BOOKCLUBLOGS . DS . 'bookclub.eml',
                    $this->create_Simulation($headers));
            $message = 'EMail send simulated';
            $type    = 'SIM';
        } else {
            $mail = $this->smtp->send($headers['To'], $headers, $this->body);
            if (\PEAR::isError($mail)) {
                $this->logger->error(
                    "Failed sending email ($log)");
                $this->logger->error($mail->getMessage());
                $message = $mail->getMessage();
                $type    = 'ERR';
                $result = false;
            } else {
                $this->logger->info("EMail sent ($log)");
                $message = 'EMail sent';
                $type    = 'OK';
            }
        }
        TableLogs::addLog([$tag, $type, $this->member ?
                $this->member->member_id : $this->user->user_login, $extra],
                $message);
        $this->setLastError($message);
        return $result;
    }

    /**
     * When an email is sent, a status or error message is stored.
     * @param string $error status/error message to set
     */
    private function setLastError(string $error): void
    {
        $this->lasterror = $error;
    }

    /** Public methods */

    /**
     * When an email is sent, a status or error message is stored.
     * @return string last error or message for simulation or send ok
     */
    public function getLastError(): string
    {
        return $this->lasterror;
    }

    /**
     * Send a test email to the given participant.
     * @param int $member_id recipient of the email notification
     * @return bool true if send is successful
     */
    public function sendTest(int $member_id): bool
    {
        $this->setRecipient($member_id);
        $this->setMacros();
        $this->setMacro('utf8from', getAdjustedName($this->macros['who']));
        $this->setContentType();
        $this->setBody('Test email', 'email_test');
        return $this->genericSend('TEST', $this->name . ', ' . $this->address);
    }

    /**
     * Send a signup email to the given participant.
     * @param int $member_id recipient of the email signup link
     * @return bool true if send is successful
     */
    public function sendSignUp(int $member_id): bool
    {
        $this->setRecipient($member_id);
        $this->setMacros();
        $this->setContentType();
        $this->setBody('Invitation to join the {{who}}', 'email_signup');
        return $this->genericSend('SIGNUP', $this->name . ', ' . $this->address);
    }

    /**
     * Send an email to the given participant.
     * @param int $member_id recipient of the email
     * @param \bookclub\TableEMails $email email object being sent
     * @return bool true if send is successful
     */
    public function sendEMail(int $member_id, TableEMails $email): bool
    {
        $this->setRecipient($member_id);
        $this->setMacros([$email]);
        $this->setContentType($this->member->format ? CONTENT_ALT : CONTENT_PLAIN);
        $this->setBody($email->subject, 'email_email');
        return $this->genericSend('EMAIL', $email->create_dt . ', ' .
                $this->name . ', ' . $this->address, $email->create_dt);
    }

    /**
     * Send an email to the given participant that they are no longer on the
     * waiting list for the given event.
     * @param int $member_id recipient of the email notification
     * @param \bookclub\TableEvents $event event the recipient is being notified
     * about
     * @return bool true if send is successful
     */
    public function sendNotification(int $member_id,
            TableEvents $event): bool
    {
        $this->setRecipient($member_id);
        $this->setMacros([$event]);
        $this->setContentType();
        $this->setBody('{{who}} RSVP status change', 'email_notify');
        return $this->genericSend('NOTIFY', $event->event_id . ', ' .
                $this->name . ', ' . $this->address, $event->event_id);
    }

    /**
     * Send an invitation to the given user inviting them to the given event.
     * @param int $member_id unique identifier for the member
     * @param string $eventid unique identifier for the event
     * @return bool true if send is successful
     */
    public function sendInvitation(int $member_id, string $eventid): bool
    {
        $event = TableEvents::findByID($eventid);
        $this->setRecipient($member_id);
        $this->setMacros([$event]);
        $this->setContentType(
                $this->member->ical ? CONTENT_MIXED :
                    ($this->member->format ? CONTENT_ALT : CONTENT_PLAIN));
        $this->setBody('Invitation: ' . $event->summary, 'email_invite');
        return $this->genericSend('INVITE', $event->event_id . ', ' .
                $this->name . ', ' . $this->address, $event->event_id);
    }

    /**
     * Send a bounce email to the original sender.
     * @param array $message array containing original email message parts with
     * the 'info', 'body', 'header' and other fields
     * @param string $error error message to send the to original sender
     * @return bool true if send is successful
     */
    public function bounce(\WP_User $sender, array $message, string $error): bool
    {
        $this->setRecipient($sender);
        $this->setMacros();
        $this->setContentType(CONTENT_MIXED);
        $subject = $message['info']->subject;
        $this->setMacro('message',  $subject);
        $this->setMacro('filename', \sanitize_file_name($subject) . '.eml');
        $this->setMacro('error',    $error);
        $this->setMacro('header',   $message['header']);
        $this->setMacro('body',     $message['body']);
        $this->setMacro('utf8from', getAdjustedName($this->macros['who']));
        //var_dump($message['info']);
        //var_dump($this->macros);
        //$this->logger->debug(print_r($this->macros, true));
        $this->setBody('List server bounce notification', 'email_bounce');
        return $this->genericSend('BOUNCE', 'Bounce ' . $this->name . ', ' .
                $this->address, $subject);
    }

    /**
     * Forward the current message to the specified WordPress user.
     * @param array $message array containing original email message parts with
     * the 'info', 'body', 'header' and other fields
     * @param \WP_User $receiver the recipient of the email
     * @return bool true if send is successful
     */
    public function forward(\WP_User $sender, \WP_User $receiver,
            array $message): bool
    {
        $this->setRecipient($receiver);
        $this->setMacros();
        $this->setContentType(array_key_exists('html', $message) ?
                CONTENT_MIXED : CONTENT_PLAIN);
        $this->setMacro('body', $message['body']);
        $this->setMacro('text', $message['text'] ?: $message['body']);
        if ($message['html']) {
            $this->setMacro('html', $message['html']);
        }
        $this->setMacro('target', $message['target']);
        $this->setMacro('type',   $message['type']);
        $this->setMacro('sender', twig_macro_object($sender));
        if ($message['group']) {
            $group       = $message['group'];
            $this->setMacro('utf8from', getAdjustedName($group->tag));
            $this->setMacro('utf8sender', getAdjustedName(
                    $sender->first_name . ' ' . $sender->last_name));
            $group_email = $this->macro_replace('"' . $group->tag . '" <{{forwarder}}>');
            $this->setMacro('utf8login', getAdjustedName($sender->user_nicename));
            $this->setMacro('group', twig_macro_object($group));
            $this->setMacro('list', true);
        } else {
            $this->setMacro('utf8from', getAdjustedName($sender->user_nicename));
            $this->setMacro('utf8login', getAdjustedName($sender->user_nicename));
        }
        //var_dump($this->macros);
        //$this->logger->debug(print_r($this->macros, true));
        $this->setBody($message['info']->subject, 'email_forward');
        return $this->genericSend('FORWARD', 'Forward ' . $this->name . ', ' .
                $this->address, $message['info']->subject);
    }
}
