<?php namespace bookclub;

/*
 * Manager for shortcode pages and menu subitem pages. Basically this is a
 * collection of Page objects which register themselves when instanciated.
 * A global instance "bcmanager" is created.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage framework
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Mini utility class used for registering a REST route handler.
 */
class RegisterObject
{
    /*
     * @var string the route to register
     */
    private $route;

    /*
     * @var callable a handle for the route
     */
    private $handler;

    /**
     * Create a register object that can be called to handle a REST call.
     * @param string $route the route to be registered
     * @param callable $handler then handler for the route
     */
    public function __construct(string $route, callable $handler)
    {
        $this->route   = $route;
        $this->handler = $handler;
    }

    /**
     * Register the REST route and handler defined for the object.
     */
    public function register(): void
    {
        $logger = \Logger::getLogger("manager");
        $logger->debug("Registering REST route $this->route");
        \register_rest_route('bc/v1', $this->route,
                ['methods' => 'GET','callback' => $this->handler]);
    }
}

class Manager
{
    /**
     * @var array collection of menu and shortcode pages.
     */
    private $pages = [];

    /**
     * @var array collection of sub-menu pages
     */
    private $menus = [];

    /**
     * @var array collection of shortcode pages
     */
    private $shorttypes = [];

    /**
     * Construct a Manager object.
     * @return \bookclub\Manager
     */
    public function __construct()
    {
    }

    /**
     * Register the given page.
     * @param \bookclub\Page $page page handler
     */
    public function add_page(Page $page): void
    {
        $this->pages[] = $page;
    }

    /**
     * Register the given page to handle the given shortcode type.
     * @param \bookclub\Page $page page handler
     * @param string $shorttype shortcode type
     */
    public function add_shorttype(Page $page, string $shorttype): void
    {
        $this->shorttypes[$shorttype] = $page;
    }

    /**
     * Add a parent menu.
     * @param string $slug main (parent) slug for the menu
     * @param string $icon_url partial path to the icon used for the menu
     * @param string $title optional menu title name
     * @param int|null $position optional ranking of the menu in the dashboard
     */
    public function add_menu(string $slug, string $icon_url = '',
            string $title = '', int $position = null): void
    {
        $this->menus[$slug] = [
            'slug'     => $slug,
            'icon_url' => $icon_url,
            'position' => $position,
            'title'    => $title,
            'items'    => []
        ];
    }

    public function add_route(string $route, callable $handler): void
    {
        $robj = new RegisterObject($route, $handler);
        \add_action('rest_api_init', [$robj, 'register']);
    }

    /**
     * Add a handler for a sub-menu.
     * @param \bookclub\MenuItem $item handler for the menu item
     * @param string $parent_slug slug of the menu parent
     * @param int $rank ranking within the parent menu
     */
    public function add_menu_item(MenuItem $item, string $parent_slug, int $rank): void
    {
        $this->menus[$parent_slug]['items'][$rank] = $item;
    }

    /**
     * Generate all dashboard menus managed by this object.
     */
    public function create_menus(): void
    {
        foreach ($this->menus as $menu) {
            $isfirst = true;
            ksort($menu['items']);
            foreach ($menu['items'] as $item) {
                if ($isfirst) {
                    if ($menu['title']) {
                        \add_menu_page($item->getPageTitle(), $menu['title'],
                                $item->getCapability(), $menu['slug'],
                                [$item, 'execute'], $menu['icon_url'],
                                $menu['position']);
                        \add_submenu_page($menu['slug'], $item->getPageTitle(),
                                $item->getMenuName(), $item->getCapability(),
                                $menu['slug'], [$item, 'execute']);
                    } else {
                        \add_menu_page($item->getPageTitle(),
                                $item->getMenuName(), $item->getCapability(),
                                $menu['slug'], [$item, 'execute'],
                                $menu['icon_url'], $menu['position']);
                    }
                } else {
                    \add_submenu_page($menu['slug'], $item->getPageTitle(),
                            $item->getMenuName(), $item->getCapability(),
                            $item->getSlug(), [$item, 'execute']);
                }
                $isfirst = false;
            }
        }
    }

    /**
     * Activate all pages. Create database.
     */
    public function activate(): void
    {
        createDatabase();
        create_cover_folder();
        addBookclubAdmin('editor');
        updateTimeZone();
        foreach ($this->pages as $page) {
            $page->activate();
        }
        \flush_rewrite_rules();
    }

    /**
     * Deactivate all pages.
     */
    public function deactivate(): void
    {
        foreach ($this->pages as $page) {
            $page->deactivate();
        }
        if (\shortcode_exists('bookclub')) {
            \remove_shortcode('bookclub');
        }
        \remove_action('init', 'bc_shortcodes_init');
        \flush_rewrite_rules();
    }

    /**
     * Initialize all pages, update the database, add the bookclub shortcode.
     */
    public function init(): void
    {
        updateDatabase();
        foreach ($this->pages as $page) {
            $page->init();
        }
        \add_shortcode('bookclub', array($this, 'render'));
    }

    /**
     * Render the shortcode based on the parameters.
     * @param array $atts shortcode parameters (including type)
     * @param string $content optional content between open/close tags
     * @return string rendered HTML for given shortcode type
     */
    public function render(array $atts, string $content = ''): string
    {
        $shorttype = 'main'; //default
        if (isset($atts['type'])) {
            $shorttype = $atts['type'];
        }
        $ret = '';
        if (isset($this->shorttypes[$shorttype])) {
            $shorttype = $this->shorttypes[$shorttype];
            $ret = $shorttype->render();
        }
        return $ret;
    }
}
