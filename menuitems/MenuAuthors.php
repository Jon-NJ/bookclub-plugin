<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_authors' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuAuthors extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuAuthors
     */
    public function __construct()
    {
        parent::__construct('bc_menu_authors',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Edit Authors',
                'menu_name'   => 'Book Authors',
                'menu_rank'   => RANK_AUTHORS,
                'capability'  => 'edit_bc_authors',
                'slug'        => 'bc_authors',
                'help'        => 'menu_authors',
                'script'      => 'menu_authors.js',
                'style'       => 'menu_authors.css',
                'nonce'       => 'authors_nonce',
                'actions'     => [[
                        'key' => 'wp_ajax_bc_authors_add',
                        'function' => 'authors_add'
                    ],[
                        'key' => 'wp_ajax_bc_authors_save',
                        'function' => 'authors_save'
                    ],[
                        'key' => 'wp_ajax_bc_authors_delete',
                        'function' => 'authors_delete'
                    ],[
                        'key' => 'wp_ajax_bc_authors_book_count',
                        'function' => 'authors_book_count'
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
        return twig_render('menu_authors', $json);
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
            'referer'       => url_menu('bc_authors'),
            'title'         => \get_admin_page_title(),
            'images'        => url_images(),
            'mode'          => 'start',
            'author_id'     => '',
            'author_name'   => '',
            'link'          => '',
            'bio'           => ''
            ];
        return $json;
    }

    /**
     * Fetch JSON used for the edit state.
     * @global string $_GET['authorid'] author identifier
     * @return array JSON for TWIG rendering
     */
    private function executeEdit(): array
    {
        $authorid = input_get('authorid');
        $nonce = $this->create_nonce();
        $author = TableAuthors::findByID($authorid);
        $json = [
            'nonce'       => $nonce,
            'admin_url'   => url_admin_post(),
            'referer'     => url_menu('bc_authors'),
            'title'       => \get_admin_page_title(),
            'images'      => url_images(),
            'mode'        => 'edit',
            'author_id'   => $author->author_id,
            'author_name' => $author->name,
            'link'        => $author->link,
            'bio'         => $author->bio
            ];
        return $json;
    }

    /**
     * Search database with the provided parameters.
     * @param string|null $authorid optional author identifier
     * @param string|null $name optional author name
     * @param string|null $link optional author URL
     * @param string|null $bio optional author bio HTML
     * @return array JSON search results
     */
    private function search(?string $authorid, ?string $name, ?string $link,
            ?string $bio): array
    {
        $results = [];
        $iterator = new TableAuthors();
        $iterator->loopSearch($authorid, $name, $link, $bio);
        while ($iterator->fetch()) {
            $results[] = [
                'author_id'   => $iterator->author_id,
                'author_name' => $iterator->name,
                'link'        => $iterator->link,
                'bio'         => $iterator->bio
            ];
        }
        return $results;
    }

    /**
     * Fetch JSON used for the search state.
     * @global string|null $_REQUEST['authorid'] optional exact author identifier
     * @global string|null $_REQUEST['name'] optional partial author name
     * @global string|null $_REQUEST['link'] optional partial author URL
     * @global string|null $_REQUEST['bio'] optional partial bio HTML
     * @return array JSON for TWIG rendering
     */
    private function executeSearch(): array
    {
        $authorid = input_request('authorid');
        $name     = input_request('name');
        $link     = input_request('link');
        $bio      = input_request('bio');
        $nonce    = $this->create_nonce();
        $json = [
            'nonce'       => $nonce,
            'admin_url'   => url_admin_post(),
            'referer'     => url_menu('bc_authors'),
            'title'       => \get_admin_page_title(),
            'images'      => url_images(),
            'mode'        => 'search',
            'author_id'   => $authorid,
            'author_name' => $name,
            'link'        => $link,
            'bio'         => $bio
            ];
        $json['found'] = $this->search($authorid, $name, $link, $bio);
        return $json;
    }

    /**
     * Generate JSON containing the count of books written by the given author.
     * @global string $_REQUEST['authorid'] author identifier
     */
    public function authors_book_count(): void
    {
        $response = $this->check_request('Get book count for author');
        if (!$response) {
            $authorid = input_request('authorid');
            $count    = TableBooks::getCountForAuthor($authorid);
            $response = $this->get_response(false, '');
            $response['count'] = $count;
            if (0 == $count) {
                $response['message'] = 'Do you really want to delete?';
            } else {
                $response['error']   = true;
                $response['message'] = "Cannot delete author of $count book(s). " .
                    'Please delete or reassign the book(s) first.';
            }
        }
        exit(json_encode($response));
    }

    /**
     * Add an author to the database.
     * @param string $name author name
     * @param string $link URL link to author WIKI or personal page
     * @param string $bio author biography HTML
     * @return int author unique identifier
     */
    private function insert(string $name, string $link, string $bio): int
    {
        $author            = new TableAuthors();
        $authorid          = TableAuthors::getNextID();
        $author->author_id = $authorid;
        $author->name      = $name;
        $author->link      = $link;
        $author->bio       = $bio;
        $author->insert();
        return $authorid;
    }

    /**
     * Add an author to the database. Generate a JSON response.
     * @global string $_REQUEST['name'] author name
     * @global string $_REQUEST['link'] URL link to author WIKI or personal page
     * @global string $_REQUEST['bio'] author biography HTML
     */
    public function authors_add(): void
    {
        $response = $this->check_request('Add author');
        if (!$response) {
            $authorid = $this->insert(input_request('name'),
                    input_request('link'), input_request('bio'));
            $this->log_info("Author added $authorid");
            $response = $this->get_response(false, 'Author added');
            $response['author_id'] = $authorid;
        }
        exit(json_encode($response));
    }

    /**
     * Delete an author from the database. Generate a JSON response.
     * @global string $_REQUEST['authorid'] author identifier
     */
    public function authors_delete(): void
    {
        $response = $this->check_request('Delete author');
        if (!$response) {
            $authorid = input_request('authorid');
            $this->log_info("Delete author id $authorid");
            TableAuthors::deleteByID($authorid);
            $response = $this->get_response(false, 'Author deleted');
        }
        exit(json_encode($response));
    }

    /**
     * Update database information about the given author.
     * @param int $authorid author identifier
     * @param string $name author name
     * @param string $link URL link to author WIKI or personal page
     * @param string $bio author biography HTML
     */
    private function update(int $authorid, string $name, string $link,
            string $bio): void
    {
        $author       = TableAuthors::findByID($authorid);
        $author->name = $name;
        $author->link = $link;
        $author->bio  = $bio;
        $author->update();
    }

    /**
     * Update information about the given author. Generate a JSON response.
     * @global string $_REQUEST['authorid'] author identifier
     * @global string $_REQUEST['name'] author name
     * @global string $_REQUEST['link'] URL link to author WIKI or personal page
     * @global string $_REQUEST['bio'] author biography HTML
     */
    public function authors_save(): void
    {
        $response = $this->check_request('Save author');
        if (!$response) {
            $this->update(input_request('authorid'), input_request('name'),
                    input_request('link'), input_request('bio'));
            $response = $this->get_response(false, 'Author updated');
        }
        exit(json_encode($response));
    }
}

new MenuAuthors();
