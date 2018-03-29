<?php

namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    /**
     * api入口，还要允许跨域吧，jsonp
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $data = [
            'code' => 200,
            'message' => 'ca2722 api index',
        ];
//       return response()->json($data);
        return $data;
    }
}
