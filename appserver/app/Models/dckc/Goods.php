<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/11
 * Time: 16:01
 */

namespace App\Models\dckc;

use App\Models\BaseModel;

class Goods extends BaseModel{



    /**
     * 首页商品列表
     */
    public static function getHomeList()
    {
        return self::formatBody([
            'all_products'  => count(self::getRecommendGoods(false)) == 0 ? null : self::getRecommendGoods(false),
        ]);
    }

    public static function getRecommendGoods($type)
    {
        $model = self::where(['is_delete' => 0, 'is_on_sale' => 1, 'is_alone_sale' => 1]);
        if( $type ){
            return $model->orderBy('sort_order')->orderBy('last_update', 'desc')->with('properties')->get();
        }else{
            return $model->where($type, 1)->orderBy('sort_order')->orderBy('last_update', 'desc')->with('properties')->get();
        }
    }


}