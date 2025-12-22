<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrequenceConge;
use Illuminate\Http\Request;

class FrequenceCongeController extends Controller
{
    public function index(Request $request)
    {
        $query = FrequenceConge::query()->orderBy('libelle');
        if ($search = $request->query('search')) {
            $query->where('libelle', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
        }
        return response()->json($query->get());
    }
}
