<?php

namespace App\Http\Controllers;

class CartController extends Controller
{
    /**
     * The cart itself lives in localStorage (client side). This page just
     * renders the shell; Alpine.js hydrates the line items from storage.
     */
    public function index()
    {
        return view('cart.index');
    }
}
