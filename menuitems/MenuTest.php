<?php namespace bookclub;

 require_once BOOKCLUBPATH.DS.'routes/ForwardEMail.php';

/*
 * Class wraps code used to generate the menu 'bc_test' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuTest extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuTest
     */
    public function __construct()
    {
        parent::__construct('bc_menu_test',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Test Page',
                'menu_name'   => 'Test',
                'menu_rank'   => RANK_TEST,
                'capability'  => 'edit_posts',
                'slug'        => 'bc_test',
                'script'      => 'menu_test.js',
                'style'       => 'menu_test.css'
            ]);
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @return string HTML content
     */
    public function render(): string
    {
        if (!parent::enqueue()) {
            return '';
        }
        $forwarder = $GLOBALS['forwarder'];
        $forwarder->handle();
        $json = [
            'referer'     => url_menu('bc_test'),
            'title'       => \get_admin_page_title(),
            'images'      => url_images(),
            'mode'        => 'search'
        ];
        return twig_render('menu_test', $json);
    }
}

new MenuTest();
