<?php

namespace CharosEMR\Presentation\Http\Controllers;

class HomeController
{
    public function index()
    {
        $title = 'Welcome - Charos Dental Clinic EMR';
        $customCss = '/css/landing.css';

        ob_start();
        require __DIR__ . '/../../Views/home/landing.php';
        $content = ob_get_clean();

        require __DIR__ . '/../../Views/layouts/main.php';
    }
}
