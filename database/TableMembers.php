<?php namespace bookclub;

/*
 * Class provides access to database member table. This has a record for
 * each member of the bookclub and contains profile information.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the member table.
 *
 * @ORM\Table(name="bc_member", uniqueConstraints={@ORM\UniqueConstraint(name="web_key", columns={"web_key"})})
 * @ORM\Entity
 */
class TableMembers extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var int unique member_id id
     *
     * @ORM\Column(name="member_id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $member_id = 0;

    /**
     * @var string|null unique UUID for member used for web interface
     *
     * @ORM\Column(name="web_key", type="string", length=16, nullable=true, options={"default"="NULL"})
     */
    public $web_key = '';

    /**
     * @var string|null member name (first and last)
     *
     * @ORM\Column(name="name", type="string", length=32, nullable=true, options={"default"="NULL"})
     */
    public $name = '';

    /**
     * @var string|null email address
     *
     * @ORM\Column(name="email", type="string", length=50, nullable=true, options={"default"="NULL"})
     */
    public $email = '';

    /**
     * @var int one if receiving emails and invitations, zero otherwise
     *
     * @ORM\Column(name="active", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $active = 1;

    /**
     * @var int 0/1 - text only/text+HTML
     *
     * @ORM\Column(name="format", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $format = 1;

    /**
     * @var int 0/1 - attach ical
     *
     * @ORM\Column(name="ical", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $ical = 1;

    /**
     * @var int one to disable receiving emails, zero otherwise
     *
     * @ORM\Column(name="noemail", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $noemail = 0;

    /**
     * @var \DateTime|null last time the user was active
     *
     * @ORM\Column(name="hittime", type="datetime", nullable=true, options={"default"="NULL"})
     */
    public $hittime;

    /**
     * @var int|null wordpress id of the member or zero if not linked yet
     *
     * @ORM\Column(name="wordpress_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $wordpress_id = 0;

    /**
     * @var int 0/1 - email address is public to other members if 1 otherwise private
     *
     * @ORM\Column(name="public_email", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $public_email = '';

    /**
     * @var int 0/1 - no email from non-admin if zero, otherwise can receive email from others
     *
     * @ORM\Column(name="receive_others", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $receive_others = 0;

    /**
     * Initialize the object.
     * @return \bookclub\TableMembers
     */
    public function __construct()
    {
        parent::__construct('members');
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
     * Delete the record for the given member.
     * @param int $member_id unique identifier for the member
     */
    public static function deleteByID(int $member_id): void
    {
        $member = new TableMembers();
        $member->deleteByString(['member_id' => $member_id]);
    }

    /**
     * Find the member for the given identifier.
     * @param int $member_id unique identifier for the member
     * @return TableMembers|null record for the member or null if not found
     */
    public static function findByID(int $member_id): ?TableMembers
    {
        $member = new TableMembers();
        $member->findByString(['member_id' => $member_id]);
        return $member->fetch() ? $member : null;
    }

    /**
     * Find the member for the given UUID.
     * @param string|null $web_key UUID for the member
     * @return TableMembers|null record for the member or null if not
     * found
     */
    public static function findByKey(?string $web_key): ?TableMembers
    {
        $member = new TableMembers();
        $member->findByString(['web_key' => $web_key]);
        return $member->fetch() ? $member : null;
    }

    /**
     * Find the member for the given WordPress identifier.
     * @param int $wpid WordPress identifier for the member
     * @return TableMembers|null record for the member or null if not found
     */
    public static function findByWordpressID(int $wpid): ?TableMembers
    {
        $member = new TableMembers();
        $member->findByString(['wordpress_id' => $wpid]);
        return $member->fetch() ? $member : null;
    }

    /**
     * Find the member with the given name.
     * @param string $name name of the member
     * @return TableMembers|null record for the member or null if not
     * found
     */
    public static function findByName(string $name): ?TableMembers
    {
        $member = new TableMembers();
        $member->findByString(['name' => $name]);
        return $member->fetch() ? $member : null;
    }

    /**
     * Fetch a new identifier that has not been used - normally MAX + 1.
     * @return int unique identifier to use if a new record is inserted
     */
    public static function getNextID(): int
    {
        $member = new TableMembers();
        $member->select('COALESCE(MAX(member_id),0) + 1 AS newid');
        $rows = $member->execute();
        return (int) $rows[0]->newid;
    }

    /**
     * Record that the user was active at the current time.
     */
    public function hit(): void
    {
        $logger = $this->getLogger();
        $this->hittime = date('Y-m-d H:i:s', time());
        $this->update();
    }

    /**
     * Create an INSERT statement for all columns using the current values.
     */
    public function insert(): void
    {
        parent::insert();
    }

    /**
     * Start looping through all members with a WordPress account.
     */
    public function loopForUsers(): void
    {
        $this->select();
        $this->where('wordpress_id <> 0');
        $this->iterate();
    }

    /**
     * Start looping through participants matching the search criteria.
     * @param int    $id member identifier
     * @param int    $wpid wordpress identifier
     * @param string $pkey participant key
     * @param string $name member name
     * @param string $email member email
     * @param int    $group group identifier or zero for all
     * @param bool   $exclude true to exclude group instead of include
     * @param string $active 0, 1 or - active flag is neutral, yes, no
     * @param int    $last number of months to check for last hit
     * @param bool   $ltgt 0 to check older than, 1 to check younger than
     */
    public function loopSearch($id, $wpid, $pkey, $name, $email, $group, $exclude,
            $active, $last, $ltgt): void
    {
        $fields = [];
        $values = [];
        $this->addSearch($fields, $values, 'member_id = %s', $id, false);
        $this->addSearch($fields, $values, 'wordpress_id = %s', $wpid, false);
        $this->addSearch($fields, $values, 'web_key = %s', $pkey, false);
        $this->addSearch($fields, $values, 'name like %s', $name);
        $this->addSearch($fields, $values, 'email like %s', $email);
        if ($group) {
            $sql = TableGroupMembers::getSQLParticipantGroup(
                    tableField('members', 'member_id') .
                    ' = member_id', $group);
            if ($exclude) {
                $fields[] = "member_id not in ($sql)";
            } else {
                $fields[] = "member_id in ($sql)";
            }
        }
        if ('1' === $active) {
            $this->addSearch($fields, $values, "active = %s", 1, false);
        } else if ('-' === $active) {
            $this->addSearch($fields, $values, "active <> %s", 1, false);
        }
        if ($last) {
            if ($ltgt) {
                $this->addSearch($fields, $values, "hittime > %s",
                        date('Y-m-d', strtotime("-$last month")), false);
            } else {
                $this->addSearch($fields, $values,
                        "(hittime < %s OR hittime IS NULL)",
                        date('Y-m-d', strtotime("-$last month")), false);
            }
        }
        $this->select();
        $this->where($fields);
        $this->orderBy('name');
        $this->prepare($values);
        $this->iterate();
    }

    /**
     * Update the database from the object data.
     */
    public function update(): void {
        parent::updateByString(['member_id']);
    }
}
