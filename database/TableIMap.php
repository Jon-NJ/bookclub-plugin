<?php namespace bookclub;

/*
 * Class provides access to database IMAP table.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the imap table.
 * 
 * @ORM\Table(name="bc_imap")
 * @ORM\Entity
 */
class TableIMap extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var unique identifier for the message
     *
     * @ORM\Column(name="message_id", type="string", length=250, nullable=false, options={"default"="''"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $message_id = '';

    /**
     * @var message subject
     *
     * @ORM\Column(name="subject", type="string", length=100, nullable=false, options={"default"="''"})
     */
    public $subject = '';

    /**
     * @var int unique identifier or zero if not found
     *
     * @ORM\Column(name="uid", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $uid = 0;

    /**
     * @var int unique identifier for the sender or zero if not found
     *
     * @ORM\Column(name="wordpress_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $wordpress_id = 0;

    /**
     * @var \DateTime timestamp of the email
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=false)
     */
    public $timestamp;

    /**
     * @var int zero if not finished processing yet
     *
     * @ORM\Column(name="processed", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $processed = 0;

    /**
     * @var int status of handling the message
     *
     * @ORM\Column(name="status", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $status = 0;

    /**
     * @var string who should the message be forwarded to (e.g. login, username or group tag) or empty
     *
     * @ORM\Column(name="target", type="text", length=40, nullable=true, options={"default"="''"})
     */
    public $target = '';

    /**
     * @var int 0-BC_IMAP_TARGET_NONE 1 if target not found, 1-BC_IMAP_TARGET_USER, otherwise 2-BC_IMAP_TARGET_GROUP
     *
     * @ORM\Column(name="target_type", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $target_type = 0;

    /**
     * @var int WordPress user id if target_type = 1 (BC_IMAP_TARGET_USER), otherwise group_id
     *
     * @ORM\Column(name="target_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $target_id = 0;

    /**
     * Initialize the object.
     * @return TableAuthors
     */
    public function __construct()
    {
        parent::__construct('imap');
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
     * Find the imap email for the given identifier.
     * @param int $message_id unique identifier for the email
     * @return TableIMap|null record for the imap item or null if not found
     */
    public static function findByID(string $message_id): ?TableIMap
    {
        $imap = new TableIMap();
        $imap->findByString(['message_id' => $message_id]);
        return $imap->fetch() ? $imap : null;
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
    function update()
    {
        parent::updateByString(['message_id']);
    }

    /**
     * Start looping through all unprocessed emails.
     * @param int $status status value
     */
    public function loopUnfinished(int $status): void
    {
        $this->select();
        $this->where('processed = 0')
             ->and('status = %s');
        $this->orderBy('timestamp');
        $this->prepare([$status]);
        $this->iterate();
    }
}
