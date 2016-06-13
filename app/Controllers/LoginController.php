<?php

namespace App\Controllers;

use Seven\Controller;
use Seven\FormUtility;

class LoginController extends Controller
{
    public $requireLogin = false;

    public function defaultAction()
    {
        $this->formView();
    }

    private function formView()
    {
        echo $this->loadTwig()->render('login.html.twig', array());
    }

    public function postAction()
    {
        $FormUtility = new FormUtility();
        $FormUtility->isEmailAddress('emailAddress', 'You Must Enter Your Email Address');
        $FormUtility->isString('password', 'You Must Enter Your Password');
        $formData = $FormUtility->getData();
        if($formErrors = $FormUtility->getErrors()) {
            $this->setFormErrorMessage($formErrors);
            $this->formView();
        } else {
            $query = 'SELECT * FROM users WHERE LOWER(emailAddress) = :emailAddress LIMIT 1';
            $user = $this->DB->selectOne($query, array(':emailAddress' => strtolower($formData['emailAddress'])));
            if(!$user || $user['password'] !== $formData['password']) {
                $formErrors[] = 'Your Email Address or Password was Incorrect';
                $this->setFormErrorMessage($formErrors);
                $this->formView();
            } else {
                $_SESSION['id'] = $user['id'];
                $_SESSION['firstName'] = $user['firstName'];
                $_SESSION['lastName'] = $user['lastName'];
                $_SESSION['emailAddress'] = $user['emailAddress'];
                $this->setMessage('messageBlue', 'Welcome to <em>Seven</em>!', true);
                header("Location: /dashboard");
            }
        }
    }
}
