<?php

namespace Seven;

class Controller
{
    protected $config;
    protected $DB;
    protected $Permissions;
    protected $Twig;

    public function __construct()
    {
        global $config;
        $this->config = $config;
        $this->loadDB();
        $this->loadPermissions();
    }

    public function isLoggedIn()
    {
        return $this->Permissions->isLoggedIn();
    }

    public function _401Action($fqClassName)
    {
        header("HTTP/1.0 401 Unauthorized");
        echo $this->loadTwig()->render('401.html.twig');
    }

    public function _403Action($fqClassName)
    {
        header("HTTP/1.0 403 Forbidden");
        echo $this->loadTwig()->render('403.html.twig');
    }

    public function _404Action()
    {
        header("HTTP/1.0 404 Not Found");
        echo $this->loadTwig()->render('404.html.twig');
    }

    public function _500Action()
    {
        header("HTTP/1.0 500 Internal Server Error");
        echo $this->loadTwig()->render('500.html.twig');
    }

    public function setMessage($cssClass, $content, $dismissable = true)
    {
        $_SESSION['message'] = array(
            'cssClass' => $cssClass,
            'content' => $content,
            'dismissable' => $dismissable
        );
    }

    public function setFormErrorMessage($formErrors)
    {
        $content = '<strong>Your form has errors:</strong><ul>';
        foreach($formErrors as $error) {
            $content .= "<li>$error</li>";
        }
        $content .= '</ul>';
        $this->setMessage('messageRed', $content, false);
    }

    protected function loadTwig()
    {
        $className = $this->getExtensionOrSeven('Twig');
        $this->Twig = new $className();
        return $this->Twig;
    }

    private function loadPermissions()
    {
        $className = $this->getExtensionOrSeven('Permissions');
        $this->Permissions = new $className();
    }

    private function loadDB()
    {
        $className = $this->getExtensionOrSeven('DB');
        $this->DB = new $className();
    }

    private function getExtensionOrSeven($name)
    {
        if(class_exists('\App\Extensions\\' . $name . 'Extension')) {
            return '\App\Extensions\\' . $name . 'Extension';
        } else {
            return "\Seven\\$name";
        }
    }

    public function loadArgument($className, $var)
    {
        return new $className($this->DB, $var);
    }
}
