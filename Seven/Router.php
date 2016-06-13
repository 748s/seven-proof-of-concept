<?php

namespace Seven;

class Router
{
    private $fqClassName = null;
    private $methodName = null;
    private $methodArgument = null;
    private $segments = array();

    public function route()
    {
        $this->segmentRequest();
        if($this->findRoute()) {
            $this->findMethod();
        }
        if(!($this->fqClassName && $this->methodName)) {
            $this->fqClassName = (class_exists('\App\Extensions\ControllerExtension')) ? '\App\Extensions\ControllerExtension' : '\Seven\Controller';
            $this->methodName = '_404Action';
        }
        $Controller = new $this->fqClassName($this->segments);
        if(property_exists($this->fqClassName, 'requireLogin') && $Controller->requireLogin === true && !$Controller->isLoggedIn()) {
            $Controller->_401Action($this->fqClassName);
        } elseif(method_exists($this->fqClassName, 'getPermission') && !$Controller->getPermission($this->methodName, $this->methodArgument)) {
            $Controller->_403Action($this->fqClassName);
        } else {
            $Controller->{$this->methodName}($this->methodArgument);
        }
    }

    private function segmentRequest()
    {
        $request = explode('?', $_SERVER['REQUEST_URI'])[0];
        $request = trim($request, '/');
        $requestArray = explode('/', $request);
        $this->segments = $requestArray;
    }

    private function findRoute()
    {
        $className = null;
        $routes = json_decode(file_get_contents('./routes.json'), true);
        while(!$className) {
            foreach($this->segments as $segment) {
                if(array_key_exists($segment, $routes) && is_array($routes[$segment])) {
                    array_shift($this->segments);
                    $routes = $routes[$segment];
                    if(empty($this->segments) && array_key_exists('', $routes)) {
                        $className = $routes[''];
                    }
                } elseif(array_key_exists($segment, $routes)) {
                    array_shift($this->segments);
                    $className = $routes[$segment];
                } elseif(!$className) {
                    return false;
                }
            }
        }
        if($className) {
            $this->fqClassName = '\App\Controllers\\' . $className;
            return true;
        } else {
            return false;
        }
    }

    private function findMethod()
    {
        if(count($_POST) && method_exists($this->fqClassName, 'postAction')) {
            $this->methodName = 'postAction';
            if($this->segments[0] === 'edit' || $this->segments[0] === 'add') {
                array_shift($this->segments);
            }
            $this->methodArgument = ($this->segments[0]) ? array_shift($this->segments) : null;
        } elseif($this->segments[0] === 'edit' && method_exists($this->fqClassName, 'editAction')) {
            $this->methodName = 'editAction';
            array_shift($this->segments);
            $this->methodArgument = ($this->segments[0]) ? array_shift($this->segments) : null;
        } elseif($this->segments[0] === 'add' && method_exists($this->fqClassName, 'addAction')) {
            $this->methodName = 'addAction';
            array_shift($this->segments);
            $this->methodArgument = ($this->segments[0]) ? array_shift($this->segments) : null;
        } elseif($this->segments[0] === 'delete' && method_exists($this->fqClassName, 'deleteAction')) {
            $this->methodName = 'deleteAction';
            array_shift($this->segments);
            $this->methodArgument = ($this->segments[0]) ? array_shift($this->segments) : null;
        } elseif($this->segments[0] && method_exists($this->fqClassName, 'getAction')) {
            $this->methodName = 'getAction';
            $this->methodArgument = array_shift($this->segments);
        } elseif(method_exists($this->fqClassName, 'defaultAction')) {
            $this->methodName = 'defaultAction';
        }
        return true;
    }
}
