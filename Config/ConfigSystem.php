<?php 
namespace SmileScreen\Config;

use SmileScreen\Base\Singleton as Singleton;
use SmileScreen\Exceptions\ConfigSystemSmileScreenException as ConfigSystemSmileScreenException;

class ConfigSystem extends Singleton
{

    private $configFS;
    private $configDirectory = '/config';

    private $mainConfigFile = '/smilescreen.json';

    private $fullConfigDirectory;
    private $fullMainConfigFile;

    private $configSettings = [];

    protected function __construct() 
    { 
        $this->configFS = ConfigFileSystem::getInstance();
    }

    private function addSlash($file)
    {
        return ( substr($file, 0, 1) !== '/' ? '/'.$file : $file);
    }

    private function changeArrayKeyRecursive($arr, $case = CASE_LOWER) 
    {
        return array_map( function($item) use ($case)
        {
            if (is_array($item)) {
                $item = $this->changeArrayKeyRecursive($item, $case);
            }

            return $item;
        }, array_change_key_case($arr, $case));
    }

    private function isJson($string) 
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false; 
    }

    public function setDefaultConfigDirectory()
    {
        //Syntactic sugar function.
        $this->setConfigDirectory($this->configDirectory); 
    }
    
    public function setConfigDirectory($configDirectory) 
    {
        $siteRoot = $this->configFS::getSiteRoot();
        $configDir = $siteRoot . $this->addSlash($configDirectory);

        if (!file_exists($configDir)) {             
            throw new ConfigSystemSmileScreenException('Can\'t find SmileScreen config folder: ' . $configDir);
        }

        if (!is_dir($configDir)) {
            throw new ConfigSystemSmileScreenException('Config directory is not a directory, But file.');
        }

        $this->configDirectory = $configDirectory;
        $this->fullConfigDirectory = $configDir;

        return true;
    }

    public function setDefaultConfigFile()
    {
        //Syntactic sugar function.
        $this->setConfigFile($this->mainConfigFile); 
    }

    public function setConfigFile($mainConfigFile)
    {
        if (is_null($this->fullConfigDirectory)) {
            throw new ConfigSystemSmileScreenException('ConfigDirectory not yet initiated.');
        }
        
        $filePath = $this->fullConfigDirectory . $this->addSlash($mainConfigFile);
        
        if (!file_exists($filePath)) {
            throw new ConfigSystemSmileScreenException('MainConfig file not found at: ' . $filePath);
        }

        if (!is_readable($filePath)) {
            throw new ConfigSystemSmileScreenException('Can\'t read main config file at: ' . $filePath);
        }

        $this->fullMainConfigFile = $filePath; 

        return true;
    }

    public function loadConfig() 
    {
        if (is_null($this->fullMainConfigFile)) {
            throw new ConfigSystemSmileScreenException('ConfigSystem not fully initialized! Set the mainConfigFile!');
        }

        $jsonFileContents = file_get_contents($this->fullMainConfigFile);
        
        if (!$this->isJson($jsonFileContents)) {
            throw new ConfigSystemSmileScreenException('ConfigSystem could not load settings file. Check it\'s syntax');
        }
         
        $jsonDecoded = json_decode($jsonFileContents, true); 
        $this->configSettings = $this->changeArrayKeyRecursive($jsonDecoded);
    }

    public function getSetting($setting, $ops = []) {
        $settingStr = strtolower($setting);
        $pointerArray = explode('.', $settingStr);

        $keysArray= $this->configSettings;
        for ($i = 0; $i<count($pointerArray); $i++) {
            $pointer = $pointerArray[$i];
             
            if (!array_key_exists($pointer, $keysArray)) {
                break; 
            }

            if (!is_array($keysArray[$pointer]) && $i != (count($pointerArray)-1)) {
                // if the config array is not going deeper.
                // but we still haven't reached the end of our loop. 
                break; 
            }

            if ($i == (count($pointerArray)-1)) {
                return $keysArray[$pointer];
            }
            
            $keysArray = $keysArray[$pointer];
        }

        if (array_key_exists('default', $ops)) {
            return $ops['default'];
        }

        throw new ConfigSystemSmileScreenException('Can\'t find config with key: "' . $setting . '"');
    }
}
