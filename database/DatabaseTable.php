<?php namespace bookclub;

/*
 * Abstract class inherited by single or joined tables. Provides common database
 * operations.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Base (abstract) database table element providing fetch, update, insert,
 * delete and iteration through a result set.
 */
abstract class DatabaseTable
{
    /**
     *
     * @var string base name of table without prefix
     */
    private $_base;

    /**
     * @var string name of the table represented by the object
     */
    private $_table;

    /*
     * @var bool when false, don't log this object
     */
    private $_logging;

    /**
     * @var null|array containing key (table name) and value (join condition)
     */
    private $_joins = null;

    /**
     * @var string SQL for query builder methods
     */
    protected $_sql = '';

    /**
     * @var null|array of records (columns as keys and values) from last query
     */
    private $_result = null;

    /** functions used for initialization */

    /**
     * Construct a table object with fields based on the database table.
     * @param string $table the name of the table
     * @param bool $logging optional (true), when false the object is not logged
     * @return \bookclub\DatabaseTable
     */
    protected function __construct(string $table, bool $logging = true)
    {
        $this->_base  = $table;
        $this->_table = tablePrefix($table);
        $this->_logging = $logging;
//        $columns = $this->getColumns();
//        foreach ($columns as $column) {
//            $this->$column = null;
//        }
    }

    /**
     * Abstract function to fetch all column names for single or joined database
     * tables. Normally these are stored statically in the child class.
     * @return array of strings column names fetched from storage
     */
    abstract protected function getColumns(): array;

    /**
     * Fetch an array containing the column names for the given table.
     * @param string $table name of database table
     * @return array of strings column names queried from the database
     */
    protected function getColumnNames(string $table): array
    {
        global $wpdb;
        $output = [];
        if (!stripos($table, ' AS ')) {
            $sql = "SHOW COLUMNS FROM $table";
            $result = $wpdb->get_results($sql);
            foreach ($result as $row) {
                $output[] = $row->Field;
            }
        }
        return $output;
    }

    /**
     * Utility function to add columns from a table assuming they are not
     * already in the target array.
     * @param array $target array of strings, column names
     * @param string $table
     */
    private function addColumnsTable(array &$target, string $table): void
    {
        $columns = $this->getColumnNames($table);
        foreach ($columns as $column) {
            if (!in_array($column, $target)) {
                $target[] = $column;
            }
        }
    }

    /**
     * Utility function to add columns all tables assuming they are not
     * already in the target array.
     * @param array $target array of strings, column names
     * @return array collection of column names
     */
    protected function addColumns(): array
    {
        $target = [];
        $this->addColumnsTable($target, $this->_table);
        if ($this->_joins) {
            foreach ($this->_joins as $table => $join) {
                $this->addColumnsTable($target, $table);
            }
        }
        return $target;
    }

    /**
     * Add join information during initialization.
     * @param string $table name of the table being joined
     * @param string $fields join condition
     * @param string $type join type defaults to "LEFT"
     */
    protected function join(string $table, string $fields, string $type = 'LEFT'): void
    {
        if (!$this->_joins) {
            $this->_joins = [];
        }
        $this->_joins[tablePrefix($table)] = [
            'fields' => $fields,
            'type'   => $type
                ];
    }

    /**
     * Return the logger for this object.
     * @return \Logger logger corresponding to this object
     */
    protected function getLogger(): \Logger
    {
        $logname = 'db.'.$this->_base;
        if ($this->_joins) {
            $logname = 'db.join';
        }
        return \Logger::getLogger($logname);
        
    }

    /** query building and execution */

