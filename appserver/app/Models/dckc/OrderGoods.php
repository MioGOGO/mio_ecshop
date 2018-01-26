<?php

namespace App\Models\dckc;
use App\Models\BaseModel;

use App\Services\Shopex\Erp;


class OrderGoods extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'order_goods';
    protected $primaryKey = 'rec_id';
    public    $timestamps = false;

    protected $guarded = [];
    protected $appends = ['id', 'product', 'property', 'attachment', 'total_amount', 'total_price', 'product_price', 'is_reviewed'];
    protected $visible = ['id', 'product', 'property', 'attachment', 'total_amount', 'total_price', 'product_price', 'is_reviewed'];


    /**
    * 获取商品出售总数
    *
    * @access public
    * @param integer $goods_id
    * @return integer
    */
    public static function getSalesCountById($goods_id)
    {
        // return self::where(['goods_id' => $goods_id])->sum('goods_number');

        // 查询该商品销量

        return self::leftJoin('order_info', 'order_info.order_id', '=', 'order_goods.order_id')
            ->where('goods_id', $goods_id)
            ->where('order_status', 5)
	    ->sum('goods_number');
    }

    /**
     * 取得某订单应该赠送的积分数
     * @param   array   $order  订单
     * @return  int     积分数
     */
    public static function integralToGive($order_id)
    {
        $model = self::select('goods.give_integral', 'order_goods.goods_price', 'order_goods.goods_number', 'goods.goods_id', 'order_goods.goods_id', 'goods.goods_name', 'order_goods.goods_attr')->join('goods', 'goods.goods_id', '=', 'order_goods.goods_id')
                ->where('order_goods.order_id', $order_id)
                ->where('order_goods.goods_id', '>', 0)
                ->where('order_goods.parent_id', 0)
                ->where('order_goods.is_gift', 0)
                ->where('order_goods.extension_code', '<>', 'package_buy')
                ->get();

        if(count($model)>0)
        {
            $integral = 0;
            $sum = 0;

            foreach ($model as $order_goods) {
                $integral = $order_goods->goods_price;
                if($order_goods->give_integral > -1){
                    $integral = $order_goods->give_integral;
                }
                $sum += $order_goods->goods_number *$integral;
            }

            return $sum;
        }

        return false;
    }

    public function orderinfo()
    {
        return $this->belongsTo('App\Models\v2\OrderInfo','order_id','order_id');
    }

    public function getIdAttribute()
    {
        return $this->attributes['goods_id'];
    }

    public function getProductAttribute()
    {
        return [
            'id' => $this->attributes['goods_id'],
            'name' => $this->attributes['goods_name'],
            'price' => $this->attributes['goods_price'],
            'photos' => GoodsGallery::getPhotosById($this->attributes['goods_id'])
        ];
    }

    public function getPropertyAttribute()
    {
        return preg_replace("/(?:\[)(.*)(?:\])/i", '', $this->attributes['goods_attr']);
    }

    public function getAttachmentAttribute()
    {
        return null;
    }

    public function getTotalAmountAttribute()
    {
        return $this->attributes['goods_number'];
    }

    public function getTotalPriceAttribute()
    {
        return number_format($this->attributes['goods_price'] * $this->attributes['goods_number'], 2, '.', '');
    }

    public function getProductPriceAttribute()
    {
        // TODO 获取选择商品属性的价格
        return $this->attributes['goods_price'];
    }

    public function getIsReviewedAttribute()
    {
        return false;
    }

    /**
     * 购物车结算
     * @param     int     $shop            // 店铺ID(无)
     * @param     int     $consignee       // 收货人ID
     * @param     int     $shipping        // 快递ID
     * @param     string     $invoice_type    // 发票类型，如：公司、个人
     * @param     string     $invoice_content // 发票内容，如：办公用品、礼品
     * @param     string     $invoice_title   // 发票抬头，如：xx科技有限公司
     * @param     int     $coupon          // 优惠券ID (无)
     * @param     int     $cashgift        // 红包ID
     * @param     int     $comment         // 留言
     * @param     int     $score           // 积分
     * @param     int     $cart_good_id    // 购物车商品id数组
     */

    public static function checkout(array $attributes)
    {

        extract($attributes);
        $consignee = UserAddress::addDckc( $attributes );
        //-- 完成所有订单操作，提交到数据库
        /* 取得购物类型 */

        /* 检查购物车中是否有商品 */

        if ( !isset( $goodsList ) || !is_array( $goodsList ) ) {
            return self::formatErrorDckc(self::BAD_REQUEST,'product error');
        }
        //$goodsArray = array_column( $goodsList,'id' );
        /* 检查商品库存 */
        /* 如果使用库存，且下订单时减库存，则减少库存 */
//        if (ShopConfig::findByCode('use_storage') == '1')
//        {
//            $cart_goods_stock = self::get_cart_goods($cart_good_ids);
//            $_cart_goods_stock = array();
//            foreach ($cart_goods_stock['goods_list'] as $value)
//            {
//                $_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
//            }
//
//            if (!self::flow_cart_stock($_cart_goods_stock)) {
//                return self::formatError(self::BAD_REQUEST,trans('message.good.out_storage'));
//            }
//
//            unset($cart_goods_stock, $_cart_goods_stock);
//        }


        $consignee_info = UserAddress::get_consignee($consignee);

        if (!$consignee_info) {
            return self::formatError(self::BAD_REQUEST,trans('message.consignee.not_found'));
        }

        $inv_type = isset($invoice_type) ? $invoice_type : ShopConfig::findByCode('invoice_type') ;
        $inv_payee = isset($invoice_title) ? $invoice_title : ShopConfig::findByCode('invoice_title');//发票抬头
        $inv_content = isset($invoice_content) ? $invoice_content : ShopConfig::findByCode('invoice_content') ;
        $postscript = isset($comment) ? $comment : '';

        $order = array(
            'shipping_id'     => intval(0),
            'pay_id'          => intval(0),
            'pack_id'         => isset($_POST['pack']) ? intval($_POST['pack']) : 0,//包装id
            'card_id'         => isset($_POST['card']) ? intval($_POST['card']) : 0,//贺卡id
            'card_message'    => '',//贺卡内容
            'surplus'         => isset($_POST['surplus']) ? floatval($_POST['surplus']) : 0.00,
            'integral'        => isset($score) ? intval($score) : 0,//使用的积分的数量,取用户使用积分,商品可用积分,用户拥有积分中最小者
            'bonus_id'        => isset($cashgift) ? intval($cashgift) : 0,//红包ID
            // 'need_inv'        => empty($_POST['need_inv']) ? 0 : 1,
            'inv_type'        => $inv_type,
            'inv_payee'       => trim($inv_payee),
            'inv_content'     => $inv_content,
            'postscript'      => trim($postscript),
            'how_oos'         => '',//缺货处理
            // 'how_oos'         => isset($_LANG['oos'][$_POST['how_oos']]) ? addslashes($_LANG['oos'][$_POST['how_oos']]) : '',
            // 'need_insure'     => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
            'user_id'         => $user_id,
            'add_time'        => time(),
            'order_status'    => Order::OS_UNCONFIRMED,
            'shipping_status' => Order::SS_UNSHIPPED,
            'pay_status'      => Order::PS_UNPAYED,
            'agency_id'       => 0 ,//办事处的id
        );


        /* 扩展信息 */
        $order['extension_code'] = '';
        $order['extension_id'] = 0;






        /* 收货人信息 */
        $order['consignee'] = $consignee_info->consignee;
        $order['country'] = $consignee_info->country;
        $order['province'] = $consignee_info->province;
        $order['city'] = $consignee_info->city;
        $order['mobile'] = $consignee_info->mobile;
        $order['tel'] = $consignee_info->tel;
        $order['zipcode'] = $consignee_info->zipcode;
        $order['district'] = $consignee_info->district;
        $order['address'] = $consignee_info->address;

        /* 订单中的总额 */


        $order['goods_amount'] = $totalFee;
        $order['discount']     = 0;
        $order['surplus']      = 0;
        $order['tax']          = 0;



        /* 配送方式 */
        if ($order['shipping_id'] > 0)
        {
            $shipping = Shipping::where('shipping_id',$order['shipping_id'])
                ->where('enabled',1)
                ->first();
            $order['shipping_name'] = addslashes($shipping['shipping_name']);
        }
        $order['shipping_fee'] = 0;
        $order['insure_fee']   = 0;




        /* 如果全部使用余额支付，检查余额是否足够 没有余额支付*/
        $order['order_amount']  = number_format($totalFee, 2, '.', '');

        /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
        if ($order['order_amount'] <= 0)
        {
            $order['order_status'] = Order::OS_CONFIRMED;
            $order['confirm_time'] = time();
            $order['pay_status']   = Order::PS_PAYED;
            $order['pay_time']     = time();
            $order['order_amount'] = 0;
        }

        $order['parent_id'] = 0;

        // 获取新订单号 验证订单号重复
        do {
            $order['order_sn'] = Order::get_order_sn();

            $order_sn = Order::where('order_sn', $order['order_sn'])->first();

        } while (!empty($order_sn));

        /* 插入订单表 */

        unset($order['timestamps']);
        unset($order['perPage']);
        unset($order['incrementing']);
        unset($order['dateFormat']);
        unset($order['morphClass']);
        unset($order['exists']);
        unset($order['wasRecentlyCreated']);
        unset($order['cod_fee']);
        // unset($order['surplus']);
        $new_order_id = Order::insertGetId($order);
        $order['order_id'] = $new_order_id;

        /* 插入订单商品 */
        $checkTotalPrice = 0;
        foreach ($goodsList as $key => $goods) {
            $goodInfo = Goods::where(['is_delete' => 0, 'goods_id' => $goods['id']])->first();
            if( !$goodInfo ){
                return self::formatError(self::BAD_REQUEST,'goods  not exists');
            }
            $order_good                 = new OrderGoods;
            $order_good->order_id       = $new_order_id;
            $order_good->goods_id       = $goodInfo->goods_id;
            $order_good->goods_name     = $goodInfo->goods_name;
            $order_good->goods_sn       = $goodInfo->goods_sn;
            $order_good->product_id     = 0;
            $order_good->goods_number   = $goods['amount'];
            $order_good->market_price   = $goodInfo->market_price;
            $order_good->goods_price    = $goodInfo->goods_price;
            //$order_good->goods_attr     = $goods->goods_attr;
            $order_good->is_real        = $goodInfo->is_real;
            $order_good->extension_code = $goodInfo->extension_code;
            //$order_good->parent_id      = $goods->parent_id;
            //$order_good->is_gift        = $goods->is_gift;
            //$order_good->goods_attr_id  = $goods->goods_attr_id;
            $order_good->save();
            $checkTotalPrice += $goods['amount']*$goodInfo->goods_price;
        }
        if( $checkTotalPrice != $totalFee ){
            return self::formatError(10035,'list price ！= totalfee  ');
        };

        /* 修改拍卖活动状态 */

        /* 处理余额、积分、红包 */


        /* 如果使用库存，且下订单时减库存，则减少库存 */
        if (ShopConfig::findByCode('use_storage') == '1' && ShopConfig::findByCode('stock_dec_time') == '1')
        {
            Order::change_order_goods_storage($order['order_id'], true, 1);
        }

        /* 给商家发邮件 */
        /* 增加是否给客服发送邮件选项 */
        /* 如果需要，发短信 */
        /* 如果订单金额为0 处理虚拟卡 */
//        if ($order['order_amount'] <= 0)
//        {
//            $res = self::where('is_real',0)
//                ->where('extension_code','virtual_card')
//                ->where('rec_type','flow_type')
//                ->selectRaw('goods_id,goods_name,goods_number as num')
//                ->get();
//
//            $virtual_goods = array();
//            foreach ($res AS $row)
//            {
//                $virtual_goods['virtual_card'][] = array('goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']);
//            }
//
//            if ($virtual_goods AND $flow_type != self::CART_GROUP_BUY_GOODS)
//            {
//                /* 虚拟卡发货 */
//                if (virtual_goods_ship($virtual_goods,$msg, $order['order_sn'], true))
//                {
//                    /* 如果没有实体商品，修改发货状态，送积分和红包 */
//                    $get_count = OrderGoods::where('order_id',$order['order_id'])
//                        ->where('is_real',1)
//                        ->count();
//
//                    if ($get_count <= 0)
//                    {
//                        /* 修改订单状态 */
//                        update_order($order['order_id'], array('shipping_status' => SS_SHIPPED, 'shipping_time' => time()));
//
//                        /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
//                        if ($order['user_id'] > 0)
//                        {
//                            /* 取得用户信息 */
//                            $user = Member::user_info($order['user_id']);
//
//                            /* 计算并发放积分 */
//                            $integral = integral_to_give($order);
//                            AccountLog::logAccountChange( 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), trans('message.score.register'), $order['order_sn']);
//
//                            /* 发放红包 */
//                            send_order_bonus($order['order_id']);
//                        }
//                    }
//                }
//            }
//
//        }

        /* 插入支付日志 */
        // $order['log_id'] = insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);


        if(!empty($order['shipping_name']))
        {
            $order['shipping_name']=trim(stripcslashes($order['shipping_name']));
        }
        $orderObj = Order::find($new_order_id);

        Erp::order($orderObj->order_sn, 'order_create');

        return self::formatBodyDckc(['order' => $orderObj]);
    }

}
