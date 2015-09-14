<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 10.09.15
 * Time: 15:11
 */

namespace vitprog\wamp;


use yii\web\AssetBundle;

class Asset extends AssetBundle {

    public $sourcePath = '@vitprog/wamp/assets';

    public $js = [
        'wamp.js'
    ];

    public $depends = [
        'yii\web\JqueryAsset'
    ];

}