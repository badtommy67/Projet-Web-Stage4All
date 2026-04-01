<?php
namespace App\Controllers;

class Controller {
    protected $twig;
    protected $pdo;

    public function __construct($twig, $pdo = null) {
        $this->twig = $twig;
        $this->pdo  = $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->twig->addGlobal('session', $_SESSION);
    }
}