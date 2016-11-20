<?php 
namespace SmileScreen\Base;

use Carbon\Carbon;
use SmileScreen\Database as Database;

/**
 * BaseModel
 *
 * @package  Smilescreen\Base
 * @author Willmar Knikker <wil@wilv.in>
 * @version 0.1.0
 */
class BaseModel
{
    protected $idField = 'id';
    protected $id;
    protected $table;

    protected $values = array();
    protected $hiddenValues = array();

    protected $attributes = array();
    protected $hidden = array();

    protected $state = ModelStates::NOT_SAVED; 

    protected $timestamps = true;
    protected $created_on;
    protected $updated_on;

    protected static function getClassSnake($className) 
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]+/', '_$0', $className));
    }

    protected function constructed($attributes = [], $state) 
    {
    }

    public static function make($attributes = []) 
    {
        return new static($attributes);
    }

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

    public static function where(Database\SelectQuery $where)
    {
        return (Database\DatabaseSystem::getInstance())->modelsFromDatabase(new static(), $where);
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

    public function __debugInfo() 
    {
        $objectVars = get_object_vars($this);
        unset($objectVars['hiddenValues']);

        return $objectVars;
    }

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

    private function setTimestamp($stamp, $datetime) 
    {
        if (is_null($datetime)) {
            return; 
        }

        $timestamp = Carbon::createFromFormat('Y-m-d H:i:s', $datetime);
        
        if(strtolower($stamp) === 'created_on') 
        {
            $this->created_on = $timestamp;
            return;
        }

        $this->updated_on = $timestamp;
    }

    public function setCreatedOn($datetime) {
        $this->setTimestamp('created_on', $datetime);
    } 

    public function setUpdatedOn($datetime) {
        $this->setTimestamp('updated_on', $datetime);
    }

	public function usesTimestamps() 
	{
		return $this->timestamps;
	}

    public function getDatabaseTable() 
    {
        if (!isset($this->table)) {
            return static::getClassSnake(get_called_class()) . 's';
        }

        return $this->table;
    }

    public function getAllDatabaseAttributes(bool $includeId = true) 
    {
        if ($includeId) {
            return array_merge([$this->idField], $this->attributes, $this->hidden);
        }
        
        return array_merge($this->attributes, $this->hidden);
    }

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
   
    public function getModelState()
    { 
        return $this->state;
    }

	public function setModelState($state)
	{
		if(($this->state & ModelStates::FROM_DATABASE) != 0 && ($state & ModelStates::FROM_DATABASE) == 0) {
			// The model comes from the database.
			// the state can now not suddenly say its not from there.
			return false;	
		}

		$this->state = $state;
	}

    public function getIdField() 
    {
        return $this->idField;
    }

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

    public function getId() 
    {
        return $this->id; 
    }
}
