<?php namespace bookclub;

/*
 * Class provides access to database recipients table. This is used to track
 * delivery status of email recipients.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the recipients table.
 *
 * @ORM\Table(name="bc_recipients")
 * @ORM\Entity
 */
class TableRecipients extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var \DateTime timestamp for the creation of the email
     *
     * @ORM\Column(name="create_dt", type="datetime", nullable=false, options={"default"="current_timestamp()"})
     */
    public $create_dt;

    /**
     * @var int member_id id of the member who received the email
     *
     * @ORM\Column(name="member_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $member_id = 0;

    /**
     * @var \DateTime|null timestamp when the email was sent or null or empty string
     *
     * @ORM\Column(name="email_sent", type="datetime", nullable=true, options={"default"="NULL"})
     */
    public $email_sent;

    /**
     * Initialize the object.
     * @return \bookclub\TableRecipients
     */
    public function __construct()
    {
        parent::__construct('recipients');
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
     * Remove the given member as a recipient of the given email.
     * @param string $created timestamp of the email
     * @param int $member_id id of the member who is a recipient
     */
    public static function deleteRecipient($created, $member_id): void
    {
        $recipient = new TableRecipients();
        $recipient->deleteByString(['create_dt' => $created,
                               'member_id' => $member_id]);
    }

    /**
     * Delete all records for the given recipient.
     * @param int $member_id unique identifier for the member
     */
    public static function deleteByID(int $member_id): void
    {
        $recipient = new TableRecipients();
        $recipient->deleteByString(['member_id' => $member_id]);
    }

    /**
     * Delete all recipients for the email with the given timestamp.
     * @param string $created datetime when the email was created
     */
    public static function deleteByTimestamp($created): void
    {
        $recipient = new TableRecipients();
        $recipient->deleteByString(['create_dt' => $created]);
    }

    /**
     * Clear the send flag for the given recipient.
     * @param string $created datetime when the email was created
     * @param int $member_id id of the recipient for the email
     */
    public static function clearRecipient($created, $member_id): void
    {
        $recipient = new TableRecipients();
        $recipient->updateSet('email_sent = NULL');
        $recipient->where('create_dt = %s')
                  ->and('member_id = %s');
        $recipient->prepare([$created, $member_id]);
        $recipient->execute();
    }

    /**
     * All recipients are set to unsent for the email with the given timestamp.
     * @param string $created datetime when the email was created
     */
    public static function clearByTimestamp($created): void
    {
        $recipient = new TableRecipients();
        $recipient->updateSet('email_sent = NULL');
        $recipient->where('create_dt = %s');
        $recipient->prepare([$created]);
        $recipient->execute();
    }

    /**
     * Add the given member as a recipient of the given email.
     * @param string $create_dt timestamp of the email
     * @param int $member_id id of the member who will be a recipient
     */
    public static function addRecipient($create_dt, $member_id): void
    {
        $recipient = new TableRecipients();
        $recipient->create_dt = $create_dt;
        $recipient->member_id = $member_id;
        $recipient->email_sent = null;
        $recipient->insert();
    }

    /**
     * Set the given member as sent using the current timestamp
     * @param string $create_dt timestamp of the email
     * @param int $member_id id of the member who will be a recipient
     */
    public static function setSent($create_dt, int $member_id): void
    {
        $recipient = new TableRecipients();
        $recipient->updateSet('email_sent = %s');
        $recipient->where('create_dt = %s')
                  ->and('member_id = %s');
        $recipient->prepare([date('Y-m-d H:i:s'), $create_dt, $member_id]);
        $recipient->execute();
    }

    /**
     * Count the number of recipients who have a sent timestamp.
     * @param string $create_dt timestamp of the email
     * @return int how many recipients have been sent the email
     */
    public static function getSentCount($create_dt): int
    {
        $recipient = new TableRecipients();
        $recipient->select('count(*) AS count');
        $recipient->where('create_dt = %s')
                  ->and('email_sent is not null');
        $recipient->prepare([$create_dt]);
        $rows = $recipient->execute();
        return (int) $rows[0]->count;
    }

    /**
     * Count the number of recipients who don't have a sent timestamp.
     * @param string $create_dt timestamp of the email
     * @return int how many recipients have not yet been sent the email
     */
    public static function getUnsentCount($create_dt): int
    {
        $recipient = new TableRecipients();
        $recipient->select('count(*) AS count');
        $recipient->where('create_dt = %s')
                  ->and('email_sent is null');
        $recipient->prepare([$create_dt]);
        $rows = $recipient->execute();
        return (int) $rows[0]->count;
    }
}
