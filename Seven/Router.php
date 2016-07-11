<?php

namespace Seven;

class Router
{
    private $fqClassName = null;
    private $methodName = null;
    private $segments = array();
    private $arguments = array();
    private $Controller;

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
        $this->Controller = new $this->fqClassName();
        if(property_exists($this->fqClassName, 'requireLogin') && $this->Controller->requireLogin === true && !$this->Controller->isLoggedIn()) {
            $this->Controller->_401Action($this->fqClassName);
        } elseif(!$this->verifyParams()) {
            $this->Controller->_404Action();
        } elseif(method_exists($this->fqClassName, 'getPermission') && !$this->Controller->getPermission($this->methodName,$this->arguments)) {
            $this->Controller->_403Action($this->fqClassName);
        } else {
            call_user_func_array(array($this->Controller, $this->methodName), $this->arguments);
        }
    }

    private function verifyParams()
    {
        $Ref = new \ReflectionMethod($this->fqClassName, $this->methodName);
        if(!(count($this->segments) >= $Ref->getNumberOfRequiredParameters() && count($this->segments) <= $Ref->getNumberOfParameters())) {
            return false;
        } else {
            $params = $Ref->getParameters();
            foreach($params as $index => $param) {
                if($paramClass = $param->getClass()) {
                    if(!($param->isOptional() && !$this->segments[$index])) {
                        $ArgumentClass = $this->Controller->loadArgument($paramClass->name, $this->segments[$index]);
                        if(!$ArgumentClass->entityExists()) {
                            return false;
                        } else {
                            $this->arguments[$param->getName()] = $ArgumentClass;
                        }
                    }
                } else {
                    $this->arguments[$param->getName()] = $this->segments[$index];
                }
            }
            return true;
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
        } elseif($this->segments[0] === 'edit' && method_exists($this->fqClassName, 'editAction')) {
            $this->methodName = 'editAction';
            array_shift($this->segments);
        } elseif($this->segments[0] === 'add' && method_exists($this->fqClassName, 'addAction')) {
            $this->methodName = 'addAction';
            array_shift($this->segments);
        } elseif($this->segments[0] === 'delete' && method_exists($this->fqClassName, 'deleteAction')) {
            $this->methodName = 'deleteAction';
            array_shift($this->segments);
        } elseif($this->segments[0] && method_exists($this->fqClassName, 'getAction')) {
            $this->methodName = 'getAction';
        } elseif(method_exists($this->fqClassName, 'defaultAction')) {
            $this->methodName = 'defaultAction';
        }
        return true;
    }
}
