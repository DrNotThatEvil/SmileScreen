<?php
namespace SmileScreen\Base;

use SmileScreen\Database as Database;

/**
 * BaseModel a class for all models to extend and get usefull functionality
 *
 * @package SmileScreen\Base
 * @author Willmar Knikker <wil@wilv.in>
 * @version 0.1.0
 */
class BaseModel
{
    /**
     * The primary key field in the database
     * @var string
     */
    protected $idField = 'id';

    /**
     * The id for the model from the database.
     * @var int
     */
    protected $id;

    /**
     * The database table for this model in the database
     * @var string
     */
    protected $table;

    /**
     * The values for this model.
     * the values can be get and set by accessing ->value.
     * @var array
     */
    protected $values = array();

    /**
     * The hidden valudes for this model.
     * These values can also get and set using ->value but are invisible by debug
     * @var array
     */
    protected $hiddenValues = array();

    /**
     * The attributes that come from the database.
     * @var array
     */
    protected $attributes = array();

    /**
     * The hidden attributes the come from the database.
     * @var array
     */
    protected $hidden = array();

    /**
     * The current state of the model this decides if the model needs to be save
     * and if the model is form the database.
     * @var int
     */
    protected $state = ModelStates::NOT_SAVED;

    /**
     * Sets if this model uses timestamps.
     * @var boolean
     */
    protected $timestamps = true;

    /**
     * The DateTime this model was created in the database.
     * @var DateTime
     */
    protected $created_on;

    /**
     * The DateTime this model was last updated in the database.
     * @var DateTime
     */
    protected $updated_on;

