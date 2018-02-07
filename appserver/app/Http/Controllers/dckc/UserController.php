<?php
//

namespace App\Http\Controllers\dckc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Helper\Token;
use App\Models\dckc\Member;
use App\Models\dckc\UserAddress;
use App\Models\BaseModel;
use App\Models\dckc\RegFields;
use App\Models\v2\Configs;
use App\Models\dckc\Features;
use Log;

class UserController extends Controller
{
    public $datavalid;
    /**
     * POST /user/signin
     */
    public function signin()
    {
        $rules = [
            'username' => 'required|string',
            'password' => 'required|min:6|max:20'
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::login($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/signup-email
     */
    public function signupByEmail()
    {
        $rules = [
            'device_id'     => 'string',
            'username'      => 'required|min:3|max:25|alpha_num',
            'email'         => 'required|email',
            'password'      => 'required|min:6|max:20',
            'invite_code'   => 'integer', 
        ];

        if($res = Features::check('signup.default'))
        {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::createMember($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/signup-mobile
     */
    public function signupByMobile()
    {
        if($res = Features::check('signup.mobile'))
        {
            return $this->json($res);
        }

        $rules = [
            'device_id'     => 'string',
            'password'      => 'required|min:6|max:20',
            'mobile'        => 'required|string',
            'code'          => 'required|string|digits:6',
            'invite_code'   => 'integer', 
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::createMemberByMobile($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/verify-mobile
     */
    public function verifyMobile()
    {
        $rules = [
            'mobile' => 'required|string',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::verifyMobile($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/send-code
     */
    public function sendCode()
    {
        $rules = [
            'mobile' => 'required|string',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::sendCode($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/profile
     */
    public function profile()
    {
        $data = Member::getMemberByToken();
        return $this->json($data);
    }

    /**
     * POST /user/update-profile
     */
    public function updateProfile()
    {
        $rules = [
            'values'        => 'json',
            'nickname'      => 'string|max:25',
            'gender'        => 'integer|in:0,1,2',
            'avatar_url'    => 'string'
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::updateMember($this->validated);
        return $this->json($data);
    }

    public function updateProfileDckc()
    {
//        $rules = [
//            'access_token'  => 'required|string|min:1',
//            'open_id'       => 'required|string|min:1',
//            'param'         => 'required|json',
//        ];
//
//        if ($error = $this->validateInputDckc($rules)) {
//            return $error;
//        }

        $userinfo = Member::authDckc( array() );

        if( !$userinfo ){
            return self::jsondckc(BaseModel::formatErrorDckc(10031, 'user error'));
        }


        $rulesJson = [
            'name'          => 'required|string|min:1',
            'sex'           => 'required|min:1',
            'phone'         => 'required|min:1',
            'address'       => 'required|string|min:1',
            'poiName'       => 'required|string|min:1',
            'otherPoiInfo'  => 'required|string|min:1',

        ];
        if( !json_decode( $this->validated['param'],true ) ){
            return self::jsondckc(BaseModel::formatErrorDckc(10032, 'json format error'));
        }
        if( $err = $this->vaidJsonOrderParsmrs( json_decode( $this->validated['param'],true ),$rulesJson,true ) ){
            return $err;
        }
        unset( $this->validated['param'] );

        $toduArr = array_merge( $this->validated,$this->datavalid,['uid'=>$userinfo->id] );


        $data = Member::updateMember( $toduArr );
        return $this->jsondckc($data);
    }
    public function authDckc(){

        $rules = [
            'redriect' => 'required|string|min:1'
        ];

        if ($error = $this->validateInputDckc($rules)) {
            return $error;
        }
        if( !$uid = Token::authorizationDckc() ){


            $url = Member::authDckcLogin( $this->validated );
            $data['redriect'] = $url;

            return $this->jsondckc( BaseModel::formatBodyDckc(['data'=>$data]) );

        }




    }
    public function ProfileDckc(){
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
        $consignee_info = UserAddress::get_consignee_dckc( $userinfo->id );
        $result = array();
        if( !$consignee_info ){
            return self::jsondckc(BaseModel::formatErrorDckc(10038, 'user not found'));
        }
        $result['id'] = $userinfo->id;
        $result['name'] = $consignee_info->consignee;
        $result['phone'] = $consignee_info->mobile;
        $result['address'] = $consignee_info->address;
        $result['otherPoiInfo'] = $consignee_info->sign_building;
        $result['poiName'] = $consignee_info->address_name;
        return $this->jsondckc( BaseModel::formatBodyDckc(['data'=>$result]) );
    }

    /**
     * POST /user/update-password
     */
    public function updatePassword()
    {
        $rules = [
            'old_password' => 'required|min:6|max:20',
            'password'     => 'required|min:6|max:20'
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::updatePassword($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/reset-password-mobile
     */
    public function resetPasswordByMobile()
    {
        $rules = [
            'mobile'   => 'required|string',
            'code'     => 'required|string|digits:6',
            'password' => 'required|min:6|max:20'
        ];

        if($res = Features::check('findpass.default'))
        {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::updatePasswordByMobile($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/reset-password-email
     */
    public function resetPasswordByEmail()
    {
        $rules = [
            'email' => 'required|email'
        ];

        if($res = Features::check('findpass.default'))
        {
            return $this->json($res);
        }

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::resetPassword($this->validated);
        return $this->json($data);
    }

    /**
     * POST /user/auth
     */
    public function auth()
    {
        $rules = [
            'device_id'     => 'string',
            'vendor'        => 'required|integer|in:1,2,3,4,5',
            'access_token'  => 'string',
            'js_code'       => 'string',
            'open_id'       => 'string',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::auth($this->validated);
        return $this->json($data);
    }

    /**
     * POST /ecapi.user.profile.fields
     */
    public function fields()
    {
        $data = RegFields::findAll();
        return $this->json($data);
    }


    /**
     * GET /user/web
     */
    public function webOauth()
    {
        $rules = [
                    'vendor'        => 'required|integer|in:1,2,3,4',
                    'referer'       => 'required|url',
                 ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::webOauth($this->validated);
        if (isset($data['error'])) {
            return $this->json($data);
        }
        return redirect($data);
    }
    /**
     * GET /ecapi.auth.web.callback/:vendor
     */
     public function webCallback($vendor)
     {
         $data = Member::webOauthCallback($vendor);
          if (isset($data['error'])) {
              return $this->json($data);
          }
          if (isset($_GET['referer'])) {
              //return redirect(urldecode($_GET['referer']).'?token='.$data['token'].'&openid='.$data['openid']);
              return redirect(urldecode($_GET['referer']) );
          }
          return $this->json(['token' => $data]);
     }

}
