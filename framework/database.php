<?php namespace bookclub;

/*
 * This is the root file for the database library. It links in all other files
 * used for database access and additionally provides some general purpose
 * database functions. A global instance of class BookclubDatabase is defined
 * as bcdb.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

define('BC_RSVP_NORESPONSE', '0');
define('BC_RSVP_NOTATTENDING', '1');
define('BC_RSVP_ATTENDING', '2');
define('BC_RSVP_MAYBE', '3');

define('BC_GROUP_CLUB', 1);         // traditional book club groups
define('BC_GROUP_SELECT', 2);       // admin selected group members
define('BC_GROUP_WORDPRESS', 3);    // wordpress user subscription listserve
define('BC_GROUP_ANNOUNCEMENTS', 4);// wordpress user subscription from admins

define('BC_IMAP_NEW', 0);           // email registered but no processing yet
define('BC_IMAP_ACTIVE', 1);        // started sending
define('BC_IMAP_ERROR', 2);         // there was some error while processing
define('BC_IMAP_BOUNCE', 3);        // the email was sent back to the sender
define('BC_IMAP_IGNORE', 4);        // nothing to do, ignore
define('BC_IMAP_FINISHED', 5) ;     // forwarding complete

define('BC_IMAP_TARGET_NONE', 0);   // no target determined
define('BC_IMAP_TARGET_USER', 1);   // target is a single user
define('BC_IMAP_TARGET_GROUP', 2);  // target is a group    

require_once(BOOKCLUBPATH.DS.'database/DatabaseTable.php');
require_once_folder(BOOKCLUBPATH.DS.'database');

/**
 * Execute an SQL file with optional macros.
 * @param string $filename name of file containing SQL
 * @param array $macros a dictionary of macros
 * @return bool true if no errors
 */
function executeSQL(string $filename, array $macros = []): bool
{
    global $wpdb;
    $result = true;
    $logger = \Logger::getLogger('db.library');
    $logger->info("Execute $filename");

    $sql = macro_replace(file_get_contents($folder.DS.$filename), $macros);
    $logger->debug("START TRANSACTION");
    $wpdb->query("START TRANSACTION");
    foreach (explode(';', $sql) as $statement) {
        $statement=trim($statement);
        if ($statement) {
            $wpdb->query($statement);
            if ($wpdb->last_error) {
                $result = false;
                $logger->error($wpdb->last_error);
                $logger->error($statement);
                break;
            } else {
                $logger->debug($statement);
            }
        }
    }
    if ($wpdb->last_error) {
        $logger->debug("ROLLBACK TRANSACTION");
        $wpdb->query("ROLLBACK TRANSACTION");
    } else {
        $logger->debug("COMMIT");
        $wpdb->query("COMMIT");
    }
    return $result;
}

/**
 * Create a database table.
 * @param string $filename the name of the SQL file
 * @return bool true if no errors
 */
function createTable(string $filename): bool
{
    global $wpdb;

    $logger = \Logger::getLogger('db.library');
    $folder  = BOOKCLUBPATH.DS.'sql';

    $result = true;
    $base = substr($filename, 0, strlen($filename) - 4);
    $table = tablePrefix($base);
    if(!existsTable($table)) {
        $logger->info("Create $table");
        $result = executeSQL($folder.DS.$filename, [
            'table'   => $table,
            'engine'  => 'InnoDB',
            'charset' => $wpdb->get_charset_collate()]);
    }
    return $result;
}

/**
 * Check if table exists
 * @param string $tablename table name to check, prepended with prefix
 * @return bool true if table exists
 */
function existsTable(string $tablename): bool
{
    global $wpdb;
    return $wpdb->get_var("SHOW TABLES LIKE '$tablename'") === $tablename;
}

/**
 * Loop through files in the SQL folder to create the database tables.
 * @return bool true if no errors
 */
function createDatabase(): bool
{
    $folder  = BOOKCLUBPATH.DS.'sql';
    $result = true;
    foreach (new \DirectoryIterator($folder) as $file) {
        $filename = $file->getFilename();
        if (preg_match('/\.sql$/', $filename)) {
            if (!preg_match('/test/i', $filename) || is_development()) {
                if (!createTable($filename)) {
                    $result = false;
                }
            }
        }
    }
    return $result;
}

function updateReceiveFromOthers(): bool
{
    $logger = \Logger::getLogger('db.update');
    $tobj = new TableMembers();
    $tobj->loopForUsers();
    while ($tobj->fetch()) {
        if ($tobj->receive_others) {
            \update_user_meta($tobj->wordpress_id, 'bc_receive_others', 1);
            $logger->debug("Receive from others " . $tobj->wordpress_id);
        }
    }
    return true;
}

/**
 * Update the database to the current version.
 * @return bool true if no errors
 */
