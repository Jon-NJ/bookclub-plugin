<?php namespace bookclub;

/*
 * Class provides access to database email table.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Description of TableEMails
 *
 * @ORM\Table(name="bc_emails")
 * @ORM\Entity
 */
class TableEMails extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var \DateTime timestamp for the creation of the email
     *
     * @ORM\Column(name="create_dt", type="datetime", nullable=false, options={"default"="current_timestamp()"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $create_dt;

    /**
     * @var int member id of the person who created the email
     *
     * @ORM\Column(name="member_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $member_id = 0;

    /**
     * @var string|null email subject line
     *
     * @ORM\Column(name="subject", type="string", length=120, nullable=true, options={"default"="NULL"})
     */
    public $subject = '';

    /**
     * @var string|null html body
     *
     * @ORM\Column(name="html", type="text", length=16777215, nullable=true, options={"default"="NULL"})
     */
    public $html = '';

    /**
     * Initialize the object.
     * @return \bookclub\TableEMails
     */
    public function __construct()
    {
        parent::__construct('emails');
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
     * Fetch record matching the creation timestamp.
     * @param string $created creation timestamp
     * @return TableEMails|null record if found
     */
    public static function findByCreateDate($created)
    {
        $email = new TableEMails();
        $email->findByString(['create_dt' => $created]);
        return $email->fetch() ? $email : null;
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
        parent::updateByString(['create_dt']);
    }

    /**
     * Delete the record for the email with the given timestamp.
     * @param string $created when the email was created
     */
    public static function deleteByTimestamp($created): void
    {
        $date = new TableEMails();
        $date->deleteByString(['create_dt' => $created]);
    }
}
