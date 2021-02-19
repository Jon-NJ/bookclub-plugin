<?php namespace bookclub;

/*
 * Class wraps code used to generate the type='previous' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage shortcodes
 * @license    https://opensource.org/licenses/MIT MIT
 */

class PagePrevious extends ShortCode
{
    /**
     * Initialize the object.
     * @return \bookclub\PagePrevious
     */
    public function __construct()
    {
        parent::__construct('bc_previous',
            [
                'shorttype' => 'previous',
                'style'     => 'page_previous.css'
            ]);
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @global string|null $_GET['y'] year or null for current
     * @global string|null $_GET['gid'] group identifier or null
     * @return string HTML content
     */
    public function render(): string
    {
        parent::enqueue();
        $year = input_get('y');
        $gid = input_get('gid');
        $json = $this->jsonPrevious($year, $gid);
        return twig_render('page_previous', $json);
    }

    /**
     * Fetch JSON for the previous books page.
     * @param int|null $year year or null for current
     * @param int|null $gid group identifier or null
     * @return array JSON for TWIG rendering
     */
    private function jsonPrevious(?int $year, ?int $gid): array
    {
        $json = [
            'year' => $year,
            'group' => $gid,
            'years' => [],
            'groups' => [],
            'books' => []
        ];

        if (!isset($year)) {
            $year = TableDates::getMaxYear();
        }
        if (!isset($gid)) {
            $gid = TableDates::getMinGroup($year);
        }

        $bcYears = new TableDates();
        $bcYears->loopPastYears();
        while ($bcYears->fetch()) {
            if ($year == $bcYears->yyyy) {
                $json['years'][] = [
                    'year' => $bcYears->yyyy,
                ];
            } else {
                $json['years'][] = [
                    'year' => $bcYears->yyyy,
                    'url' => url_previous($bcYears->yyyy, $gid)
                ];
            }
        }

        $tobj = new JoinDatesGroups();
        $tobj->loopGroupsForYear($year);
        while ($tobj->fetch()) {
            if ($gid == $tobj->group_id) {
                $json['groups'][] = [
                    'group' => $tobj->group_id,
                    'tag'   => $tobj->tag
                ];
            } else {
                $json['groups'][] = [
                    'group' => $tobj->group_id,
                    'tag'   =>  $tobj->tag,
                    'url'   => url_previous($year, $tobj->group_id)
                ];
            }
        }

        $tobj = new JoinDatesBooksAuthors();
        $tobj->loopGroupForYear($year, $gid);
        while ($tobj->fetch()) {
            $json['books'][] = [
                'title'  => $tobj->title,
                'cover'  => url_cover($tobj->cover_url),
                'date'   => $tobj->day,
                'author' => $tobj->name,
                'link'   => $tobj->link,
                'url'    => url_book($tobj->book_id)
            ];
        }

        return $json;
    }
}

new PagePrevious();