function updateDatabase(): bool
{
    global $wpdb;
    $result = true;
    $logger = \Logger::getLogger('db.library');
    $updates = json_decode(file_get_contents(BOOKCLUBPATH.DS.'updates.json'));
    $new_version = $updates->version;
    $option = 'db_version';
    $old_version = getOption($option);
    if ($old_version) {
        while ($old_version !== $new_version) {
            if (property_exists($updates, $old_version)) {
                $update = $updates->{ $old_version };
                $next_version = $update->next;
                $logger->info("Updating $old_version to $next_version");
                foreach ($update->steps as $step) {
                    $parts = preg_split('/\s+/', $step);
                    if ('table' === $parts[0]) {
                        createTable($parts[1].'.sql');
                    }
                    elseif ('exec' === $parts[0]) {
                        if (!call_user_func(__NAMESPACE__ . '\\' . $parts[1])) {
                            $result = false;
                        }
                    } else {
                        $sql = macro_replace($step, [
                            'prefix' => tablePrefix('')
                        ]);
                        $logger->info($sql);
                        $wpdb->get_results($sql);
                        if ($wpdb->last_error) {
                            $logger->error($wpdb->last_error);
                            $result = false;
                        }
                    }
                }
                $old_version = $next_version;
            } else {
                break;
            }
        }
    }
    setOption($option, $new_version);
    return $result;
}

/**
 * Adds role "Bookclub Admin" (slug bookclub_admin) if not defined.
 * @param string $base optional slug for role to base the bookclub admin on
 * (defaults to editor)
 */
function addBookclubAdmin(string $base = 'editor'): void
{
    $bc_admin = \get_role('bookclub_admin');
    if (!$bc_admin) {
        $admin_capabilities = [
            'edit_bc_authors', 'edit_bc_books', 'edit_bc_covers',
            'edit_bc_dates', 'edit_bc_emails', 'edit_bc_events',
            'edit_bc_groups', 'edit_bc_members', 'edit_bc_news',
            'edit_bc_places'];
        $clone_capabilities = [];
        $editor = \get_role('editor');
        $admin  = \get_role('administrator');

        foreach ($editor->capabilities as $key => $value) {
            $clone_capabilities[$key] = $value;
        }
        foreach ($admin_capabilities as $key) {
            $clone_capabilities[$key] = true;
            $admin->add_cap($key, true);
        }
        $bookclub_admin = add_role(
                'bookclub_admin', 'Bookclub Admin', $clone_capabilities);
    }
}

/**
 * Check if the given status reserves a seat (yes or maybe).
 * @param int $status RSVP status number
 * @return bool true if the person is reserving a seat (YES or MAYBE)
 */
function isReservedRSVP(int $status): bool
{
    return BC_RSVP_ATTENDING == $status || BC_RSVP_MAYBE == $status;
}

/**
 * Fetch the title of the book from the database.
 * @param int $bid book identifier
 * @return string title of the book
 */
function getBookTitle(int $bid): string
{
    $book = TableBooks::findByID($bid);
    return $book->title;
}

/**
 * Fetch the list of book club administrators.
 * @return array JSON administrator collection (name, WP id)
 */
function getAdministrators(): array
{
    $admins = [];
    $query  = new \WP_User_Query(
            ['role__in' => ['administrator', 'bookclub_admin']]);
    $users  = $query->get_results();
    foreach ($users as $user) {
        $admins[] = [
            'name' => $user->first_name . ' ' . $user->last_name,
            'id'   => $user->ID
        ];
    }
    return $admins;
}

/**
 * Fetch the list of book authors from the database.
 * @return array JSON author collection (name,id)
 */
function getAuthors(): array
{
    $authors = [];
    $bcAuthors = new TableAuthors();
    $bcAuthors->loopByName();
    while ($bcAuthors->fetch()) {
        $authors[] = [
            'name' => $bcAuthors->name,
            'id'   => $bcAuthors->author_id
        ];
    }
    return $authors;
}

/**
 * Fetch the list of books from the database.
 * @return array JSON book collection (bookid,title)
 */
function getBooks(): array
{
    $books = [];
    $bcBooks = new TableBooks();
    $bcBooks->loopByTitle();
    while ($bcBooks->fetch()) {
        $books[] = [
            'bookid'   => $bcBooks->book_id,
            'title'    => $bcBooks->title
        ];
    }
    return $books;
}

/**
 * Fetch the filenames of the book covers from the file system.
 * @return array JSON cover filename collection
 */
function getCovers(): array
{
    $covers = [];
    $folder = folder_covers();
    if (file_exists($folder)) {
        foreach (new \DirectoryIterator($folder) as $file) {
            if (!$file->isDot()) {
                $covers[] = $file->getFilename();
            }
        }
    }
    usort($covers, 'strcasecmp');
    return $covers;
}

/**
 * Fetch the normal book club groups.
 * @param int $type 0=all groups 1=BC_GROUP_CLUB bookclub group,
 * @return array JSON group collection
 */
