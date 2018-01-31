<?php
/**
 * Created by PhpStorm.
 * User: mio
 * Date: 2018/1/31
 * Time: 16:52
 */

namespace App\Helper;


class ProgramLong{

    private static $EARTH_RADIUS = 6371.0;//km 地球半径 平均值，千米



    public static function test(){

        echo self::Distance( 39.94607, 116.32793, 31.24063, 121.42575 );

    }

    public static function HaverSin( $theta ){

        $v =  floatval( sin( $theta / 2  ) );

        return $v * $v;

    }


    /// <summary>
    /// 给定的经度1，纬度1；经度2，纬度2. 计算2个经纬度之间的距离。
    /// </summary>
    /// <param name="lat1">经度1</param>
    /// <param name="lon1">纬度1</param>
    /// <param name="lat2">经度2</param>
    /// <param name="lon2">纬度2</param>
    /// <returns>距离（公里、千米）</returns>
    public static function Distance( $lat1,$lot1,$lat2,$lot2 ){

        //用haversine公式计算球面两点间的距离。
        //经纬度转换成弧度
        $lat1 = self::ConvertDegreesToRadians( floatval( $lat1 ) );
        $lot1 = self::ConvertDegreesToRadians( floatval( $lot1 ) );
        $lat2 = self::ConvertDegreesToRadians( floatval( $lat2 ) );
        $lot2 = self::ConvertDegreesToRadians( floatval( $lot2 ) );

        $vLon = floatval( abs( $lot1 - $lot2 ) );
        $vLat = floatval( abs( $lat1 - $lat2 ) );

        //h is the great circle distance in radians, great circle就是一个球体上的切面，它的圆心即是球心的一个周长最大的圆。
        $h = floatval( self::HaverSin( $vLat ) + cos( $lat1 ) * cos( $lat2 ) * self::HaverSin( $vLon ) );

        $distance = floatval( 2 * self::$EARTH_RADIUS * asin( sqrt( $h ) ) );

        return $distance;
    }


    // / <summary>
    // / 将角度换算为弧度。
    // / </summary>
    // / <param name="degrees">角度</param>
    // / <returns>弧度</returns>
    public static function ConvertDegreesToRadians ( $degrees ){
        return floatval( floatval($degrees) * M_PI / 180 );
    }


}