<?php namespace bookclub;

/*
 * Class wraps code used to generate the menu 'bc_covers' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage menuitems
 * @license    https://opensource.org/licenses/MIT MIT
 */

class MenuCovers extends MenuItem
{
    /**
     * Initialize the object.
     * @return \bookclub\MenuCovers
     */
    public function __construct()
    {
        parent::__construct('bc_menu_covers',
            [
                'parent_slug' => 'bc_menu',
                'page_title'  => 'Book Covers',
                'menu_name'   => 'Book Covers',
                'menu_rank'   => RANK_COVERS,
                'capability'  => 'edit_bc_covers',
                'slug'        => 'bc_covers',
                'help'        => 'menu_covers',
                'script'      => 'menu_covers.js',
                'style'       => 'menu_covers.css',
                'nonce'       => 'covers_nonce',
                'actions'     => [[
                        'key' => 'wp_ajax_bc_covers_upload',
                        'function' => 'covers_upload'
                    ],[
                        'key' => 'wp_ajax_bc_covers_rename',
                        'function' => 'covers_rename'
                    ],[
                        'key' => 'wp_ajax_bc_covers_delete',
                        'function' => 'covers_delete'
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
        return twig_render('menu_covers', $json);
    }

    /**
     * Fetch JSON used for start state.
     * @return array JSON for TWIG rendering
     */
    private function executeStart(): array
    {
        $nonce = $this->create_nonce();
        $json = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_covers'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'start',
            'covers'    => getCovers(),
            'cover'     => '',
            'older'     => '',
            'ounit'     => 'days',
            'younger'   => '',
            'yunit'     => 'days'
        ];
        return $json;
    }

    /**
     * Fetch JSON used for the edit state.
     * @global string $_GET['cover'] cover filename
     * @return array JSON for TWIG rendering
     */
    private function executeEdit(): array
    {
        $cover = input_get('cover');
        $nonce = $this->create_nonce();
        $json = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_covers'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'edit',
            'baseurl'   => url_cover(''),
            'covers'    => getCovers(),
            'cover'     => $cover
            ];
        return $json;
    }

    /**
     * Generate a timestamp in the past for the specified amount.
     * @param string|null $relative count if defined
     * @param string|null $units months/weeks/days - default to days
     * @return int timestamp or zero if not defined
     */
    private function get_timestamp(?string $relative, ?string $units): int
    {
        if ('months' === $units) {
            $spec = 'M';
        } elseif ('weeks' === $units) {
            $spec = 'W';
        } else {
            $spec = 'D';
        }
        if ($relative) {
            $when = new \DateTime();
            $when->sub(new \DateInterval("P$relative$spec"));
            $ret = $when->getTimestamp();
        } else {
            $ret = 0;
        }
        return $ret;
    }

    /**
     * Search for files with the provided parameters.
     * @param string|null $pattern optional partial filename
     * @param array $covers optional partial cover filename
     * @param int|null $older optional older than timestamp
     * @param int|null $younger optional younger than timestamp
     * @return array JSON for TWIG rendering
     */
    private function search(?string $pattern, array $covers,
            ?int  $older, ?int $younger): array
    {
        $results = [];
        $pattern = strtolower($pattern);
        $id = 0;
        foreach ($covers as $cover) {
            if (('' == $pattern) ||
                    (false !== strpos(strtolower($cover), $pattern))) {
                $ts = filemtime(folder_covers() . $cover);
                if (((0 == $older) || ($ts < $older)) &&
                        ((0 == $younger) || ($ts > $younger))) {
                    $results[] = [
                        'id'    => $id++,
                        'cover' => $cover
                            ];
                }
            }
        }
        return $results;
    }

    /**
     * Fetch JSON used for the search state.
     * @global string|null $_GET['cover'] optional partial cover filename
     * @global string|null $_GET['older'] optional days/weeks/months older than
     * amount
     * @global string|null $_GET['younger'] optional days/weeks/months younger
     * than amount
     * @global string|null $_GET['ounit'] unit days/weeks/months for older than
     * @global string|null $_GET['yunit'] unit days/weeks/months for younger
     * than
     * @return array JSON for TWIG rendering
     */
    private function executeSearch(): array
    {
        $cover   = input_get('cover');
        $older   = input_get('older');
        $younger = input_get('younger');
        $ounit   = input_get('ounit');
        $yunit   = input_get('yunit');
        $covers  = getCovers();
        $nonce   = $this->create_nonce();
        $json = [
            'nonce'     => $nonce,
            'admin_url' => url_admin_post(),
            'referer'   => url_menu('bc_covers'),
            'title'     => \get_admin_page_title(),
            'images'    => url_images(),
            'mode'      => 'search',
            'folder'    => folder_covers(),
            'baseurl'   => url_cover(''),
            'covers'    => $covers,
            'cover'     => $cover,
            'older'     => $older,
            'ounit'     => $ounit,
            'younger'   => $younger,
            'yunit'     => $yunit
            ];
        $oldts   = $this->get_timestamp($older, $ounit);
        $youngts = $this->get_timestamp($younger, $yunit);
        $json['found'] = $this->search($cover, $covers, $oldts, $youngts);
        return $json;
    }

    /**
     * Upload one or more cover files from an AJAX POST. Generate a JSON
     * response.
     * @global array $_FILES[] uploaded files
     */
    public function covers_upload(): void
    {
        $response = $this->check_request('Add cover');
        if (!$response) {
            $count_success = 0;
            $count_error = 0;
            $count_rename = 0;
            $last_success = '';
            foreach ($_FILES as $key => $file) {
                $cover = $file['name'];
                $newname = $cover;
                $parts = pathinfo($cover);
                $renumber = 0;
                do {
                    $exists = file_exists(folder_covers() . $newname);
                    if ($exists) {
                        ++$renumber;
                        $newname = $parts['filename'] . " ($renumber)." .
                                $parts['extension'];
                    }
                } while($exists);
                $success = move_uploaded_file($file['tmp_name'],
                        folder_covers() . $newname);
                if ($success) {
                    $last_success = $newname;
                    ++$count_success;
                    if ($renumber) {
                        ++$count_rename;
                    }
                    $this->log_info("Cover uploaded \"$newname\"");
                } else {
                    ++$count_error;
                    $this->log_error("Cover upload \"$newname\" FAILED");
                }
            }
            if ($count_success) {
                $message = "Successful uploads: $count_success";
                if ($count_rename) {
                    $message = $message . " ($count_rename renamed)";
                }
                if ($count_error) {
                    $message = $message . ", failed: $count_error";
                }
            } else {
                $message = "Upload failed - $count_error file(s)";
            }
            $response = $this->get_response(0 == $count_success, $message);
            $response['cover'] = $last_success;
        }
        exit(json_encode($response));
    }

    /**
     * Rename the cover filename. Generate a JSON response.
     * @global string $_REQUEST['original'] original filename
     * @global string $_REQUEST['newname'] new filename
     */
    public function covers_rename(): void
    {
        $response = $this->check_request('Rename cover');
        if (!$response) {
            $original = input_request('original');
            $newname = input_request('newname');
            if (file_exists(folder_covers() . $newname)) {
                $response = $this->get_response(true,
                        'Cannot rename, file exists');
                $this->log_error("Cover rename $original -> $newname EXISTS");
            } else {
                $result = rename(folder_covers() . $original,
                        folder_covers() . $newname);
                if ($result) {
                    $response = $this->get_response(false, 'Cover renamed');
                    $this->log_info("Cover rename $original -> $newname");
                } else {
                    $response = $this->get_response(true, 'Rename failed');
                    $this->log_error(
                            "Cover rename $original -> $newname FAILED");
                }
            }
        }
        exit(json_encode($response));
    }

    /**
     * Delete a book cover file. Generate a JSON response.
     * @global string $_REQUEST['cover'] cover filename
     */
    public function covers_delete(): void
    {
        $response = $this->check_request('Delete cover');
        if (!$response) {
            $cover = input_request('cover');
            $result = unlink(folder_covers() . $cover);
            if ($result) {
                $response = $this->get_response(false, 'Cover removed');
                $this->log_info("Cover remove \"$cover\"");
            } else {
                $response = $this->get_response(true, 'Delete failed');
                $this->log_error("Cover remove \"$cover\" FAILED");
            }
        }
        exit(json_encode($response));
    }
}

new MenuCovers();
