<?php namespace bookclub;

/*
 * Class provides access to database news table which was used as a sort of
 * mini blog for the bookclub.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the news table.
 *
 * @ORM\Table(name="bc_news")
 * @ORM\Entity
 */
class TableNews extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var \DateTime timestamp for the news item
     *
     * @ORM\Column(name="post_dt", type="datetime", nullable=false, options={"default"="current_timestamp()"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $post_dt;

    /**
     * @var string name of the person posting
     *
     * @ORM\Column(name="poster", type="string", length=20, nullable=false, options={"default"="''"})
     */
    public $poster = '';

    /**
     * @var int member id of the person posting
     *
     * @ORM\Column(name="member_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $member_id = 0;

    /**
     * @var string text of the news
     *
     * @ORM\Column(name="message", type="text", length=65535, nullable=false)
     */
    public $message = '';

    /**
     * Initialize the object.
     * @return \bookclub\TableNews
     */
    public function __construct()
    {
        parent::__construct('news');
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
     * Delete the record for the given news item.
     * @param string $post_dt timestamp of the news item
     */
    static function deleteByTimestamp($post_dt)
    {
        $news = new TableNews();
        $news->deleteByString(['post_dt' => $post_dt]);
    }

    /**
     * Find the news item by timestamp.
     * @param string $post_dt timestamp of the news item
     * @return TableNews|null news record or null if not found
     */
    static function findByTimestamp($post_dt)
    {
        $news = new TableNews();
        $news->findByString(['post_dt' => $post_dt]);
        return $news->fetch() ? $news : null;
    }

    /**
     * Start looping through the posters.
     */
    public function loopPosters(): void
    {
        $this->select('DISTINCT(poster) AS poster');
        $this->iterate();
    }

    /**
     * Start looping through all news items.
     * @param int $age optional maximum age of posts, zero for all
     */
    public function loopByNewest(int $age = 0): void
    {
        $this->select();
        if ($age) {
            $this->where('post_dt > %s');
        }
        $this->orderBy('post_dt DESC');
        if ($age) {
            $this->prepare([date('Y-m-d', strtotime("-$age month"))]);
        }
        $this->iterate();
    }

    /**
     * Start looping through authors matching the search criteria.
     * @param string|null $datetime date when posted
     * @param string|null $poster name of person posting the item
     * @param string|null $news text of the news item
     * @param int|null $age maximum age of the post
     */
    public function loopSearch($datetime, $poster, $news, $age): void
    {
        $fields = [];
        $values = [];
        $this->addSearch($fields, $values, 'DATE(post_dt) = %s', $datetime, false);
        $this->addSearch($fields, $values, 'poster like %s', $poster);
        $this->addSearch($fields, $values, 'message like %s', $news);
        if ($age) {
            $this->addSearch($fields, $values, 'post_dt > %s',
                    date('Y-m-d', strtotime("-$age month")), false);
        }
        $this->select();
        $this->where($fields);
        $this->orderBy('post_dt DESC');
        $this->prepare($values);
        $this->iterate();
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
    function update()
    {
        parent::updateByString(['post_dt']);
    }
}
