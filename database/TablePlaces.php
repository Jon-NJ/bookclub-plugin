<?php namespace bookclub;

/*
 * Class provides access to database places table. This is used in conjunction
 * with the dates table.
 *
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage database
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Access to the places table.
 *
 * @ORM\Table(name="bc_places")
 * @ORM\Entity
 */
class TablePlaces extends DatabaseTable
{
    /**
     * @var array of strings, local storage of column names
     */
    private static $_columns = null;

    /**
     * @var int unique identifier for location or zero
     *
     * @ORM\Column(name="place_id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $place_id = 0;

    /**
     * @var string place name, short string
     *
     * @ORM\Column(name="place", type="string", length=20, nullable=false, options={"default"="''"})
     */
    public $place = '';

    /**
     * @var string|null street address
     *
     * @ORM\Column(name="address", type="string", length=60, nullable=true, options={"default"="NULL"})
     */
    public $address = '';

    /**
     * @var string|null URL to map location
     *
     * @ORM\Column(name="map", type="string", length=100, nullable=true, options={"default"="NULL"})
     */
    public $map = '';

    /**
     * @var string|null detailed description how to get there
     *
     * @ORM\Column(name="directions", type="text", length=65535, nullable=true, options={"default"="NULL"})
     */
    public $directions = '';

    /**
     * Initialize the object.
     * @return \bookclub\TablePlaces
     */
    public function __construct()
    {
        parent::__construct('places');
    }

    /**
     * Fetch the list of column names for the table.
     * @return array of column name strings
     */
    protected function getColumns(): array
    {
        if (is_null(self::$_columns)) {
            self::$_columns = parent::addColumns();
        }
        return self::$_columns;
    }

    /**
     * Delete the record for the given place.
     * @param int $place_id unique identifier for the place
     */
    public static function deleteByID(int $place_id): void
    {
        $place = new TablePlaces();
        $place->deleteByString(['place_id' => $place_id]);
    }

    /**
     * Find the place for the given identifier.
     * @param int $place_id unique identifier for the place
     * @return TablePlaces|null record for the place or null if not found
     */
    public static function findByID(int $place_id): ?TablePlaces
    {
        $place = new TablePlaces();
        $place->findByString(['place_id' => $place_id]);
        return $place->fetch() ? $place : null;
    }

    /**
     * Find the place for the given place name.
     * @param string $name place name
     * @return TablePlaces|null record for the place or null if not found
     */
    public static function findByPlace($name)
    {
        $place = new TablePlaces();
        $place->findByString(['place' => $name]);
        return $place->fetch() ? $place : null;
    }

    /**
     * Fetch a new identifier that has not been used - normally MAX + 1.
     * @return int unique identifier to use if a new record is inserted
     */
    public static function getNextID(): int
    {
        $place = new TablePlaces();
        $place->select('COALESCE(MAX(place_id),0) + 1 AS newid');
        $rows = $place->execute();
        return (int) $rows[0]->newid;
    }

    /**
     * Start looping through all places on identifier order.
     */
    public function loopByID(): void
    {
        parent::loopOrder('place_id');
    }

    /**
     * Start looping through places matching the search criteria.
     * @param int|null $placeid unique identifier for the place if given
     * @param string|null $place name of place
     * @param string|null $address place address
     * @param string|null $map URL to map location
     * @param string|null $directions directions to get to the place
     */
    public function loopSearch(?int $placeid, ?string $place, ?string $address,
            ?string $map, ?string $directions): void
    {
        $fields = [];
        $values = [];

        $this->addSearch($fields, $values, 'place_id = %s', $placeid, false);
        $this->addSearch($fields, $values, 'place like %s', $place);
        $this->addSearch($fields, $values, 'address like %s', $address);
        $this->addSearch($fields, $values, 'map like %s', $map);
        $this->addSearch($fields, $values, 'directions like %s', $directions);
        $this->select();
        $this->where($fields);
        $this->orderBy('place');
        $this->prepare($values);
        $this->iterate();
    }

    /**
     * Create an INSERT statement for all columns using the current values.
     */
    public function insert(): void
    {
        parent::insert();
    }

    /**
     * Update the database from the object data.
     */
    public function update(): void
    {
        parent::updateByString(['place_id']);
    }
}
