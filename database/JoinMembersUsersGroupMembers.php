<?php namespace bookclub;

/*
 * Class provides access to joined members, group members, users and usermeta tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined members, group members, users and usermeta tables.
 */
class JoinMembersUsersGroupMembers extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinMembersUsersGroupMembers
     */
    public function __construct()
    {
        parent::__construct('members');
        parent::join('groupmembers',
            tableField('members', 'member_id') . ' = ' . tableField('groupmembers', 'member_id') .
                ' AND ' . tableField('groupmembers', 'group_id') . ' = %s');
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
     * Check the group member status of the current member.
     * @return bool true if this person is included in the current group
     */
    public function isMember(): bool
    {
            return !is_null($this->group_id);
    }

    /**
     * Start looping through all members to see if they are in the given group.
     * @param int $group_id group to check for inclusion
     */
    public function loopMembersForGroup(int $group_id): void
    {
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->orderBy('fullname');
        $this->prepare([$group_id]);
        $this->iterate();
    }
}
