<?php namespace bookclub;

/*
 * Class provides access to database options table. This table tracks RSVP
 * status changes of participants to an event.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the RSVPs table.
 *
 * @ORM\Table(name="bc_rsvps", indexes={@ORM\Index(name="event_id", columns={"event_id", "member_id"})})
 * @ORM\Entity
 */
class TableRSVPs  extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var string unique event identifier
     *
     * @ORM\Column(name="event_id", type="string", length=40, nullable=false, options={"default"="''"})
     */
    public $event_id = '';

    /**
     * @var int unique member identifier
     *
     * @ORM\Column(name="member_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $member_id = 0;

    /**
     * @var int RSVP status at modtime - 0=BC_RSVP_NORESPONSE no response yet,
     * 1=BC_RSVP_NOTATTENDING not attending, 2=BC_RSVP_ATTENDING attending,
     * 3=BC_RSVP_MAYBE maybe
     *
     * @ORM\Column(name="rsvp", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $rsvp = 0;

    /**
     * @var \DateTime|null timestamp when some status change was made
     *
     * @ORM\Column(name="modtime", type="datetime", nullable=true, options={"default"="current_timestamp()"})
     */
    public $modtime;

    /**
     * Initialize the object.
     * @return \bookclub\TableRSVPs
     */
    public function __construct()
    {
        parent::__construct('rsvps');
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
     * The primary key of the given event is changed.
     * @param string $oldid original event id
     * @param string $newid new event id
     */
    public static function updateID($oldid, $newid): void
    {
        $recipient = new TableRSVPs();
        $recipient->updateSet('event_id = %s');
        $recipient->where('event_id = %s');
        $recipient->prepare([$newid, $oldid]);
        $recipient->execute();
    }

    /**
     * Delete all RSVPs for the event with the given identifier.
     * @param string $event_id event identifier
     */
    public static function deleteByEvent($event_id): void
    {
        $participants = new TableRSVPs();
        $participants->deleteByString(['event_id' => $event_id]);
    }

    /**
     * Add an RSVP status change record for the given event and member.
     * @param string $event_id unique event id
     * @param int $member_id unique member id
     * @param int $status RSVP status - 0 no response, 1 not attending,
     * 2 attending, 3 maybe
     */
    public static function add($event_id, $member_id, $status): void
    {
        $rsvp = new TableRSVPs();
        $rsvp->event_id = $event_id;
        $rsvp->member_id = $member_id;
        $rsvp->rsvp = $status;
        $rsvp->modtime = date('Y-m-d H:i:s');
        $rsvp->insert();
    }
}
