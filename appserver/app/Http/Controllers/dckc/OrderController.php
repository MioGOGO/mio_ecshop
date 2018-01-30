<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/23
 * Time: 17:21
 */

namespace App\Http\Controllers\dckc;


use App\Http\Controllers\Controller;
use App\Models\dckc\Member;
use App\Models\dckc\Order;
use App\Models\dckc\OrderGoods;
use Validator;
use App\Models\BaseModel;
use App\Models\dckc\Payment;



class OrderController extends Controller
{

    public $datavalid;

    public function add(){
        $rules = [
            'access_token'  => 'required|string|min:1',
            'params'        => 'required|string|min:1',
            'open_id'       => 'required|string|min:1',
        ];

        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }

        $userinfo = Member::authDckc( $this->validated );

        if( !$userinfo ){
            return self::jsondckc(BaseModel::formatErrorDckc(10031, 'user error'));
        }
        $rulesJson = [
            //'address'   => 'required|string|min:1',
            'bookDate'  => 'required|string|min:1',
            'bookTime'  => 'required|string|min:1',
            'totalAmount'  => 'required|min:1',
            'message'      => 'required|string|min:1',
            'paymentMethod'   => 'required|min:1',

        ];
        if( !json_decode( $this->validated['params'],true ) ){
            return self::jsondckc(BaseModel::formatErrorDckc(10032, 'json format error'));
        }
        if( $data = $this->vaidJsonOrderParsmrs( json_decode( $this->validated['params'],true ),$rulesJson,true ) ){
            return $data;
        }
        if( !isset( $this->datavalid['goodsList'] )  ){
            return self::jsondckc(BaseModel::formatErrorDckc(10033, 'goodsList is not exists'));
        }
        $goodlistvalid = [
            'id'    => 'required|min:1',
            'count'    => 'required|min:1',
            'amount'    => 'required|min:1',
        ];
        if( $error = $this->vaidJsonOrderParsmrs( $this->datavalid['goodsList'],$goodlistvalid ) ){
            return $error;
        }
        $this->datavalid['user_id'] = $userinfo->id;
        $this->datavalid['open_id'] = $this->validated['open_id'];
        $orderInfo = OrderGoods::checkout( $this->datavalid );
        return $this->jsondckc($orderInfo);

    }
    public function orderlist(){
        $rules = [
            'access_token'  => 'required|string|min:1',
            'open_id'       => 'required|string|min:1',
        ];
        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }
        $userinfo = Member::authDckc( $this->validated );

        if( !$userinfo ){
            return self::jsondckc(BaseModel::formatErrorDckc(10031, 'user error'));
        }
        $orderIinfo = Order::getListDckc( ['uid'=>$userinfo->id] );
        return $this->jsondckc( $orderIinfo );

    }
    /**
     * POST /order/notify/:code
     */
    public function notify($code)
    {
        Payment::notify($code);
    }

}