function getGroups(int $type = 0): array
{
    $groups = [];
    $tobj = new TableGroups();
    $tobj->loopForType($type);
    while ($tobj->fetch()) {
        $groups[] = [
            'groupid'     => $tobj->group_id,
            'tag'         => $tobj->tag,
            'description' => $tobj->description,
            'url'         => $tobj->url
        ];
    }
    return $groups;
}

/**
 * Fetch the specified option.
 * @param string $option_name the name of the option
 * @param string $default optional default value
 * @return string option value
 */
function getOption(string $option_name, string $default = ''): string
{
    return \get_option('bc_' . $option_name, $default);
}

/**
 * Set the specified option.
 * @param string $option_name the name of the option
 * @param string $new_value new option value
 */
function setOption(string $option_name, string $new_value): void
{
    \update_option('bc_' . $option_name, $new_value);
}

/**
 * Fetch the specified option, split it into lines.
 * @param string $option_name the name of the option
 * @param string $default optional default value
 * @return array the lines of the option
 */
function splitOption(string $option_name, string $default = ''): array
{
    return preg_split('/\r\n|\n|\r/',
                getOption($option_name, $default), -1, PREG_SPLIT_NO_EMPTY);
}

/**
 * Fetch the list of places from the database.
 * @return array JSON places collection (placeid,place)
 */
function getPlaces(): array
{
    $places = [];
    $bcPlaces = new TablePlaces();
    $bcPlaces->loopByID();
    while ($bcPlaces->fetch()) {
        $places[] = [
            'placeid' => $bcPlaces->place_id,
            'place'   => $bcPlaces->place
        ];
    }
    return $places;
}

/**
 * Add table name prefix and concatenate field.
 * @param string $tablename base table name without prefix
 * @param string $fieldname field name
 * @return string table.field
 */
function tableField(string $tablename, string $fieldname): string
{
    return tablePrefix($tablename) . '.' . $fieldname;
}

/**
 * Add table name prefix.
 * @param string $tablename base table name without prefix, if starts with "\"
 * it is a WordPress table
 * @return string table name with prefix
 */
function tablePrefix(string $tablename): string
{
    global $wpdb;
    if ('\\' === $tablename[0]) {
        $prefix = $wpdb->prefix;
        $tablename = substr($tablename, 1);
    } else {
        $prefix = $wpdb->prefix . 'bc_';
    }
    return $prefix . $tablename;
}

/**
 * Update the RSVP status for an event of the person specified in the RSVP
 * record.
 * @param \bookclub\TableEvents $event record of the event
 * @param \bookclub\TableParticipants $rsvp RSVP record
 * @param int $status new RSVP status
 * @param string|null $comment optional comment for RSVP
 * @param callable $callback function to call if someone removed from the
 * waiting list callback(TableMembers, TableEvents)
 * @return JoinMembersUsers|null the person who got a notification if any
 */
function updateRSVP(TableEvents $event, TableParticipants $rsvp, int $status,
        ?string $comment, callable $callback): ?JoinMembersUsers
{
    $lucky_person = null;
    // write a status record
    TableRSVPs::add($rsvp->event_id, $rsvp->member_id, $status);
    TableLogs::addLog(['RSVP', $status,
        $rsvp->event_id,
        $rsvp->member_id], $comment ? $comment : '- no comment -');
    // waiting list only if there is a limit and this is a change from before
    if (($event->max_attend != 0) &&
            ((isReservedRSVP($status) != isReservedRSVP($rsvp->rsvp)))) {
        // are they coming - yes or maybe?
        if (isReservedRSVP($status)) {
            // is there room for them?
            if ($event->rsvp_attend >= $event->max_attend) {
                // no - add to waiting list
                $rsvp->waiting = 1;
                $rsvp->modtime = date('Y-m-d H:i:s', time());
            } else {
                // yes - increase count of attending
                $event->rsvp(1);
            }
            // were they on the waiting list?
        } elseif ($rsvp->waiting) {
            // yes - take them off the list
            $rsvp->waiting = 0;
            // no - move someone off waiting list
            // check if someone found and there is room
            // note that the attend check kicks in if the max allowed is lower than current rsvp count
        } elseif ($event->rsvp_attend - 1 < $event->max_attend) {
            $lucky_guy = TableParticipants::getNextWaiting($rsvp->event_id);
            if ($lucky_guy) {
                $lucky_person = JoinMembersUsers::findByID($lucky_guy);
                call_user_func($callback, $lucky_guy, $event);
            } else {
                // decrease the count of attending
                $event->rsvp(-1);
            }
        } else {
            // decrease the count of attending
            $event->rsvp(-1);
        }
    }

    // store new status, comment and anything else that may have changed in the record
    $rsvp->rsvp = $status;
    if (!is_null($comment)) {
        $rsvp->comment = $comment;
    }
    $rsvp->update();
    return $lucky_person;
}
