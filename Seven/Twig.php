<?php

namespace Seven;

use Twig_Loader_Filesystem;
use Twig_Environment;

class Twig
{
    private $Twig;

    public function __construct()
    {
        $TwigLoader = new Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/templates');
        $this->Twig = new Twig_Environment($TwigLoader);
    }

    public function render($template, $vars = array())
    {
        return $this->Twig->render($template, $this->getData($vars));
    }

    public function getData($vars)
    {
        return array_merge(
            array(
                'message' => $this->getMessage()
            ),
            $vars
        );
    }

    protected function getMessage()
    {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
}
