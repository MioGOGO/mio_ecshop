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

    protected $connection = 'shop';

    protected $table      = 'goods';

    public    $timestamps = false;

    protected $primaryKey = 'goods_id';

    protected $guarded = [];

    protected $appends = [
        'id', 'category', 'brand', 'shop', 'sku', 'default_photo', 'photos', 'name', 'price', 'current_price', 'discount', 'sales_count','score','good_stock',
        'comment_count', 'is_liked', 'review_rate', 'intro_url', 'share_url', 'created_at', 'updated_at'
    ];

    protected $visible = [
        'id', 'category', 'brand', 'shop', 'tags', 'default_photo', 'photos','sku', 'name', 'price', 'current_price', 'discount', 'is_shipping', 'promos','stock','properties','sales_count', 'attachments','goods_desc','score','comments','good_stock','comment_count', 'is_liked', 'review_rate', 'intro_url', 'share_url', 'created_at', 'updated_at'
    ];

    // protected $with = [];

    const NOSORT     = 0;
    const PRICE      = 1;
    const POPULAR    = 2;
    const CREDIT     = 3;
    const SALE       = 4;
    const DATE       = 5;

    const ASC        = 1;
    const DESC       = 2;


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
            return $model->where($type, 1)->orderBy('sort_order')->orderBy('last_update', 'desc')->with('properties')->get();
        }else{
            return $model->orderBy('sort_order')->orderBy('last_update', 'desc')->with('properties')->get();
        }
    }


    public function getIdAttribute()
    {
        return $this->goods_id;
    }


    public function properties()
    {
        return $this->belongsToMany('App\Models\v2\Attribute','goods_attr','goods_id','attr_id')->where('attribute.attr_type', '!=',0)->groupBy('attr_id');
    }

}