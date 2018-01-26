<?php

namespace App\Models\dckc;
use App\Models\BaseModel;

use App\Helper\Token;


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

        $consignee = UserAddress::addDckc( $attributes );
        print_r( $consignee );exit;
        extract($attributes);
        //-- 完成所有订单操作，提交到数据库
        /* 取得购物类型 */
        $flow_type = self::CART_GENERAL_GOODS;

        /* 检查购物车中是否有商品 */

        if (json_decode($cart_good_id,true)) {
            $cart_good_ids = json_decode($cart_good_id,true);
        }else{
            return self::formatError(self::BAD_REQUEST,trans('message.cart.json_invalid'));
        }
        if (count($cart_good_ids) > 0) {
            foreach ($cart_good_ids as $key => $cart_id) {
                if (!Cart::find($cart_id)) {
                    return self::formatError(self::BAD_REQUEST,trans('message.cart.cart_goods_error'));
                }
            }
        }else{
            return self::formatError(self::BAD_REQUEST,trans('message.cart.no_goods'));
        }
        /* 检查商品库存 */
        /* 如果使用库存，且下订单时减库存，则减少库存 */
        if (ShopConfig::findByCode('use_storage') == '1')
        {
            $cart_goods_stock = self::get_cart_goods($cart_good_ids);
            $_cart_goods_stock = array();
            foreach ($cart_goods_stock['goods_list'] as $value)
            {
                $_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
            }

            if (!self::flow_cart_stock($_cart_goods_stock)) {
                return self::formatError(self::BAD_REQUEST,trans('message.good.out_storage'));
            }

            unset($cart_goods_stock, $_cart_goods_stock);
        }


        $consignee_info = UserAddress::get_consignee($consignee);

        if (!$consignee_info) {
            return self::formatError(self::BAD_REQUEST,trans('message.consignee.not_found'));
        }

        $inv_type = isset($invoice_type) ? $invoice_type : ShopConfig::findByCode('invoice_type') ;
        $inv_payee = isset($invoice_title) ? $invoice_title : ShopConfig::findByCode('invoice_title');//发票抬头
        $inv_content = isset($invoice_content) ? $invoice_content : ShopConfig::findByCode('invoice_content') ;
        $postscript = isset($comment) ? $comment : '';
        $user_id = Token::authorization();

        $order = array(
            'shipping_id'     => intval($shipping),
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

        /* 订单中的商品 */
        $cart_goods = self::cart_goods($flow_type, $cart_good_ids);
        if (empty($cart_goods))
        {
            return self::formatError(self::BAD_REQUEST, trans('message.cart.no_goods'));
        }

        /* 检查积分余额是否合法 */
        if ($user_id > 0)
        {
            $user_info = Member::user_info($user_id);

            $order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
            if ($order['surplus'] < 0)
            {
                $order['surplus'] = 0;
            }

            // 查询用户有多少积分
            $total_integral = 0;
            foreach ($cart_goods as $goods) {
                $integral = Goods::where('goods_id', $goods['goods_id'])->value('integral');
                $total_integral = $total_integral + $integral * $goods['goods_number'];
            }

            $scale = ShopConfig::findByCode('integral_scale');

            if($scale > 0){
                $flow_points = $total_integral / ($scale / 100);
            }else{
                $flow_points = 0;
            }

            $user_points = $user_info['pay_points']; // 用户的积分总数

            $order['integral'] = min($order['integral'], $user_points, $flow_points);
            if ($order['integral'] < 0)
            {
                $order['integral'] = 0;
            }
        }
        else
        {
            $order['surplus']  = 0;
            $order['integral'] = 0;
        }

        /* 检查红包是否存在 */
        if ($order['bonus_id'] > 0)
        {
            $bonus = BonusType::bonus_info($order['bonus_id']);

            if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > self::cart_amount(true, $flow_type))
            {
                $order['bonus_id'] = 0;
            }
        }

        /* 订单中的商品 */
        $cart_goods = self::cart_goods($flow_type,$cart_good_ids);
        if (empty($cart_goods))
        {
            return self::formatError(self::BAD_REQUEST,trans('message.cart.no_goods'));
        }

        /* 检查商品总额是否达到最低限购金额 */
        // app和web有区别，购物车到结算不同
        // app 可以选择要结算的商品
        if ($flow_type == self::CART_GENERAL_GOODS && self::getCartAmount($cart_good_ids) < ShopConfig::findByCode('min_goods_amount'))
        {
            return self::formatError(self::BAD_REQUEST,trans('message.good.min_goods_amount'));
        }
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
        /* 判断是不是实体商品 */
        foreach ($cart_goods AS $val)
        {
            /* 统计实体商品的个数 */
            if ($val['is_real'])
            {
                $is_real_good=1;
            }
        }
        if(isset($is_real_good))
        {
            $shipping_is_real = Shipping::where('shipping_id',$order['shipping_id'])->where('enabled',1)->first();
            if(!$shipping_is_real)
            {
                return self::formatError(self::BAD_REQUEST, '您必须选定一个配送方式');
            }
        }
        /* 订单中的总额 */
        $total = Order::order_fee($order, $cart_goods, $consignee_info,$cart_good_id,$shipping,$consignee);
        /* 红包 */
        if (!empty($order['bonus_id']))
        {
            $bonus          = BonusType::bonus_info($order['bonus_id']);
            $total['bonus'] = $bonus['type_money'];
        }
        // $total['bonus_formated'] = Goods::price_format($total['bonus'], false);

        $order['bonus']        = isset($bonus)? $bonus['type_money'] : '';

        $order['goods_amount'] = $total['goods_price'];
        $order['discount']     = $total['discount'];
        $order['surplus']      = $total['surplus'];
        $order['tax']          = $total['tax'];

        // 购物车中的商品能享受红包支付的总额
        $discount_amout = self::compute_discount_amount($cart_good_ids);
        // 红包和积分最多能支付的金额为商品总额
        $temp_amout = $order['goods_amount'] - $discount_amout;

        if ($temp_amout <= 0)
        {
            $order['bonus_id'] = 0;
        }

        /* 配送方式 */
        if ($order['shipping_id'] > 0)
        {
            $shipping = Shipping::where('shipping_id',$order['shipping_id'])
                ->where('enabled',1)
                ->first();
            $order['shipping_name'] = addslashes($shipping['shipping_name']);
        }
        $order['shipping_fee'] = $total['shipping_fee'];
        $order['insure_fee']   = 0;
        /* 支付方式 */
        if ($order['pay_id'] > 0)
        {
            $payment = payment_info($order['pay_id']);
            $order['pay_name'] = addslashes($payment['pay_name']);
        }
        $order['pay_fee'] = $total['pay_fee'];
        $order['cod_fee'] = $total['cod_fee'];

        /* 商品包装 */

        /* 祝福贺卡 */

        /* 如果全部使用余额支付，检查余额是否足够 没有余额支付*/
        $order['order_amount']  = number_format($total['amount'], 2, '.', '');

        /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
        if ($order['order_amount'] <= 0)
        {
            $order['order_status'] = Order::OS_CONFIRMED;
            $order['confirm_time'] = time();
            $order['pay_status']   = Order::PS_PAYED;
            $order['pay_time']     = time();
            $order['order_amount'] = 0;
        }

        $order['integral_money']   = $total['integral_money'];
        $order['integral']         = $total['integral'];

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
        $cart_goods = Cart::whereIn('rec_id',$cart_good_ids)->where('rec_type',$flow_type)->get();
        foreach ($cart_goods as $key => $cart_good) {
            $order_good                 = new OrderGoods;
            $order_good->order_id       = $new_order_id;
            $order_good->goods_id       = $cart_good->goods_id;
            $order_good->goods_name     = $cart_good->goods_name;
            $order_good->goods_sn       = $cart_good->goods_sn;
            $order_good->product_id     = $cart_good->product_id;
            $order_good->goods_number   = $cart_good->goods_number;
            $order_good->market_price   = $cart_good->market_price;
            $order_good->goods_price    = $cart_good->goods_price;
            $order_good->goods_attr     = $cart_good->goods_attr;
            $order_good->is_real        = $cart_good->is_real;
            $order_good->extension_code = $cart_good->extension_code;
            $order_good->parent_id      = $cart_good->parent_id;
            $order_good->is_gift        = $cart_good->is_gift;
            $order_good->goods_attr_id  = $cart_good->goods_attr_id;
            $order_good->save();
        }

        /* 修改拍卖活动状态 */

        /* 处理余额、积分、红包 */

        if ($order['user_id'] > 0 && $order['integral'] > 0)
        {
            AccountLog::logAccountChange(0, 0, 0, $order['integral'] * (-1), trans('message.score.pay'), $order['order_sn']);
        }


        if ($order['bonus_id'] > 0 && $temp_amout > 0)
        {
            UserBonus::useBonus($order['bonus_id'], $new_order_id);
        }

        /* 如果使用库存，且下订单时减库存，则减少库存 */
        if (ShopConfig::findByCode('use_storage') == '1' && ShopConfig::findByCode('stock_dec_time') == self::SDT_PLACE)
        {
            Order::change_order_goods_storage($order['order_id'], true, self::SDT_PLACE);
        }

        /* 给商家发邮件 */
        /* 增加是否给客服发送邮件选项 */
        /* 如果需要，发短信 */
        /* 如果订单金额为0 处理虚拟卡 */
        if ($order['order_amount'] <= 0)
        {
            $res = self::where('is_real',0)
                ->where('extension_code','virtual_card')
                ->where('rec_type','flow_type')
                ->selectRaw('goods_id,goods_name,goods_number as num')
                ->get();

            $virtual_goods = array();
            foreach ($res AS $row)
            {
                $virtual_goods['virtual_card'][] = array('goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']);
            }

            if ($virtual_goods AND $flow_type != self::CART_GROUP_BUY_GOODS)
            {
                /* 虚拟卡发货 */
                if (virtual_goods_ship($virtual_goods,$msg, $order['order_sn'], true))
                {
                    /* 如果没有实体商品，修改发货状态，送积分和红包 */
                    $get_count = OrderGoods::where('order_id',$order['order_id'])
                        ->where('is_real',1)
                        ->count();

                    if ($get_count <= 0)
                    {
                        /* 修改订单状态 */
                        update_order($order['order_id'], array('shipping_status' => SS_SHIPPED, 'shipping_time' => time()));

                        /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                        if ($order['user_id'] > 0)
                        {
                            /* 取得用户信息 */
                            $user = Member::user_info($order['user_id']);

                            /* 计算并发放积分 */
                            $integral = integral_to_give($order);
                            AccountLog::logAccountChange( 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), trans('message.score.register'), $order['order_sn']);

                            /* 发放红包 */
                            send_order_bonus($order['order_id']);
                        }
                    }
                }
            }

        }
        /* 清空购物车 */
        self::clear_cart_ids($cart_good_ids,$flow_type);

        /* 插入支付日志 */
        // $order['log_id'] = insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);


        if(!empty($order['shipping_name']))
        {
            $order['shipping_name']=trim(stripcslashes($order['shipping_name']));
        }
        $orderObj = Order::find($new_order_id);

        Erp::order($orderObj->order_sn, 'order_create');

        return self::formatBody(['order' => $orderObj]);
    }

}
