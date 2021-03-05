<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_news' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuNews extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuNews
     */
    public function __construct()
    {
        parent::__construct('bc_menu_news',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Bookclub News',
                'menu_name'   => 'News',
                'menu_rank'   => RANK_NEWS,
                'capability'  => 'edit_bc_news',
                'slug'        => 'bc_news',
                'help'        => 'menu_news',
                'script'      => 'menu_news.js',
                'style'       => 'menu_news.css',
                'nonce'       => 'news_nonce',
                'actions'     => [[
                        'key' => 'wp_ajax_bc_news_add',
                        'function' => 'news_add'
                    ],[
                        'key' => 'wp_ajax_bc_news_save',
                        'function' => 'news_save'
                    ],[
                        'key' => 'wp_ajax_bc_news_delete',
                        'function' => 'news_delete'
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
        return twig_render('menu_news', $json);
    }

    /**
     * Create a list of administrators or previous posters and id numbers.
     * @return array JSON list of posters
     */
    private function getPosters(): array
    {
        $posters = [];
        $query = new \WP_User_Query(['role' => 'Administrator']);
        foreach ($query->get_results() as $poster) {
            $posters[$poster->get('user_login')] = $poster->ID;
        }
        $news = new TableNews();
        $news->loopPosters();
        while ($news->fetch()) {
            $poster = $news->poster;
            if (!array_key_exists($poster, $posters)) {
                $posters[$poster] = 0;
            }
        }
        ksort($posters);
        return $posters;
    }

    /**
     * Search database with the provided parameters.
     * @param string|null $datetime exact date or empty
     * @param string|null $poster partial poster name
     * @param string|null $news partial news item
     * @param string|null $age maximum age of the post in months or empty
     * @return array JSON search results
     */
    private function search(?string $datetime, ?string $poster, ?string $news,
            ?string $age): array
    {
        $results = [];
        $iterator = new TableNews();
        $iterator->loopSearch($datetime, $poster, $news, $age);
        $line = 0;
        while ($iterator->fetch()) {
            $message = $iterator->message;
            $results[] = [
                'line'     => $line++,
                'datetime' => $iterator->post_dt,
                'poster'   => $iterator->poster,
                'news'     => $message
            ];
        }
        return $results;
    }

    /**
     * Fetch JSON used for the search state.
     * @global string|null $_GET['datetime'] optional date of the news item
     * @global string|null $_GET['poster'] optional partial match of the poster
     * @global string|null $_GET['news'] optional partial news item
     * @global string|null $_GET['age'] optional news item age in months
     * @return array JSON for TWIG rendering
     */
    private function executeSearch(): array
    {
        $datetime = input_request('datetime');
        $poster   = input_request('poster');
        $news     = input_request('news');
        $age      = input_request('age');
        $nonce    = $this->create_nonce();
        $json = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_news'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'search',
            'datetime'  => $datetime,
            'age'       => $age,
            'poster'    => $poster,
            'posters'   => $this->getPosters(),
            'news'      => $news
            ];
        if (!$this->validateDate($datetime) &&
                !$this->validateDate($datetime, 'Y-m-d')) {
            $datetime = '';
        }
        $json['found'] = $this->search($datetime, $poster, $news, $age);
        return $json;
    }

    /**
     * Fetch JSON used for the edit state.
     * @global string $_GET['datetime'] news timestamp
     * @return array JSON for TWIG rendering
     */
    private function executeEdit(): array
    {
        $datetime = input_get('datetime');
        $nonce    = $this->create_nonce();
        $news     = TableNews::findByTimestamp($datetime);
        $json     = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_news'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'edit',
            'datetime'  => $datetime,
            'poster'    => $news->poster,
            'posters'   => $this->getPosters(),
            'news'      => $news->message
            ];
        return $json;
    }

    /**
     * Fetch JSON used for start state.
     * @return array JSON for TWIG rendering
     */
    private function executeStart(): array
    {
        $nonce = $this->create_nonce();
        $json = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_news'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'start',
            'datetime'  => '',
            'age'       => 6,
            'poster'    => '',
            'posters'   => $this->getPosters(),
            'news'      => ''
            ];
        return $json;
    }

    /**
     * Check that the date (if it is provided) is valid and matches the format.
     * @param string|null $date empty or datetime
     * @param string $format date format string
     * @return bool true if valid
     */
    private function validateDate(?string $date,
            string $format = 'Y-m-d H:i:s'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /** AJAX handling */

    /**
     * Add a news item to the database.
     * @param string $datetime datetime of the item
     * @param string $poster name of the poster
     * @param int $member_id id of member posting or zero
     * @param string|null $message the actual news item
     */
    private function insert(string $datetime, string $poster, int $member_id,
            ?string $message): void
    {
        $news            = new TableNews();
        $news->post_dt   = $datetime;
        $news->poster    = $poster;
        $news->member_id = $member_id;
        $news->message   = $message;
        $news->insert();
    }

    /**
     * Add a news item to the database. Generate a JSON response.
     * @global null|string $_REQUEST['datetime'] datetime of the item or empty
     * @global null|string $_REQUEST['poster'] name of the poster or empty
     * @global string $_REQUEST['news'] the actual news item
     */
    public function news_add(): void
    {
        $response = $this->check_request('Add news');
        if (!$response) {
            $datetime  = input_request('datetime');
            $poster    = input_request('poster');
            $member_id = 0;
            $news      = input_request('news');
            if (!$datetime) {
                $datetime = date('Y-m-d H:i:s');
            }
            if (!$poster) {
                $poster    = \wp_get_current_user()->get('user_login');
                $member_id = \get_current_user_id();
            }
            if ($this->validateDate($datetime) ||
                    $this->validateDate($datetime, 'Y-m-d')) {
                $this->insert($datetime, $poster, $member_id, $news);
                $this->log_info("News added $datetime");
                $response = $this->get_response(false, 'Post added');
                $response['datetime'] = $datetime;
            } else {
                $response = $this->get_response(true, 'Date invalid');
                $this->log_error("Add News date format invalid - $datetime");
            }
        }
        exit(json_encode($response));
    }

    /**
     * Delete a news item from the database. Generate a JSON response.
     * @global string $_REQUEST['datetime'] datetime of the item
     */
    public function news_delete(): void
    {
        $response = $this->check_request('Delete News');
        if (!$response) {
            $datetime = input_request('datetime');
            $this->log_info("Delete News $datetime");
            TableNews::deleteByTimestamp($datetime);
            $response = $this->get_response(false, 'Post deleted');
        }
        exit(json_encode($response));
    }

    /**
     * Update database news item.
     * @param string $datetime datetime of the item
     * @param string $poster author name
     * @param string $message the actual news item
     */
    private function update(string $datetime, string $poster,
            string $message): void
    {
        $news = TableNews::findByTimestamp($datetime);
        $news->poster  = $poster;
        $news->message = $message;
        $news->update();
    }

    /**
     * Update news item. Generate a JSON response.
     * @global string $_REQUEST['datetime'] datetime of the item
     * @global string $_REQUEST['poster'] author name
     * @global string $_REQUEST['news'] the actual news item
     */
    public function news_save(): void
    {
        $response = $this->check_request('Save News');
        if (!$response) {
            $datetime = input_request('datetime');
            $this->update($datetime, input_request('poster'),
                    input_request('news'));
            $response = $this->get_response(false, 'Post updated');
        }
        exit(json_encode($response));
    }
}

new MenuNews();
