<?php
namespace SmileScreen\Routing;

class Route
{

    protected $method;
    protected $pattern;
    protected $action;

    protected $defaultRoute = false;

    protected $actionParameters = [];

    private function stripUrlSlashes(string $pattern)
    {
        return ltrim(rtrim($pattern), '/');
    }

    public function __construct(string $method, string $pattern, $action)
    {
        $this->method = $method;
        $this->pattern = $this->stripUrlSlashes($pattern);
        $this->action = $action;

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
        if(is_callable($this->action)) {
            call_user_func_array($this->action, $this->actionParameters);
        }

        if(is_string($this->action)) {
            $className = explode('@', $this->action)[0];
            $methodName = explode('@', $this->action)[1];
            $object = new $className();

            call_user_func_array(array($object, $methodName), $this->actionParameters);
        }
    }
}
