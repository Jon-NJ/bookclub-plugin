<?php namespace bookclub;

/*
 * Class provides access to joined member, users, usermeta and recipients tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined member, users, usermeta and recipients tables.
 */
class JoinMembersUsersRecipients extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinMembersUsersRecipients
     */
    public function __construct()
    {
        parent::__construct('members');
        parent::join('\users',
            tableField('members', 'wordpress_id') . ' = ' . tableField('\users', 'ID'));
        parent::join('\usermeta AS f',
            tableField('members', 'wordpress_id') . " = f.user_id AND f.meta_key = 'first_name'");
        parent::join('\usermeta AS l',
            tableField('members', 'wordpress_id') . " = l.user_id AND l.meta_key = 'last_name'");
        /**
         * EMail created time is a JOIN condition instead of a WHERE condition
         * to ensure that all participants are included in the result set
         * because it is a LEFT JOIN. The fields from an unmatched join are NULL.
         */

        parent::join('recipients',
            tableField('members', 'member_id') . ' = ' . tableField('recipients', 'member_id') . ' AND ' .
            tableField('recipients', 'create_dt') . ' = %s');
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
     * Fetch the number of rows returned from last query.
     * @return int number of rows returned from last result set
     */
    public function count(): int
    {
        return parent::count();
    }

    /**
     * Check the email status of the current recipient.
     * @return bool true if email was sent
     */
    public function isEMailSent(): bool
    {
        return (bool) $this->email_sent;
    }

    /**
     * Check the recipient status of the current member.
     * @return bool true if this person is a recipient
     */
    public function isRecipient(): bool
    {
            return !is_null($this->create_dt);
    }

    /**
     * Start looping through recipients that received the email.
     * @param string $create_dt creation timestamp
     */
    public function loopSent($create_dt): void
    {
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->where(tableField('recipients', 'email_sent') . ' is not null');
        $this->orderBy(tableField('recipients', 'email_sent'));
        $this->prepare([$create_dt]);
        $this->iterate();
    }

    /**
     * Start looping through recipients that haven't received the email.
     * @param string $create_dt creation timestamp
     */
    public function loopUnsent($create_dt): void
    {
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->where(tableField('recipients', 'create_dt') . ' is not null')
             ->and(tableField('recipients', 'email_sent') . ' is null');
        $this->orderBy(tableField('recipients', 'email_sent'));
        $this->prepare([$create_dt]);
        $this->iterate();
    }

    /**
     * Start looping through participants to get their status for a given email.
     * @param string $create_dt creation timestamp of the email
     */
    public function loopRecipientForEmail($create_dt): void
    {
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->orderBy('fullname');
        $this->prepare([$create_dt]);
        $this->iterate();
    }
}
