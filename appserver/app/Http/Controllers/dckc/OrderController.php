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
use Validator;
use App\Models\BaseModel;



class OrderController extends Controller
{

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

        $rulesJson = [
            'address'   => 'required|string|min:1',
            'dishTime'  => 'required|string|min:1',
            'totalFee'  => 'required|min:1',
            'note'      => 'required|string|min:1',
            'payType'   => 'required|string|min:1',

        ];
        if( $data = $this->vaidJsonOrderParsmrs( json_decode( $this->validated['params'],true ),$rulesJson ) ){
            return $data;
        }
        if( !isset( $data['goodsList'] )  ){
            return self::jsondckc(BaseModel::formatErrorDckc(10033, 'goodsList is not exists'));
        }
        $goodlistvalid = [
            'id'    => 'required|min:1',
            'amount'    => 'required|min:1',
            'fee'    => 'required|min:1',
        ];
        if( $error = $this->vaidJsonOrderParsmrs( $data['goodsList'],$goodlistvalid ) ){
            return $error;
        }

        print_r( $data );exit;




    }


    /**
     * 验证json数据
     * @param  array $rules
     * @return response
     */
    public function vaidJsonOrderParsmrs($decodeJson,$rules)
    {
        $validator = Validator::make($decodeJson, $rules);
        if ($validator->fails()) {
            return self::jsondckc(BaseModel::formatErrorDckc(BaseModel::BAD_REQUEST, $validator->messages()->first()));
        } else {
            $res = array_intersect_key($decodeJson, $rules);
            $res = $decodeJson ;
            return $res;
        }
    }



}