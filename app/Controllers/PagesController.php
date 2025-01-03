<?php

namespace App\Controllers;


class PagesController extends Controller
{
    public function index()
    {
        $this->view('pages.home');
    }

    public function policy()
    {
        $this->view('pages.payment-policy');
    }
}
