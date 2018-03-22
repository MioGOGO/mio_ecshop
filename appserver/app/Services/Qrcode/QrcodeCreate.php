<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/3/22
 * Time: 10:51
 */

namespace App\Services\Qrcode;
require_once("phpqrcode.php");

class QrcodeCreate
{

    public static function createQr( $msg ){
        $errorCorrectionLevel = 'L';
        $matrixPointSize = 5;
        $img = QRcode::png($msg,false, $errorCorrectionLevel, $matrixPointSize, 2);
        return "<img src='$img'>";
    }

}