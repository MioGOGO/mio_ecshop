<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/2/7
 * Time: 18:34
 */

namespace App\Http\Controllers;


use App\Models\dckc\Goods;

class IndexController extends Controller
{


    public function index(){

        $data = Goods::getHomeList( ['type'=>'now'] );

        return view('indexdckc',  ['pageData'=> json_encode( $data)]  );

    }
    public function getlist()
    {

        $rules = [
            'type' => 'required|string|min:1',
        ];
        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }
        $data = Goods::getHomeList($this->validated);

        return $this->json($data);

    }

}