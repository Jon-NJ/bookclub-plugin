<?php namespace bookclub;

/*
 * Class wraps code used to generate a shortcode or to handle a menu item.
 * See shortcode examples in pages folder or menu examples in menu folder.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage framework
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Base object for short code renderers and dashboard menu items and other
 * managed objects.
 */
class Page
{
    /**
     * @var string each page has a unique key which may be used to identify
     * the logger or enqueuing or localizing scripts.
     */
    private $key;

    /**
     * @var array a JSON structure containing the remaining configuration data.
     */
    protected $data;

    /**
     * @var \Logger a logging object for debug, info and error reporting
     */
    protected $logger;

    /**
     * Construct a Page object.
     * @param string $key unique key for the page which may be used for various
     * purposes
     * @param array $data additional data in JSON format
     * @return \bookclub\Page
     */
    public function __construct(string $key, array $data)
    {
        global $bcmanager;
        $this->key = $key;
        $this->data = $data;
        $this->logger = \Logger::getLogger("page.$key");
        $bcmanager->add_page($this);
    }

    /**
     * Used for register_activation_hook. Currently does nothing.
     */
    public function activate(): void
    {
    }

    /**
     * Create a nonce string used for verifying requests.
     * @return string nonce based on 'nonce' key
     */
    protected function create_nonce(): string
    {
        // temporary change to map creation of nonces
        $id    = $this->data['nonce'];
        $nonce = \wp_create_nonce($id);
        TableLogs::addLog(['NONCE', $id, \get_current_user_id(), $nonce],
                "Create nonce: $nonce, id: $id @" . input_server('REMOTE_ADDR'));
        return $nonce;
    }

    /**
     * Create a JSON response object.
     * @param bool|int $error true if the response is an error or error number
     * @param string $message return success or error message
     * @param string $redirect optional redirect URL
     * @return array JSON response string with error, message (and redirect)
     */
    protected function get_response($error, string $message,
            string $redirect = ''): array
    {
        $response = [
            'error' => $error,
            'message' => $message
                ];
        if ($redirect) {
            $response['redirect'] = $redirect;
        }
        return $response;
    }

    /**
     * Test if the nonce REQUEST/GET parameter valid.
     * @return bool true if nonce is valid
     */
    protected function check_nonce(): bool
    {
        $nonce  = get_nonce();
        $result = \wp_verify_nonce($nonce, $this->data['nonce']);
        if (!$result) {
            $this->log_info("Bad nonce - $error ($nonce)");
        }
        return $result;
    }

    /**
     * Checks if REQUEST and if the 'nonce' value is correct for this page.
     * @param string $error additional string for error message
     * @return array JSON response or empty array if no error
     */
    protected function check_request(string $error): array
    {
        $response = [];
        if (!is_request()) {
            $this->log_error("Not a reqest - $error (" . input_referer() . ")");
            $response = $this->get_response(true, 'Bad request');
        } elseif (!$this->check_nonce()) {
            $response = $this->get_response(true,
                    'Bad nonce - refreshing the page may fix this');
        }
        return $response;
    }

    /**
     * Unregister any styles queued or registered for the page.
     */
    private function deactivate_style(): void
    {
        // unregister style
        $style = $this->data['style'];
        if (isset($style)) {
            if (\wp_style_is($this->key, 'enqueued' )) {
                \wp_dequeue_style($this->key);
            }
            if (\wp_style_is($this->key, 'registered' )) {
                \wp_deregister_style($this->key);
            }
        }
    }

    /**
     *  Dequeue and deregister any scripts for the page.
     */
    private function deactivate_script(): void
    {
        $script = $this->data['script'];
        if (isset($script)) {
            if (\wp_script_is($this->key, 'enqueued' )) {
                \wp_dequeue_script($this->key);
            }
            if (\wp_script_is($this->key, 'registered' )) {
                \wp_deregister_script($this->key);
            }
        }
    }

    /**
     * Remove any filters used for the page.
     */
    private function deactivate_filters(): void
    {
        if (isset($this->data['filters'])) {
            foreach ($this->data['filters'] as $filter) {
                if (\has_filter($filter['filter'], $filter['function'])) {
                    \remove_filter($filter['filter'], $filter['function']);
                }
            }
        }
    }

