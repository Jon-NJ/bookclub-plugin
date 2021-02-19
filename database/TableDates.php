<?php namespace bookclub;

/*
 * Class provides access to database dates table which maps the date of a
 * meeting, the book to read, the group involved and optionally the place of
 * the meeting.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the dates table.
 *
 * @ORM\Table(name="bc_dates", indexes={@ORM\Index(name="dt_index", columns={"day"})})
 * @ORM\Entity
 */
class TableDates extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var int unique identifier for the book
     *
     * @ORM\Column(name="book_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $book_id = 0;

    /**
     * @var int unique identifier for location or zero
     *
     * @ORM\Column(name="place_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $place_id = 0;

    /**
     * @var int group meeting on the date, one to four
     *
     * @ORM\Column(name="group_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $group_id = 0;

    /**
     * @var \DateTime day of the meeting
     *
     * @ORM\Column(name="day", type="date", nullable=false, options={"default"="'0000-00-00'"})
     */
    public $day;

    /**
     * @var int normally zero, one if not shown on forthcoming books list
     *
     * @ORM\Column(name="hide", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $hide = 0;

    /**
     * @var int normally zero, one if the meeting will be private
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
     * Initialize the object.
     * @return \bookclub\TableDates
     */
    public function __construct()
    {
        parent::__construct('dates');
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
     * Fetch the number of rows returned from last query.
     * @return int number of rows returned from last result set
     */
    public function count(): int
    {
        return parent::count();
    }

    /**
     * Delete the record for the given day, group and book.
     * @param string $day day of event to delete
     * @param int $group_id group identifier
     * @param int $book_id book id to delete
     */
    public static function deleteByDateGroupAndBook($day, int $group_id,
            int $book_id): void
    {
        $date = new TableDates();
        $date->deleteByString(['day' => $day,
                               'group_id' => $group_id,
                               'book_id' => $book_id]);
    }

    /**
     * Fetch record matching day and group identifier.
     * @param string $day day to search for
     * @param int $group_id group id one to four
     * @param int $book_id book identifier
     * @return TableDates|null record if found
     */
    public static function findByDateGroupAndBook($day, $group_id, $book_id)
    {
        $date = new TableDates();
        $date->findByString(['day'      => $day,
                             'group_id' => $group_id,
                             'book_id'  => $book_id]);
        return $date->fetch() ? $date : null;
    }

    /**
     * Find the maximum year from all records.
     * @return int year
     */
    public static function getMaxYear(): int
    {
        $date = new TableDates();
        $min = date('Y');
        $date->select("COALESCE(MAX(YEAR(day)),$min) year");
        $date->where('day < %s');
        $date->prepare([date('Y-m-d')]);
        $rows = $date->execute();
        return $rows[0]->year;
    }

    /**
     * Find the minimum group number for all records matching the given year.
     * @param string $year year to find
     * @return int group identifier
     */
    public static function getMinGroup($year): int
    {
        $date = new TableDates();
        $date->select('COALESCE(MIN(group_id),0) group_id');
        $date->where("YEAR(day) = $year")
             ->and('day < %s');
        $date->prepare([date('Y-m-d')]);
        $rows = $date->execute();
        return $rows[0]->group_id;
    }

    /**
     * Find the next meeting date for the given group.
     * @param int $group_id group identifier
     * @return string|null meeting date if found
     */
    public static function getNextDateForGroup(int $group_id): ?string
    {
        $date = new TableDates();
        $date->select('day');
        $date->where('day >= %s')
             ->and('hide = 0')
             ->and('group_id = %s');
        $date->orderBy('day');
        $date->prepare([date('Ymd'), $group_id]);
        $rows = $date->execute();
        $result = null;
        if (count($rows) > 0) {
            $result = $rows[0]->day;
        }
        return $result;
    }

    /**
     * Count the number of groups with meeting dates in the given year.
     * @param int $year year to match
     * @return int number of groups
     */
    public static function getGroupCountForYear(int $year): int
    {
        $date = new TableDates();
        $date->select('distinct group_id');
        $date->where("YEAR(day) = $year")
             ->and('day < %s');
        $date->reselect('COUNT(*) AS group_count');
        $date->prepare([date('Y-m-d')]);
        $rows = $date->execute();
        return (int) $rows[0]->group_count;
    }

    /**
     * Start looping through group ids for given year.
     * @param int $year  year to loop through
     */
    public function loopGroupsForYear(int $year): void
    {
        $this->select('distinct group_id');
        $this->where('YEAR(day) = %s')
             ->and('day < %s');
        $this->orderBy('group_id');
        $this->prepare([$year, date('Y-m-d')]);
        $this->iterate();
    }

    /**
     * Start looping through records where given book was read.
     * @param int $book_id book identifier
     */
    public function loopDatesForBook(int $book_id): void
    {
        $this->year = null;
        $this->month = null;
        $this->select('DISTINCT YEAR(day) AS year, MONTH(day) AS month,' .
                ' day, group_id');
        $this->where('book_id = %s');
        $this->orderBy('month DESC');
        $this->prepare([$book_id]);
        $this->iterate();
    }

    /**
     * Start looping through past records for given group.
     * @param string $time_expression mysql time expression e.g. -4 year
     * @param int $group_id group identifier
     */
    public function loopTimeLimited($time_expression, $group_id): void
    {
        $this->select();
        $this->where('day > %s');
        if ($group_id) {
            $this->and("group_id = $group_id");
            $this->orderBy('day DESC');
        } else {
            $this->orderBy('day DESC, group_id');
        }
        $this->prepare([date('Y-m-d', strtotime($time_expression))]);
        $this->iterate();
    }

    /**
     * Start looping through previous years in reverse order.
     */
    public function loopPastYears(): void
    {
        $this->yyyy = null;
        $this->select('DISTINCT YEAR(day) AS yyyy');
        $this->where('day < %s');
        $this->orderBy('yyyy DESC');
        $this->prepare([date('Y-m-d')]);
        $this->iterate();
    }

    /**
     * Create an INSERT statement for all columns using the current values.
     */
    public function insert(): void
    {
        parent::insert();
    }

    /**
     * The current event is moved to a new date and/or a new group.
     * Note that this changes the primary key.
     * @param string $day new date for given event
     * @param int $group_id new group identifier
     * @param int $book_id new book id
     */
    public function reschedule(string $day, int $group_id, int $book_id): void
    {
        $this->changeKey(['day'      => $day,
                          'group_id' => $group_id,
                          'book_id'  => $book_id]);
    }

    /**
     * Update the database from the object data.
     */
    public function update(): void
    {
        parent::updateByString(['day', 'group_id', 'book_id']);
    }
}
