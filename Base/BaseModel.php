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

    protected $values = array();

    protected $state = ModelStates::NOT_SAVED; 

    public function __construct($values = [], $state = ModelStates::NOT_SAVED)
    {
    
    }

    public static function hasDatabaseTable()
    {
         
    }

    public static function make($values = []) 
    {
            
    }
    
}
