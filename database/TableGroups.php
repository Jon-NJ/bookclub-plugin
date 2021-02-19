<?php namespace bookclub;

/*
 * Class provides access to database events table. This table contains group
 * information.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the groups table.
 *
 * @ORM\Table(name="bc_groups")
 * @ORM\Entity
 */
class TableGroups extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var int group identifier
     *
     * @ORM\Column(name="group_id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $group_id = 0;

    /**
     * @var int group type - 1=BC_GROUP_CLUB bookclub group, 2=BC_GROUP_SELECT, 3=BC_GROUP_WORDPRESS list, 4=BC_GROUP_ANNOUNCEMENTS
     *
     * @ORM\Column(name="type", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $type = 0;

    /**
     * @var string short description
     *
     * @ORM\Column(name="tag", type="string", length=20, nullable=false)
     */
    public $tag = '';

    /**
     * @var string|null group description
     *
     * @ORM\Column(name="description", type="string", length=50, nullable=true, options={"default"="NULL"})
     */
    public $description = '';

    /**
     * @var string|null URL for group
     *
     * @ORM\Column(name="url", type="string", length=50, nullable=false, options={"default"="''"})
     */
    public $url = '';

    /**
     * @var string t_event_id event identifier template
     *
     * @ORM\Column(name="t_event_id", type="string", length=80, nullable=false, options={"default"="''"})
     */
    public $t_event_id = '';

    /**
     * @var string t_max_attend max attend template
     *
     * @ORM\Column(name="t_max_attend", type="string", length=5, nullable=false, options={"default"="''"})
     */
    public $t_max_attend = '';

    /**
     * @var string t_starttime start time template
     *
     * @ORM\Column(name="t_starttime", type="string", length=10, nullable=false, options={"default"="''"})
     */
    public $t_starttime = '';

    /**
     * @var string t_endtime end time template
     *
     * @ORM\Column(name="t_endtime", type="string", length=10, nullable=false, options={"default"="''"})
     */
    public $t_endtime = '';

    /**
     * @var string t_summary summary template
     *
     * @ORM\Column(name="t_summary", type="string", length=80, nullable=false, options={"default"="''"})
     */
    public $t_summary = '';

    /**
     * @var string t_description description template
     *
     * @ORM\Column(name="t_description", type="text", length=65535, nullable=false, options={"default"="''"})
     */
    public $t_description = '';

    /**
     * @var int t_include group to include or zero for template
     *
     * @ORM\Column(name="t_include", type="integer", nullable=false, options={"unsigned"=true,"default"="'0'"})
     */
    public $t_include = '';

    /**
     * Initialize the object.
     * @return \bookclub\TableGroups
     */
    public function __construct()
    {
        parent::__construct('groups');
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
     * Delete the record for the given group.
     * @param int $group_id unique identifier for the group
     */
    public static function deleteByID(int $group_id): void
    {
        $group = new TableGroups();
        $group->deleteByString(['group_id' => $group_id]);
    }

    /**
     * Find the group for the given identifier.
     * @param int $group_id unique identifier for the group
     * @return TableGroups|null record for the group or null if not found
     */
    public static function findByID(int $group_id): ?TableGroups
    {
        $group = new TableGroups();
        $group->findByString(['group_id' => $group_id]);
        return $group->fetch() ? $group : null;
    }

    /**
     * Find the group for the given tag.
     * @param string $tag tag identifier
     * @return TableGroups|null record for the group or null if not found
     */
    public static function findByTag(string $tag): ?TableGroups
    {
        $group = new TableGroups();
        $group->select();
        $group->where('tag like %s');
        $group->prepare([$tag]);
        $group->iterate();
        return $group->fetch() ? $group : null;
    }

    /**
     * Fetch a new identifier that has not been used - normally MAX + 1.
     * But all types have a 1000 block range.
     * @param int $type 1=BC_GROUP_CLUB bookclub group, 2=BC_GROUP_SELECT list,
     * 3=BC_GROUP_WORDPRESS. 4=BC_GROUP_ANNOUNCEMENTS
     * @return int unique identifier to use if a new record is inserted
     */
    public static function getNextID(int $type): int
    {
        $group = new TableGroups();
        $group->select('COALESCE(MAX(group_id),0) + 1 AS newid');
        $group->where("type = $type");
        $rows = $group->execute();
        $newid = (int) $rows[0]->newid;
        if ((BC_GROUP_SELECT === $type) && ($newid < 1000)) {
            $newid = 1000;
        }
        if ((BC_GROUP_WORDPRESS === $type) && ($newid < 2000)) {
            $newid = 2000;
        }
        if ((BC_GROUP_ANNOUNCEMENTS === $type) && ($newid < 3000)) {
            $newid = 3000;
        }
        return $newid;
    }

    /**
     * Start looping through all groups of the given type.
     * @param int $type 0=all groups 1=BC_GROUP_CLUB bookclub group,
     * 2=BC_GROUP_SELECT list, 3=BC_GROUP_WORDPRESS, 4=BC_GROUP_ANNOUNCEMENTS
     */
    public function loopForType(int $type): void
    {
        $this->select();
        if ($type) {
            $this->where("type = $type");
        }
        $this->orderBy('group_id');
        $this->iterate();
    }

    /**
     * Start looping through groups matching the search criteria.
     * @param string|null $groupid unique group identifier if given
     * @param int|null $type  1=BC_GROUP_CLUB bookclub group, 2=BC_GROUP_SELECT
     * list if given, 3=BC_GROUP_WORDPRESS, 4=BC_GROUP_ANNOUNCEMENTS
     * @param string|null $tag short description if given
     * @param string|null $description group description
     */
    public function loopSearch(?string $groupid, ?int $type, ?string $tag,
            ?string $description): void
    {
        $fields = [];
        $values = [];
        $this->addSearch($fields, $values, 'group_id = %s', $groupid, false);
        $this->addSearch($fields, $values, 'type = %s', $type, false);
        $this->addSearch($fields, $values, 'tag like %s', $tag);
        $this->addSearch($fields, $values, 'description like %s', $description);
        $this->select();
        $this->where($fields);
        $this->orderBy('group_id');
        $this->prepare($values);
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
     * Update the database from the object data.
     */
    public function update(): void
    {
        parent::updateByString(['group_id']);
    }
}
