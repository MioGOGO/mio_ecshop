<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/11
 * Time: 11:41
 */

namespace App\Http\Controllers\dckc;


use App\Http\Controllers\Controller;
use App\Models\dckc\Goods;

class GoodsController extends Controller
{


    /**
     * 获取首页菜单
     */
    public function getlist()
    {

        $rules = [
            'when' => 'required|string|min:1',
        ];
        if ($error = $this->validateInput($rules)) {
            return $error;
        }
        $data = Goods::getHomeList($this->validated);

        return $this->json($data);

    }

    /*
     * 获取菜品明细
     * */
    public function goodsdetail()
    {

        $rules = [
            'id' => 'required|integer|min:1',
        ];
        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Goods::getInfo( $this->validated );


        return $this->json( $data );
    }





}