<?php namespace bookclub;

/**
 * Class implements Iterator, used for looping through the result set of a
 * query.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Wraps an iterator for results of database queries.
 */
class DatabaseIterator implements \Iterator
{
    /**
     * @var array containing query result set to iterate on
     */
    private $result;

    /**
     * @var int current index within the result set
     */
    private $position = 0;

    /**
     * Initialize the iterator.
     * @param array of results from a query
     * @return \bookclub\DatabaseIterator
     */
    public function __construct($result)
    {
        $this->result = $result;
    }

    /**
     * Fetch the current record from the result set.
     * @return array of keys (column name) and values for the current record
     */
    public function current()
    {
        return $this->result[$this->position];
    }

    /**
     * Fetch the number of records for the iterator.
     * @return int number of records in the result set
     */
    public function count()
   {
        return count($this->result);
    }

    /**
     * Return a reproducible identifier for the current position.
     * @return int integer representing the current position in the iterator
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Advance to the next record.
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Reset iterator to start of the result set.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Check if we are at the end or at a valid position in the result set.
     * @return bool true if there is a record at the current position
     */
    public function valid(): bool
    {
        return isset($this->result[$this->position]);
    }
}
