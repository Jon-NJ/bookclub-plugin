<?php namespace bookclub;

/*
 * Class provides access to database authors table.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the authors table.
 * 
 * @ORM\Table(name="bc_authors")
 * @ORM\Entity
 */
class TableAuthors extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var int unique identifier for an author
     *
     * @ORM\Column(name="author_id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $author_id = 0;

    /**
     * @var string name of the author
     *
     * @ORM\Column(name="name", type="string", length=40, nullable=false, options={"default"="''"})
     */
    public $name = '';

    /**
     * @var string|null URL for the author
     *
     * @ORM\Column(name="link", type="string", length=80, nullable=true, options={"default"="NULL"})
     */
    public $link = '';

    /**
     * @var string|null HTML bio about the author
     *
     * @ORM\Column(name="bio", type="text", length=65535, nullable=true, options={"default"="NULL"})
     */
    public $bio = '';

    /**
     * Initialize the object.
     * @return TableAuthors
     */
    public function __construct()
    {
        parent::__construct('authors');
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
     * Delete the record for the given author.
     * @param int $author_id unique identifier for the author
     */
    public static function deleteByID(int $author_id): void
    {
        $author = new TableAuthors();
        $author->deleteByString(['author_id' => $author_id]);
    }

    /**
     * Find the author for the given identifier.
     * @param int $author_id unique identifier for the author
     * @return TableAuthors|null record for the author or null if not found
     */
    public static function findByID(int $author_id)
    {
        $author = new TableAuthors();
        $author->findByString(['author_id' => $author_id]);
        return $author->fetch() ? $author : null;
    }

    /**
     * Find the author by name.
     * @param string $author_name name of the author
     * @return TableAuthors|null record for the author or null if not found
     */
    public static function findByName(string $author_name)
    {
        $author = new TableAuthors();
        $author->findByString(['name' => $author_name]);
        return $author->fetch() ? $author : null;
    }

    /**
     * Fetch a new identifier that has not been used - normally MAX + 1.
     * @return int unique identifier to use if a new record is inserted
     */
    public static function getNextID(): int
    {
        $author = new TableAuthors();
        $author->select('COALESCE(MAX(author_id),0) + 1 AS newid');
        $rows = $author->execute();
        return (int) $rows[0]->newid;
    }

    /**
     * Start looping through all authors on name order.
     */
    public function loopByName(): void
    {
        parent::loopOrder('name');
    }

    /**
     * Start looping through authors matching the search criteria.
     * @param string|null $authorid unique identifier for the author if given
     * @param string|null $name partial author name
     * @param string|null $link partial link URL
     * @param string|null $bio partial biography information
     */
    public function loopSearch(?string $authorid, ?string $name, ?string $link,
            ?string $bio): void
    {
        $fields = [];
        $values = [];
        $this->addSearch($fields, $values, 'author_id = %s', $authorid, false);
        $this->addSearch($fields, $values, 'name like %s', $name);
        $this->addSearch($fields, $values, 'link like %s', $link);
        $this->addSearch($fields, $values, 'bio like %s', $bio);
        $this->select();
        $this->where($fields);
        $this->orderBy('name');
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
    public function update(): void
    {
        parent::updateByString(['author_id']);
    }
}
