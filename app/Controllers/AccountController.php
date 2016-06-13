<?php

namespace App\Controllers;

use Seven\Controller;
use Seven\FormUtility;

class AccountController extends Controller
{
    public $requireLogin = true;

    public function defaultAction()
    {
        echo $this->loadTwig()->render('account.default.html.twig', array(
            'user' => $this->DB->getOneById('users', $_SESSION['id'])
        ));
    }

    public function editAction()
    {
        $this->formView($this->DB->getOneById('users', $_SESSION['id']));
    }

    private function formView($user)
    {
        echo $this->loadTwig()->render('account.edit.html.twig', array(
            'user' => $user
        ));
    }

    public function postAction()
    {
        $FormUtility = new FormUtility();
        $FormUtility->isCleanString('firstName', 'First Name is Required');
        $FormUtility->isCleanString('lastName', 'Last Name is Required');
        $FormUtility->isEmailAddress('emailAddress', 'Email Address is Required');
        $formData = $FormUtility->getData();
        if($formErrors = $FormUtility->getErrors()) {
            $this->setFormErrorMessage($formErrors);
            $this->formView($formData);
        } else {
            $this->DB->put('users', $formData, $_SESSION['id']);
            $_SESSION['firstName'] = $formData['firstName'];
            $_SESSION['lastName'] = $formData['lastName'];
            $_SESSION['emailAddress'] = $formData['emailAddress'];
            $this->setMessage('messageBlue', 'You just updated your account information', true);
            header("Location: /dashboard");
        }
    }
}
