<?php

namespace Seven;

class FormUtility
{
    public $data = array();
    public $errors = array();

    public function isString($key, $errorMessage)
    {
        $this->data[$key] = trim($_POST[$key]);
        if($this->data[$key] == '' && $errorMessage) {
            $this->errors[$key] = $errorMessage;
        }
    }

    public function isCleanString($key, $errorMessage)
    {
        $this->data[$key] = trim(strip_tags($_POST[$key]));
        if($this->data[$key] == '' && $errorMessage) {
            $this->errors[$key] = $errorMessage;
        }
    }

    public function isCheckbox($key, $errorMessage)
    {
        $checkboxValues = array('yes', 'on', 1, '1', true);
        $this->isChoices($key, $checkboxValues, $errorMessage);
    }

    public function isInt($key, $errorMessage)
    {
        if (preg_match('/^[1-9][0-9]{0,15}$/', $_POST[$key])) {
            $this->data[$key] = (int) $_POST[$key];
        } else {
            if($errorMessage) {
                $this->errors[$key] = $errorMessage;
            }
        }
    }

    public function isChoices($key, $choices, $errorMessage)
    {
        if(in_array($_POST[$key], $choices)) {
            $this->data[$key] = $_POST[$key];
        } elseif($errorMessage) {
            $this->errors[$key] = $errorMessage;
        }
    }

    public function isEmailAddress($key, $errorMessage)
    {
        if(filter_var(trim($_POST[$key]), FILTER_VALIDATE_EMAIL)) {
            $this->data[$key] = trim($_POST[$key]);
        } elseif($errorMessage) {
            $this->errors[$key] = $errorMessage;
        }
    }

    public function dataMatch($key1, $key2, $errorKey, $errorMessage)
    {
        if($_POST[$key1] != $_POST[$key2] && $errorMessage) {
            $this->errors[$errorKey] = $errorMessage;
        }
    }

    public function getData($key = null)
    {
        if($key) {
            return $this->data[$key];
        } else {
            return $this->data;
        }
    }

    public function getErrors($key = null)
    {
        if($key) {
            return $this->errors[$key];
        } else {
            return $this->errors;
        }
    }
}
