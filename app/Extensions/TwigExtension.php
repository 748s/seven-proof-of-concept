<?php

namespace App\Extensions;

class TwigExtension extends \Seven\Twig
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getData($vars)
    {
        return array_merge(
            array(
                'message' => $this->getMessage(),
                'session' => $_SESSION
            ),
            $vars
        );
    }
}
