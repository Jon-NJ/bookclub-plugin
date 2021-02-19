<?php namespace bookclub;

/*
 * Class provides access to database group members table. This table maps
 * members to groups.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the group members table.
 *
 * @ORM\Table(name="bc_groupmembers", uniqueConstraints={@ORM\UniqueConstraint(name="gm_index", columns={"group_id", "participant_id"})})
 * @ORM\Entity
 */
class TableGroupMembers extends DatabaseTable
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
     * @var int unique member id
     *
     * @ORM\Column(name="member_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $member_id = 0;

    /**
     * Initialize the object.
     * @return \bookclub\TableGroups
     */
    public function __construct()
    {
        parent::__construct('groupmembers');
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
     * Generate SQL that will query group for member and group id.
     * @param string $match match condition
     * @param int    $groupid group identifier
     * @return string SQL for generated query
     */
    public static function getSQLParticipantGroup(string $match, int $groupid): string
    {
        $group = new TableGroupMembers();
        $group->select('member_id');
        $group->where('group_id = %s')
              ->and($match);
        $group->prepare([$groupid]);
        return $group->_sql;
    }

    /**
     * Include the member in the given group.
     * @param int $group_id unique identifier for the group
     * @param int $member_id unique identifier for the member
     */
    public static function addMember(int $group_id, int $member_id): void
    {
        $groupmember = new TableGroupMembers();
        $groupmember->group_id = $group_id;
        $groupmember->member_id = $member_id;
        $groupmember->insert();
    }

    /**
     * Start looping through all members of the given group.
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
        $groupmember = new TableGroupMembers();
        $groupmember->deleteByString(['group_id' => $group_id]);
    }

    /**
     * Remove member from all groups.
     * @param int $member_id unique identifier for the member
     */
    public static function deleteByMember(int $member_id): void
    {
        $groupmember = new TableGroupMembers();
        $groupmember->deleteByString(['member_id' => $member_id]);
    }

    /**
     * Remove member from group.
     * @param int $group_id unique identifier for the group
     * @param int $member_id unique identifier for the member
     */
    public static function removeMember(int $group_id, int $member_id): void
    {
        $groupmember = new TableGroupMembers();
        $groupmember->deleteByString(['group_id'  => $group_id,
                                      'member_id' => $member_id]);
    }

    /**
     * Check if the given member is in the given group.
     * @param int $group_id unique identifier for the group
     * @param int $member_id unique identifier for the member
     * @return bool true if given member is in given group
     */
    public static function isMember(int $group_id, int $member_id): bool
    {
        $groupmember = new TableGroupMembers();
        $groupmember->findByString(['group_id'  => $group_id,
                                    'member_id' => $member_id]);
        return $groupmember->fetch();
    }
}
