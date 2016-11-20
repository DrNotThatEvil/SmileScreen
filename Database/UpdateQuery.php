<?php
namespace SmileScreen\Database;

class UpdateQuery 
{
    protected $table;

    protected $idField = 'id';

    protected $id;

    protected $columns = [];
    
    protected $values = [];

    public function __construct() 
    {
        return $this;
    }

    public function setTable(string $table)
    {
        $this->table = $table;
        return $this;
    }

    public function setIdField(string $idField)
    {
        $this->idField = $idField; 
        return $this;
    }

    public function setId(int $id)
    {
        $this->id = $id; 
        return $this;
    }

    public function setColumns(array $columns) 
    {
        $this->columns = $columns; 
        return $this;
    }

    public function setValues(array $values)
    {
        $this->values = $values;
        return $this;
    }

    public function isValid() 
    {
        return (isset($this->table) 
            && !empty($this->columns)
            && !empty($this->values)
            && isset($this->id)
            && isset($this->idField));
    }

    public function getTable() 
    {
        return $this->table; 
    }

    public function getColumns() 
    {
        return $this->columns; 
    }

    public function getValues($withId = false) 
    {
        if($withId) {
            return array_merge($this->values, [$this->id]);
        }

        return $this->values; 
    }

    public function getStatement() 
    {
        if(!$this->isValid()) {
            return false; 
        }

        $statement = 'UPDATE ';
        $statement .= $this->table . ' SET ';

        for ($i=0; $i<count($this->columns); $i++) {
            $statement .= $this->columns[$i] . '=?';
            $statement .= ($i < (count($this->columns)-1) ? ', ' : ' '); 
        }

        $statement .= 'WHERE '.$this->idField.' = ?;';

        return $statement;
    }
}
