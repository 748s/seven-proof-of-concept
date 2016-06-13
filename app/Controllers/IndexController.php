<?php

namespace App\Controllers;

use League\CommonMark\CommonMarkConverter;
use Seven\Controller;

class IndexController extends Controller
{
    public $requireLogin = false;

    public function defaultAction()
    {
        $CommonMarkConverter = new CommonMarkConverter();
        echo $this->loadTwig()->render('index.default.html.twig', array(
            'readme' => $CommonMarkConverter->convertToHtml(file_get_contents('./README.md'))
        ));
    }
}
