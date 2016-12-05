<?php 
namespace SmileScreen\Routing;

use SmileScreen\Config\ConfigSystem as ConfigSystem;

class Response
{
    protected $configSystem;

    protected $type;
    
    protected $redirectUrl = '/';

    protected $data = [];

    public function __construct() 
    {
        $this->configSystem = ConfigSystem::getInstance();
        return $this;
    }

    public function redirect($url) 
    {
        $this->type = 'redirect';
        $this->redirectUrl = $url;
        return $this;
    }

    public function json($data) 
    {
        $this->type = 'json';
        $this->data = $data;
        return $this;
    }

    public function execute() 
    {
        switch($this->type) {
        case 'redirect':
            $url = $this->configSystem->getSetting('webconfig.protocol');
            $url .= '://' . $this->configSystem->getSetting('webconfig.webadress');
            $url .= $this->redirectUrl;
                
            Header('Location: ' . $url);
            die;
            break;
        case 'json':
            header('Content-Type: application/json');  
            echo json_encode($this->data);
            die;
            break;
        }
    }
}
