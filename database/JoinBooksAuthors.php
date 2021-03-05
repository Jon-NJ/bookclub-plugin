<?php namespace bookclub;

/*
 * Class provides access to joined database books and authors tables.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the joined books and authors tables.
 */
class JoinBooksAuthors extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * Initialize the object.
     * @return \bookclub\JoinBooksAuthors
     */
    public function __construct()
    {
        parent::__construct('books');
        parent::join('authors',
            tableField('books', 'author_id') . ' = ' . tableField('authors', 'author_id'));
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
     * Find the book and author for the given identifier.
     * @param int|null $book_id unique identifier for the book
     * @return JoinBooksAuthors|null record for the book/author or null if not
     * found
     */
    public static function findBookByID(?int $book_id)
    {
        $book = new JoinBooksAuthors();
        $book->select();
        $book->where(tableField('books', 'book_id') . ' = %s');
        $book->prepare([$book_id]);
        $book->iterate();
        return $book->fetch() ? $book : null;
    }

    /**
     * Find the book and author for the given book title.
     * @param string $title
     * @return JoinBooksAuthors|null record for the book/author or null if not
     * found
     */
    public static function findBookByTitle($title)
    {
        $book = new JoinBooksAuthors();
        $book->select();
        $book->where(tableField('books', 'title') . ' = %s');
        $book->prepare([$title]);
        $book->iterate();
        return $book->fetch() ? $book : null;
    }

    /**
     * Loop through all books sorted by title.
     */
    public function loopByTitle(): void
    {
        $this->select();
        $this->orderBy(tableField('books', 'title'));
        $this->iterate();
    }

    /**
     * Start looping through books and authors matching the search criteria.
     * @param int|null    $bookid unique identifier for the book if given
     * @param string|null $title partial title string
     * @param string|null $cover partial cover filename
     * @param string|null $authorname partial author name
     * @param string|null $summary partial summary
     */
    public function loopSearch($bookid, $title, $cover, $authorname,
            $summary): void
    {
        $fields = [];
        $values = [];
        $this->addSearch($fields, $values, tableField('books', 'book_id') . ' = %s', $bookid, false);
        $this->addSearch($fields, $values, tableField('books', 'title') . ' like %s', $title);
        $this->addSearch($fields, $values, tableField('books', 'cover_url') . ' like %s', $cover);
        $this->addSearch($fields, $values, tableField('authors', 'name') . ' like %s', $authorname);
        $this->addSearch($fields, $values, tableField('books', 'summary') . ' like %s', $summary);
        $this->select();
        $this->where($fields);
        $this->orderBy(tableField('books', 'title'));
        $this->prepare($values);
        $this->iterate();
    }
}
