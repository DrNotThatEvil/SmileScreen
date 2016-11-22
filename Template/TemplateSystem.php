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
            throw new GenericSmileScreenException('Could not load template directory: ' . $directory . ' Does it exist?');
        }

        $this->fullTemplateDirectory = realpath($directory);
    }

    public function renderTemplate(array $vars, string $file)
    {
        extract($vars);
        include $this->fullTemplateDirectory . '/' . $file;
    }
    
}
