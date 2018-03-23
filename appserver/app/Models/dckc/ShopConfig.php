<?php

namespace App\Models\dckc;
use App\Helper\ProgramLong;
use App\Models\BaseModel;

use App\Helper\Header;

class ShopConfig extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'shop_config';
    public    $timestamps = true;

    public static function findByCode($code)
    {
        return self::where(['code' => $code])->value('value');
    }

    public static function getSiteInfo()
    {
        return[
            'site_info'=> [
                'name' => self::findByCode('shop_name'),
                'desc' => self::findByCode('shop_desc'),
                'logo' => formatPhoto(self::findByCode('shop_logo')),
                'opening' => (bool)!self::findByCode('shop_closed'),
                'telephone' => self::findByCode('service_phone'),
                'terms_url' => env('TERMS_URL'),
                'about_url' => env('ABOUT_URL'),
            ]
        ];
    }

    private static function getConfigure($configure)
    {
        $data = [];
        $configure = unserialize($configure);
        foreach ($configure as $key => $val) {
            $data[$val['name']] = $val['value'];
        }

        return $data;
    }
    public static function checkRange( array  $attributes ){
        extract($attributes);
//        if( json_decode( $params,true ) ){
//            $paramsArray = json_decode( $params,true );
//        }else{
//            return self::formatErrorDckc(40001,'json format error');
//        }
        if( !isset( $lng ) && !isset( $lat ) ){
            return self::formatErrorDckc(40002,'lng or lat is null');
        }
        $res = ['data'=>['inRange'=>0]];
        $sconf = self::findByCode( 'close_comment' );
        $sconfArray = json_decode( $sconf,true );
        if( !empty( $sconfArray ) ){
            foreach ( $sconfArray as $k => $v ){
                $h = ProgramLong::Distance( $v['lat'],$v['lng'],$lat,$lng );
                if( $h <= 5 ){
                    $res = ['data'=>['inRange'=>1]];
                }
            }
        }
        return self::formatBodyDckc( $res );
    }

    public static function getRangeList(){
//        if( json_decode( $params,true ) ){
//            $paramsArray = json_decode( $params,true );
//        }else{
//            return self::formatErrorDckc(40001,'json format error');
//        }
        $sconf = self::findByCode( 'close_comment' );
        $sconfArray = json_decode( $sconf,true );
        $res = ['data'=>['RangeList'=>$sconfArray]];
        return self::formatBodyDckc( $res );
    }
}
