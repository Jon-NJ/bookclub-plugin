<?php namespace bookclub;

/*
 * Class provides access to joined database emails, members, users and usermeta tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined emails, members, users and usermeta tables.
 */
class JoinEMailsMembersUsers extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinEMailsMembersUsers
     */
    public function __construct()
    {
        parent::__construct('emails');
        parent::join('members',
            tableField('emails', 'member_id') . ' = ' . tableField('members', 'member_id'));
        parent::join('\users',
            tableField('members', 'wordpress_id') . ' = ' . tableField('\users', 'ID'));
        parent::join('\usermeta AS f',
            tableField('members', 'wordpress_id') . " = f.user_id AND f.meta_key = 'first_name'");
        parent::join('\usermeta AS l',
            tableField('members', 'wordpress_id') . " = l.user_id AND l.meta_key = 'last_name'");
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
     * Fetch record matching the creation timestamp.
     * @param string $created creation datetime
     * @return JoinEMailsMembersUsers|null record if found
     */
    public static function findByCreateDate($created)
    {
        $email = new JoinEMailsMembersUsers();
        $email->findByString([tableField('emails', 'create_dt') => $created]);
        return $email->fetch() ? $email : null;
    }

    /**
     * Start looping through emails and member tables matching the search criteria.
     * @param int|null $age maximum age of the email in months
     * @param string|null $author name of the member who created the email
     * @param string|null $subject subject of the email
     * @param string|null $body HTML body of the email
     */
    public function loopSearch($age, $author, $subject, $body): void
    {
        $fields = [];
        $values = [];
        if ($age) {
            $this->addSearch($fields, $values, tableField('emails', 'create_dt') . ' > %s',
                    date('Y-m-d', strtotime("-$age month")), false);
        }
        $this->addSearch($fields, $values,
                'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) LIKE %s", $author);
        $this->addSearch($fields, $values, tableField('emails', 'subject') . ' like %s', $subject);
        $this->addSearch($fields, $values, tableField('emails', 'html') . ' like %s', $body);
        $this->select('',
            'IF(ISNULL(' . tableField('\users', 'ID') . '),' .
                    tableField('members', 'name') . ', ' .
                    "CONCAT(f.meta_value, ' ', l.meta_value)) AS fullname");
        $this->where($fields);
        $this->orderBy(tableField('emails', 'create_dt') . ' DESC');
        $this->prepare($values);
        $this->iterate();
    }
}
