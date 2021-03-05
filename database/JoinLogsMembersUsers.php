<?php namespace bookclub;

/*
 * Class provides access to joined groups and group users tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined logs, members and users tables.
 */
class JoinLogsMembersUsers extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @param int $parm optional join field (defaults to param2)
     * @return \bookclub\JoinGroupsGroupUsers
     */
    public function __construct(int $parm = 2)
    {
        parent::__construct('logs');
        parent::join('members',
            tableField('logs', "param$parm") . ' = ' . tableField('members', 'member_id'));
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
            $fields[] = tableField('logs', 'type') . ' = %s';
            $values[] = $selectors[0];
        }
        if ((count($selectors) > 1) && ($selectors[1])) {
            $fields[] = tableField('logs', 'param1') . ' = %s';
            $values[] = $selectors[1];
        }
        if ((count($selectors) > 2) && ($selectors[2])) {
            $fields[] = tableField('logs', 'param2') . ' = %s';
            $values[] = $selectors[2];
        }
        if ((count($selectors) > 3) && ($selectors[3])) {
            $fields[] = tableField('logs', 'param3') . ' = %s';
            $values[] = $selectors[3];
        }
        if ($start && $end) {
            $fields[] = tableField('logs', 'timestamp') . ' BETWEEN %s AND %s';
            $values[] = $start;
            $values[] = $end;
        }
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->where($fields);
        $this->orderBy('timestamp');
        $this->prepare($values);
        $this->iterate();
    }
}
