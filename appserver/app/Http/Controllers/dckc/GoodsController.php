<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/11
 * Time: 11:41
 */

namespace App\Http\Controllers\dckc;


use App\Http\Controllers\Controller;

class GoodsController extends Controller{



    public function getlist(){

        $g = 'hello world';



        return $this->json( $g );

    }





}