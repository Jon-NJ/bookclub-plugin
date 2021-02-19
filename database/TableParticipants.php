<?php namespace bookclub;

/*
 * Class provides access to database participants table. This tracks the
 * invitation and RSVP status of members for a particular event.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the participants table.
 *
 * @ORM\Table(name="bc_participants")
 * @ORM\Entity
 */
class TableParticipants extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var string unique event identifier
     *
     * @ORM\Column(name="event_id", type="string", length=40, nullable=false, options={"default"="'0'"})
     */
    public $event_id = '';

    /**
     * @var int unique member identifier
     *
     * @ORM\Column(name="member_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $member_id = 0;

    /**
     * @var int|null current RSVP status - 0=BC_RSVP_NORESPONSE no response yet,
     * 1=BC_RSVP_NOTATTENDING not attending, 2=BC_RSVP_ATTENDING attending,
     * 3=BC_RSVP_MAYBE maybe
     *
     * @ORM\Column(name="rsvp", type="integer", nullable=true)
     */
    public $rsvp = 0;

    /**
     * @var int zero if seat assigned, one if waiting
     *
     * @ORM\Column(name="waiting", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $waiting = 0;

    /**
     * @var \DateTime|null datetime of last status update
     *
     * @ORM\Column(name="modtime", type="datetime", nullable=true, options={"default"="current_timestamp()"})
     */
    public $modtime;

    /**
     * @var string|null additional public remark
     *
     * @ORM\Column(name="comment", type="string", length=60, nullable=true, options={"default"="NULL"})
     */
    public $comment = '';

    /**
     * @var \DateTime|null datetime when the last email invitation was sent
     *
     * @ORM\Column(name="email_sent", type="datetime", nullable=true, options={"default"="NULL"})
     */
    public $email_sent;

    /**
     * Initialize the object.
     * @return \bookclub\TableParticipants
     */
    public function __construct()
    {
        parent::__construct('participants');
    }

    /**
     * Fetch the list of column names for the table.
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
     * Remove the given member from the given event.
     * @param string $event_id unique event identifier
     * @param int $member_id id of the member for the event
     */
    public static function deleteParticipant($event_id, $member_id): void
    {
        $recipient = new TableParticipants();
        $recipient->deleteByString(['event_id' => $event_id,
                               'member_id' => $member_id]);
    }

    /**
     * Add the given member to the event.
     * @param string $event_id unique event identifier
     * @param int $member_id id of the member for the event
     */
    public static function addParticipant($event_id, $member_id): void
    {
        $recipient = new TableParticipants();
        $recipient->event_id = $event_id;
        $recipient->member_id = $member_id;
        $recipient->rsvp = BC_RSVP_NORESPONSE;
        $recipient->waiting = 0;
        $recipient->modtime = date('Y-m-d H:i:s');
        $recipient->comment = '';
        $recipient->email_sent = null;
        $recipient->insert();
    }

    /**
     * Set the given participant as sent using the current timestamp
     * @param string $event_id event identifier
     * @param int $member_id id of the member who will be a recipient
     */
    public static function setSent($event_id, $member_id): void
    {
        $recipient = new TableParticipants();
        $recipient->updateSet('email_sent = %s');
        $recipient->where('event_id = %s')
                  ->and('member_id = %s');
        $recipient->prepare([date('Y-m-d H:i:s'), $event_id, $member_id]);
        $recipient->execute();
    }

    /**
     * Find the record corresponding to a given event and member.
     * @param string $event_id identifier for the event
     * @param int $member_id identifier for the member
     * @return TableParticipants|null
     */
    public static function findByEventAndPerson($event_id, $member_id)
    {
        $participants = new TableParticipants();
        $participants->findByString(['event_id'       => $event_id,
                                     'member_id' => $member_id]);
        return $participants->fetch() ? $participants : null;
    }

    /**
     * Find the next person on the waiting list for a given event.
     * @param string $event_id event identifier
     * @return int zero if none waiting, otherwise member id
     */
    public static function getNextWaiting($event_id): int
    {
        $participants = new TableParticipants();
        $participants->select('member_id');
        $participants->where('waiting = 1')
                     ->and('event_id = %s');
        $participants->orderBy('modtime');
        $participants->prepare([$event_id]);
        $rows = $participants->execute();
        $ret = sizeof($rows) > 0 ? $rows[0]->member_id : 0;

        if ($ret != 0) {
            $participants->updateSet('waiting = 0');
            $participants->where('event_id = %s')
                         ->and('member_id = %s');
            $participants->prepare([$event_id, $ret]);
            $participants->execute();
        }
        return $ret;
    }

    /**
     * Delete all recipients for the event with the given identifier.
     * @param string $event_id event identifier
     */
    public static function deleteByEvent($event_id): void
    {
        $participants = new TableParticipants();
        $participants->deleteByString(['event_id' => $event_id]);
    }

    /**
     * Delete all records for the given participant.
     * @param int $member_id unique identifier for the member
     */
    public static function deleteByID(int $member_id): void
    {
        $participant = new TableParticipants();
        $participant->deleteByString(['member_id' => $member_id]);
    }

    /**
     * Clear the send flag for the given participant.
     * @param string $event_id unique event identifier
     * @param int $member_id id of the member for the event
     */
    public static function clearParticipant($event_id, $member_id): void
    {
        $recipient = new TableParticipants();
        $recipient->updateSet('email_sent = NULL');
        $recipient->where('event_id = %s')
                  ->and('member_id = %s');
        $recipient->prepare([$event_id, $member_id]);
        $recipient->execute();
    }

    /**
     * All participants are set to unsent for the event with the given identifier.
     * @param string $event_id event identifier
     */
    public static function clearByID($event_id): void
    {
        $recipient = new TableParticipants();
        $recipient->updateSet('email_sent = NULL');
        $recipient->where('event_id = %s');
        $recipient->prepare([$event_id]);
        $recipient->execute();
    }

    /**
     * The primary key of the given event is changed.
     * @param string $oldid original event id
     * @param string $newid new event id
     */
    public static function updateID($oldid, $newid): void
    {
        $recipient = new TableParticipants();
        $recipient->updateSet('event_id = %s');
        $recipient->where('event_id = %s');
        $recipient->prepare([$newid, $oldid]);
        $recipient->execute();
    }

    /**
     * Update the database from the object data.
     */
    public function update(): void
    {
        parent::updateByString(['member_id', 'event_id']);
    }

    /**
     * Count the number of participants who have a sent timestamp.
     * @param string $event_id unique event identifier
     * @return int how many participants have been sent the email
     */
    public static function getSentCount(string $event_id): int
    {
        $participant = new TableParticipants();
        $participant->select('count(*) AS count');
        $participant->where('event_id = %s')
                    ->and('email_sent is not null');
        $participant->prepare([$event_id]);
        $rows = $participant->execute();
        return (int) $rows[0]->count;
    }

    /**
     * Count the number of participants who don't have a sent timestamp.
     * @param string $event_id unique event identifier
     * @return int how many participants have not yet been sent the email
     */
    public static function getUnsentCount($event_id): int
    {
        $participant = new TableParticipants();
        $participant->select('count(*) AS count');
        $participant->where('event_id = %s')
                    ->and('email_sent is null');
        $participant->prepare([$event_id]);
        $rows = $participant->execute();
        return (int) $rows[0]->count;
    }

    /**
     * Count the number of participants yes/maybe and not waiting
     * @param string $event_id unique event identifier
     * @return int number of people attending, not waiting
     */
    public static function getAttendingCount($event_id): int
    {
        $participant = new TableParticipants();
        $participant->select('count(*) AS count');
        $participant->where('event_id = %s')
                    ->and('(rsvp = 2 OR rsvp = 3)')
                    ->and('0 = waiting');
        $participant->prepare([$event_id]);
        $rows = $participant->execute();
        return (int) $rows[0]->count;
    }

    /**
     * Count the number of participants on the waiting list
     * @param string $event_id unique event identifier
     * @return int number of people waiting
     */
    public static function getWaitingCount($event_id): int
    {
        $participant = new TableParticipants();
        $participant->select('count(*) AS count');
        $participant->where('event_id = %s')
                    ->and('1 = waiting');
        $participant->prepare([$event_id]);
        $rows = $participant->execute();
        return (int) $rows[0]->count;
    }
}
