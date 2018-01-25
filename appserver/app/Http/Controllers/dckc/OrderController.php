<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/23
 * Time: 17:21
 */

namespace App\Http\Controllers\dckc;


use App\Helper\Token;
use App\Http\Controllers\Controller;
use App\Models\dckc\Member;

class OrderController extends Controller
{

    public function add(){
        $rules = [
            'access_token'  => 'required|string|min:1',
            'params'        => 'required|string|min:1',
            'open_id'       => 'required|string|min:1',
        ];

        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }

        $userinfo = Member::authDckc( $this->validated );

        $decodeJson = Token::jsonDecode( $this->validated['params'] );



        print_r( $decodeJson );

    }



}