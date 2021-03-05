<?php namespace bookclub;

/*
 * Class wraps code used to generate the type='main' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage shortcodes
 * @license    https://opensource.org/licenses/MIT MIT
 */

class PageMain extends ShortCode
{
    /**
     * Initialize the object.
     * @return \bookclub\PageMain
     */
    public function __construct()
    {
        parent::__construct('bc_main',
            [
                'shorttype' => 'main',
                'style'     => 'page_main.css'
            ]);
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @return string HTML content
     */
    public function render(): string
    {
        parent::enqueue();
        $json = $this->jsonMain();
        return twig_render('page_main', $json);
    }

    /**
     * Fetch JSON for the main page.
     * @return array JSON for TWIG rendering
     */
    private function jsonMain(): array
    {
        $json = [
            'records' => []
        ];
        $tobj = new TableGroups();
        $tobj->loopForType(BC_GROUP_CLUB);
        while ($tobj->fetch()) {
            // what is the next date of the current group
            $date = TableDates::getNextDateForGroup($tobj->group_id);
            $private = 0;
            $priority = 0;
            if (!is_null($date)) {
                $tobj2 = new JoinDatesBooksAuthorsPlacesGroups();
                $tobj2->loopForDateAndGroup($date, $tobj->group_id);
                $lid = 0;
                $place = '';
                $children = [];
                while ($tobj2->fetch()) {
                    $place = $tobj2->place;
                    $lid = $tobj2->place_id;
                    $children[] = [
                        'url'     => url_book($tobj2->book_id),
                        'cover'   => url_cover($tobj2->cover_url),
                        'title'   => $tobj2->title,
                        'author'  => $tobj2->name,
                        'link'    => $tobj2->link
                    ];
                    if ($tobj2->private) {
                        $private = 1;
                    }
                    if ($tobj2->priority) {
                        $priority = $tobj2->priority;
                    }
                }
                $json['records'][] = [
                    'group'    => $tobj->group_id,
                    'tag'      => $tobj->tag,
                    'url'      => $tobj->url,
                    'place_id' => $lid,
                    'place'    => $place,
                    'date'     => $date,
                    'books'    => $children,
                    'private'  => $private,
                    'priority' => $priority
                ];
            }
        }
        return $json;
    }
}

new PageMain();
