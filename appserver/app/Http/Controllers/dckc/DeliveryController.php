<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/31
 * Time: 17:14
 */

namespace App\Http\Controllers\dckc;


use App\Helper\ProgramLong;
use App\Http\Controllers\Controller;
use App\Models\dckc\ShopConfig;

class DeliveryController extends Controller
{


    public function checkRange(){
        $rules = [
            'params'        => 'required|json',
        ];

        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }
        $result = ShopConfig::checkRange( $this->validated );
        return $this->jsondckc( $result );

    }

}