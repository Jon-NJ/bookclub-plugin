<?php namespace bookclub;

/*
 * Class provides access to joined members, users, usermeta and participants tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined members, users, usermeta and participants tables.
 */
class JoinMembersUsersParticipants extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinMembersUsersParticipants
     */
    public function __construct()
    {
        parent::__construct('members');
        parent::join('\users',
            tableField('members', 'wordpress_id') . ' = ' . tableField('\users', 'ID'));
        parent::join('\usermeta AS f',
            tableField('members', 'wordpress_id') . " = f.user_id AND f.meta_key = 'first_name'");
        parent::join('\usermeta AS l',
            tableField('members', 'wordpress_id') . " = l.user_id AND l.meta_key = 'last_name'");
        parent::join('participants',
            tableField('members', 'member_id') . ' = ' . tableField('participants', 'member_id') . ' AND ' .
            tableField('participants', 'event_id') . ' = %s');
    }

    /**
     * Fetch the list of column names combined from the joined tables.
     * @return array of column name strings
     */
    protected function getColumns(): array
    {
        if (is_null(self::$_columns)) {
            self::$_columns = parent::addColumns();
        }
        return self::$_columns;
    }

    /**
     * Check the email status of the current member.
     * @return bool true if email was sent
     */
    public function isEMailSent(): bool
    {
            return (bool) $this->email_sent;
    }

    /**
     * Check the participant status of the current member.
     * @return bool true if this person is included in the current event
     */
    public function isParticipant(): bool
    {
            return !is_null($this->event_id);
    }

    /**
     * Start looping through participants that received the invitation.
     * @param string $event_id event identifier
     */
    public function loopSent($event_id): void
    {
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->where(tableField('participants', 'email_sent') . ' is not null');
        $this->orderBy(tableField('participants', 'email_sent'));
        $this->prepare([$event_id]);
        $this->iterate();
    }

    /**
     * Start looping through recipients that haven't received the invitation.
     * @param string $event_id event identifier
     */
    public function loopUnsent($event_id): void
    {
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->where(tableField('participants', 'event_id') . ' is not null')
             ->and(tableField('participants', 'email_sent') . ' is null');
        $this->orderBy(tableField('participants', 'email_sent'));
        $this->prepare([$event_id]);
        $this->iterate();
    }

    /**
     * Loop through members invited to given event with the given status.
     * @param string $event_id unique event identifier
     * @param int $rsvp limit to given RSVP status 0 no response, 1 not
     * attending, 2 attending, 3 maybe
     * @param bool $waiting limit to given waiting status, 1 if waiting
     * @param string $age_dt limit to users active since give datetime
     */
    function loopEventForRSVPStatus($event_id, $rsvp, $waiting, $age_dt): void
    {
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->where(tableField('participants', 'rsvp') . ' = %s')
             ->and(tableField('participants', 'waiting') . ' = %s');
        if (BC_RSVP_NORESPONSE == $rsvp) {
            $this->and(tableField('members', 'hittime') . ' >= %s');
        }
        $this->orderBy('fullname');
        $this->prepare([$event_id, $rsvp, $waiting,
            date('Y-m-d H:i:s', $age_dt)]);
        $this->iterate();
    }

    /**
     * Start looping through participants to get their status for a given event.
     * @param string $event_id unique event identifier
     */
    public function loopEvent($event_id): void
    {
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->where(tableField('participants', 'rsvp') . ' is not null');
        $this->orderBy('fullname');
        $this->prepare([$event_id]);
        $this->iterate();
    }

    /**
     * Start looping through all participants to get their status for a given event.
     * @param string $event_id unique event identifier
     */
    public function loopParticipantForEvent($event_id): void
    {
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->orderBy('fullname');
        $this->prepare([$event_id]);
        $this->iterate();
    }

    /**
     * Start looping through participants on the waiting list.
     * @param string $event_id unique event identifier
     */
    public function loopWaitingList($event_id): void
    {
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->where(tableField('participants', 'waiting') . ' = 1');
        $this->orderBy(tableField('participants', 'modtime'));
        $this->prepare([$event_id]);
        $this->iterate();
    }
}
