<?php namespace bookclub;

/*
 * Class wraps code used to handle a URL path match.
 * Look for examples in routes folder.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage framework
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Base object for URL path matching.
 */
abstract class Path extends Page
{
    /**
     * @var string partial string to match in URL
     */
    private $path;

    /**
     * Initialize the object.
     * @return \bookclub\Path
     */
    public function __construct(string $key, $path)
    {
        $this->path = $path;
        parent::__construct($key, [
                'actions'   => [[
                        'key'      => 'wp_loaded',
                        'function' => 'path_hook'
                    ]]
                ]);
    }

    /**
     * Intercept the server request and check for URL prefix.
     */
    public function path_hook()
    {
        if ($this->path === substr(input_server('REQUEST_URI'), 0, strlen($this->path))) {
            $this->handle();
            die();
        }
    }

    /**
     * Abstract function handles URL intercept.
     */
    abstract public function handle(): void;
}
