<?php 
namespace SmileScreen\Config;

use SmileScreen\Base\Singleton as Singleton;

/**
 * ConfigFileSystem
 * A configuration system specifally for handling
 * the file system.
 * 
 * I sperated this from the main ConfigSystem so it can be extended with ease
 *
 * @uses Singleton
 * @package \SmileScreen\Config
 * @author Willmar Knikker <wil@wilv.in>
 * @version 0.1.0
 */
class ConfigFileSystem extends Singleton
{
    /**
     * Gets the sites root directory.
     * This is the folder that contains the public and app directory
     *
     * @return string The realpath of the site's root directory
     */
    public static function getSiteRoot() 
    {
        return realpath( __DIR__ . '/../../../' );
    }    

    /**
     * Gets the app directory
     * This is the directory containing the SmileScreen Folder
     * and the folder where you application specific data is stored
     *
     * @return string The realpaht() of the site's app directory
     */
    public static function getAppRoot() 
    {
        return realpath( static::getSiteRoot() . '/app' );
    }
}
