<?php namespace bookclub;

/*
 * Class wraps code used to generate the type='news' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage shortcodes
 * @license    https://opensource.org/licenses/MIT MIT
 */

namespace bookclub;

/**
 * Description of PageNews
 *
 * @author jonnj
 */
class PageNews extends ShortCode
{
    /**
     * Initialize the object.
     * @return \bookclub\PageBook
     */
    public function __construct()
    {
        parent::__construct('bc_news',
            [
                'shorttype' => 'news',
                'style'     => 'page_news.css'
            ]);
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @return string HTML content
     */
    public function render(): string
    {
        parent::enqueue();
        $json = $this->jsonNews();
        return twig_render('page_news', $json);
    }

    /**
     * Fetch JSON for news.
     * @return array JSON for TWIG rendering
     */
    private function jsonNews(): array
    {
        $records = [];
        $iterator = new TableNews();
        $iterator->loopByNewest(5);
        while ($iterator->fetch()) {
            $message = $iterator->message;
            $results = [
                'datetime' => $iterator->post_dt,
                'poster'   => $iterator->poster,
                'news'     => $message
            ];
            $records[] = $results;
        }
        return ['records' => $records];
    }
}

new PageNews();
