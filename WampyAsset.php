<?php

/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 14.09.2015
 * Time: 19:01
 */

namespace vitprog\wamp;


use yii\web\AssetBundle;

class WampyAsset extends AssetBundle {

    public $sourcePath = '@bower/wampy.js';

    public $js = [
        'src/wampy.js',
    ];

}