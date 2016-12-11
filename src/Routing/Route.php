<?php
namespace SmileScreen\Routing;

class Route
{

    protected $method;
    protected $pattern;
    protected $action;

    protected $defaultRoute = false;

    protected $actionParameters = [];
    protected $routeOptions = [];

    private function stripUrlSlashes(string $pattern)
    {
        return ltrim(rtrim($pattern), '/');
    }

    public function __construct(string $method, string $pattern, $action, $ops = [])
    {
        $this->method = $method;
        $this->pattern = $this->stripUrlSlashes($pattern);
        $this->action = $action;
        $this->routeOptions = $ops;

        return $this;
    }

    public function getPartsArray()
    {
        return preg_split('~\/(?![^()]*\))~', $this->pattern);
    }

    public function isPartRegex(int $part)
    {
        if ($part > (count($this->getPartsArray()) - 1)) {
            return false;
        }

        $patternPart = $this->getPartsArray()[$part];

        if(substr($patternPart, 0, 1) !== '(' || substr($patternPart, -1) !== ')') {
            return false; // the regex should start with a ( and end with a )
        }

        $patternRegex = '/' . $patternPart . '/';

        return preg_match("/^\/.+\/[a-z]*$/i", $patternRegex);
    }

    public function addActionParameter($parameter)
    {
        $this->actionParameters[] = $parameter;
    }

    public function setDefault(bool $default)
    {
        $this->defaultRoute = $default;
    }

    public function execute()
    {
        if (array_key_exists('middleware', $this->routeOptions)) {
            if (is_array($this->routeOptions['middleware'])) { 
                foreach($this->routeOptions['middleware'] as $middleware) {
                    $className = explode('@', $middleware)[0];
                    $methodName = explode('@', $middleware)[1];
                    $object = new $className();
                    
                    $middlewareResult = call_user_func(array($object, $methodName));
                    if($middlewareResult === false) {
                        return false; 
                    }

                    if (get_class($middlewareResult) == 'SmileScreen\Routing\Response') {
                        return $middlewareResult;
                    }
                }
            } else {
                $className = explode('@', $this->routeOptions['middleware'])[0];
                $methodName = explode('@', $this->routeOptions['middleware'])[1];
                $object = new $className();
                    
                $middlewareResult = call_user_func(array($object, $methodName));
                if($middlewareResult === false) {
                    return false; 
                }
                
                if (get_class($middlewareResult) == 'SmileScreen\Routing\Response') {
                    return $middlewareResult;
                }
            }
        }

        if (is_callable($this->action)) {
            return call_user_func_array($this->action, $this->actionParameters);
        }

        if (is_string($this->action)) {
            $className = explode('@', $this->action)[0];
            $methodName = explode('@', $this->action)[1];
            $object = new $className();

            return call_user_func_array(array($object, $methodName), $this->actionParameters);
        }
    }
}
