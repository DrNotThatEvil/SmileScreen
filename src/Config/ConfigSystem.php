<?php 
namespace SmileScreen\Config;

use SmileScreen\Base\Singleton as Singleton;
use SmileScreen\Exceptions\ConfigSystemSmileScreenException as ConfigSystemSmileScreenException;

class ConfigSystem extends Singleton
{

    /**
     * A saved instance of ConfigFileSystem
     *
     * @var \SmileScreen\Config\ConfigFileSystem
     */
    private $configFS;

    /**
     * The config directory 
     *
     * @var string the location of the config directory from the site's root folder
     */
    private $configDirectory = '/config';
    
    /**
     * The main configuration file.
     *
     * @var string the location of the main config file in the $configDirectory
     */
    private $mainConfigFile = '/smilescreen.json';

    /**
     * The full path of the config directory.
     *
     * @var mixed
     */
    private $fullConfigDirectory;

    /**
     * The full path of the main config file.
     *
     * @var string
     */
    private $fullMainConfigFile;

    /**
     * The array that holds the configuration form the configuration file 
     *
     * @var array
     */
    private $configSettings = [];

    /**
     * The constructor of the ConfigSystem it only instanciates the ConfigFileSystem
     * At this moment
     *
     * @return void
     */
    protected function __construct() 
    { 
        $this->configFS = ConfigFileSystem::getInstance();
    }

    /**
     * Adds slashes to the start of a file name they are not there
     *
     * @param string $filepath the filepath to changed 
     * @return string the new file path
     */
    private function addSlash(string $filepath)
    {
        return ( substr($filepath, 0, 1) !== '/' ? '/'.$filepath : $filepath);
    }

    /**
     * This function resursivly changes the keys to lowercase or uppercase.
     * This helps when looking up the settings since they are all lowercase now
     *
     * @param array $arr The array to changed to the $case.
     * @param mixed $case The case you want default is CASE_LOWER 
     * @return array The new array
     */
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

    /**
     * Checks if a string is valid json
     *
     * @param string $string The string to be checked
     * @return boolean true if valid string false otherwise
     */
    private function isJson(string $string) 
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false; 
    }

    /**
     * Set the default configuration directory.
     * Syntactic sugar
     *
     * @return void
     */
    public function setDefaultConfigDirectory()
    {
        //Syntactic sugar function.
        $this->setConfigDirectory($this->configDirectory); 
    }
    
    /**
     * Sets the config directory of the configSystem
     *
     * @param mixed $configDirectory the configuration directory respective to the siteroot
     * @return boolean true if succesfully set.
     * @throws \SmileScreen\Exceptions\ConfigSystemSmileScreenException
     */
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

    /**
     * Sets the configuration file to the default.
     * Syntactic sugar function.
     *
     * @return void
     */
    public function setDefaultConfigFile()
    {
        //Syntactic sugar function.
        $this->setConfigFile($this->mainConfigFile); 
    }

    /**
     * Sets the main configuration file.
     *
     * @param string $mainConfigFile
     * @return void
     */
    public function setConfigFile(string $mainConfigFile)
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

    /**
     * loads the values from the config file.
     *
     * @return void
     * @throws \SmileScreen\Exceptions\ConfigSystemSmileScreenException
     */
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

    /**
     * Gets the setting from the config. 
     *
     * @param string $setting dot separated config key. Like 'setting.somesetting'
     * @param array $ops A array with extra options like ['default' => 'cheese']
     * @return void
     * @throws \SmileScreen\Exceptions\ConfigSystemSmileScreenException
     */
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
