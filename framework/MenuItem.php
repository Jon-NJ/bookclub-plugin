<?php namespace bookclub;

/*
 * Class wraps code used to handle a menu item. See menu examples in menuitems
 * folder.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage framework
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Base object for dashboard menu items.
 */
abstract class MenuItem extends Page
{
    /**
     * Construct a MenuItem object.
     * @param string $key unique key for the page which may be used for various
     * purposes
     * @param array $data additional data in JSON format
     * @return \bookclub\MenuItem
     */
    public function __construct(string $key, array $data)
    {
        global $bcmanager;
        $bcmanager->add_menu_item($this, $data['parent_slug'],
                $data['menu_rank']);
        $this->logger = \Logger::getLogger("menu.$key");
        parent::__construct($key, $data);
    }

    /**
     * Fetch menu title.
     * @return string title for the menu item if it is a menu
     */
    public function getMenuName(): string
    {
        return $this->data['menu_name'];
    }

    /**
     * Fetch menu slug - used for dashboard URL.
     * @return string slug for the page if it is a menu
     */
    public function getSlug(): string
    {
        return $this->data['slug'];
    }

    /**
     * Fetch page title.
     * @return string title for the page if it is a menu
     */
    public function getPageTitle(): string
    {
        return $this->data['page_title'];
    }

    /**
     * Fetch page permissions.
     * @return string permissions for accessing the page
     */
    public function getCapability(): string
    {
        return $this->data['capability'];
    }

    /**
     * Render the page, output the HTML.
     */
    public function execute(): void
    {
        echo $this->render();
    }

    /**
     * Abstract function renders the HTML for the page.
     */
    abstract public function render(): string;
}
