<?php namespace bookclub;

/*
 * Class wraps code used to generate the type='forthcoming' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage shortcodes
 * @license    https://opensource.org/licenses/MIT MIT
 */

class PageForthcoming extends ShortCode
{
    /**
     * Initialize the object.
     * @return \bookclub\PageForthcoming
     */
    public function __construct()
    {
        parent::__construct('bc_forthcoming',
            [
                'shorttype' => 'forthcoming',
                'style'     => 'page_forthcoming.css'
            ]);
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @global string|null $_GET['gid'] group identifier or zero/null for all
     * @return string HTML content
     */
    public function render(): string
    {
        parent::enqueue();
        $gid = input_get('gid');
        $json = $this->jsonForthcoming($gid);
        return twig_render('page_forthcoming', $json);
    }

    /**
     * Fetch JSON for forthcoming books.
     * @param int|null $gid group identifier or zero/null for all
     * @return array JSON for TWIG rendering
     */
    private function jsonForthcoming(?int $gid): array
    {
        $json = [];
        if ('' == $gid) {
            $gid = 0;
        }

        $item = [
            'group' => 0,
            'tag'   => 'All Groups'
        ];
        if ($gid != 0) {
            $item['url'] = url_forthcoming(0);
        }

        $json['groups'] = [$item];
        foreach (getGroups(BC_GROUP_CLUB) as $group) {
            $id = $group['groupid'];
            $item = [
                'group' => $id,
                'tag'   => $group['tag']
            ];
            if ($gid != $id) {
                $item['url'] = url_forthcoming($id);
            }
            $json['groups'][] = $item;
            
        }

        $json['records'] = [];
        $tobj = new JoinDatesBooksAuthorsPlacesGroups();
        $tobj->loopChronological($gid);
        while ($tobj->fetch()) {
            $child = [
                'group'    => $tobj->group_id,
                'tag'      => $tobj->tag,
                'groupurl' => $tobj->url,
                'place_id' => $tobj->place_id,
                'place'    => $tobj->place,
                'date'     => $tobj->day,
                'bookurl'  => url_book($tobj->book_id),
                'cover'    => url_cover($tobj->cover_url),
                'title'    => $tobj->title,
                'author'   => $tobj->name,
                'link'     => $tobj->link,
                'private'  => $tobj->private,
                'priority' => $tobj->priority
            ];
            if ($tobj->summary) {
                $child['summary'] = $tobj->summary;
            }
            if ($tobj->bio) {
                $child['bio'] = $tobj->bio;
            }
            $json['records'][] = $child;
        }
        return $json;
    }
}

new PageForthcoming();
