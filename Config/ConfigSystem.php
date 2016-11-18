<?php 
namespace SmileScreen\Config;

use SmileScreen\Base\Singleton as Singleton;
use SmileScreen\Exceptions\GenericSmileScreenException as GenericSmileScreenException;

class ConfigSystem extends Singleton
{

    private $configFS;
    private $configDirectory = '/config';

    private $realConfigDirectory;

    protected function __construct() 
    { 
        $this->configFS = ConfigFileSystem::getInstance();
        $this->setConfigDirectory($this->configDirectory);        
    }

    public function setConfigDirectory($configDirectory) 
    {
        $siteRoot = $this->configFS::getSiteRoot();
        
        if (!file_exists($siteRoot . $configDirectory)) 
        {             
            throw new GenericSmileScreenException("Can't find SmileScreen config folder: " . $configDirectory);
        }
    }

    public function loadConfig() 
    {
         
    }
}
