<?php
namespace SmileScreen\Routing;

use SmileScreen\Base\Singleton as Singleton;
use SmileScreen\Routing\Route as Route;

class Router extends Singleton 
{
    protected $prefix = '';
    protected $routes = [];
    protected $defaultRoute;

    protected function __construct() 
    {
        $this->defaultRoute = new Route('ANY', '404', function() {
            return 'Page not found 404';
        });
    }

    
    private function stripUrlSlashes($trim)
    {
        return ltrim(rtrim($trim, '/'), '/');

    }

    private function calculateUrlRoute()
    {
        $requestUrl = strtok($_SERVER['REQUEST_URI'], '?'); 
        $requestUrl = $this->stripUrlSlashes($requestUrl);  

        return $requestUrl;
    }

    private function addRoute(string $method, string $pattern, $action) 
    {
        $method = strtoupper($method);
        $fullPattern = $this->prefix . $pattern;

        $route = new Route($method, $fullPattern, $action);
        
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        $this->routes[$method][] = $route;
    }

    private function matchRouteParts(array $urlExploded, array $routeParts, Route $route) 
    {
        $routeMatch = false;
        
        for ($i=0; $i<count($routeParts); $i++) {
            $lastPart = ($i == (count($routeParts)-1)); // Check if its the last part.

            if ($routeParts[$i] === $urlExploded[$i] && !$route->isPartRegex($i)) {
                // The route parts match. if this is the last part we have a match.
                // Also the routepart is not a regex
                // We check that to see if there not literally using a regex as url.
                      
                $routeMatch = $lastPart;
            }

            if ($routeParts[$i] !== $urlExploded[$i] && $route->isPartRegex($i)) {
                $regex = '~' . $routeParts[$i] . '~';
  
                $matches = [];                        
                $match = @preg_match_all($regex, $urlExploded[$i], $matches);

                if ($match != false) {
                    if (count($matches) == 2) {
                        $route->addActionParameter($matches[1][0]);
                    } else {
                        $route->addActionParameter($matches[0]);
                    }

                    $routeMatch = $lastPart;
                }
            }
        }

        return $routeMatch;
    }

    public function get(string $pattern, $action)
    {
        $this->addRoute('GET', $pattern, $action); 
    }

    public function post($pattern, $action)
    {
        $this->addRoute('POST', $pattern, $action);
    }

    public function put(string $pattern, $action)
    {
        $this->addRoute('PUT', $pattern, $action);
    }

    public function delete(string $pattern, $action)
    {
        $this->addRoute('DELETE', $pattern, $action);
    }

    public function options(string $pattern, $action)
    {
        $this->addRoute('OPTIONS', $pattern, $action); 
    }

    public function patch(string $pattern, $action) 
    {
        $this->addRoute('PATCH', $pattern, $action); 
    }

    public function group(string $pattern, callable $fn)
    {
        $this->prefix = $pattern;

        call_user_func($fn);

        $this->prefix = ''; 
    }

    public function defaultRoute($action)
    {
        $defaultRoute = new Route('ANY', '404', $action);
        $defaultRoute->setDefault(true);
        
        $this->defaultRoute = $defaultRoute;
    }

    public function startRouter() 
    {
        $urlExploded = explode('/', $this->calculateUrlRoute());
        
        if (!isset($this->routes[$_SERVER['REQUEST_METHOD']])) {
            $this->routes[$_SERVER['REQUEST_METHOD']] = [];
        }
        
        $methodRoutes = $this->routes[$_SERVER['REQUEST_METHOD']];
        
        $foundRoute = null; 
        foreach($methodRoutes as $route) {
            $routeParts = $route->getPartsArray();
            
            if (count($urlExploded) == count($routeParts)) {
                if ($this->matchRouteParts($urlExploded, $routeParts, $route)) {
                    $foundRoute = $route;
                    break; 
                }
            }

            if (count($urlExploded) > count($routeParts)) {
                $lastRoutePartIndex = (count($routeParts) - 1);
                
                if ($route->isPartRegex($lastRoutePartIndex)) {
                    // the last part is a regex.
                    // we now combine the parts of the url that came after the last route part.
                    //
                    
                    $combineLastUrlParts = implode('/', array_slice($urlExploded, $lastRoutePartIndex));
                    $regex = '~' . $routeParts[$lastRoutePartIndex] . '~';
  
                    $matches = [];                        
                    $match = @preg_match_all($regex, $combineLastUrlParts, $matches);

                    if ($match != false) {
                        // We have a match on the last regex with the combineLastUrlParts.
                        // This means it's still a possibility to be the correct route.
                        // We now need to verify the routeParts that came before unless there are no 
                        // before parts 

                        if($lastRoutePartIndex == 0) { 
                            // Wie wie bonjour we have a match 
                            if (count($matches) == 2) {
                                $route->addActionParameter($matches[1][0]);
                            } else {
                                $route->addActionParameter($matches[0]);
                            }

                            $foundRoute = $route;
                            break;

                        } else { 
                            
                            $previousUrlExplode = array_slice($urlExploded, 0, $lastRoutePartIndex);
                            $newUrlExplode = array_merge($previousUrlExplode, [$combineLastUrlParts]);

                            if ($this->matchRouteParts($newUrlExplode, $routeParts, $route)) {
                                $foundRoute = $route;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        if(is_null($foundRoute)) {
            $foundRoute = $this->defaultRoute;
            return;
        }
    
        
        return $foundRoute->execute(); 
    }
    
}