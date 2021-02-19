<?php namespace bookclub;

/*
 * Class provides access to database logger table. This class tracks loggable
 * events.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the logger table.
 *
 * @ORM\Table(name="bc_logs", uniqueConstraints={@ORM\UniqueConstraint(name="timestamp", columns={"timestamp", "type", "param1", "param2", "param3"})})
 * @ORM\Entity
 */
class TableLogs extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var \DateTime timestamp of the event being logged
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=false, options={"default"="current_timestamp(6)"})
     */
    public $timestamp;

    /**
     * @var string code for type of logging event
     *
     * @ORM\Column(name="type", type="string", length=20, nullable=false)
     */
    public $type = '';

    /**
     * @var string|null param1 optional main selection criterion
     *
     * @ORM\Column(name="param1", type="string", length=20, nullable=true, options={"default"="NULL"})
     */
    public $param1 = '';

    /**
     * @var string|null param2 optional secondary selection criterion
     *
     * @ORM\Column(name="param2", type="string", length=20, nullable=true, options={"default"="NULL"})
     */
    public $param2 = '';

    /**
     * @var string|null param3 optional tertiary selection criterion
     *
     * @ORM\Column(name="param3", type="string", length=20, nullable=true, options={"default"="NULL"})
     */
    public $param3 = '';

    /**
     * @var string|null message the logged message
     *
     * @ORM\Column(name="message", type="text", length=65535, nullable=true, options={"default"="NULL"})
     */
    public $message = '';

    /**
     * Initialize the object.
     * @return \bookclub\TableLogs
     */
    public function __construct()
    {
        parent::__construct('logs', false);
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
     * The log message is saved with the current millisecond timestamp and
     * the provided selectors.
     * @param array $selectors type key to search for and up to three additional
     * selecting parameters.
     * @param string $message value to use if option name not defined
     */
    public static function addLog($selectors, $message): void
    {
        $logger = new TableLogs();
        $logger->message = $message;
        $logger->type = $selectors[0];
        if (count($selectors) > 1) {
            $logger->param1 = $selectors[1];
        }
        if (count($selectors) > 2) {
            $logger->param2 = $selectors[2];
        }
        if (count($selectors) > 3) {
            $logger->param3 = $selectors[3];
        }
        $logger->insert();
    }

    /**
     * Update to new type field based on selectors.
     * @param string $newtype new type name
     * @param array $selectors selectors, first should be old type name
     */
    public static function changeTypeBySelectors(string $newtype,
            array $selectors): void
    {
        $values = [$newtype];
        $fields  = [];
        if ($selectors[0]) {
            $fields[] = 'type = %s';
            $values[] = $selectors[0];
        }
        if ((count($selectors) > 1) && ($selectors[1])) {
            $fields[] = 'param1 = %s';
            $values[] = $selectors[1];
        }
        if ((count($selectors) > 2) && ($selectors[2])) {
            $fields[] = 'param2 = %s';
            $values[] = $selectors[2];
        }
        if ((count($selectors) > 3) && ($selectors[3])) {
            $fields[] = 'param3 = %s';
            $values[] = $selectors[3];
        }
        $tobj = new TableLogs();
        $tobj->updateSet('type = %s');
        $tobj->where($fields);
        $tobj->prepare($values);
        $tobj->execute();
    }

    /**
     * Search for the last log object matching the selectors.
     * @param array $selectors type key to search for and up to three additional
     * @return \bookclub\TableLogs|null last log object if any found
     */
    public static function findLastBySelectors(array $selectors): ?TableLogs
    {
        $values = [$selectors[0]];
        $tobj = new TableLogs();
        $tobj->select();
        $tobj->where('type = %s');
        if ((count($selectors) > 1) && ($selectors[1])) {
            $tobj->and('param1 = %s');
            $values[] = $selectors[1];
        }
        if ((count($selectors) > 2) && ($selectors[2])) {
            $tobj->and('param2 = %s');
            $values[] = $selectors[2];
        }
        if ((count($selectors) > 3) && ($selectors[3])) {
            $tobj->and('param3 = %s');
            $values[] = $selectors[3];
        }
        $tobj->orderBy('timestamp DESC')->limit(1);
        $tobj->prepare($values);
        $tobj->iterate();
        return $tobj->fetch() ? $tobj : null;
    }

    /**
     * Start looping through records matching the selection criteria.
     * @param array $selectors type key to search for and up to three additional
     * selecting parameters.
     * @param string $start timestamp between field if start and end specified
     * @param string $end timestamp between field if start and end specified
     */
    public function loopBySelectors(array $selectors,
            string $start = '',string $end = ''): void
    {
        $values = [];
        $fields  = [];
        if ($selectors[0]) {
            $fields[] = 'type = %s';
            $values[] = $selectors[0];
        }
        if ((count($selectors) > 1) && ($selectors[1])) {
            $fields[] = 'param1 = %s';
            $values[] = $selectors[1];
        }
        if ((count($selectors) > 2) && ($selectors[2])) {
            $fields[] = 'param2 = %s';
            $values[] = $selectors[2];
        }
        if ((count($selectors) > 3) && ($selectors[3])) {
            $fields[] = 'param3 = %s';
            $values[] = $selectors[3];
        }
        if ($start && $end) {
            $fields[] = 'timestamp BETWEEN %s AND %s';
            $values[] = $start;
            $values[] = $end;
        }
        $this->select();
        $this->where($fields);
        $this->orderBy('timestamp');
        $this->prepare($values);
        $this->iterate();
    }
}
