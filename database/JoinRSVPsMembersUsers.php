<?php namespace bookclub;

/*
 * Class provides access to joined rsvps, members, users and usermeta tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined rsvps, members, users and usermeta tables.
 */
class JoinRSVPsMembersUsers extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinRSVPsMembersUsers
     */
    public function __construct()
    {
        parent::__construct('rsvps');
        parent::join('members',
            tableField('rsvps', 'member_id') . ' = ' . tableField('members', 'member_id'));
        parent::join('\users',
            tableField('members', 'wordpress_id') . ' = ' . tableField('\users', 'ID'));
        parent::join('\usermeta AS f',
            tableField('members', 'wordpress_id') . " = f.user_id AND f.meta_key = 'first_name'");
        parent::join('\usermeta AS l',
            tableField('members', 'wordpress_id') . " = l.user_id AND l.meta_key = 'last_name'");
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
     * Start looping the history of RSVP changes for the given event.
     * @param string $event_id identifier of the event
     */
    public function loopRecentOptionalEvent($event_id): void
    {
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->where(tableField('rsvps', 'event_id') . ' = %s ');
        $this->orderBy(tableField('rsvps', 'modtime'));
        $this->prepare([$event_id]);
        $this->iterate();
    }
}
