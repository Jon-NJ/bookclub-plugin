<?php namespace bookclub;

/*
 * Class provides access to database fowarding table.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the forwards table.
 * 
 * @ORM\Table(name="bc_forwards")
 * @ORM\Entity
 */
class TableForwards extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var unique identifier for the message
     *
     * @ORM\Column(name="message_id", type="string", length=100, nullable=false, options={"default"="''"})
     */
    public $message_id = '';

    /**
     * @var int user ID of the recipient
     *
     * @ORM\Column(name="wordpress_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $wordpress_id = 0;

    /**
     * @var \DateTime timestamp of when the email was sent
     *
     * @ORM\Column(name="time_sent", type="datetime", nullable=false)
     */
    public $time_sent;

    /**
     * Initialize the object.
     * @return TableAuthors
     */
    public function __construct()
    {
        parent::__construct('forwards');
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
     * Check if a forward target for the given user already exists.
     * @param string $message_id unique message identifier
     * @param int $wordpress_id user identifier
     * @return bool true if record already exists
     */
    public static function exists(string $message_id, int $wordpress_id): bool
    {
        $forwards = new TableForwards();
        $forwards->select();
        $forwards->where('message_id = %s')
                 ->and('wordpress_id = %s');
        $forwards->prepare([$message_id, $wordpress_id]);
        $forwards->prepare([$message_id]);
        $forwards->iterate();
        return $forwards->fetch();
    }

    /**
     * Create a record for the given message and user.
     * @param string $message_id unique message identifier
     * @param int $wordpress_id user identifier
     */
    public static function target(string $message_id, int $wordpress_id): void
    {
        if (!self::exists($message_id, $wordpress_id)) {
            $forwards = new TableForwards();
            $forwards->message_id   = $message_id;
            $forwards->wordpress_id = $wordpress_id;
            $forwards->insert();
        }
    }

    /**
     * Set the given user as sent using the current timestamp.
     * @param string $message_id id of message being forwarded
     * @param int $wordpress_id id of the user who will be a recipient
     */
    public static function setSent(string $message_id, int $wordpress_id): void
    {
        $recipient = new TableForwards();
        $recipient->updateSet('time_sent = %s');
        $recipient->where('message_id = %s')
                  ->and('wordpress_id = %s');
        $recipient->prepare([date('Y-m-d H:i:s'), $message_id, $wordpress_id]);
        $recipient->execute();
    }

    /**
     * Start looping through recipients for the given message identifier.
     * @param string $message_id unique message identifier
     */
    public function loopMail(string $message_id): void
    {
        $this->select();
        $this->where('message_id' . ' = %s');
        $this->prepare([$message_id]);
        $this->iterate();
    }

    /**
     * Check the email status of the current recipient.
     * @return bool true if email was sent
     */
    public function isEMailSent(): bool
    {
        return (bool) $this->time_sent;
    }
}
