<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ThankYouController extends Controller
{
    public function index(): View
    {
        $data = [];

        return view('catalog.pages.thank-you', $data);
    }
}
