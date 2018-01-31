<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/31
 * Time: 17:14
 */

namespace App\Http\Controllers\dckc;


use App\Helper\ProgramLong;

class DeliveryController
{


    public function checkRange(){

        ProgramLong::test();

    }

}