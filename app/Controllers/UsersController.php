<?php

namespace App\Controllers;

use App\Arguments\UserId;
use Seven\Controller;
use Seven\FormUtility;

class UsersController extends Controller
{
    public $requireLogin = true;

    public function getPermission($method, $arguments)
    {
        switch($method) {
            case 'deleteAction':
            return ($_SESSION['id'] != $arguments['userId']) ? true : false;
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

    public function getAction(UserId $userId)
    {
        echo $this->loadTwig()->render('users.get.html.twig', array(
            'user' => $this->DB->getOneById($userId)
        ));
    }

    public function addAction()
    {
        $this->formView();
    }

    public function editAction(UserId $userId)
    {
        $user = $this->DB->getOneById($userId);
        $this->formView($user);
    }

    private function formView($user = array())
    {
        echo $this->loadTwig()->render('users.edit.html.twig', array(
            'user' => $user
        ));
    }

    public function postAction(UserId $userId = null)
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
            $this->DB->put('users', $user, $userId);
            $message = ($userId) ? "You just updated $user[firstName] $user[lastName]&rsquo;s record" : "You just added $user[firstName] $user[lastName] as a user";
            $this->setMessage('messageBlue', $message, true);
            header("Location: /users");
        }
    }

    public function deleteAction(UserId $userId)
    {
        $user = $this->DB->getOneById($userId);
        $this->DB->deleteOneById($userId);
        $this->setMessage('messageBlue', "You just deleted $user[firstName] $user[lastName]", true);
        header("Location: /users");
    }
}
