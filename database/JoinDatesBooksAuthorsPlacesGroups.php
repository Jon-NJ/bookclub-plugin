<?php namespace bookclub;

/*
 * Class provides access to joined database books, authors, places and groups
 * tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined dates, books, authors, places and groups tables.
 */
class JoinDatesBooksAuthorsPlacesGroups extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinDatesBooksAuthorsPlacesGroups
     */
    public function __construct()
    {
        parent::__construct('dates');
        parent::join('books',
            tableField('dates', 'book_id') . ' = ' . tableField('books', 'book_id'));
        parent::join('authors',
            tableField('books', 'author_id') . ' = ' . tableField('authors', 'author_id'));
        parent::join('places',
            tableField('dates', 'place_id') . ' = ' . tableField('places', 'place_id'));
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
     * Fetch the number of rows returned from last query.
     * @return int number of rows returned from last result set
     */
    public function count(): int
    {
        return parent::count();
    }

    /**
     * Fetch record matching day, group and book identifiers.
     * @param string $day day to search for
     * @param int $group_id group id one to four
     * @param int $book_id book identifier
     * @return JoinDatesBooksAuthorsPlacesGroups|null record if found
     */
    public static function findByDateGroupAndBook(string $day, int $group_id,
            int $book_id): ?JoinDatesBooksAuthorsPlacesGroups
    {
        $date = new JoinDatesBooksAuthorsPlacesGroups();
        $date->findByString([tableField('dates', 'day') => $day,
                             tableField('dates', 'group_id') => $group_id,
                             tableField('dates', 'book_id') => $book_id]);
        return $date->fetch() ? $date : null;
    }

    /**
     * Start looping through books read on given date by given group. Usually
     * there will only be one record, but multiple books could be scheduled.
     * @param string $date date of the meeting
     * @param int $group_id group that will be meeting
     */
    public function loopForDateAndGroup(string $date, $group_id): void
    {
        $this->select();
        $this->where(tableField('dates', 'day') . ' = %s')
             ->and(tableField('dates', 'group_id') . ' = %s');
        $this->prepare([$date, $group_id]);
        $this->iterate();
    }

    /**
     * Start looping through future books for given group excluding those with
     * the hide flag.
     * @param int $group_id group identifier
     */
    public function loopChronological($group_id): void
    {
        $this->select();
        $this->where(tableField('dates', 'day') . ' >= %s')
             ->and(tableField('dates', 'hide') . ' <> 1');
        if ($group_id) {
            $this->and(tableField('dates', 'group_id') . ' = %s');
            $this->orderBy(tableField('dates', 'day') . ', ' . tableField('books', 'title'));
            $this->prepare([date('Y-m-d'), $group_id]);
        } else {
            $this->orderBy(tableField('dates', 'day') . ', ' . tableField('books', 'title'));
            $this->prepare([date('Y-m-d')]);
        }
        $this->iterate();
    }

    /**
     * Start looping through dates and related tables matching the search criteria.
     * @param int         $groupid group id for the meeting
     * @param int|null    $age maximum age of the meeting in months
     * @param string|null $date date of the meeting
     * @param string|null $book partial book title
     * @param string|null $author partial author name
     * @param string|null $place partial place name
     */
    public function loopSearch($groupid, $age, $date, $book, $author,
            $place): void
    {
        $fields = [];
        $values = [];
        if ($groupid) {
            $this->addSearch($fields, $values, tableField('dates', 'group_id') . ' = %s', $groupid, false);
        }
        if ($age) {
            $this->addSearch($fields, $values, tableField('dates', 'day') . ' > %s',
                    date('Y-m-d', strtotime("-$age month")), false);
        }
        $this->addSearch($fields, $values, tableField('dates', 'day') . ' = %s', $date, false);
        $this->addSearch($fields, $values, tableField('books', 'title') . ' like %s', $book);
        $this->addSearch($fields, $values, tableField('authors', 'name') . ' like %s', $author);
        $this->addSearch($fields, $values, tableField('places', 'place') . ' like %s', $place);
        $this->select();
        $this->where($fields);
        $this->orderBy(tableField('dates', 'day') . ' DESC, ' .
                tableField('dates', 'group_id'));
        $this->prepare($values);
        $this->iterate();
    }
}
