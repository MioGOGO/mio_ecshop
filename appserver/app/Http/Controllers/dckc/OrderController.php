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
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrderController extends Controller
{

    public $datavalid;

    public function add(){
        $rules = [
            'params'        => 'required|min:1',
        ];

        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }

        $rulesJson = [
            //'address'   => 'required|string|min:1',
            'bookDate'  => 'required|string|min:1',
            'bookTime'  => 'required|string|min:1',
            'totalAmount'  => 'required|min:1',
            'message'      => 'string|min:1',
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
//        $this->datavalid['user_id'] = $userinfo->id;
//        $this->datavalid['open_id'] = $this->validated['open_id'];
        $orderInfo = OrderGoods::checkout( $this->datavalid );
        return $this->jsondckc($orderInfo);

    }
    public function orderlist(){
        $orderIinfo = Order::getListDckc( array() );
        return $this->jsondckc( $orderIinfo );

    }
    public function orderdetail(){
        $rules = [
            'id'        => 'required|string|min:1',
        ];
        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }
        $orderInfo = Order::getDetailDckc( $this->validated );

        return $this->jsondckc( $orderInfo );


    }
    public function orderrepay(){
        $rules = [
            'id'        => 'required|string|min:1',
        ];
        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }
        $orderInfo = OrderGoods::repayorder( $this->validated );
        return $this->jsondckc( $orderInfo );
    }
    //确认发货的状态，seller 确认订单！
    public function sellerDelivery(){
        $rules = [
            'id'        => 'required|string|min:1',
        ];
        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }
        $data = Order::sellerDelivery( $this->validated );
        return $this->jsondckc( $data );
    }
    //发货端获取订单信息
    public function sellerGetOrderInfo(){
        $rules = [
            'id'        => 'required|string|min:1',
        ];
        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }
        $orderInfo = Order::getDetailSeller( $this->validated );
        return $this->jsondckc( $orderInfo );

    }
    //生成订单二维码
    public function createQr(){
        $rules = [
            'id'        => 'required|string|min:1',
            'size'      => 'integer|min:1'
        ];
        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }
        $info = $this->validated['id'];
        $size = isset( $this->validated['size'] ) ? $this->validated['size'] : 350;
        $info = urlencode( $info );
        if(!file_exists(base_path('public/img/qrcodes/'.$info.'.png'))){
            QrCode::format('png')->size($size)->merge('/public/img/logo.gif',.15)->margin(0)->generate($info,base_path('public/img/qrcodes/'.$info.'.png'));
        }
        $img = '/img/qrcodes/'.$info.'.png';
        //$a = QrCode::generate('Hello,LaravelAcademy!');
        //echo "<img src='$a>";

        return view('qcview',  ['img'=>$img]  );

    }
    /**
     * POST /order/notify/:code
     */
    public function notify($code)
    {
        Payment::notify($code);
    }

}