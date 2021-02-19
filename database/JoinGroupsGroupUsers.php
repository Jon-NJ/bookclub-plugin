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
 * Access to the joined groups and group users tables.
 */
class JoinGroupsGroupUsers extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinGroupsGroupUsers
     */
    public function __construct()
    {

        parent::__construct('groups');
        /**
         * Member id is a JOIN condition instead of a WHERE condition
         * to ensure that all groups are included in the result set because it
         * is a LEFT JOIN. The fields from an unmatched join are NULL.
         */
        parent::join('groupusers',
            tableField('groups', 'group_id') . ' = ' . tableField('groupusers', 'group_id') . ' AND ' .
            tableField('groupusers', 'wordpress_id') . ' = %s');
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
     * Start looping through groups that the member belongs to.
     * @param int $member_id unique member id
     * @param int $type optional type 3=BC_GROUP_WORDPRESS WP user group,
     * 4=BC_GROUP_ANNOUNCEMENTS list
     */
    public function loopForUser(int $wordpress_id, int $type = 0): void
    {
        $this->select();
        if ($type) {
             $this->where(tableField('groups', 'type') . ' = %s');
        } else {
            $this->where(tableField('groups', 'type') . ' in (3,4)');
        }
        $this->orderBy(tableField('groups', 'group_id'));
        if ($type) {
            $this->prepare([$wordpress_id, $type]);
        } else {
            $this->prepare([$wordpress_id]);
        }
        $this->iterate();
    }

    /**
     * Check whether the current member is in the current group.
     * @return bool true if this person is a recipient
     */
    public function inGroup(): bool
    {
        return !is_null($this->wordpress_id);
    }
}
