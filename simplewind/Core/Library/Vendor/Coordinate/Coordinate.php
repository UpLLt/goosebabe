<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/11
 * Time: 10:36
 */
class Coordinate
{
    public function __construct()
    {

    }

    /**
     * 坐标集合
     * @param array $data 结构 array('id'=>1,'lng'=>2435345,'lat'=>354345);
     * @param $here 结构 array('lng'=>24234,'lat'=>23123123);
     * @param bool $asc
     */
    public function latitude_and_longitude(array $data, array $here, $asc = true)
    {
        foreach ($data as $k => $v) {
            $data[$k]['len'] = self::getDistance($v, $here);
        }
        unset($v);
        return self::arraySortByKey($data, 'len', $asc);
    }

    /**
     * 排序
     * @param array $array
     * @param $key
     * @param bool $asc
     * @return array
     */
    public function arraySortByKey(array $array, $key, $asc = true)
    {
        $result = array();
        // 整理出准备排序的数组
        foreach ($array as $k => &$v) {
            $values[$k] = isset($v[$key]) ? $v[$key] : '';
        }
        unset($v);
        // 对需要排序键值进行排序
        $asc ? asort($values) : arsort($values);
        // 重新排列原有数组
        foreach ($values as $k => $v) {
            $result[] = $array[$k];
        }

        return $result;
    }


    /**
     * 根据两点间的经纬度计算距离
     * @param $plana $plana = array('lat' => 0, 'lng' => '');
     * @param $planb $planb = array('lat' => 0, 'lng' => '');
     * @return float
     */
    public function getDistance($plana, $planb)
    {
        $earthRadius = 6367000; //approximate radius of earth in meters
        /*
        Convert these degrees to radians
        to work with the formula
        */

        $plana['lat'] = ($plana['lat'] * pi()) / 180;
        $plana['lng'] = ($plana['lng'] * pi()) / 180;

        $planb['lat'] = ($planb['lat'] * pi()) / 180;
        $planb['lng'] = ($planb['lng'] * pi()) / 180;

        /*
        Using the
        Haversine formula
        http://en.wikipedia.org/wiki/Haversine_formula
        calculate the distance
        */

        $calcLongitude = $planb['lng'] - $plana['lng'];
        $calcLatitude = $planb['lat'] - $plana['lat'];
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($plana['lat']) * cos($planb['lat']) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;

        return round($calculatedDistance);
    }
}