    /**
     * Gets the snake case for class name
     * @param  string $className The class name needed for snake case
     * @return string The snake for the class.
     */
    protected static function getClassSnake(string $className)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]+/', '_$0', $className));
    }

    protected function constructed($attributes = [], $state)
    {
    }

    /**
     * The make function generates a new instance of this class
     * @param  array $attributes The attributes for this class.
     * @return object            The newly generated object.
     */
    public static function make($attributes = [])
    {
        return new static($attributes);
    }

    /**
     * This function creates a object if it is not found in the databas 
     * @param  array $attributes   The attributes of model.
     * @param  string $whereCombine The Where combiner 'OR' (default) | 'AND' 
     * @return object               The model from the database or a newly saved model.
     */
    public static function insertIfNotExist($attributes, $whereCombine = 'OR')
    {
        $whereQuery = new Database\SelectQuery();
        $whereArray = [];
        foreach ($attributes as $attribute => $value) {
            $whereArray[$attribute] = ['=', $value];
        }

        $whereQuery->where($whereArray, $whereCombine);

        $results = static::where($whereQuery);

        return $results;
    }

    
    /**
     * Gets a list of objects matching to the SelectQuery
     *
     * @param Database\SelectQuery $where
     * @return array A array of objects or a empty array
     */
    public static function where(Database\SelectQuery $where)
    {
        $database = Database\DatabaseSystem::getInstance();
        $datbase->modelsFromDatabase(new static(), $where);
    }

    public function __construct($attributes = [], $state = ModelStates::NOT_SAVED)
    {
        foreach($attributes as $attribute => $value) {

            if ($attribute === $this->idField) {
                $this->id = $value;
                continue;
            }

            if (in_array($attribute, $this->hidden)) {
                $this->hiddenValues[$attribute] = $value;
            }

            if (in_array($attribute, $this->attributes) && !in_array($attribute, $this->hidden)) {
                $this->values[$attribute] = $value;
            }
        }

        $this->state = $state;

        $this->constructed($attributes, $state);
    }

    /**
     * Hides the hiddenValues array from debug printout
     *
     * @return void
     */
    public function __debugInfo()
    {
        $objectVars = get_object_vars($this);
        unset($objectVars['hiddenValues']);

        return $objectVars;
    }

    /**
     * The magic set method checks if you are trying to set a
     * parameter in Attriubtes or the HiddenAtributes and sets it.
     *
     * @param mixed $name Name of the value
     * @param mixed $value The value
     * @return void
     */
    public function __set($name, $value)
    {
        $attributes = $this->getAllDatabaseAttributes(false);
        if(!in_array($name, $attributes))
        {
            return;
        }

        if(in_array($name, $this->hidden)) {
            $this->hiddenValues[$name] = $value;
        }

        if(in_array($name, $this->attributes)) {
            $this->hiddenValues[$name] = $value;
        }

        $this->setModelState($this->state | ModelStates::NOT_SAVED);
    }

    /**
     * Sets the timestamp of this object.
     *
     * @param string $stamp The timestamp that needs to be set
     * @param string $datetime A Y-m-d H:i:s datetime string of the date.
     * @return void
     */
    private function setTimestamp(string $stamp, $datetime)
    {
        if (is_null($datetime)) {
            return;
        }

        $timestamp = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);

        if(strtolower($stamp) === 'created_on')
        {
            $this->created_on = $timestamp;
            return;
        }

        $this->updated_on = $timestamp;
    }

    /**
     * Sets the crated on timestamp of the model. 
     * Mostly syntactic sugar
     *
     * @param string $datetime 
     * @return void
     * @see BaseModel::setTimestamp
     */
    public function setCreatedOn($datetime) {
        $this->setTimestamp('created_on', $datetime);
    }

    /**
     * Sets the updated on timestamp of the model.
     * Mostly syntactic sugar
     *
     * @param mixed $datetime
     * @return void
     * @see \SmileScreen\Base\BaseModel::setTimestamp
     */
    public function setUpdatedOn($datetime) {
        $this->setTimestamp('updated_on', $datetime);
    }

	/**
	 * Returns if this model uses timestamps
	 *
	 * @return boolean True if this model uses Timestamps False if not
	 */
	public function usesTimestamps()
	{
		return $this->timestamps;
	}

    /**
     * Gets the database table of this model.
     *
     * @return string the database table of this model
     */
    public function getDatabaseTable()
    {
        if (!isset($this->table)) {
            return static::getClassSnake(get_called_class()) . 's';
        }

        return $this->table;
    }

    /**
     * Returns all database attributes of this model
     *
     * @param bool $includeId Should it include the Id in the attributes?
     * @return array array of the dataabase attributes of this model.
     */
    public function getAllDatabaseAttributes(bool $includeId = true)
    {
        if ($includeId) {
            return array_merge([$this->idField], $this->attributes, $this->hidden);
        }

        return array_merge($this->attributes, $this->hidden);
    }

    /**
     * Returns all databse values for this model
     * The order of the values is the same as the order of the attributes returned
     * by getAllDatabaseAttributes
     *
     * @param bool $includeId should it include the id in the values?
     * @return array A array of all the values for the database.
     * @see BaseModel::getAllDatabaseAttributes
     */
    public function getAllDatabaseValues($includeId = true) {
        $attributes = $this->getAllDatabaseAttributes($includeId);

        $values = [];
        foreach($attributes as $key => $attribute) {
            if ($attribute == $this->idField) {
                $values[$key] = $this->id;
                continue;
            }

            if (isset($this->hiddenValues[$attribute])) {
                $values[$key] = $this->hiddenValues[$attribute];
                continue;
            }

            if (isset($this->values[$attribute])) {
                $values[$key] = $this->values[$attribute];
                continue;
            }

            $values[$key] = null;
        }

        return $values;
    }

    /**
     * gets the models current modelstate
     *
     * @return int The state of the model
     * @see ModelStates
     */
    public function getModelState()
    {
        return $this->state;
    }
    
	/**
     * Sets the current state of the model.
     * Please note a models state can not be changed from FROM_DATABASE
     * ModelStates are binary numbers to make oprations easier
	 *
	 * @param int $state
	 * @return boolean true if the state was succesfully set
	 */
	public function setModelState(int $state)
	{
		if(($this->state & ModelStates::FROM_DATABASE) != 0 && ($state & ModelStates::FROM_DATABASE) == 0) {
			// The model comes from the database.
			// the state can now not suddenly say its not from there.
			return false;
		}

        $this->state = $state;
        return true;
	}

    /**
     * Gets the id field of this model usefull for the database system.
     *
     * @return string The id field in the databae.
     */
    public function getIdField()
    {
        return $this->idField;
    }

	/**
     * Sets the id of this model.
     * Warning its not possible to change a already assigned id cause
     * this would most certainly damage the intergrity of the database when saved.
	 *
	 * @param int $id
	 * @return boolean true if succesully set false otherwise
	 */
	public function setId($id)
	{
		if (isset($this->id)) {
			// I can't really think of a reason why the id would ever need to be changed mid operation.
			// Not only that but it's probably really bad practice to do so.
			// Thats why if the id has been set. It can and should not be changed.
			return false;
		}

		$this->id = $id;
		return true;
    }

    /**
     * gets the id of this model 
     *
     * @return id the id of the model
     */
    public function getId()
    {
        return $this->id;
    }
}
