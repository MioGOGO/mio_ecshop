<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/23
 * Time: 18:54
 */

namespace App\Http\Middleware;
use App\Helper\WxOpenid;

class OpenidAuthenticate
{

    public function handle($request, Closure $next){
        $memberName  = WxOpenid::authorization();


    }

}