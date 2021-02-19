<?php namespace bookclub;

/*
 * Class provides access to joined groups and group members tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined groups and group members tables.
 */
class JoinGroupsGroupMembers extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinGroupsGroupMembers
     */
    public function __construct()
    {

        parent::__construct('groups');
        /**
         * Member id is a JOIN condition instead of a WHERE condition
         * to ensure that all groups are included in the result set because it
         * is a LEFT JOIN. The fields from an unmatched join are NULL.
         */
        parent::join('groupmembers',
            tableField('groups', 'group_id') . ' = ' . tableField('groupmembers', 'group_id') . ' AND ' .
            tableField('groupmembers', 'member_id') . ' = %s');
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
     * @param int $type optional type 1=BC_GROUP_CLUB bookclub group,
     * 2=BC_GROUP_SELECT list
     */
    public function loopForMember(int $member_id, int $type = 0): void
    {
        $this->select();
        if ($type) {
             $this->where(tableField('groups', 'type') . ' = %s');
        } else {
            $this->where(tableField('groups', 'type') . ' in (1,2)');
        }
        $this->orderBy(tableField('groups', 'group_id'));
        if ($type) {
            $this->prepare([$member_id, $type]);
        } else {
            $this->prepare([$member_id]);
        }
        $this->iterate();
    }

    /**
     * Check whether the current member is in the current group.
     * @return bool true if this person is a recipient
     */
    public function inGroup(): bool
    {
        return !is_null($this->member_id);
    }
}