    /**
     * Create a SELECT statement for all table fields. If the table object is a
     * join, also add those fields and the join clause.
     * @param string|array $fields (optional) for the select, empty for all columns
     * @param string|null $additional (optional) additional columns
     * @return DatabaseTable to allow concatenated calls
     */
    protected function select($fields = '', $additional = '')
    {
        if ($this->_joins) {
            if ($fields) {
                $this->_sql = "SELECT $fields ";
            } else {
                $this->_sql = 'SELECT ';
                // first add joined tables because with a
                // left join some of these may be null even
                // if they are not null in the main table
                foreach ($this->_joins as $table => $join) {
                    $pos = stripos($table, ' AS ');
                    $this->_sql .= substr($table, $pos ? $pos + 4 : 0) . '.*, ';
                }
                $this->_sql .= "$this->_table.*";
                if ($additional) {
                    $this->_sql .= ", $additional";
                }
            }
            $this->_sql .= " FROM $this->_table";
            foreach ($this->_joins as $table => $join) {
                $this->_sql .= ' ' . $join['type'] . " JOIN $table ON " .
                        $join['fields'];
            }
        } elseif ($fields) {
            $this->_sql = "SELECT $fields FROM $this->_table";
        } else {
            $this->_sql = "SELECT * FROM $this->_table";
        }
        return $this;
    }

    /**
     * Create an UPDATE statement. Append the SET field or fields.
     * @param string|array $sets single or array of SET assignments
     * @return $this to allow concatenated calls
     */
    protected function updateSet($sets)
    {
        $this->_sql = "UPDATE $this->_table SET ";
        if (!is_array($sets)) {
            $this->_sql .= $sets;
        } else {
            $this->_sql .= implode(', ', $sets);
        }
        return $this;
    }

    /**
     * The currently created SELECT statement is used as the basis for a new
     * select statement for the specified fields.
     * @param string $fields one or more select fields
     * @return $this to allow concatenated calls
     */
    protected function reselect(string $fields)
    {
        $this->_sql = "SELECT $fields FROM ($this->_sql)";
        return $this;
    }

    /**
     * Create a DELETE statement.
     * @return $this to allow concatenated calls
     */
    protected function delete()
    {
        $this->_sql = "DELETE FROM $this->_table";
        return $this;
    }

    /**
     * Create an INSERT statement for all columns using the current values.
     */
    protected function insert(): void
    {
        $binds = [];
        $values = [];
        $columns = $this->getColumns();
        foreach ($columns as $column) {
            if (is_null($this->$column)) {
                $binds[] = 'NULL';
            } else {
                $binds[] = '%s';
                $values[] = $this->$column;
            }
        }
        $this->_sql =
            "INSERT INTO $this->_table (" .
             implode(', ', $columns) .
             ') VALUES (' .
             implode(', ', $binds) .
             ')';
        $this->prepare($values);
        $this->execute();
    }

    /**
     * Append a WHERE clause to the current statement.
     * @param string|array $conditions one or more WHERE conditions
     * @return $this to allow concatenated calls
     */
    protected function where($conditions)
    {
        if (!is_array($conditions)) {
            $this->_sql .= " WHERE $conditions";
        } else {
            if (sizeof($conditions) > 0) {
                $this->_sql .= ' WHERE ' . join(' AND ', $conditions);
            }
        }
        return $this;
    }

    /**
     * Append an AND clause for the previous WHERE.
     * @param string $condition an additional WHERE condition
     * @return $this to allow concatenated calls
     */
    protected function and(string $condition)
    {
        $this->_sql .= " AND $condition";
        return $this;
    }

    /**
     * Append an ORDER BY clause for the current statement.
     * @param string $order the field or fields to sort by
     * @return $this to allow concatenated calls
     */
    protected function orderBy(string $order)
    {
        $this->_sql .= " ORDER BY $order";
        return $this;
    }

    /**
     * Append a LIMIT clause for the current statement.
     * @param int $count the field or fields to sort by
     * @return $this to allow concatenated calls
     */
    protected function limit(int $count)
    {
        $this->_sql .= " LIMIT $count";
        return $this;
    }

    /**
     * Reformat current statement to safely include given parameters.
     * @param array $parms contains parameter values for the current statement
     */
    protected function prepare(array $parms): void
    {
        global $wpdb;
        $this->_sql = $wpdb->prepare($this->_sql, $parms);
    }

    /**
     * Executes the current SQL statement and returns the result set.
     * @return array result set (array of records where each is a dictionary)
     */
    protected function execute(): array
    {
        global $wpdb;
        if ($this->_logging) {
            $logger = $this->getLogger();
            $logger->debug($this->_sql);
        }
        $results = $wpdb->get_results($this->_sql);
        if ($this->_logging && $wpdb->last_error) {
            $logger->error($wpdb->last_error);
            $logger->error($this->_sql);
        }
        return $results;
    }

