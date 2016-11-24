<?php
namespace SmileScreen\Base;

/**
 * Singleton
 * Singleton base class
 * This abstract class needs to be exteded 
 * when extended it will prevent multiple instances of a object
 * to exist. Pretty simple stuff.
 *
 * @package \SmileScreen\Base
 * @author Willmar Knikker aka <wil@wilv.in>
 * @version 0.1.0
 */
abstract class Singleton
{
    /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    protected static $instance;
    
    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    final public static function getInstance()
    {
        static $instances = [];

        $calledClass = get_called_class();

        if (!isset($instances[$calledClass])) {
            $instances[$calledClass] = new $calledClass();
        }

        return $instances[$calledClass];
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }
}
