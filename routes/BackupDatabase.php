<?php namespace bookclub;

/*
 * Class wraps code used to backup the WordPress database.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage routes
 * @license    https://opensource.org/licenses/MIT MIT
 */

class BackupDatabase extends REST
{
    /**
     * Initialize the object.
     * @return \bookclub\BackupDatabase
     */
    public function __construct()
    {
        parent::__construct('backupdb', '/backupdb/');
    }

    /**
     * Fetch a list of tables from the database that start with the given prefix.
     * @param type $prefix table name prefix
     * @return array list of tables
     */
    private function get_tables($prefix): array
    {
        global $wpdb;
        return $wpdb->get_col("SHOW TABLES LIKE '$prefix%'", 0);
    }

    /**
     * REST endpoint for /backupdb/.
     * @param \WP_REST_Request $request the REST request
     * @return string JSON response
     */
    public function handle(\WP_REST_Request $request): string
    {
        global $wpdb;

        // curl -k -X POST -J -O -d "{\"pkey\":\"--web key--\",\"type\":\"all\"}" https://wordpress.ultra.sunshine/wp-json/bc/v1/backupdb/
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
        $this->logger->info("Backup database $body");
        $tables = [];
        if (!\array_key_exists('type', $jobj)) {
            $jobj->type = 'all';
        }
        if ('bconly' == $jobj->type) {
            $tables = $this->get_tables(tablePrefix(''));
        } elseif ('wponly' == $jobj->type) {
            $tables = $wpdb->tables();
        } else { // 'all' == $jobj->type
            $tables = $this->get_tables($wpdb->prefix);
        }
        $db_name = DB_NAME;
        $command = "mysqldump --host ".DB_HOST." -u ".DB_USER." -p".DB_PASSWORD." $db_name " . join(' ', $tables) . " > backup.sql";
        $filename = "$db_name.sql.gz";

        header("Content-Type: application/x-gzip");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        exec($command);
        passthru("gzip --best < backup.sql");
        unlink("backup.sql");
        exit(0);
    }
}

new BackupDatabase();