    /**
     * Execute the current statement and start an iterator for the results.
     */
    protected function iterate(): void
    {
        $this->_result = new DatabaseIterator($this->execute());
    }

    /**
     * Fetch the number of rows returned from last query.
     * @return int number of rows returned from last result set
     */
    protected function count(): int
    {
        return $this->_result->count();
    }

    /**
     * Advance to the next record (if any) and load the fields into this.
     * @return bool true if there is a valid record for the current iterator
     */
    public function fetch(): bool
    {
        $valid = $this->_result->valid();
        if ($valid) {
            $row = $this->_result->current();
            $this->_result->next();
            foreach ($row as $key => $value) {
                $this->$key = $value;
            }
        }
        return $valid;
    }

    /** utility functions */

    /**
     * Utility function to add search conditions if value is specified.
     * If the value is numeric, the search condition is exact, otherwise
     * the search selector is made with "LIKE".
     * @param array $fields target collection of search fields
     * @param array $values target collection of search values
     * @param string $field name of the field to search on
     * @param string|int $value to match the field with
     * @param bool optional flag whether field is like or not
     */
    protected function addSearch(array &$fields, array &$values, string $field,
            $value, bool $like = true): void
    {
        if ($value) {
            $fields[] = $field;
            if (!$like) {
                $values[] = $value;
            } else {
                $values[] = '%%' . $value . '%%';
            }
        }
    }

    /*
     * Update primary fields to new values.
     * @param array $fields dictionary array of field names and values
     */
    protected function changeKey(array $fields): void
    {
        $sets  = [];
        $binds = [];
        $this->delete();
        $first = true;
        foreach ($fields as $field => $value) {
            $sets[]  = "$field = %s";
            $binds[] = $value;
        }
        $this->updateSet($sets);
        foreach ($fields as $field => $value) {
            if ($first) {
                $this->where("$field = %s");
            } else {
                $this->and("$field = %s");
            }
            $binds[] = $this->$field;
            $this->$field = $value;
            $first = false;
        }
        $this->prepare($binds);
        $this->execute();
    }

    /**
     * Delete a record from the table where the fields matches the value.
     * @param array $fields dictionary array of field names and values
     */
    protected function deleteByString(array $fields): void
    {
        $binds = [];
        $this->delete();
        $first = true;
        foreach ($fields as $field => $value) {
            if ($first) {
                $this->where("$field = %s");
            } else {
                $this->and("$field = %s");
            }
            $binds[] = $value;
            $first = false;
        }
        $this->prepare($binds);
        $this->execute();
    }

    /**
     * Find a record from the table where the field(s) matches the value(s).
     * @param array $fields dictionary array of field names and values
     */
    protected function findByString(array $fields): void
    {
        $binds = [];
        $this->select();
        $first = true;
        foreach ($fields as $field => $value) {
            if ($first) {
                $this->where("$field = %s");
            } else {
                $this->and("$field = %s");
            }
            $binds[] = $value;
            $first = false;
        }
        $this->prepare($binds);
        $this->iterate();
    }

    /**
     * Start looping through all records of the table.
     * @param string $order the ORDER BY field or fields
     */
    protected function loopOrder(string $order): void
    {
        $this->select();
        $this->orderBy($order);
        $this->iterate();
    }

    /**
     * Write back all columns based on the current contents and the key fields.
     * @param array $fields array of fields to match when writing back current record
     */
    protected function updateByString(array $fields): void
    {
        $binds = [];
        $sets = [];
        $columns = $this->getColumns();
        foreach ($columns as $column) {
            if (is_null($this->$column)) {
                $sets[] = "$column = NULL";
            } else {
                $sets[] = "$column = %s";
                $binds[] = $this->$column;
            }
        }
        $this->updateSet($sets);
        $first = true;
        foreach ($fields as $field) {
            if ($first) {
                $this->where("$field = %s");
            } else {
                $this->and("$field = %s");
            }
            $binds[] = $this->$field;
            $first = false;
        }
        $this->prepare($binds);
        $this->execute();
    }
}
