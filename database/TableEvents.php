<?php namespace bookclub;

/*
 * Class provides access to database events table. This table is a more
 * complicated variant of the dates table and is used for the actual event
 * with attached participants and RSVP information.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the events table.
 *
 * @ORM\Table(name="bc_events")
 * @ORM\Entity
 */
class TableEvents extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var string unique identifier for the event
     *
     * @ORM\Column(name="event_id", type="string", length=40, nullable=false, options={"default"="''"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $event_id = '';

    /**
     * @var int member id of the event creator
     *
     * @ORM\Column(name="organiser", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $organiser = 0;

    /**
     * @var \DateTime timestamp when the event begins
     *
     * @ORM\Column(name="starttime", type="datetime", nullable=false, options={"default"="'0000-00-00 00:00:00'"})
     */
    public $starttime;

    /**
     * @var \DateTime timestamp when the event is expected to finish
     *
     * @ORM\Column(name="endtime", type="datetime", nullable=false, options={"default"="'0000-00-00 00:00:00'"})
     */
    public $endtime;

    /**
     * @var \DateTime timestamp when the event was last edited
     *
     * @ORM\Column(name="modtime", type="datetime", nullable=false, options={"default"="'0000-00-00 00:00:00'"})
     */
    public $modtime;

    /**
     * @var string event title
     *
     * @ORM\Column(name="summary", type="string", length=80, nullable=false, options={"default"="''"})
     * @var string event title
     */
    public $summary = '';

    /**
     * @var string address where the meeting is taking place
     *
     * @ORM\Column(name="location", type="string", length=60, nullable=false, options={"default"="''"})
     */
    public $location = '';

    /**
     * @var string|null URL for google or other map pinpointing the location
     *
     * @ORM\Column(name="map", type="string", length=80, nullable=true, options={"default"="NULL"})
     */
    public $map = '';

    /**
     * @var string HTML description of the event
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=false)
     */
    public $description = '';

    /**
     * @var int one to make event private, zero otherwise
     *
     * @ORM\Column(name="private", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $private = 0;

    /**
     * @var int if not zero, the number of hours prior to the meeting when it becomes public
     *
     * @ORM\Column(name="priority", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $priority = 0;

    /**
     * @var int maximum number that can attend or zero if no maximum
     *
     * @ORM\Column(name="max_attend", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $max_attend = 0;

    /**
     * @var int count of people who already have a reserved seat
     *
     * @ORM\Column(name="rsvp_attend", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $rsvp_attend = 0;

    /**
     * Initialize the object.
     * @return \bookclub\TableEvents
     */
    public function __construct()
    {
        parent::__construct('events');
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
     * Create an INSERT statement for all columns using the current values.
     */
    public function insert(): void
    {
        parent::insert();
    }

    /**
     * Find the event for the given identifier.
     * @param string|null $event_id unique identifier for the event
     * @return TableEvents|null record for the event or null if not found
     */
    public static function findByID(?string $event_id): ?TableEvents
    {
        $event = new TableEvents();
        $event->findByString(['event_id' => $event_id]);
        return $event->fetch() ? $event : null;
    }

    /**
     * Delete the record for the email with the given timestamp.
     * @param string $event_id unique identifier for the event
     */
    public static function deleteByID($event_id): void
    {
        $event = new TableEvents();
        $event->deleteByString(['event_id' => $event_id]);
    }

    /**
     * The primary key of the given event is changed.
     * @param string $oldid original event id
     * @param string $newid new event id
     */
    public static function updateID($oldid, $newid): void
    {
        $recipient = new TableEvents();
        $recipient->updateSet('event_id = %s');
        $recipient->where('event_id = %s');
        $recipient->prepare([$newid, $oldid]);
        $recipient->execute();
    }

    /**
     * Change the number of people attending the event.
     * @param int $change relative amount to change by
     */
    public function rsvp($change): void
    {
        $this->rsvp_attend += $change;
        $this->update();
    }

    /**
     * Update the database from the object data.
     */
    public function update(): void
    {
        parent::updateByString(['event_id']);
    }

    /**
     * Start looping through table for events matching the parameters.
     * @param string|null $eventid partial event key or null
     * @param string|null $datetime date of the event
     * @param int|null    $age maximum age of the event in months
     * @param string|null $what partial event summary
     * @param string|null $where partial location
     * @param string|null $map partial map link
     * @param string|null $body partial event description
     */
    public function loopSearch($eventid, $datetime, $age, $what, $where, $map, $body): void
    {
        $fields = [];
        $values = [];
        $this->addSearch($fields, $values, 'event_id like %s', $eventid);
        if ($datetime) {
            $this->addSearch($fields, $values, 'DATE(starttime) = %s',
                    date('Y-m-d', strtotime($datetime)), false);
        }
        if ($age) {
            $this->addSearch($fields, $values, 'starttime > %s',
                    date('Y-m-d', strtotime("-$age month")), false);
        }
        $this->addSearch($fields, $values, 'summary like %s', $what);
        $this->addSearch($fields, $values, 'location like %s', $where);
        $this->addSearch($fields, $values, 'map like %s', $map);
        $this->addSearch($fields, $values, 'description like %s', $body);
        $this->select();
        $this->where($fields);
        $this->orderBy('starttime DESC');
        $this->prepare($values);
        $this->iterate();
    }
}
