<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;

class AreaController extends Controller
{
    public function index()
    {
        return Area::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
