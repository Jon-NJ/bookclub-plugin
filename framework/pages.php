<?php namespace bookclub;

/*
 * This is the root file for pages. A page is either a menu item (as defined
 * in the menu subpackage) or a shortcode (as defined in the pages subpackage)
 * It links in all other files in these subpackages. A global instance of
 * the Manager class is defined as bcmanager.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @license    https://opensource.org/licenses/MIT MIT
 */

$GLOBALS['bcmanager'] = new Manager();

define('RANK_PROFILE',  1);
define('RANK_AUTHORS',  2);
define('RANK_COVERS',   3);
define('RANK_BOOKS',    4);
define('RANK_PLACES',   5);
define('RANK_DATES',    6);
define('RANK_GROUPS',   7);
define('RANK_NEWS',     8);
define('RANK_MEMBERS',  9);
define('RANK_EVENTS',   10);
define('RANK_EMAILS',   11);
define('RANK_SETTINGS', 12);
define('RANK_TEST',     13);

// include routes
require_once_folder(BOOKCLUBPATH.DS.'routes');

// include shortcodes
require_once_folder(BOOKCLUBPATH.DS.'shortcodes');

// include menus
$GLOBALS['bcmanager']->add_menu('bc_menu', url_images() . 'bookshelf.png',
        'Book Club', 20);

require_once_folder(BOOKCLUBPATH.DS.'menuitems');

add_action('admin_menu', [$GLOBALS['bcmanager'], 'create_menus']);
