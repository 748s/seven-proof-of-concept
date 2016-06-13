<?php

namespace App\Controllers;

use Seven\Controller;

class logoutController extends Controller
{
    public function defaultAction()
    {
        session_destroy();
        header("Location: /");
    }
}
