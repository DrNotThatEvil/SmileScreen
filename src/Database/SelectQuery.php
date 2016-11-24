<?php 
namespace SmileScreen\Database;

use SmileScreen\Exceptions\DatabaseSmileScreenException as DatabaseSmileScreenException;

class SelectQuery {
    protected $table;

    protected $select = array();

    protected $where = array();
    protected $whereComb;
    protected $whereRaw;
    protected $whereRawValues;

    protected $groupBy = array();

    protected $having = array();

    protected $order = array();

    protected $limit = array();

    public function __construct()
    {
        return $this;
    } 

    public function setTable($table) 
    {
        $this->table = $table;
        return $this;
    }

    public function select($ops)
    {
        if ($ops == '*') {
            $this->select[0] = '*';
        }

        $this->select = $ops;
        return $this;
    }

    public function where($ops, $comb = 'AND') 
    {
        $this->where = $ops;
        $this->whereComb = $comb;
        return $this;
    } 

    public function whereRaw($raw, $values)
    {
        $this->whereRaw = $raw;
        $this->whereRawValues = $values;
        return $this;
    }

    public function getStatement() 
    {
        if (!isset($this->table)) {
            throw new DatabaseSmileScreenException('SelectQuery has no table set');
        }

        if (count($this->select) == 0) {
            throw new DatabaseSmileScreenException('SelectQuery has no select statement set');
        }

        $statement = 'SELECT ';
        $whereValues = array();

        if (count($this->select) >= 1) {
            for($i=0; $i<count($this->select); $i++) {
                $statement .= $this->select[$i].($i < (count($this->select)-1) ? ', ' : ' ');
            }
        }

        $statement .= ' FROM ' . $this->table . ' ';
    

        if (!isset($this->whereRaw)) {
            for($i=0; $i<count(array_keys($this->where)); $i++) {
                $key = array_keys($this->where)[$i];

                if($i == 0) {
                    $statement .= 'WHERE ';
                }

                $statement .= $key . ' ' . $this->where[$key][0] . ' ? ' . ($i < (count(array_keys($this->where))-1) ? $this->whereComb . ' ' : '');
                $whereValues[] = $this->where[$key][1];
            }
        } else {
            $statement .= 'WHERE ' . $this->whereRaw;
            $whereValues = $this->whereRawValues;
        }

        return [trim($statement).';', $whereValues];
    }
}