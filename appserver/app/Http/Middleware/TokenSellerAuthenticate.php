<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/3/21
 * Time: 17:10
 */

namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Response;
use App\Helper\Token;
use App\Helper\Protocol;


class TokenSellerAuthenticate
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = Token::authorizationSeller();

        if ($token === false) {
            return show_error_dckc(80010, trans('message.token.invalid'));
        }

        if ($token ===  'token-expired') {
            return show_error_dckc(80011, trans('message.token.expired'));
        }

        return $next($request);
    }

}