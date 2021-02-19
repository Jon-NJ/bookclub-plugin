<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_dates' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuDates extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuDates
     */
    public function __construct()
    {
        parent::__construct('bc_menu_dates',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Meeting Dates',
                'menu_name'   => 'Dates',
                'menu_rank'   => RANK_DATES,
                'capability'  => 'edit_bc_dates',
                'slug'        => 'bc_dates',
                'help'        => 'menu_dates',
                'script'      => 'menu_dates.js',
                'style'       => 'menu_dates.css',
                'nonce'       => 'dates_nonce',
                'actions'     => [[
                        'key' => 'wp_ajax_bc_dates_add',
                        'function' => 'dates_add'
                    ],[
                        'key' => 'wp_ajax_bc_dates_save',
                        'function' => 'dates_save'
                    ],[
                        'key' => 'wp_ajax_bc_dates_delete',
                        'function' => 'dates_delete'
                    ],[
                        'key'      => 'wp_ajax_bc_dates_lookup_author',
                        'function' => 'dates_lookup_author'
                    ],[
                        'key'      => 'wp_ajax_bc_dates_lookup_book',
                        'function' => 'dates_lookup_book'
                    ],[
                        'key'      => 'wp_ajax_bc_dates_lookup_place',
                        'function' => 'dates_lookup_place'
                    ],[
                        'key'      => 'wp_ajax_bc_dates_event',
                        'function' => 'dates_event'
                    ]]
            ]);
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @global string|null $_GET['action'] 'edit', 'search' or empty
     * @return string HTML content
     */
    public function render(): string
    {
        if (!parent::enqueue()) {
            return '';
        }
        $action = input_get('action');
        if ('search' === $action) {
            $json = $this->executeSearch();
        } elseif ('edit' === $action) {
            $json = $this->executeEdit();
        } else {
            $json = $this->executeStart();
        }
        return twig_render('menu_dates', $json);
    }

    /**
     * Fetch JSON used for start state.
     * @return array JSON for TWIG rendering
     */
    private function executeStart(): array
    {
        $nonce = $this->create_nonce();
        $json = [
            'nonce'         => $nonce,
            'admin_url'     => url_admin_post(),
            'referer'       => url_menu('bc_dates'),
            'title'         => \get_admin_page_title(),
            'images'        => url_images(),
            'mode'          => 'start',
            'groups'        => getGroups(BC_GROUP_CLUB),
            'group_id'      => 0,
            'authors'       => getAuthors(),
            'age'           => 6,
            'date'          => '',
            'book'          => '',
            'books'         => getBooks(),
            'author'        => '',
            'place'         => '',
            'places'        => getPlaces(),
            'calmonth'      => date('Y-m-d',
                                    strtotime('first day of this month')),
            'start_of_week' => get_start_of_week()
            ];
        return $json;
    }

    /**
     * A specified template is used to generate a text with the current date
     * object.
     * @param string $template template string
     * @param int $groupid group identifier
     * @param string $date meeting date
     * @param int $bookid book identifier
     * @param $placeid $place place identifier
     * @return string empty if no template, otherwise applied template
     */
    private function apply_template(object $join, string $template): string
    {
        $result = '';
        if ($template) {
            $jgroup = [
                'id'          => $join->group_id,
                'tag'         => $join->tag,
                'description' => $join->description
            ];
            $jbook = [
                'id'           => $join->book_id,
                'authorid'     => $join->author_id,
                'title'        => $join->title,
                'author'       => $join->name
            ];
            $jplace = [
                'id     '      => $join->place_id,
                'name'         => $join->place,
                'address'      => $join->address,
                'directions'   => $join->directions                
            ];
            try {
                $t = twig_template($template);
                $result = twig_render($t, [
                    'date'  => $join->day,
                    'group' => $jgroup,
                    'book'  => $jbook,
                    'place' => $jplace,
                    'name'  => '{{name}}',
                    'first' => '{{first}}',
                    'last'  => '{{last}}'
                ]);
            } catch (\Exception $e) {
                $this->log_error('(' . get_class($e) . ') ' . $e->getMessage() . ' ' . $template);
            }
        }
        return $result;
    }
    

    /**
     * Fetch JSON used for the edit state.
     * @global string $_GET['group'] group id 1 to 4
     * @global string $_GET['date'] date of meeting
     * @global string $_GET['book'] book identifier
     * @return array JSON for TWIG rendering
     */
    private function executeEdit(): array
    {
        $group    = input_request('group');
        $date     = input_request('date');
        $book     = input_request('book');
        $join     = JoinDatesBooksAuthorsPlacesGroups::findByDateGroupAndBook(
                        $date, $group, $book);
        $calmonth = substr($date, 0, 8) . '01';
        $nonce    = $this->create_nonce();
        $json     = [
            'nonce'         => $nonce,
            'admin_url'     => url_admin_post(),
            'referer'       => url_menu('bc_dates'),
            'bc_events'     => url_menu('bc_events'),
            'title'         => \get_admin_page_title(),
            'images'        => url_images(),
            'mode'          => 'edit',
            'groups'        => getGroups(BC_GROUP_CLUB),
            'group_id'      => $group,
            'date'          => $date,
            'book'          => $join->title,
            'book_id'       => $book,
            'books'         => getBooks(),
            'author'        => $join->name,
            'author_id'     => $join->author_id,
            'authors'       => getAuthors(),
            'place'         => $join->place_id ? $join->place : '',
            'place_id'      => $join->place_id,
            'places'        => getPlaces(),
            'calmonth'      => $calmonth,
            'start_of_week' => get_start_of_week(),
            'hide'          => $join->hide,
            'private'       => $join->private,
            'priority'      => $join->priority
            ];
        $eventid  = $this->apply_template($join, $join->t_event_id);
        if ($eventid) {
            $json['eventid'] = $eventid;
            $event = TableEvents::findByID($eventid);
            if ($event) {
                $json['exists']    = true;
                $json['eventlink'] = url_event_edit($eventid);
            }
        }
        return $json;
    }

    /**
     * Search database with the provided parameters.
     * @param string|null $groupid optional exact group identifier
     * @param string|null $age optional maximum age in months
     * @param string|null $date optional exact meeting date
     * @param string|null $book optional partial book title
     * @param string|null $author optional partial author name
     * @param string|null $place optional partial place name
     * @return array JSON search results
     */
    private function search(?string $groupid, ?string $age, ?string $date,
            ?string $book, ?string $author, ?string $place): array
    {
        $results = [];
        $iterator = new JoinDatesBooksAuthorsPlacesGroups();
        $iterator->loopSearch($groupid, $age, $date, $book, $author, $place);
        $line = 0;
        $today = strtotime(date('Y-m-d'));
        while ($iterator->fetch()) {
            $results[] = [
                'line'     => $line++,
                'date'     => $iterator->day,
                'group_id' => $iterator->group_id,
                'place'    => $iterator->place,
                'place_id' => $iterator->place_id,
                'book'     => $iterator->title,
                'book_id'  => $iterator->book_id,
                'author'   => $iterator->name,
                'hide'     => $iterator->hide,
                'private'  => $iterator->private,
                'priority' => $iterator->priority,
                'past'     => strtotime($iterator->day) < $today
            ];
        }
        return $results;
    }

    /**
     * Fetch JSON used for the search state.
     * @global string|null $_GET['groupid'] optional exact group identifier
     * @global string|null $_GET['age'] optional maximum age in months
     * @global string|null $_GET['date'] optional exact meeting date
     * @global string|null $_GET['book'] optional partial book title
     * @global string|null $_GET['author'] optional partial author name
     * @global string|null $_GET['place'] optional partial place name
     * @global string|null $_GET['calmonth'] YYYY-MM-DD first date of currently
     * selected calendar month
     * @return array JSON for TWIG rendering
     */
    private function executeSearch(): array
    {
        $groupid = input_request('groupid');
        if (!$groupid) {
            $groupid = 0;
        }
        $age      = input_request('age');
        $date     = input_request('date');
        $book     = input_request('book');
        $author   = input_request('author');
        $place    = input_request('place');
        $calmonth = input_request('calmonth');
        $nonce    = $this->create_nonce();
        $json = [
            'nonce'         => $nonce,
            'admin_url'     => url_admin_post(),
            'referer'       => url_menu('bc_dates'),
            'title'         => \get_admin_page_title(),
            'images'        => url_images(),
            'mode'          => 'search',
            'groups'        => getGroups(BC_GROUP_CLUB),
            'group_id'      => $groupid,
            'age'           => $age,
            'date'          => $date,
            'author'        => $author,
            'authors'       => getAuthors(),
            'book'          => $book,
            'books'         => getBooks(),
            'place'         => $place,
            'places'        => getPlaces(),
            'calmonth'      => $calmonth,
            'start_of_week' => get_start_of_week()
            ];
        $json['found'] = $this->search($groupid, $age, $date, $book,
                $author, $place);
        return $json;
    }

    /** AJAX functions */

    /**
     * Add a date to the database.
     * @param int $groupid group identifier
     * @param string $day date of the meeting YYYY-MM-DD
     * @param int $bookid book identifier
     * @param int $placeid place identifier or zero
     * @param bool $hideflag 0-not hidden,1-hidden
     * @param bool $private 0-public,1-private
     */
    private function insert(int $groupid, string $day, int $bookid,
            int $placeid, bool $hideflag, bool $private): void
    {
        $date = new TableDates();
        $date->group_id = $groupid;
        $date->day      = $day;
        $date->book_id  = $bookid;
        $date->place_id = $placeid;
        $date->hide     = $hideflag;
        $date->private  = $private;
        $date->priority = 0;
        $date->insert();
    }

    /**
     * Add a date to the database. Generate a JSON response.
     * @global string $_REQUEST['groupid'] group identifier
     * @global string $_REQUEST['bookid'] book identifier
     * @global string $_REQUEST['date'] date of the meeting YYYY-MM-DD
     * @global string $_REQUEST['placeid'] place identifier or zero
     * @global string $_REQUEST['hideflag'] 0-not hidden,1-hidden
     * @global string $_REQUEST['private'] 0-public,1-private
     */
    public function dates_add(): void
    {
        $response = $this->check_request('Add date');
        if (!$response) {
            $group = input_request('groupid');
            $book  = input_request('bookid');
            $day   = input_request('date');
            $date  = TableDates::findByDateGroupAndBook($day, $group, $book);
            if ($date) {
                $response = $this->get_response(true,
                        'Event for date/group/book exists.');
                $this->log_error("Date already exists $day, $group, $book");
            } else {
                $this->insert($group, $day, $book, input_request('placeid'),
                        input_request('hideflag'), input_request('private'));
                $this->log_info("Date added $day, $group, $book");
                $response = $this->get_response(false, 'Date added');
                $response['date']  = $day;
                $response['group'] = $group;
                $response['book']  = $book;
            }
        }
        exit(json_encode($response));
    }

    /**
     * Delete a date from the database. Generate a JSON response.
     * @global string $_REQUEST['groupid'] group identifier
     * @global string $_REQUEST['bookid'] book identifier
     * @global string $_REQUEST['date'] date of the meeting YYYY-MM-DD
     */
    public function dates_delete(): void
    {
        $response = $this->check_request('Delete date');
        if (!$response) {
            $group = input_request('groupid');
            $book  = input_request('bookid');
            $day   = input_request('date');
            TableDates::deleteByDateGroupAndBook($day, $group, $book);
            $this->log_info("Delete date $day, $group, $book");
            $response = $this->get_response(false, 'Date deleted');
        }
        exit(json_encode($response));
    }

    /**
     * Update meeting information. Generate a JSON response.
     * @global string $_REQUEST['groupid'] group identifier
     * @global string $_REQUEST['original_groupid'] original group identifier
     * @global string $_REQUEST['date'] meeting date
     * @global string $_REQUEST['original_date'] original meeting date
     * @global string $_REQUEST['bookid'] book identifier
     * @global string $_REQUEST['original_bookid'] original book identifier
     * @global string $_REQUEST['placeid'] place identifier
     * @global string $_REQUEST['hideflag']  0-not hidden,1-hidden
     * @global string $_REQUEST['private']  0-public,1-private
     * @global string $_REQUEST['priority']  hours before public
     */
    public function dates_save(): void
    {
        $response = $this->check_request('Save date');
        if (!$response) {
            $group  = input_request('groupid');
            $ogroup = input_request('original_groupid');
            $day    = input_request('date');
            $oday   = input_request('original_date');
            $book   = input_request('bookid');
            $obook  = input_request('original_bookid');
            $url    = '';
            if (($group != $ogroup) || ($day != $oday) || ($book != $obook)) {
                $date   = TableDates::findByDateGroupAndBook(
                                $day, $group, $book);
                if ($date) {
                    $response = $this->get_response(true,
                            'Event for date/group/book exists.');
                    $this->log_error(
                            "Date update collision $day, $group, $book");
                } else {
                    $date = TableDates::findByDateGroupAndBook(
                            $oday, $ogroup, $obook);
                    $date->reschedule($day, $group, $book);
                    $this->log_info(
                            "Date moved $oday, $ogroup, $obook => $day, $group, $book");
                    $url = input_request('referer') .
                            "&action=edit&date=$day&group=$group&book=$book";
                }
            }
            if (!$response) {
                $date   = TableDates::findByDateGroupAndBook($day, $group, $book);
                $date->place_id = input_request('placeid');
                $date->hide     = input_request('hideflag');
                $date->private  = input_request('private');
                $date->priority = input_request('priority');
                $date->update();
                $this->log_info("Date updated $day, $group, $book");
                $response = $this->get_response(false, 'Date updated', $url);
            }
        }
        exit(json_encode($response));
    }

    /**
     * Find the author, fetch identifier or zero if not found. Generate a JSON
     * response.
     * @global string $_REQUEST['author'] author name
     */
    public function dates_lookup_author(): void
    {
        $response = $this->check_request('Lookup author');
        if (!$response) {
            $name   = input_request('author');
            $author = TableAuthors::findByName($name);
            if ($author) {
                $response = $this->get_response(false, '');
                $response['author_id'] = $author->author_id;
            } else {
                $response = $this->get_response(true,
                        "Author not found $author");
            }
        }
        exit(json_encode($response));
    }

    /**
     * Find the book, fetch identifier or zero if not found. Generate a JSON
     * response.
     * @global string $_REQUEST['book'] book title
     */
    public function dates_lookup_book(): void
    {
        $response = $this->check_request('Lookup book');
        if (!$response) {
            $title   = input_request('book');
            $book = JoinBooksAuthors::findBookByTitle($title);
            if ($book) {
                $response = $this->get_response(false, '');
                $response['book_id'] = $book->book_id;
                $response['author_id'] = $book->author_id;
                $response['author'] = $book->name;
            } else {
                $response = $this->get_response(true,
                        "Book not found $title");
            }
        }
        exit(json_encode($response));
    }

    /**
     * Find the place, fetch identifier or zero if not found. Generate a JSON
     * response.
     * @global string $_REQUEST['place'] place name
     */
    public function dates_lookup_place(): void
    {
        $response = $this->check_request('Lookup place');
        if (!$response) {
            $name   = input_request('place');
            if (!$name) {
                $response = $this->get_response(false, '');
                $response['place_id'] = 0;
            } else {
                $place = TablePlaces::findByPlace($name);
                if ($place) {
                    $response = $this->get_response(false, '');
                    $response['place_id'] = $place->place_id;
                } else {
                    $response = $this->get_response(true,
                            "Place not found $name");
                }
            }
        }
        exit(json_encode($response));
    }

    /**
     * If the create/update event button exists, the function handles the action
     * when it is pressed.
     * @global string $_REQUEST['groupid'] group identifier
     * @global string $_REQUEST['date'] event date
     * @global string $_REQUEST['bookid'] book identifier
     * @global string $_REQUEST['bc_events'] base URL for the events menu page
     */
    public function dates_event(): void
    {
        $response = $this->check_request('Create/Update event');
        if (!$response) {
            $group  = input_request('groupid');
            $date   = input_request('date');
            $book   = input_request('bookid');
            $member = TableMembers::findByWordpressID(\get_current_user_id());
            $join   = JoinDatesBooksAuthorsPlacesGroups::findByDateGroupAndBook(
                            $date, $group, $book);
            $eventid  = $this->apply_template($join, $join->t_event_id);
            if ($eventid) {
                $json['eventid'] = $eventid;
                $event = TableEvents::findByID($eventid);
                $new = false;
                if (!$event) {
                    $event = new TableEvents();
                    $event->event_id = $eventid;
                    $new = true;
                }
                $event->organiser = $member->member_id;
                if ($join->t_max_attend) {
                    $event->max_attend = $join->t_max_attend;
                }
                $event->starttime = $join->day . ' ' .
                        ($join->t_starttime ?:'19:30:00');
                $event->endtime   = $join->day . ' ' .
                        ($join->t_endtime ?:'22:00:00');
                $event->summary   = $this->apply_template($join, $join->t_summary);
                if ($join->place_id) {
                    $event->location = $join->address;
                    $event->map      = $join->map;
                } else {
                    $event->location = '';
                    $event->map      = '';
                }
                $event->private  = $join->private;
                $event->priority = $join->priority;
                $event->rsvp_attend = 0;
                $event->description = $this->apply_template($join, $join->t_description);
                if ($new) {
                    $event->max_attend = $join->t_max_attend ?: 0;
                    $event->insert();
                } else {
                    if ($join->t_max_attend) {
                        $event->max_attend = $join->t_max_attend;
                    }
                    $event->update();
                }
                $groupid = $join->t_include;
                if ($groupid) {
                    $include = [];
                    $tobj = new TableGroupMembers();
                    $tobj->loopForGroup($groupid);
                    while ($tobj->fetch()) {
                        $include[] = $tobj->member_id;
                    }
                    $tobj = new JoinMembersUsersParticipants();
                    $tobj->loopParticipantForEvent($eventid);
                    while ($tobj->fetch()) {
                        if (in_array($tobj->member_id, $include)) {
                            if (!$tobj->isParticipant()) {
                                TableParticipants::addParticipant($eventid,
                                        $tobj->member_id);
                            }
                        } else {
                            if ($tobj->isParticipant()) {
                                TableParticipants::deleteParticipant($eventid,
                                        $tobj->member_id);
                            }
                        }
                    }
                }
                $url =input_request('bc_events') . "&action=edit&eventid=$eventid";
                $response = $this->get_response(false, 'Event generated - redirecting', $url);
            } else {
                $response = $this->get_response(true, 'Could not generate event');
            }
        }
        exit(json_encode($response));
    }
}

new MenuDates();
