<?php

namespace App\Http\Controllers;

use App\Http\Resources\LinkResource;
use App\Models\Link;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function show($code)
    {
        $link = Link::where('code', $code)->first();

        return new LinkResource($link);
    }
}
