<?php 
namespace SmileScreen\Config;

use SmileScreen\Base\Singleton as Singleton;

class ConfigFileSystem extends Singleton
{
    public static function getSiteRoot() 
    {
        return realpath( __DIR__ . '/../../../' );
    }    

    public static function getAppRoot() 
    {
        return realpath( static::getSiteRoot() . '/app' );
    }
}
