<?php namespace bookclub;

/*
 * Class provides access to joined member and wordpress users and usermeta tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined member and wordpress users and usermeta tables.
 */
class JoinMembersUsers extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinMembersUsers
     */
    public function __construct()
    {
        parent::__construct('members');
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
     * Find the member with the given name.
     * @param string $name name of the member
     * @return JoinMembersUsers|null record for the member or null if not
     * found
     */
    public static function findByName(string $name): ?JoinMembersUsers
    {
        $member = new JoinMembersUsers();
        $member->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $member->where(
                'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) = %s");
        $member->prepare([$name]);
        $member->iterate();
        return $member->fetch() ? $member : null;
    }

    /**
     * Find the member for the given identifier.
     * @param int $member_id unique identifier for the member
     * @return JoinMembersUsers|null record for the member or null if not found
     */
    public static function findByID(int $member_id): ?JoinMembersUsers
    {
        $person = new JoinMembersUsers();
        $person->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $person->where(tableField('members', 'member_id') . ' = %s');
        $person->prepare([$member_id]);
        $person->iterate();
        return $person->fetch() ? $person : null;
    }

    /**
     * Find the member for the given WordPress identifier.
     * @param int $wpid WordPress identifier for the member
     * @return JoinMembersUsers|null record for the member or null if not found
     */
    public static function findByWordpressID(int $wpid): ?JoinMembersUsers
    {
        $person = new JoinMembersUsers();
        $person->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $person->where(tableField('members', 'wordpress_id') . ' = %s');
        $person->prepare([$wpid]);
        $person->iterate();
        return $person->fetch() ? $person : null;
    }

    /**
     * Find the member for the given UUID.
     * @param string|null $web_key UUID for the member
     * @return JoinMembersUsers|null record for the member or null if not
     * found
     */
    public static function findByKey(?string $web_key): ?JoinMembersUsers
    {
        $person = new JoinMembersUsers();
        $person->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $person->where(tableField('members', 'web_key') . ' = %s');
        $person->prepare([$web_key]);
        $person->iterate();
        return $person->fetch() ? $person : null;
    }

    /**
     * Start looping through participants matching the search criteria.
     * @param int         $id member identifier
     * @param string|null $wpid wordpress identifier or * for only WP users
     * @param string|null $login partial wordpress login
     * @param string      $pkey participant key
     * @param string      $name member name
     * @param string      $email member email
     * @param int         $group group identifier or zero for all
     * @param bool        $exclude true to exclude group instead of include
     * @param string      $active 0, 1 or - active flag is neutral, yes, no
     * @param int         $last number of months to check for last hit
     * @param bool        $ltgt 0 to check older than, 1 to check younger than
     */
    public function loopSearch($id, ?string $wpid, ?string $login, $pkey, $name,
            $email, $group, $exclude, $active, $last, $ltgt): void
    {
        $fields = [];
        $values = [];
        $this->addSearch($fields, $values, tableField('members', 'member_id') . ' = %s', $id, false);
        if ('*' == $wpid) {
            $fields[] = tableField('members', 'wordpress_id') . ' <> 0';
        } else {
            $this->addSearch($fields, $values, tableField('members', 'wordpress_id') . ' = %s', $wpid, false);
        }
        $this->addSearch($fields, $values, tableField('\users', 'user_login') . ' LIKE %s', $login);
        $this->addSearch($fields, $values, tableField('members', 'web_key') . ' = %s', $pkey, false);
        $this->addSearch($fields, $values,
                'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) LIKE %s", $name);
        $this->addSearch($fields, $values,
                'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'email') . ', ' .
                    tableField('\users', 'user_email') . ') LIKE %s', $email);
        if ($group) {
            $sql = TableGroupMembers::getSQLParticipantGroup(
                    tableField('members', 'member_id') .
                    ' = member_id', $group);
            if ($exclude) {
                $fields[] = "member_id NOT IN ($sql)";
            } else {
                $fields[] = "member_id IN ($sql)";
            }
        }
        if ('1' === $active) {
            $this->addSearch($fields, $values, tableField('members', 'active') . ' = %s', 1, false);
        } else if ('-' === $active) {
            $this->addSearch($fields, $values, tableField('members', 'active') . ' <> %s', 1, false);
        }
        if ($last) {
            if ($ltgt) {
                $this->addSearch($fields, $values, tableField('members', 'hittime') . ' > %s',
                        date('Y-m-d', strtotime("-$last month")), false);
            } else {
                $this->addSearch($fields, $values,
                        '(' . tableField('members', 'hittime') . ' < %s OR ' .
                        tableField('members', 'hittime') . ' IS NULL)',
                        date('Y-m-d', strtotime("-$last month")), false);
            }
        }
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->where($fields);
        $this->orderBy('fullname');
        $this->prepare($values);
        $this->iterate();
    }
}
