<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/23
 * Time: 18:58
 */

namespace App\Helper;


class WxOpenid
{


    public static function authorization(){
        $token = app('request')->header('X-'.config('app.name').'-Authorization');
        $openid = app('request');
        echo 'flag'."<br>";
        var_dump( $openid );exit;
        Log::debug('Authorization', ['token' => $token]);


    }

}