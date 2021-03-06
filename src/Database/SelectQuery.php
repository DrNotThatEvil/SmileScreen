<?php 
namespace SmileScreen\Database;

use SmileScreen\Exceptions\DatabaseSmileScreenException as DatabaseSmileScreenException;
use SmileScreen\Database\Query as Query;

class SelectQuery extends Query {
    protected $select = array();

    protected $where = array();
    protected $whereComb;
    protected $whereRaw;
    protected $whereRawValues;
    
    protected $fullTextOn = false;
    protected $fullTextFields = array();
    protected $fullTextValue = array();

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

    public function fullText(array $fields, array $match)
    {
        $this->fullTextOn = true;
        $this->fullTextFields = $fields;
        $this->fullTextValue = $match;
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

    public function orderBy($orderBy) 
    {
        $this->order = $orderBy;    
    }

    public function isFullText()
    {
        return $this->fullTextOn; 
    } 

    public function getFullTextValue() 
    {
        $build = '';
        foreach($this->fullTextValue as $value) {
            $build .= $value.'* ';
        }
        return $build; 
    }

    public function getStatement() 
    {
        if (!isset($this->table)) {
            throw new DatabaseSmileScreenException('SelectQuery has no table set');
        }

        if (count($this->select) == 0 && !$this->fullTextOn) {
            throw new DatabaseSmileScreenException('SelectQuery has no select statement set');
        }

        $statement = 'SELECT ';
        $whereValues = array();

        if (!$this->fullTextOn) {
            if (count($this->select) >= 1) {
                for($i=0; $i<count($this->select); $i++) {
                    $statement .= $this->select[$i].($i < (count($this->select)-1) ? ', ' : ' ');
                }
            }
        } else {
            $statement .= '*, MATCH('; 
            for($i=0; $i<count($this->fullTextFields); $i++) {
                $statement .= $this->fullTextFields[$i];
                $statement .= ($i <(count($this->fullTextFields)-1) ? ',' : '');
            }
            $statement .= ') AGAINST(:matchvalue1 IN BOOLEAN MODE) AS score';
        }

        $statement .= ' FROM ' . $this->table . ' ';


        if (!isset($this->whereRaw) && !$this->fullTextOn) {
            for($i=0; $i<count(array_keys($this->where)); $i++) {
                $key = array_keys($this->where)[$i];

                if($i == 0) {
                    $statement .= 'WHERE ';
                }

                $statement .= $key . ' ' . $this->where[$key][0] . ' ? ' . ($i < (count(array_keys($this->where))-1) ? $this->whereComb . ' ' : '');
                $whereValues[] = $this->where[$key][1];
            }
        } else {
            if(!$this->fullTextOn) {
                $statement .= 'WHERE ' . $this->whereRaw;
                $whereValues = $this->whereRawValues;
            } else {
                $statement .= 'WHERE MATCH('; 
                for($i=0; $i<count($this->fullTextFields); $i++) {
                    $statement .= $this->fullTextFields[$i];
                    $statement .= ($i <(count($this->fullTextFields)-1) ? ',' : '');
                }
                $statement .= ') AGAINST(:matchvalue2 IN BOOLEAN MODE) ORDER BY score DESC';
            }
        }

        if (!empty($this->order) && !$this->fullTextOn) {
             
            $statement .= ' ORDER BY '; 
            for($i=0; $i<count($this->order); $i++) {
                $statement .= $this->order[$i][0] . ' ' . $this->order[$i][1];
                $statement .= ($i <(count($this->order)-1) ? ',' : '');
            }
        }

        return [trim($statement).';', $whereValues];
    }
}
