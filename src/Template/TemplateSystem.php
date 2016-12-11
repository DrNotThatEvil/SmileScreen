<?php 
namespace SmileScreen\Template;

use SmileScreen\Base\Singleton as Singleton;
use SmileScreen\Config\ConfigFileSystem as ConfigFileSystem;
use SmileScreen\Config\ConfigSystem as ConfigSystem;
use SmileScreen\Exceptions\GenericSmileScreenException as GenericSmileScreenException;




class TemplateSystem extends Singleton
{
    protected $configSystem;
    protected $fullTemplateDirectory;    
    
    protected function __construct() 
    {
        $this->configSystem = ConfigSystem::getInstance(); 

        $tmpTemplateDirectory = configFileSystem::getSiteRoot() . '/';
        $tmpTemplateDirectory .= $this->configSystem->getSetting('templates.templatedirectory');
        
        $this->setTemplateDirectory($tmpTemplateDirectory);
    }

    private function setTemplateDirectory(string $directory)
    {
        if (!is_dir($directory)) {
            throw new GenericSmileScreenException('Coudn\'t load template directory: ' .$directory. ' Does it exist?');
        }

        $this->fullTemplateDirectory = realpath($directory);
    }


    // This function i added for my own website. Its not used at the moment please ignore it.
    // I can explain what it does briefly: 
    // it reads a json file with asset files and there filenames
    // so you can lookup what the real name is of for example a file /main.js 
    // witch is acutally called main-HASH.js this is to ensure cache is busted on a javascipt change
    // sounds technical but it means pepole dont get old javascript when using a caching server
    public function asset(string $path)
    {
        $filename = basename($path);
        $assetsFile = configFileSystem::getSiteRoot() . '/public/assets.json';
        if (!file_exists($assetsFile)) {
            return $path; 
        }

        $fileParts = explode('.', $filename);
        $jsonFile = json_decode(file_get_contents($assetsFile), true);

        if (!array_key_exists($fileParts[0], $jsonFile)) {
            return $path; 
        }

        if (!array_key_exists($fileParts[1], $jsonFile[$fileParts[0]])) {
            return $path; 
        }

        return $jsonFile[$fileParts[0]][$fileParts[1]];
    }

    public function renderTemplate(array $vars, string $file)
    {
        extract($vars);

        $template = $this;

        include $this->fullTemplateDirectory . '/' . $file;
    }
    
}
