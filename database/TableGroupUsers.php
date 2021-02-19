<?php namespace bookclub;

/*
 * Class provides access to database group users table. This table maps
 * WordPress users to groups.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the group users table.
 *
 * @ORM\Table(name="bc_groupusers", uniqueConstraints={@ORM\UniqueConstraint(columns={"group_id", "wordpress_id"})})
 * @ORM\Entity
 */
class TableGroupUsers extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var int group identifier
     *
     * @ORM\Column(name="group_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $group_id = 0;

    /**
     * @var int unique wordpress id
     *
     * @ORM\Column(name="wordpress_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $wordpress_id = 0;

    /**
     * Initialize the object.
     * @return \bookclub\TableGroupUsers
     */
    public function __construct()
    {
        parent::__construct('groupusers');
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
     * Include the WordPress user in the given group.
     * @param int $group_id unique identifier for the group
     * @param int $wordpress_id unique identifier for the user
     */
    public static function addUser(int $group_id, int $wordpress_id): void
    {
        $groupmember = new TableGroupUsers();
        $groupmember->group_id = $group_id;
        $groupmember->wordpress_id  = $wordpress_id;
        $groupmember->insert();
    }

    /**
     * Start looping through all users of the given group.
     * @param int $group_id group identifier
     */
    public function loopForGroup(int $group_id): void
    {
        $this->select();
        $this->where("group_id = $group_id");
        $this->iterate();
    }

    /**
     * Delete the records for the given group.
     * @param int $group_id unique identifier for the group
     */
    public static function deleteByID(int $group_id): void
    {
        $groupmember = new TableGroupUsers();
        $groupmember->deleteByString(['group_id' => $group_id]);
    }

    /**
     * Remove user from all groups.
     * @param int $wordpress_id unique identifier for the user
     */
    public static function deleteByUser(int $wordpress_id): void
    {
        $groupmember = new TableGroupUsers();
        $groupmember->deleteByString(['wordpress_id' => $wordpress_id]);
    }

    /**
     * Remove user from group.
     * @param int $group_id unique identifier for the group
     * @param int $wordpress_id unique identifier for the user
     */
    public static function removeUser(int $group_id, int $wordpress_id): void
    {
        $groupmember = new TableGroupUsers();
        $groupmember->deleteByString(['group_id' => $group_id,
                                      'wordpress_id' => $wordpress_id]);
    }

    /**
     * Check if the given user is in the given group.
     * @param int $group_id unique identifier for the group
     * @param int $wordpress_id unique identifier for the user
     * @return bool true if given user is in given group
     */
    public static function isUser(int $group_id, int $wordpress_id): bool
    {
        $groupuser = new TableGroupUsers();
        $groupuser->findByString(['group_id' => $group_id,
                              'wordpress_id' => $wordpress_id]);
        return $groupuser->fetch();
    }
}