    /**
     * Remove any actions used for the page.
     */
    private function deactivate_actions(): void
    {
        if (isset($this->data['actions'])) {
            foreach ($this->data['actions'] as $action) {
                $key = $action['key'];
                $function = $action['function'];
                \remove_action($key, $function);
            }
        }
        if (isset($this->data['help'])) {
            \remove_action('wp_ajax_' . $this->data['slug'] . '_help',
                    [$this, 'get_help']);
        }
    }

    /**
     * Used for register_deactivation_hook.
     */
    public function deactivate(): void
    {
        // unregister style
        $this->deactivate_style();
        // unregister script
        $this->deactivate_script();
        // remove filters
        $this->deactivate_filters();
        // remove actions
        $this->deactivate_actions();
    }

    /**
     * Register the CSS style file if specified by 'style'.
     */
    private function register_style(): void
    {
        if (\array_key_exists('style', $this->data)) {
            $style = $this->data['style'];
            \wp_register_style($this->key, \plugins_url(
                    "css/$style", BOOKCLUBFILE));
        }
    }

    /**
     * Register the JavaScript file if specified by 'script'.
     */
    private function register_script(): void
    {
        if (\array_key_exists('script', $this->data)) {
            $script = $this->data['script'];
            \wp_register_script($this->key, \plugins_url(
                    "js/$script", BOOKCLUBFILE));
        }
        if (\array_key_exists('scripts', $this->data)) {
            $scripts = $this->data['scripts'];
            foreach ($scripts as $key => $script) {
                \wp_register_script($key, $script);
            }
        }
    }

    /**
     * Register all filters if specified by 'filters'.
     */
    private function register_filters(): void
    {
        if (isset($this->data['filters'])) {
            foreach ($this->data['filters'] as $filter) {
                $name = $filter['name'];
                $function = $filter['function'];
                $args = isset($filter['args']) ? $filter['args'] : 1;
                \add_filter($name, [$this, $function], 10, $args);
            }
        }
    }

    /**
     * Register all actions if specified by 'actions'.
     */
    private function register_actions(): void
    {
        if (isset($this->data['actions'])) {
            foreach ($this->data['actions'] as $action) {
                $key = $action['key'];
                $function = $action['function'];
                $args = isset($action['args']) ? $action['args'] : 1;
                \add_action($key, [$this, $function], 10, $args);
            }
        }
        if (isset($this->data['help'])) {
            \add_action('wp_ajax_' . $this->data['slug'] . '_help',
                    [$this, 'get_help'], 10);
        }
    }

    /**
     * Record a log entry with debug level.
     * @param string $message message to log
     */
    protected function log_debug(string $message): void
    {
        $this->logger->debug($message);
    }

    /**
     * Record a log entry with info level.
     * @param string $message message to log
     */
    protected function log_info(string $message): void
    {
        $this->logger->info($message);
    }

    /**
     * Record a log entry with error level.
     * @param string $message message to log
     */
    protected function log_error(string $message): void
    {
        $this->logger->error($message);
    }

    /**
     * Initialize the page, register the style, script, filters and actions.
     */
    public function init(): void
    {
        // register style
        $this->register_style();
        // register script
        $this->register_script();
        // add filters
        $this->register_filters();
        // add actions
        $this->register_actions();
    }

    /**
     * Queue up (and localize) styles and scripts used by the page.
     * @return bool false if user not allowed for defined capability
     */
    protected function enqueue(): bool
    {
        if (\array_key_exists('style', $this->data)) {
            \wp_enqueue_style($this->key);
        }
        if (\array_key_exists('script', $this->data)) {
            \wp_enqueue_script($this->key);
            \wp_localize_script($this->key, BOOKCLUB_AJAX_OBJECT,
                array('ajax_url' => url_admin_ajax()));
        }
        if (\array_key_exists('scripts', $this->data)) {
            $scripts = $this->data['scripts'];
            foreach ($scripts as $key => $script) {
                \wp_enqueue_script($key);
            }
        }
        // no capability defined
        if (!array_key_exists('capability', $this->data)) {
            return true;
        }
        // check user capabilities
        if (!\current_user_can($this->data['capability'])) {
            return false;
        }
        return true;
    }

    /**
     * Create a JSON response containing the rendered HTML help file.
     */
    public function get_help(): void
    {
        $this->log_debug("Get help");
        $response   = $this->get_response(false, 'Get help');
        $json       = twig_macro_fields([]);
        $json['md'] = $this->data['help'];
        $response['html'] = twig_render('markdown_help',$json);
        exit(json_encode($response));
    }
}
