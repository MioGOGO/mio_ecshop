<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/2/7
 * Time: 18:34
 */

namespace App\Http\Controllers;


use App\Models\dckc\Goods;
use Log;

class IndexController extends Controller
{


    public function index(){

        $data = Goods::getHomeList( ['type'=>'now'] );
        $res['goodlist'] = $data;
        $token = isset( $_COOKIE['dckc-token'] ) ? $_COOKIE['dckc-token']  : '';
        foreach ( $_COOKIE as $k => $v ){
            Log::debug('openid: '.$k .'----'.$v);
        }
        $res['user'] = [
            'token' => $token,
            'loginStatus' => empty( $token ) ? 0 : 1,
        ];

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