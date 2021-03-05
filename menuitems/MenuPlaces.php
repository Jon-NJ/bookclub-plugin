<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_places' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuPlaces extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuPlaces
     */
    public function __construct()
    {
        parent::__construct('bc_menu_places',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Edit Places',
                'menu_name'   => 'Places',
                'menu_rank'   => RANK_PLACES,
                'capability'  => 'edit_bc_places',
                'slug'        => 'bc_places',
                'help'        => 'menu_places',
                'script'      => 'menu_places.js',
                'style'       => 'menu_places.css',
                'nonce'       => 'places_nonce',
                'actions'     => [[
                        'key' => 'wp_ajax_bc_places_add',
                        'function' => 'places_add'
                    ],[
                        'key' => 'wp_ajax_bc_places_save',
                        'function' => 'places_save'
                    ],[
                        'key' => 'wp_ajax_bc_places_delete',
                        'function' => 'places_delete'
                    ]]
            ]);
    }

    /**
     * Fetch GET parameters, use them to generate HTML content.
     * @global string|null $_GET['action'] 'edit', 'search' or empty
     * @return string HTML content
     */
    public function render(): string
    {
        if (!parent::enqueue()) {
            return '';
        }
        $action = input_get('action');
        if ('search' === $action) {
            $json = $this->executeSearch();
        } elseif ('edit' === $action) {
            $json = $this->executeEdit();
        } else {
            $json = $this->executeStart();
        }
        return twig_render('menu_places', $json);
    }

    /**
     * Fetch JSON used for start state.
     * @return array JSON for TWIG rendering
     */
    private function executeStart(): array
    {
        $nonce = $this->create_nonce();
        $json = [
            'nonce'      => $nonce,
            'admin_url'  => url_admin_post(),
            'referer'    => url_menu('bc_places'),
            'title'      => \get_admin_page_title(),
            'images'     => url_images(),
            'mode'       => 'start',
            'place_id'   => '',
            'place'      => '',
            'address'    => '',
            'map'        => '',
            'directions' => ''
            ];
        return $json;
    }

    /**
     * Fetch JSON used for the edit state.
     * @global string $_GET['placeid'] place identifier
     * @return array JSON for TWIG rendering
     */
    private function executeEdit(): array
    {
        $placeid = input_get('placeid');
        $nonce = $this->create_nonce();
        $place = TablePlaces::findByID($placeid);
        $json = [
            'nonce'      => $nonce,
            'admin_url'  => url_admin_post(),
            'referer'    => url_menu('bc_places'),
            'title'      => \get_admin_page_title(),
            'images'     => url_images(),
            'mode'       => 'edit',
            'place_id'   => $place->place_id,
            'place'      => $place->place,
            'address'    => $place->address,
            'map'        => $place->map,
            'directions' => $place->directions
            ];
        return $json;
    }

    /**
     * Search database with the provided parameters.
     * @param string|null $placeid optional exact place identifier
     * @param string|null $place optional partial place name
     * @param string|null $address optional partial street address
     * @param string|null $map optional partial map URL
     * @param string|null $directions optional partial HTML directions
     * @return array JSON search results
     */
    private function search(?string $placeid, ?string $place, ?string $address,
            ?string $map, ?string $directions): array
    {
        $results = [];
        $iterator = new TablePlaces();
        $iterator->loopSearch($placeid, $place, $address, $map, $directions);
        while ($iterator->fetch()) {
            $results[] = [
                'place_id'   => $iterator->place_id,
                'place'      => $iterator->place,
                'address'    => $iterator->address,
                'map'        => $iterator->map,
                'directions' => $iterator->directions
            ];
        }
        return $results;
    }

    /**
     * Fetch JSON used for the search state.
     * @global string|null $_GET['placeid'] optional exact place identifier
     * @global string|null $_GET['place'] optional partial place name
     * @global string|null $_GET['address'] optional partial street address
     * @global string|null $_GET['map'] optional partial map URL
     * @global string|null $_GET['directions'] optional partial HTML directions
     * @return array JSON for TWIG rendering
     */
    private function executeSearch(): array
    {
        $placeid    = input_request('placeid');
        $name       = input_request('place');
        $address    = input_request('address');
        $map        = input_request('map');
        $directions = input_request('directions');
        $nonce      = $this->create_nonce();
        $json = [
            'nonce'      => $nonce,
            'admin_url'  => url_admin_post(),
            'referer'    => url_menu('bc_places'),
            'title'      => \get_admin_page_title(),
            'images'     => url_images(),
            'mode'       => 'search',
            'place_id'   => $placeid,
            'place'      => $name,
            'address'    => $address,
            'map'        => $map,
            'directions' => $directions
            ];
        $json['found'] = $this->search($placeid, $name, $address,
                $map, $directions);
        return $json;
    }

    /**
     * Insert new place with given parameters.
     * @param string $name place name
     * @param string $address street address
     * @param string $map map URL such as google maps
     * @param string $directions HTML directions
     * @return int new place identifier
     */
    private function insert(string $name, string $place_id, string $address,
            string $map, string $directions): int
    {
        $place             = new TablePlaces();
        $placeid           = is_numeric($place_id) ?
                $place_id : TablePlaces::getNextID();
        $place->place_id   = $placeid;
        $place->place      = $name;
        $place->address    = $address;
        $place->map        = $map;
        $place->directions = $directions;
        $place->insert();
        return $placeid;
    }

    /**
     * Add a place to the database. Generate a JSON response.
     * @global null|string $_REQUEST['place_id'] optional place identifier
     * @global null|string $_REQUEST['place'] place name
     * @global null|string $_REQUEST['map'] map URL such as google maps
     * @global null|string $_REQUEST['directions'] HTML directions
     */
    public function places_add(): void
    {
        $response = $this->check_request('Add place');
        if (!$response) {
            $placeid = $this->insert(input_request('place'),
                    input_request('placeid'), input_request('address'),
                    input_request('map'), input_request('directions'));
            $this->log_info("Place added $placeid");
            $response = $this->get_response(false, 'Place added');
            $response['place_id'] = $placeid;
        }
        exit(json_encode($response));
    }

    /**
     * Delete a place from the database. Generate a JSON response.
     * @global string $_REQUEST['placeid'] place identifier
     */
    public function places_delete(): void
    {
        $response = $this->check_request('Delete place');
        if (!$response) {
            $placeid = input_request('placeid');
            $this->log_info("Delete place id $placeid");
            TablePlaces::deleteByID($placeid);
            $response = $this->get_response(false, 'Place deleted');
        }
        exit(json_encode($response));
    }

    /**
     * Update database place.
     * @param int $placeid place identifier
     * @param string $name place name
     * @param string $address place street address
     * @param string $map map URL such as google maps
     * @param string $directions HTML directions
     */
    private function update(int $placeid, string $name, string $address,
            string $map, string $directions): void
    {
        $place             = TablePlaces::findByID($placeid);
        $place->place      = $name;
        $place->address    = $address;
        $place->map        = $map;
        $place->directions = $directions;
        $place->update();
    }

    /**
     * Update place. Generate a JSON response.
     * @global string $_REQUEST['placeid'] place identifier
     * @global string $_REQUEST['place'] place name
     * @global string $_REQUEST['address'] place street address
     * @global string $_REQUEST['map'] map URL such as google maps
     * @global string $_REQUEST['directions'] HTML directions
     */
    public function places_save(): void
    {
        $response = $this->check_request('Save place');
        if (!$response) {
            $this->update(input_request('placeid'), input_request('place'),
                    input_request('address'), input_request('map'),
                    input_request('directions'));
            $response = $this->get_response(false, 'Place updated');
        }
        exit(json_encode($response));
    }
}

new MenuPlaces();
