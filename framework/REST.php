<?php namespace bookclub;

/*
 * Class wraps code used to handle REST routes.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage framework
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Base object for REST API handlers
 */
abstract class REST
{
    /**
     * @var \Logger a logging object for debug, info and error reporting
     */
    protected $logger;

    /**
     * Initialize the handler object.
     * @return \bookclub\REST
     */
    public function __construct(string $key, string $route)
    {
        global $bcmanager;
        $bcmanager->add_route($route, [$this, 'handle']);
        $this->logger = \Logger::getLogger("rest.$key");
    }

    /**
     * Abstract function handles REST call.
     */
    abstract public function handle(\WP_REST_Request $request): string;
}
