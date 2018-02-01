<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/30
 * Time: 14:52
 */

namespace App\Http\Controllers\dckc;


use App\Http\Controllers\Controller;
use App\Models\BaseModel;
use App\Models\dckc\Payment;
use App\Services\Other\JSSDK;
use Cache;

class WxConfigController extends Controller
{

    public function get(){


        $payment = Payment::where(['type' => 'oauth', 'status' => 1, 'code' => 'wechat.web'])->first();
        if (!$payment) {
            return BaseModel::formatErrorDckc('10040');
        }
        $config = Payment::checkConfig(['app_id', 'app_secret'], $payment);
        if (!$config) {
            return BaseModel::formatErrorDckc('10041');
        }

        $jssdk = new JSSDK($config['app_id'], $config['app_secret']);
        $arr = $jssdk->GetSignPackage();
        $arr = BaseModel::formatBodyDckc([ 'data'=>$arr ]);
        return $this->jsondckc( $arr );
    }
    public function test(){
        $aa = Cache::get("access_token");
        print_r( $aa );exit;
    }

}