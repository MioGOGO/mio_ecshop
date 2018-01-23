<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/23
 * Time: 18:54
 */

namespace App\Http\Middleware;



use App\Helper\WxOpenid;
use Closure;
class OpenidAuthenticate
{

    public function handle($request, Closure $next){
        $userinfo = WxOpenid::authorization();
        if( !$userinfo ){
            return false;
        }
        return $next($request);

    }

}