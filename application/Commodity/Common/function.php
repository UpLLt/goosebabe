<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/10/18
 * Time: 9:38
 */

function Descartes()
{
    $t = func_get_args();

    if (func_num_args() == 1) {
        $t0 = $t[0];
        return call_user_func_array(__FUNCTION__, $t0);
    }

    $a = array_shift($t);
    if (!is_array($a)) $a = array($a);
    $a = array_chunk($a, 1);
    do {
        $r = array();
        $b = array_shift($t);
        if (!is_array($b)) $b = array($b);
        foreach ($a as $p)
            foreach (array_chunk($b, 1) as $q)
                $r[] = array_merge($p, $q);
        $a = $r;
    } while ($t);
    return $r;
}