<?php namespace bookclub;

/*
 * Class provides access to database books table.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the books table.
 *
 * @ORM\Table(name="bc_books")
 * @ORM\Entity
 */
class TableBooks extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var int unique identifier for the book
     *
     * @ORM\Column(name="book_id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $book_id = 0;

    /**
     * @var int unique identifier for the author, links to author table
     *
     * @ORM\Column(name="author_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    public $author_id = 0;

    /**
     * @var string book title
     *
     * @ORM\Column(name="title", type="string", length=60, nullable=false, options={"default"="''"})
     */
    public $title = '';

    /**
     * @var string|null filename relative to base folder with the cover image
     *
     * @ORM\Column(name="cover_url", type="string", length=40, nullable=true, options={"default"="NULL"})
     */
    public $cover_url = '';

    /**
     * @var string|null HTML description about the book
     *
     * @ORM\Column(name="summary", type="text", length=65535, nullable=true, options={"default"="NULL"})
     */
    public $summary = '';

    /**
     * Initialize the object.
     * @return \bookclub\TableBooks
     */
    public function __construct()
    {
        parent::__construct('books');
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
     * Fetch the number of rows returned from last query.
     * @return int number of rows returned from last result set
     */
    public function count(): int
    {
        return parent::count();
    }

    /**
     * Delete the record for the given book.
     * @param int $book_id unique identifier for the book
     */
    public static function deleteByID(int $book_id): void
    {
        $book = new TableBooks();
        $book->deleteByString(['book_id' => $book_id]);
    }

    /**
     * Find the book for the given identifier.
     * @param int $book_id unique identifier for the book
     * @return TableBooks|null record for the book or null if not found
     */
    public static function findByID(int $book_id)
    {
        $book = new TableBooks();
        $book->findByString(['book_id' => $book_id]);
        return $book->fetch() ? $book : null;
    }

    /**
     * Fetch a new identifier that has not been used - normally MAX + 1.
     * @return int unique identifier to use if a new record is inserted
     */
    public static function getNextID(): int
    {
        $book = new TableBooks();
        $book->select('COALESCE(MAX(book_id),0) + 1 AS newid');
        $rows = $book->execute();
        return (int) $rows[0]->newid;
    }

    /**
     * Check how many books in the database reference the given author.
     * @param int $author_id unique author identifier
     * @return int number of books referencing the given author
     */
    public static function getCountForAuthor(int $author_id): int
    {
        $author = new TableBooks();
        $author->select('count(*) AS count');
        $author->where('author_id = %s');
        $author->prepare([$author_id]);
        $rows = $author->execute();
        return (int) $rows[0]->count;
    }

    /**
     * Start looping through all books on title order.
     */
    public function loopByTitle(): void
    {
        parent::loopOrder('title');
    }

    /**
     * Start looping through all books by a given author excluding specified
     * book.
     * @param int $author_id author to loop through
     * @param int $book_id book to exclude
     */
    public function loopForAuthorExcludeBook(int $author_id, int $book_id): void
    {
        $this->select();
        $this->where('book_id <> %s')
             ->and('author_id = %s');
        $this->orderBy('title');
        $this->prepare([$book_id, $author_id]);
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
        parent::updateByString(['book_id']);
    }
}
