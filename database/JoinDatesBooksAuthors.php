<?php namespace bookclub;

/*
 * Class provides access to joined database dates, books and authors table.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined dates, books and authors tables.
 */
class JoinDatesBooksAuthors extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinDatesBooksAuthors
     */
    public function __construct()
    {
        parent::__construct('dates');
        parent::join('books',
            tableField('dates', 'book_id') . ' = ' . tableField('books', 'book_id'));
        parent::join('authors',
            tableField('books', 'author_id') . ' = ' . tableField('authors', 'author_id'));
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
     * Fetch the number of rows returned from last query.
     * @return int number of rows returned from last result set
     */
    public function count(): int
    {
        return parent::count();
    }

    /**
     * Start looping through previous books read by given group for given year.
     * @param int $year limit to records matching this year
     * @param int $gid limit to records matching this group id
     */
    public function loopGroupForYear($year, $gid): void
    {
        $this->select();
        $this->where('YEAR(' . tableField('dates', 'day') . ') = %s')
             ->and(tableField('dates', 'day') . ' < %s')
             ->and(tableField('dates', 'group_id') . ' = %s');
        $this->orderBy('day');
        $this->prepare([$year, date('Y-m-d'), $gid]);
        $this->iterate();
    }

    /**
     * Start looping through future books for given group including those not
     * shown on the forthcoming page (used in profile).
     * @param string $groups limit to records matching these group ids
     */
    public function loopFutureForGroups($groups): void
    {
        $this->select();
        $this->where(tableField('dates', 'day') . ' >= %s ')
             ->and(tableField('dates', 'group_id') . " in ($groups)");
        $this->orderBy(tableField('dates', 'day') . ', ' . tableField('books', 'title'));
        $this->prepare([date('Y-m-d')]);
        $this->iterate();
    }
}
