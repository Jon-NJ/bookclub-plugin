<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_books' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuBooks extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuBooks
     */
    public function __construct()
    {
        parent::__construct('bc_menu_books',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Edit Books',
                'menu_name'   => 'Books',
                'menu_rank'   => RANK_BOOKS,
                'capability'  => 'edit_bc_books',
                'slug'        => 'bc_books',
                'help'        => 'menu_books',
                'script'      => 'menu_books.js',
                'style'       => 'menu_books.css',
                'nonce'       => 'books_nonce',
                'actions'     => [[
                        'key' => 'wp_ajax_bc_books_add',
                        'function' => 'books_add'
                    ],[
                        'key' => 'wp_ajax_bc_books_save',
                        'function' => 'books_save'
                    ],[
                        'key' => 'wp_ajax_bc_books_delete',
                        'function' => 'books_delete'
                    ],[
                        'key' => 'wp_ajax_bc_books_lookup_author',
                        'function' => 'books_lookup_author'
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
        return twig_render('menu_books', $json);
    }

    /**
     * Fetch JSON used for start state.
     * @return array JSON for TWIG rendering
     */
    private function executeStart(): array
    {
        $nonce = $this->create_nonce();
        $json = [
            'nonce'       => $nonce,
            'admin_url'   => url_admin_post(),
            'referer'     => url_menu('bc_books'),
            'title'       => \get_admin_page_title(),
            'images'      => url_images(),
            'mode'        => 'start',
            'authors'     => getAuthors(),
            'covers'      => getCovers(),
            'book_id'     => '',
            'booktitle'   => '',
            'cover'       => '',
            'author_name' => '',
            'blurb'       => ''
            ];
        return $json;
    }

    /**
     * Fetch JSON used for the edit state.
     * @global string $_GET['bookid'] book identifier
     * @return array JSON for TWIG rendering
     */
    private function executeEdit(): array
    {
        $bookid = input_get('bookid');
        $nonce = $this->create_nonce();
        $book = JoinBooksAuthors::findBookByID($bookid);
        $json = [
            'nonce'       => $nonce,
            'admin_url'   => url_admin_post(),
            'referer'     => url_menu('bc_books'),
            'title'       => \get_admin_page_title(),
            'images'      => url_images(),
            'mode'        => 'edit',
            'authors'     => getAuthors(),
            'covers'      => getCovers(),
            'book_id'     => $bookid,
            'booktitle'   => $book->title,
            'cover'       => $book->cover_url,
            'author_id'   => $book->author_id,
            'author_name' => $book->name,
            'blurb'       => $book->summary
            ];
        return $json;
    }

    /**
     * Search database with the provided parameters.
     * @param string|null $bookid optional book identifier
     * @param string|null $title optional book title
     * @param string|null $cover optional book cover filename
     * @param string|null $authorname optional author name
     * @param string|null $summary optional book summary
     * @return array JSON search results
     */
    private function search(?string $bookid, ?string $title, ?string $cover,
            ?string $authorname, ?string $summary): array
    {
        $results = [];
        $iterator = new JoinBooksAuthors();
        $iterator->loopSearch($bookid, $title, $cover, $authorname, $summary);
        while ($iterator->fetch()) {
            $results[] = [
                'book_id'     => $iterator->book_id,
                'author_name' => $iterator->name,
                'author_id'   => $iterator->author_id,
                'author'      => $iterator->name,
                'booktitle'   => $iterator->title
            ];
        }
        return $results;
    }

    /**
     * Fetch JSON used for the search state.
     * @global string|null $_GET['bookid'] optional exact book identifier
     * @global string|null $_GET['title'] optional partial book title
     * @global string|null $_GET['cover'] optional partial book cover filename
     * @global string|null $_GET['authorname'] optional partial author name
     * @global string|null $_GET['summary'] optional partial book summary HTML
     * @return array JSON for TWIG rendering
     */
    private function executeSearch(): array
    {
        $bookid     = input_request('bookid');
        $title      = input_request('title');
        $cover      = input_request('cover');
        $authorname = input_request('authorname');
        $summary    = input_request('summary');
        $nonce = $this->create_nonce();
        $json = [
            'nonce'       => $nonce,
            'admin_url'   => url_admin_post(),
            'referer'     => url_menu('bc_books'),
            'title'       => \get_admin_page_title(),
            'images'      => url_images(),
            'mode'        => 'search',
            'authors'     => getAuthors(),
            'covers'      => getCovers(),
            'author_name' => $authorname,
            'book_id'     => $bookid,
            'booktitle'   => $title,
            'cover'       => $cover,
            'blurb'       => $summary
            ];
        $json['found'] = $this->search($bookid, $title, $cover,
                $authorname, $summary);
        return $json;
    }

    /**
     * Add a book to the database.
     * @param string $name author name
     * @param string $title book title
     * @param string $cover filename of book cover image
     * @param string $blurb HTML information about the book
     * @return int new book identifier
     */
    private function insert(string $name, string $title, string $cover,
            string $blurb): int
    {
        $author          = TableAuthors::findByName($name);
        $book            = new TableBooks();
        $bookid          = TableBooks::getNextID();
        $book->book_id   = $bookid;
        $book->author_id = $author->author_id;
        $book->title     = $title;
        $book->cover_url = $cover;
        $book->summary   = $blurb;
        $book->insert();
        return $bookid;
    }

    /**
     * Add a book to the database. Generate a JSON response.
     * @global string $_REQUEST['author'] author name
     * @global string $_REQUEST['title'] book title
     * @global string $_REQUEST['cover'] filename of book cover image
     * @global string $_REQUEST['blurb'] HTML information about the book
     */
    public function books_add(): void
    {
        $response = $this->check_request('Add book');
        if (!$response) {
            $bookid = $this->insert(input_request('author'),
                    input_request('title'), input_request('cover'),
                    input_request('blurb'));
            $this->log_info("Book added $bookid");
            $response = $this->get_response(false, 'Book added');
            $response['book_id'] = $bookid;
        }
        exit(json_encode($response));
    }

    /**
     * Find the author, fetch identifier or zero if not found. Generate a JSON
     * response.
     * @global string $_REQUEST['author'] author name
     */
    public function books_lookup_author(): void
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
     * Delete a book from the database. Generate a JSON response.
     * @global string $_REQUEST['bookid'] book identifier
     */
    public function books_delete(): void
    {
        $response = $this->check_request('Delete book');
        if (!$response) {
            $bookid = input_request('bookid');
            $this->log_info("Delete book id $bookid");
            TableBooks::deleteByID($bookid);
            $response = $this->get_response(false, 'Book deleted');
        }
        exit(json_encode($response));
    }

    /**
     * Update book information.
     * @param int $bookid book identifier
     * @param string $name author name
     * @param string $title book title
     * @param string $cover filename of book cover image
     * @param string $blurb HTML information about the book
     */
    private function update(int $bookid, string $name, string $title,
            string $cover, string $blurb): void
    {
        $author          = TableAuthors::findByName($name);
        $book            = TableBooks::findByID($bookid);
        $book->title     = $title;
        $book->cover_url = $cover;
        $book->author_id = $author->author_id;
        $book->summary   = $blurb;
        $book->update();
    }

    /**
     * Update book information. Generate a JSON response.
     * @global string $_REQUEST['bookid'] book identifier
     * @global string $_REQUEST['author'] author name
     * @global string $_REQUEST['title'] book title
     * @global string $_REQUEST['cover'] filename of book cover image
     * @global string $_REQUEST['blurb'] HTML information about the book
     */
    public function books_save(): void
    {
        $response = $this->check_request('Save book');
        if (!$response) {
            $this->update(input_request('bookid'), input_request('author'),
                    input_request('title'), input_request('cover'),
                    input_request('blurb'));
            $response = $this->get_response(false, 'Book updated');
        }
        exit(json_encode($response));
    }
}

new MenuBooks();

