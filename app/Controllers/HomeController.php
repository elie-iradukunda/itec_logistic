<?php

namespace Controllers;

class HomeController
{
    public function index(): void
    {
        \view('home/index', ['title' => 'Logistics made visible']);
    }
}
