<?php namespace bookclub;

/*
 * Class provides access to joined database dates, books, authors and groups table.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined dates, books, authors and groups tables.
 */
class JoinDatesBooksAuthorsGroups extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinDatesBooksAuthorsGroups
     */
    public function __construct()
    {
        parent::__construct('dates');
        parent::join('books',
            tableField('dates', 'book_id') . ' = ' . tableField('books', 'book_id'));
        parent::join('authors',
            tableField('books', 'author_id') . ' = ' . tableField('authors', 'author_id'));
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
