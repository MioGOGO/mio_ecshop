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
use App\Models\dckc\OrderGoods;
use Validator;
use App\Models\BaseModel;



class OrderController extends Controller
{

    private $datavalid;

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

        print_r( $userinfo );exit;
        $rulesJson = [
            'address'   => 'required|string|min:1',
            'dishTime'  => 'required|string|min:1',
            'totalFee'  => 'required|min:1',
            'note'      => 'required|string|min:1',
            'payType'   => 'required|string|min:1',

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
            'amount'    => 'required|min:1',
            'fee'    => 'required|min:1',
        ];
        if( $error = $this->vaidJsonOrderParsmrs( $this->datavalid['goodsList'],$goodlistvalid ) ){
            return $error;
        }

        $info = OrderGoods::checkout(  );

        print_r( $this->datavalid );exit;






    }


    /**
     * 验证json数据
     * @param  array $rules
     * @return response
     */
    public function vaidJsonOrderParsmrs( array $decodeJson,$rules,$flag = false)
    {
        if( $flag ){
            $validator = Validator::make($decodeJson, $rules);
            if ($validator->fails()) {
                return self::jsondckc(BaseModel::formatErrorDckc(BaseModel::BAD_REQUEST, $validator->messages()->first()));
            } else {
                $this->datavalid = array_intersect_key($decodeJson, $rules);
                $this->datavalid = $decodeJson ;
                return false;
            }
        }else{
            if( count( $decodeJson )  !== count( $decodeJson,1 ) ){
                foreach ( $decodeJson as $val ){
                    $validator = Validator::make($val, $rules);
                    if( $validator->fails() ){
                        return self::jsondckc(BaseModel::formatErrorDckc(BaseModel::BAD_REQUEST, $validator->messages()->first()));
                    }
                }
            }

        }
    }



}