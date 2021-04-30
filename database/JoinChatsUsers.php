<?php namespace bookclub;

/*
 * Class provides access to joined database chats, users and usermeta tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined chats, users and usermeta tables.
 */
class JoinChatsUsers extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinChatsUsers
     */
    public function __construct()
    {
        parent::__construct('chats');
        parent::join('\users',
            tableField('chats', 'wordpress_id') . ' = ' . tableField('\users', 'ID'));
        parent::join('\usermeta AS f',
            tableField('chats', 'wordpress_id') . " = f.user_id AND f.meta_key = 'first_name'");
        parent::join('\usermeta AS l',
            tableField('chats', 'wordpress_id') . " = l.user_id AND l.meta_key = 'last_name'");
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
     * Loop through chat messages between the current user and a partner.
     * @param int $first WordPress id of this user
     * @param int $second WordPress id of chat partner
     * @param string $start optional start chat id or timestamp
     */
    public function loopForDirectMessages(int $first, int $second,
            string $start = ''): void
    {
        $values = [$first, $second, $first, $second];
        $extra = '';
        if ($start) {
            if (is_numeric($start)) {
                $extra = " AND chat_id > %s";
                $values[] = $start;
            } else {
                $extra = " AND timestamp >= %s";
                $values[] = $start;
            }
        }
        $this->select('', "CONCAT(f.meta_value, ' ', l.meta_value) AS fullname");
        $this->where("target_type = " . BC_CHAT_TARGET_USER .
                " AND ((wordpress_id = %s AND target_id = %s)" .
                " OR (target_id = %s AND wordpress_id = %s))" . $extra);
        $this->orderBy('timestamp');
        $this->prepare($values);
        $this->iterate();
    }

    /**
     * Loop through chat messages for the given type and identifier.
     * @param int $target_type 2-BC_CHAT_TARGET_GROUP, 3-BC_CHAT_TARGET_BOOK,
     * 4-BC_CHAT_TARGET_EVENT
     * @param string $target_id identifier for chat
     * @param string $start optional start chat id or timestamp
     */
    public function loopForTarget(int $target_type, string $target_id,
            string $start = ''): void
    {
        $values = [$target_type, $target_id];
        $fields = ['target_type = %s', 'target_id = %s'];
        if ($start) {
            if (is_numeric($start)) {
                $values[] = $start;
                $fields[] = 'chat_id > %s';
            } else {
                $values[] = $start;
                $fields[] = 'timestamp >= %s';
            }
        }
        $this->select('', "CONCAT(f.meta_value, ' ', l.meta_value) AS fullname");
        $this->where($fields);
        $this->orderBy('timestamp');
        $this->prepare($values);
        $this->iterate();
    }
}
