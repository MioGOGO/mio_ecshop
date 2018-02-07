<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/2/7
 * Time: 18:34
 */

namespace App\Http\Controllers;


class IndexController extends Controller
{


    public function index(){

        return view('indexdckc',  ['pageData'=> json_encode( array() )]  );

    }

}