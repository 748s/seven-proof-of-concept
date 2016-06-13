<?php

namespace App\Controllers;

use Seven\Controller;
use Seven\FormUtility;

class UsersController extends Controller
{
    public $requireLogin = true;

    public function getPermission($method, $argument)
    {
        switch($method) {
            case 'deleteAction':
            return ($_SESSION['id'] != $argument) ? true : false;
            break;
            default:
            return true;
            break;
        }
    }

    public function defaultAction()
    {
        $query = 'SELECT * FROM users ORDER BY lastName ASC';
        $users = $this->DB->select($query);
        echo $this->loadTwig()->render('users.default.html.twig', array(
            'users' => $users
        ));
    }

    public function getAction($id)
    {
        echo $this->loadTwig()->render('users.get.html.twig', array(
            'user' => $this->DB->getOneById('users', $id)
        ));
    }

    public function addAction()
    {
        $this->formView();
    }

    public function editAction($id)
    {
        $user = $this->DB->getOneById('users', $id);
        $this->formView($user);
    }

    private function formView($user = array())
    {
        echo $this->loadTwig()->render('users.edit.html.twig', array(
            'user' => $user
        ));
    }

    public function postAction($id)
    {
        $FormUtility = new FormUtility();
        $FormUtility->isCleanString('firstName', 'First Name is Required');
        $FormUtility->isCleanString('lastName', 'Last Name is Required');
        $FormUtility->isEmailAddress('emailAddress', 'A Valid Email Address is Required');
        $user = $FormUtility->getData();
        if($formErrors = $FormUtility->getErrors()) {
            $this->setFormErrorMessage($formErrors);
            $this->formView($user);
        } else {
            $this->DB->put('users', $user, $id);
            $message = ($id) ? "You just updated $user[firstName] $user[lastName]&rsquo;s record" : "You just added $user[firstName] $user[lastName] as a user";
            $this->setMessage('messageBlue', $message, true);
            header("Location: /users");
        }
    }

    public function deleteAction($id)
    {
        $user = $this->DB->getOneById('users', $id);
        $this->DB->deleteOneById('users', $id);
        $this->setMessage('messageBlue', "You just deleted $user[firstName] $user[lastName]", true);
        header("Location: /users");
    }
}
