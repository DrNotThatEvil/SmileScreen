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
    protected $fullTemplateCacheDirectory;

    protected $twigLoader;
    protected $twigEnviroment;
    
    protected function __construct() 
    {
        $this->configSystem = ConfigSystem::getInstance(); 

        $tmpTemplateDirectory = configFileSystem::getSiteRoot() . '/';
        $tmpTemplateDirectory .= $this->configSystem->getSetting('templates.templatedirectory');
        
        $tmpCacheDirectory = configFileSystem::getSiteRoot() . '/';
        $tmpCacheDirectory .= $this->configSystem->getSetting('templates.templatecachedirectory');

        $this->setTemplateDirectory($tmpTemplateDirectory);
        $this->setTemplateCacheDirectory($tmpTemplateDirectory);
        $this->loadTemplateEngine();
    }

    private function setTemplateDirectory(string $directory)
    {
        if (!is_dir($directory)) {
            throw new GenericSmileScreenException('Could not load template directory: ' . $directory . ' Does it exist?');
        }

        $this->fullTemplateDirectory = realpath($directory);
    }

    private function setTemplateCacheDirectory(string $directory) 
    {
        if (!is_dir($directory)) {
            throw new GenericSmileScreenException('Could not load template cache directory: ' . $directory . ' Does it exist?');
        }

        $this->fullTemplateCacheDirectory = realpath($directory);
    }

    private function loadTemplateEngine()
    {
        $this->twigLoader = new \Twig_Loader_Filesystem($this->fullTemplateDirectory);
        $this->twigEnviroment = new \Twig_Environment($this->twigLoader, array(
            'cache' => $this->twigEnviroment
        ));
    }

    public function getTwigEnviroment()
    {
        return $this->twigEnviroment;
    }
    
}
