<?php
/**
 * Plugin Name: Connect Bookclub Plugin
 * Plugin URI: http://connectberlin.de
 * Description: A wordpress plugin for use with our bookclub.
 * Version: 3.2.18
 * Requires PHP: 7.3
 * Author: Jon Wolfe jonnj@connectberlin.de
 * Copyright: 2018-2021 Jonathan Wolfe
 * Author URI: https://github.com/Jon-NJ
 * License: MIT
 * License URL: https://opensource.org/licenses/MIT
 */

#defined ('ABSPATH') || die;

namespace bookclub {
    // Paths
    if (!defined('DS')) {
        define('DS', DIRECTORY_SEPARATOR);
    }
    define('BOOKCLUBPATH', __DIR__);
    define('BOOKCLUBFILE', __FILE__);
    define('BOOKCLUBLOGS', BOOKCLUBPATH . DS . 'log');
    define('BOOKCLUB_AJAX_OBJECT', 'bookclub_ajax_object');

    // includes
    require_once(BOOKCLUBPATH.DS.'framework/library.php');
}

namespace {
    global $bcmanager;
    add_action('init', [$bcmanager, 'init']);
    register_activation_hook( __FILE__, [$bcmanager, 'activate']);
    register_deactivation_hook( __FILE__, [$bcmanager, 'deactivate']);
}
