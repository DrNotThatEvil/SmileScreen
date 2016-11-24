<?php
namespace SmileScreen\Database;

use SmileScreen\Database\Query as Query;

class InsertQuery extends Query
{
    protected $columns = [];
    
    protected $values = [];

    public function __construct() 
    {
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
        return (isset($this->table) && !empty($this->columns) && !empty($this->values));
    }

    public function getTable() 
    {
        return $this->table; 
    }

    public function getColumns() 
    {
        return $this->columns; 
    }

    public function getValues() 
    {
        return $this->values; 
    }

    public function getStatement() 
    {
        if(!$this->isValid()) {
            return false; 
        }

        $statement = 'INSERT INTO ';
        $statement .= $this->table . ' ';

        for ($i=0; $i<count($this->columns); $i++) {
            if ($i == 0) {
                $statement .= '(';
            }

            $statement .= $this->columns[$i];
            $statement .= ( $i < (count($this->columns)-1) ? ', ' : ') ');
        }

        if (!is_array($this->values[0])) {
            for($i=0; $i<count($this->values); $i++) {
                if ($i == 0) {
                    $statement .= 'VALUES (';
                }

                $statement .= ( $i < (count($this->columns)-1) ? '?, ' : '?)');
            }

            return $statement;
        }

        for ($i=0; $i<count($this->values); $i++) {
            if ($i == 0) {
                $statement .= 'VALUES ';
            }
            
            for($j=0; $j<count($this->values[$i]); $j++) {
                if ($j == 0) {
                    $statement .= '(';
                }

                $statement .= ( $j < (count($this->values[$i])-1) ? '?, ' : '?)');
            }

            $statement .= ( $i < (count($this->values)-1) ? ', ' : ';');
        }


        return $statement;
    }
}
