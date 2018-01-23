<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/23
 * Time: 17:21
 */

namespace App\Http\Controllers\dckc;


use App\Http\Controllers\Controller;

class OrderController extends Controller
{

    public function add(){
        $rules = [
            'openid'            => 'required|string|min:1',
            'params'        => 'required|json|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        print_r( $requests = $this->request->all() );

    }



}