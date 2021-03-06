<?php

namespace app\modules\api1\models;

use yii\base\Object;

class ConsoleRunner extends Object
{
    private $_yiiPath;

    public function __construct()
    {
        $this->_yiiPath = \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'yii';
    }

    public function run( $command, $arguments )
    {
        $cmd = strtr( 'php {yii} {command} {arguments}', [
            '{yii}' => $this->_yiiPath,
            '{command}' => $command,
            '{arguments}' => implode(' ', $arguments)
        ]);
        if ( $this->isWindows() )
        {
            pclose( popen( 'start /b ' . $cmd, 'r' ) );
        }
        else
        {
            pclose( popen( $cmd . ' > /dev/null &', 'r' ) );
        }
        return true;
    }

    protected function isWindows()
    {
        return ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' );
    }
}