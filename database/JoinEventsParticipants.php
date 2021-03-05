<?php namespace bookclub;

/*
 * Class provides access to joined events and participants tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined event and participants tables.
 */
class JoinEventsParticipants extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinEventsParticipants
     */
    public function __construct()
    {
        parent::__construct('events');
        parent::join('participants',
            tableField('events', 'event_id') . ' = ' . tableField('participants', 'event_id') . ' AND ' .
            tableField('participants', 'member_id') . ' = %s');
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
     * Start looping through future events. If the member is not included,
     * the member id will be empty.
     * @param int $member_id unique member id
     */
    public function loopFutureForMember($member_id): void
    {
        $this->select();
        $this->where(tableField('events', 'endtime') . ' >= %s');
        $this->orderBy(tableField('events', 'starttime'));
        $this->prepare([$member_id, date('Y-m-d H:i')]);
        $this->iterate();
    }
}
