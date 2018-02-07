<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/2/7
 * Time: 18:34
 */

namespace App\Http\Controllers;


use App\Models\dckc\Goods;

class IndexController extends Controller
{


    public function index(){

        $data = Goods::getHomeList( ['type'=>'now'] );
        $res['goodlist'] = $data;
        $token = isset( $_COOKIE['dckc_token'] ) ? $_COOKIE['dckc_token']  : '';
        $res['user'] = [
            'token' => $token,
            'iflogin' => empty( $token ) ? 0 : 1,
        ];
        setcookie( 'mio','wudi' );
        return view('indexdckc',  ['pageData'=> json_encode( $res)]  );

    }
    public function getlist()
    {

        $rules = [
            'type' => 'required|string|min:1',
        ];
        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }
        $data = Goods::getHomeList($this->validated);

        return $this->json($data);

    }

}