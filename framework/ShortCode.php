<?php namespace bookclub;

/*
 * Class wraps code used to generate a shortcode. See shortcode examples in
 * shortcodes folder.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage framework
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Base object for short code renderers
 */
abstract class ShortCode extends Page
{
    /**
     * Construct a ShortCode object.
     * @param string $key unique key for the page which may be used for various
     * purposes
     * @param array $data additional data in JSON format
     * @return \bookclub\ShortCode
     */
    public function __construct(string $key, array $data)
    {
        global $bcmanager;
        $bcmanager->add_shorttype($this, $data['shorttype']);
        $this->logger = \Logger::getLogger("sc.$key");
        parent::__construct($key, $data);
    }

    /**
     * Abstract function renders the HTML for the page.
     */
    abstract public function render(): string;
}
