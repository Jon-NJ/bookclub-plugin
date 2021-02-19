<?php namespace bookclub;

/*
 * Class provides access to joined wordpress users and usermeta tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined wordpress users and usermeta tables.
 */
class JoinUsers extends DatabaseTable
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
        parent::__construct('\users');
        parent::join('\usermeta AS f',
            tableField('\users', 'ID') . " = f.user_id AND f.meta_key = 'first_name'");
        parent::join('\usermeta AS l',
            tableField('\users', 'ID') . " = l.user_id AND l.meta_key = 'last_name'");
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
     * Find the user with the given name.
     * @param string $name name of the wordpress user
     * @return JoinUsers|null record for the member or null if not
     * found
     */
    public static function findByName(string $name): ?JoinUsers
    {
        $user = new JoinUsers();
        $user->select('',
            "CONCAT(f.meta_value, ' ', l.meta_value) AS fullname");
        $user->where(
            "CONCAT(f.meta_value, ' ', l.meta_value) like %s");
        $user->prepare([$name]);
        $user->iterate();
        return $user->fetch() ? $user : null;
    }
}
