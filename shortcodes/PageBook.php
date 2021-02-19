<?php namespace bookclub;

/*
 * Class wraps code used to generate the type='book' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage shortcodes
 * @license    https://opensource.org/licenses/MIT MIT
 */

class PageBook extends ShortCode
{
    /**
     * Initialize the object.
     * @return \bookclub\PageBook
     */
    public function __construct()
    {
        parent::__construct('bc_book',
            [
                'shorttype' => 'book',
                'style'     => 'page_book.css',
                'filters'   => [[
                        'name'     => 'document_title_parts',
                        'function' => 'override_post_title'
                    ]]
            ]);
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @global string $_GET['bid'] book identifier
     * @return string HTML content
     */
    public function render(): string
    {
        parent::enqueue();
        $bid = input_get('bid');
        $json = $this->jsonBook($bid);
        return twig_render('page_book', $json);
    }

    /**
     * Fetch JSON for book information.
     * @param int|null $bid book identifier
     * @return array JSON for TWIG rendering
     */
    private function jsonBook(?int $bid): array
    {
        $bookInfo = JoinBooksAuthors::findBookByID($bid);
        if (!$bookInfo) {
            return [];
        }
        $json = [
            'cover' => url_cover($bookInfo->cover_url),
            'title' => $bookInfo->title,
            'author' => $bookInfo->name,
            'link' => $bookInfo->link,
            'groups' => [],
            'others' => []
        ];

        /* dates for this book */
        $tobj = new JoinDatesGroups();
        $tobj->loopDatesForBook($bid);
        while ($tobj->fetch()) {
            $child = [
                    'group' => $tobj->group_id,
                    'tag'   => $tobj->tag,
                    'url'   => $tobj->url,
                    'date'  => $tobj->day
                ];
            $json['groups'][] = $child;
        }

        /* other books by same author */
        $tobj = new TableBooks();
        $tobj->loopForAuthorExcludeBook($bookInfo->author_id, $bid);
        while ($tobj->fetch()) {
            $child = [
                'title' => $tobj->title,
                'url' => url_book($tobj->book_id)
            ];
            $json['others'][] = $child;
        }
        if ($bookInfo->summary) {
            $json['summary'] = $bookInfo->summary;
        }
        if ($bookInfo->bio) {
            $json['bio'] = $bookInfo->bio;
        }
        return $json;
    }

    /**
     * Adjust page title if there is a book identifier as a parameter.
     * @param array $title original title array
     * @global string $_GET['bid'] book identifier
     * @return array possibly modified title array
     */
    public function override_post_title(array $title): array
    {
        if(\is_singular('page') && !is_null(input_get('bid'))) {
            $title['title'] = getBookTitle(input_get('bid'));
        }
        return $title;
    }
}

new PageBook();
