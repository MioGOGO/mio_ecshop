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
        $open_id = app('request')->request->get('open_id');
        $access_token = app('request')->request->get('access_token');
        if( !$open_id || !$access_token ){
            return false;
        }
        Log::debug('Wx_openid_Authorization', ['open_id' => $open_id,'access_token' => $access_token]);

        $info = self::getWxUserinfoByOpenid( $access_token,$open_id );
        if( !$info ){
            return false;
        }
        return $info;


    }


    private static function getWxUserinfoByOpenid($access_token, $open_id)
    {
        $api = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$open_id}";
        $res = curl_request($api);
        if (isset($res['errcode'])) {
            Log::error('weixin_oauth_log: '.json_encode($res));
            return false;
        }

        return [
            'nickname' => $res['nickname'],
            'gender' => $res['sex'],
            'prefix' => 'wx',
            'avatar' => $res['headimgurl']
        ];
    }

}