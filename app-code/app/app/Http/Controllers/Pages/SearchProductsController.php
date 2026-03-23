<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchProductsShowRequest;

class SearchProductsController extends Controller
{
    public function index(SearchProductsShowRequest $request) {}
}
