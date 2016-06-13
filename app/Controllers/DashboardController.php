<?php

namespace App\Controllers;

use Seven\Controller;

class DashboardController extends Controller
{
    public $requireLogin = true;

    public function defaultAction()
    {
        echo $this->loadTwig()->render('dashboard.default.html.twig', array());
    }
}
