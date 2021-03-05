<?php namespace bookclub;

/*
 * Class provides access to joined database dates and groups tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined dates and groups tables.
 */
class JoinDatesGroups extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinDatesGroups
     */
    public function __construct()
    {
        parent::__construct('dates');
        parent::join('groups',
            tableField('dates', 'group_id') . ' = ' . tableField('groups', 'group_id'));
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
     * Start looping through records where given book was read.
     * @param int $book_id book identifier
     */
    public function loopDatesForBook(int $book_id): void
    {
        $this->year = null;
        $this->month = null;
        $this->select(
                'DISTINCT YEAR(' . tableField('dates', 'day') . ') AS year, ' .
                'MONTH(' . tableField('dates', 'day') . ') AS month, ' .
                tableField('dates', 'day') . ', ' .
                tableField('dates', 'group_id') . ', ' .
                tableField('groups', 'tag') . ', ' .
                tableField('groups', 'url'));
        $this->where(tableField('dates', 'book_id') . ' = %s');
        $this->orderBy('month DESC');
        $this->prepare([$book_id]);
        $this->iterate();
    }

    /**
     * Start looping through group ids for given year.
     * @param int $year  year to loop through
     */
    public function loopGroupsForYear(int $year): void
    {
        $this->select('DISTINCT ' . tableField('dates', 'group_id') . ', ' .
                tableField('groups', 'tag'));
        $this->where('YEAR(' . tableField('dates', 'day') . ') = %s')
             ->and(tableField('dates', 'day') . ' < %s');
        $this->orderBy(tableField('dates', 'group_id'));
        $this->prepare([$year, date('Y-m-d')]);
        $this->iterate();
    }
}
