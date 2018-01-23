<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/23
 * Time: 18:58
 */

namespace App\Helper;

use Log;


class WxOpenid
{


    public static function authorization(){
        $openid = app('request')->header('X-'.config('app.name').'-Authorization');
        $openid = app('request')->request->get('openid');
        if( !$openid ){
            return false;
        }
        echo $openid;
        Log::debug('Wx_openid_Authorization', ['openid' => $openid]);


    }

}