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
use App\Models\dckc\UserAddress;

class IndexController extends Controller
{


    public function index(){

        $data = Goods::getHomeList( ['type'=>'now'] );
        $res['goodlist'] = $data;
        $token = isset( $_COOKIE['dckc-token'] ) ? $_COOKIE['dckc-token']  : '';
        $res['user'] = [
            //'token' => $token,
            'loginStatus' => empty( $token ) ? 0 : 1,
        ];
        if( $token ){
            $consignee_info = UserAddress::get_consignee_dckc( true );
            $res['user'] = array_push( $res['user'],$consignee_info );
        }
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