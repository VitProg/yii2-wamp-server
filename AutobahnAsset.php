<?php

/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 14.09.2015
 * Time: 19:01
 */

namespace vitprog\wamp;


use yii\web\AssetBundle;

class AutobahnAsset extends AssetBundle {

    public $sourcePath = '@bower/autobahn';

    public $js = [
        'autobahn.js',
    ];

}