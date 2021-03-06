<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/11
 * Time: 16:01
 */

namespace App\Models\dckc;

use App\Models\BaseModel;
use App\Helper\Token;
use \DB;

class Goods extends BaseModel
{

    protected $connection = 'shop';

    protected $table = 'goods';

    public $timestamps = false;

    protected $primaryKey = 'goods_id';

    protected $guarded = [];

    protected $appends = [
        'id','coverImg', 'price','name','detailImg'
    ];

    protected $visible = [
        'id','coverImg', 'price','name','detailImg'
    ];

    // protected $with = [];

    const NOSORT = 0;
    const PRICE = 1;
    const POPULAR = 2;
    const CREDIT = 3;
    const SALE = 4;
    const DATE = 5;

    const ASC = 1;
    const DESC = 2;


    /**
     * 首页商品列表
     */
    public static function getHomeList( array $attributes )
    {
        extract( $attributes );
        $infos = GoodsCategory::getCategorybykeyword( [ $type.'GoodsList' ] );
        return self::formatBodyDckc([
            'data' => count(self::getRecommendGoods( $infos )) == 0 ? null : self::getRecommendGoods( $infos ),
        ]);
    }

    public static function getRecommendGoods( array $cat_id_arr )
    {
        $model = self::where(['is_delete' => 0, 'is_on_sale' => 1, 'is_alone_sale' => 1]);
        return $model->whereIn( 'cat_id', $cat_id_arr )->orderBy('sort_order')->orderBy('last_update', 'desc')->with('properties')->get();
    }

    public static function getInfo(array $attributes)
    {
        extract($attributes);

        $model = Goods::where(['is_delete' => 0, 'goods_id' => $id]);

        $data = $model->with(['properties', 'tags', 'stock', 'attachments'])->first();

        if (!$data) {
            return self::formatErrorDckc(self::NOT_FOUND);
        }

        if (!$data->is_on_sale) {
            return self::formatErrorDckc(self::BAD_REQUEST, trans('message.good.off_sale'));
        }
        $infos = Attribute::get_goods_attr_info_byid( $id );
        $headerImg = GoodsGallery::getPhotoById($data->goods_id,'headerImg');
        $good_desc = ['desc'=>$data->goods_desc,'img'=>GoodsGallery::getPhotoById($data->goods_id,'detailImg') ];
        $data = $data->toArray();
        extract( $infos );
        $data['headerImg'] = $headerImg;
        $data['detail'] = $good_desc;
        $data['nutrient'] = [ 'entree' => isset($entree) ? $entree : array(),
            'dish' => isset($dish) ? $dish : array(),
            'staple' => isset($staple) ? $staple : array()
        ];
        $data['energy'] = [
            'calorie' => isset($calorie) ? $calorie : '',
            'protein' => isset($protein) ? $protein : '',
            'sugar' => isset($sugar) ? $sugar : '',
        ];
        // $current_price = UserRank::getMemberRankPriceByGid($product);
        //$data['promos'] = FavourableActivity::getPromoByGoods($id, $data->cat_id, $data->brand_id);


//        if ($data->promote_price == 0) {
//            $current_price = UserRank::getMemberRankPriceByGid($product);
//            return self::formatBody(['product' => array_merge($data->toArray(), ['current_price' => $current_price])]);
//        }
        return self::formatBodyDckc(['data' => $data]);
    }

        /**
     * 判断某个商品是否正在特价促销期
     *
     * @access  public
     * @param   float   $price      促销价格
     * @param   string  $start      促销开始日期
     * @param   string  $end        促销结束日期
     * @return  float   如果还在促销期则返回促销价，否则返回0
     */
    public static function bargain_price($price, $start, $end)
    {
        if ($price == 0)
        {
            return 0;
        }
        else
        {
            $time = time();
            // $time = gmtime();
            if ($time >= $start && $time <= $end)
            {
                return $price;
            }
            else
            {
                return 0;
            }
        }
    }

    public function getIdAttribute()
    {
        return $this->goods_id;
    }

    public function getCategoryAttribute()
    {
        return $this->cat_id;
    }

    public function getScoreAttribute()
    {
        $scale = ShopConfig::findByCode('integral_scale');
        if ($scale > 0) {
            return $this->integral / ($scale / 100);
        }
        return 0;
    }

    public function getBrandAttribute()
    {
        return $this->brand_id;
    }

