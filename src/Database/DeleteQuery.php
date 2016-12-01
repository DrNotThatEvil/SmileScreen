<?php 
namespace SmileScreen\Database;

use SmileScreen\Database\Query as Query;

class DeleteQuery extends Query
{
    protected $idField = 'id';

    protected $id;

    public function __construct() 
    {
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

    public function getid()
    {
        return $this->id;
    }

    public function isValid() 
    {
        return (isset($this->table) 
            && isset($this->id)
            && isset($this->idField));
    }

    public function getTable() 
    {
        return $this->table; 
    }

    public function getStatement() 
    {
        if(!$this->isValid()) {
            return false; 
        }

        $statement = 'DELETE FROM ';
        $statement .= $this->table;

        $statement .= ' WHERE '.$this->idField.' = ?;';

        return $statement;
    }
}
