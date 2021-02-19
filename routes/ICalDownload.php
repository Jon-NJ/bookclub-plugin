<?php namespace bookclub;

/*
 * Class wraps code used to download an iCalendar file.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage pages
 * @license    https://opensource.org/licenses/MIT MIT
 */

class ICalDownload extends Path
{
    /**
     * Initialize the object.
     * @return \bookclub\PageRSVP
     */
    public function __construct()
    {
        parent::__construct('bc_ical', '/bc_ical/');
        $this->data['nonce'] = 'ical_nonce';
    }

    /**
     * Fetch a URL for downloading an ICAL file for the given user and event.
     * @param string $pkey web key for the given user
     * @param string $eventid event identifier
     * @return string URL for downloading an ical file
     */
    public function url_ical(string $pkey, string $eventid): string
    {
        $nonce = $this->create_nonce();
        return url_site() . "/bc_ical/?pkey=$pkey&eid=$eventid&nonce=$nonce";
    }

    /**
     * Hook for requests starting with /bc_ical/. If recognized, a download of
     * the ICAL for the given user and event identifier is triggered, avoiding
     * the normal request handling.
     * @global string $_GET['eid'] event identifier
     * @global string $_GET['pkey'] WordPress user identifier
     */
    public function handle(): void
    {
        $this->log_debug('Download iCal');
        $eventid = input_get('eid');
        $pkey    = input_get('pkey');

        if (!$this->check_nonce()) {
            $this->log_error('Bad nonce');
            header("HTTP/1.1 403 Bad nonce");
            die();
        }

        header('Content-Disposition: attachment; filename="bookclub.ics"');
        header('Content-Type: application/octet-stream');

        $event  = TableEvents::findByID($eventid);
        $member = TableMembers::findByKey($pkey);
        $user   = $member->wordpress_id ?
                \get_userdata($member->wordpress_id) : null;
        $json   = twig_macro_fields([$member, $event, $user]);
        exit(twig_render('email_invite_ical', $json));
    }
}

$GLOBALS['icaldownload'] = new ICalDownload;
