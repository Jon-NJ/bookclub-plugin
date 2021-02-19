<?php namespace bookclub;

/*
 * Class wraps code used to generate the type='book' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage pages
 * @license    https://opensource.org/licenses/MIT MIT
 */

class FetchLogs extends REST
{
    /**
     * Initialize the object.
     * @return \bookclub\FetchLogs
     */
    public function __construct()
    {
        parent::__construct('logs', '/logs/');
    }

    /**
     * REST endpoint for /log/.
     * @param \WP_REST_Request $request the REST request
     * @return string JSON response
     */
    public function handle(\WP_REST_Request $request): string
    {
        $body = $request->get_body();
        $jobj = json_decode($request->get_body());
        $wkey = $jobj->pkey;
        $person = TableMembers::findByKey($wkey);
        $wpid = ($person && $person->wordpress_id) ? $person->wordpress_id : 0;
        if (!$wpid || !user_can($wpid, 'manage_options')) {
                header("HTTP/1.1 403 not authorized");
                $this->logger->error("Unauthorized REST call: $body");
                die();
        }
        $this->logger->debug("Log request $body");

        if (!isset($jobj->start)) {
            $start = date('Y-m-d') . ' 00:00:00';
        } else {
            $start = $jobj->start;
        }
        if (!isset($jobj->end)) {
            $end   = date('Y-m-d H:i:s');
        } else {
            $end   = $jobj->end;
        }
        $logs  = [];
        $type  = isset($jobj->type) ? $jobj->type : null;
        $parm1 = isset($jobj->parm1) ? $jobj->parm1 : null;
        $parm2 = isset($jobj->parm2) ? $jobj->parm2 : null;
        $parm3 = isset($jobj->parm3) ? $jobj->parm3 : null;
        $tobj  = new TableLogs();
        $tobj->loopBySelectors([$type, $parm1, $parm2, $parm3], $start, $end);
        while ($tobj->fetch()) {
            $logs[] = [
                'timestamp' => $tobj->timestamp,
                'type'      => $tobj->type,
                'param1'    => $tobj->param1 ?: '',
                'param2'    => $tobj->param2 ?: '',
                'param3'    => $tobj->param3 ?: '',
                'message'   => $tobj->message
            ];
        }
        $response = [
            'answer' => 'OK',
            'logs'   => $logs
        ];
        return json_encode($response);
    }
}

new FetchLogs();