    public function getShopAttribute()
    {
        $data = [];
        // $data['name'] = ShopConfig::findByCode('shop_name');
        $data['id'] = 1;
        return $data['id'];
    }

    public function tags()
    {
        return $this->hasMany('App\Models\v2\Tags', 'goods_id', 'goods_id');

    }

    // public function promos()
    // {
    //     return $this->hasMany('App\Models\v2\GoodsActivity', 'goods_id', 'goods_id');

    // }

    public function properties()
    {
        return $this->belongsToMany('App\Models\v2\Attribute', 'goods_attr', 'goods_id', 'attr_id')->where('attribute.attr_type', '!=', 0)->groupBy('attr_id');
    }

    public function attachments()
    {
        return $this->hasMany('App\Models\v2\GoodsGroup', 'parent_id', 'goods_id');
    }

    public function stock()
    {
        return $this->hasMany('App\Models\v2\Products', 'goods_id', 'goods_id');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\v2\Comment', 'id_value', 'goods_id')->where('comment.comment_type', 0)->where('comment_rank', '>', 3); //商品
    }

    public function getSkuAttribute()
    {
        return $this->goods_sn;
    }

    public function getNameAttribute()
    {
        return $this->goods_name;
    }

    public function getGoodstockAttribute()
    {
        return $this->goods_number;
    }

    public function getPriceAttribute()
    {
        return $this->market_price;
    }

    public function getCurrentpriceAttribute()
    {
        $promote_price = self::bargain_price($this->promote_price, $this->promote_start_date, $this->promote_end_date);
        if (!empty($promote_price)) {
            return $promote_price;
        }

        $user_price = UserRank::getMemberRankPriceByGid($this->goods_id);
        // $user_rank = UserRank::getUserRankByUid();
        // $user_price = MemberPrice::getMemberPriceByUid($user_rank['rank_id'], $this->goods_id);

        if (!empty($user_price)) {
            return $user_price;
        }

        $current_price = UserRank::getMemberRankPriceByGid($this->goods_id);

        return self::price_format($current_price, false);
    }

    public function getDiscountAttribute()
    {
        $price = self::bargain_price($this->promote_price, $this->promote_start_date, $this->promote_end_date);
        if ($price > 0) {
            return [
                "price" => $price,                                  // 促销价格
                "start_at" => $this->promote_start_date,               // 开始时间
                "end_at" => $this->promote_end_date,                 // 结束时间
            ];

        } else {
            return null;
        }
    }

    public function getShareUrlAttribute()
    {
        $uid = Token::authorization();
        if ($uid) {
            return config('app.shop_h5') . '/?u=' . $uid . '#/product/?product=' . $this->goods_id;
        }
        return config('app.shop_h5') . '/#/product/?product=' . $this->goods_id;
    }

    public function getIslikedAttribute()
    {
        return CollectGoods::getIsLiked($this->goods_id) ? 1 : 0;
    }

    public function getSalescountAttribute()
    {
        return OrderGoods::getSalesCountById($this->goods_id);
        //return $this->virtual_sales;
    }

    public function getCommentcountAttribute()
    {
        return Comment::getCommentCountById($this->goods_id);
    }

    public function getcoverImgAttribute()
    {
//        $goods =  Goods::where('goods_id', $this->goods_id)->first();
//
//        $goods_images = formatPhoto($goods->goods_img, $goods->goods_thumb);
//
//        $arr = GoodsGallery::getPhotosById($this->goods_id);
//
//        if (!empty($goods_images)) {
//            array_unshift($arr, $goods_images);
//        }
//
//        if (empty($arr)) {
//            return null;
//        }
//
//        return $arr;

        return GoodsGallery::getPhotoById($this->goods_id,'coverImg');
    }
    public function getdetailImgAttribute(){

        return GoodsGallery::getPhotoById($this->goods_id,'detailImg');
    }

    public function getheaderImgAttribute(){

        return GoodsGallery::getPhotoById($this->goods_id,'headerImg');
    }
    public function getDefaultPhotoAttribute()
    {
        return formatPhoto($this->goods_img);
    }

    public function getReviewrateAttribute()
    {
        return Comment::getCommentRateById($this->goods_id) . '%';
    }

    public function getIntrourlAttribute()
    {
        if (empty($this->goods_desc)) {
            return null;
        }
        return url('/v2/product.intro.' . $this->goods_id);
    }

    public function getCreatedatAttribute()
    {
        return $this->add_time;
    }

    public function getUpdatedatAttribute()
    {
        return $this->last_update;


    }
}