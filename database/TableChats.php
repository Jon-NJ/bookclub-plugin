<?php namespace bookclub;

/*
 * Class provides access to database chats table.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the chats table.
 *
 * @ORM\Table(name="bc_chats")
 * @ORM\Entity
 */
class TableChats extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var int unique identifier for the record
     *
     * @ORM\Column(name="chat_id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $chat_id = null;

    /**
     * @var \DateTime timestamp of the chat
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=false, options={"default"="current_timestamp(6)"})
     */
    public $timestamp;

    /**
     * @var int identifier for the user/owner
     *
     * @ORM\Column(name="wordpress_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $wordpress_id = 0;

    /**
     * @var int 0 not deleted or wordpress_id of user who deleted it
     *
     * @ORM\Column(name="deleted_by", type="integer", nullable=false, options={"unsigned"=false})
     */
    public $deleted_by = 0;

    /**
     * @var int 1-BC_CHAT_TARGET_USER, 2-BC_CHAT_TARGET_GROUP, 3-BC_CHAT_TARGET_BOOK, 4- BC_CHAT_TARGET_EVENT
     *
     * @ORM\Column(name="target_type", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $target_type = 0;

    /**
     * @var string (or integer as string) target_type: 1-WordPress user ID, 2-Group ID, 3-Book ID, 4-Event ID
     *
     * @ORM\Column(name="target_id", type="string", length=40, nullable=false, options={"default"="''"})
     */
    public $target_id = '';

    /**
     * @var string chat message
     *
     * @ORM\Column(name="message", type="string", length=255, nullable=false, options={"default"="''"})
     */
    public $message = '';

    /**
     * Initialize the object.
     * @return \bookclub\TableBooks
     */
    public function __construct()
    {
        parent::__construct('chats');
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
     * Create an INSERT statement for all columns using the current values.
     */
    public function insert(): void
    {
        parent::insert();
    }

    /**
     * 
     * @param int $target_type 2-BC_CHAT_TARGET_GROUP, 3-BC_CHAT_TARGET_BOOK,
     * 4-BC_CHAT_TARGET_EVENT
     * @param string $target_id identifier for chat
     * @param string $date optional start date for inclusion in the count
     * @return int count of messages
     */
    public static function countRecent(int $target_type, string $target_id, string $date = ''): int
    {
        $chat = new TableChats();
        $chat->select('count(*) AS count');
        $chat->where('target_type = %s')
             ->and('target_id = %s')
             ->and('timestamp >= %s');
        if (!$date) {
            $date = strtotime("-1 month");
        }
        $chat->prepare([$target_type, $target_id, date('Y-m-d', $date)]);
        $rows = $chat->execute();
        return (int) $rows[0]->count;
    }

    /**
     * This generates an SQL statement returning partner_id (WordPress
     * identifier of the chat partner) and chat_count. The SQL is then used
     * as a join clause in another query.
     * @param int $first the WordPress ID for the user being queried
     * @param string $date optional start date for inclusion in the count
     * @return string SQL that counts records grouped by each partner
     */
    public static function getSQLCountRecentDirectMessages(int $first,
            string $date = ''): string
    {
        if (!$date) {
            $date = strtotime("-1 month");
        }
        $values = [$first, $first, $first, $date];
        $counts = new TableChats();
        $counts->select(
                'CASE WHEN %s = wordpress_id' .
                    ' THEN target_id' .
                    ' ELSE wordpress_id' .
                ' END AS partner_id, ' .
                ' COUNT(*) AS chat_count');
        $counts->where("target_type = " . BC_CHAT_TARGET_USER .
                " AND (wordpress_id = %s OR target_id = %s)" .
                " AND timestamp >= %s");
        $counts->_sql .= " GROUP BY partner_id";
        $counts->prepare($values);
        return $counts->_sql;
    }

    /**
     * The primary key of the given event is changed.
     * @param string $oldid original event id
     * @param string $newid new event id
     */
    public static function updateEventID($oldid, $newid): void
    {
        $recipient = new TableChats();
        $recipient->updateSet('target_id = %s');
        $recipient->where("target_type = " . BC_CHAT_TARGET_EVENT)
                  ->and('target_id = %s');
        $recipient->prepare([$newid, $oldid]);
        $recipient->execute();
    }

    /**
     * Mark the message as deleted.
     * @param int $index unique identifier for the record
     * @param int $wordpress_id non-zero identifier of user that deleted it
     */
    public static function markDeletedByID(int $index, int $wordpress_id): void
    {
        $chat = new TableChats();
        $chat->updateSet('deleted_by = %s');
        $chat->where('chat_id = %s');
        $chat->prepare([$wordpress_id, $index]);
        $chat->execute();

        $chat = new TableChats();
        $chat->target_type = BC_CHAT_TARGET_DELETE;
        $chat->target_id = strval($index);
        $chat->insert();
    }

    /**
     * Mark all messages from given user as deleted.
     * @param int $wordpress_id non-zero identifier of user that deleted it
     */
    public static function markDeletedByUser(int $wordpress_id): void
    {
        $tobj = new TableChats();
        $tobj->select();
        $tobj->where("$wordpress_id = %s")
             ->and("deleted_by = 0");
        $tobj->orderBy('timestamp');
        $tobj->prepare([$wordpress_id]);
        $tobj->iterate();
        while ($tobj->fetch()) {
            $tobj->deleted_by = $wordpress_id;
            $tobj::updateByString(['chat_id']);
            $chat = new TableChats();
            $chat->target_type = BC_CHAT_TARGET_DELETE;
            $chat->target_id = strval($tobj->chat_id);
            $chat->insert();
        }
    }

    /**
     * Search for delete records after the chat id passed as start.
     * @param int $start last chat_id that was displayed
     */
    public function loopForDeleted(int $start): void
    {
        $values = [$start];
        $this->select();
        $this->where("target_type = " . BC_CHAT_TARGET_DELETE)
             ->and("chat_id > %s");
        $this->orderBy('timestamp');
        $this->prepare($values);
        $this->iterate();
    }
}
