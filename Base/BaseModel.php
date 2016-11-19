<?php 
namespace SmileScreen\Base;


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

    protected static function getClassSnake($className) 
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]+/', '_$0', $className));
    }

    protected function constructed($attributes = [], $state) 
    {
    }

    public static function make($attributes = []) 
    {
        
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
 

    public function getDatabaseTable() 
    {
        if (!isset($this->table)) {
            return static::getClassSnake(get_called_class()) . 's';
        }

        return $this->table;
    }

    public function getAllDatabaseAttributes() 
    {
        return array_merge([$this->idField], $this->attributes, $this->hidden);
    }
    
}
