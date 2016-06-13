<?php

namespace Seven;

class Permissions
{
    public function isLoggedIn()
    {
        return (isset($_SESSION['id']) && $_SESSION['id']);
    }
}
