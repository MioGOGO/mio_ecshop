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

class WxConfigController extends Controller
{

    public function get(){


        $payment = Payment::where(['type' => 'payment', 'status' => 1, 'code' => 'wechat.web'])->first();
        if (!$payment) {
            return BaseModel::formatErrorDckc('10040');
        }
        $config = self::checkConfig(['app_id', 'app_secret', 'mch_id', 'mch_key'], $payment);
        if (!$config) {
            return BaseModel::formatErrorDckc('10041');
        }

        $jssdk = new JSSDK($config['app_id'], $config['app_secret']);
        $arr = $jssdk->GetSignPackage();
        print_r( $arr );exit;
    }

}