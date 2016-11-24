<?php 
namespace SmileScreen\Database;

class Query 
{
    protected $table;    
    
    public function setTable($table) 
    {
        $this->table = $table; 
        return $this;
    }
}
