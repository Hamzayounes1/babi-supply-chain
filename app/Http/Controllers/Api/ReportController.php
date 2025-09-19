<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // Example index - replace with your implementation
    public function index(Request $request)
    {
        return response()->json(['message' => 'reports index']);
    }
}
