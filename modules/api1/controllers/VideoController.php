<?php

namespace app\modules\api1\controllers;

use app\models\Video;
use app\models\VideoStatus;
use app\modules\api1\models\ConsoleRunner;
use app\modules\api1\models\FFMpegConverter;
use app\modules\api1\models\Uploader;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

class VideoController extends BaseController
{
    public function actionView( $id )
    {
        $video = Video::findOne( $id );
        $this->checkAccess( $video );
        return $video;
    }

    public function actionUpload()
    {
        $file = UploadedFile::getInstanceByName( 'file' );
        $video = new Video();
        $video->userId = $this->user->id;
        $saveFilePath = $video->generateSaveFilePath( $file->name );
        $uploader = new Uploader();
        if ( !$uploader->save( $file, $saveFilePath ) )
        {
            throw new ServerErrorHttpException( $uploader->getFirstError() );
        }
        $converter = new FFMpegConverter();
        $info = $converter->getInfo( $saveFilePath );
        $video->setInfo( $info );
        $video->status = VideoStatus::NO_ACTION;
        if ( !$video->save() )
        {
            throw new ServerErrorHttpException( $video->getFirstError() );
        }
        (new ConsoleRunner())->run( 'worker/convert ' . $video->id );
        return $video;
    }

    public function actionDownload( $id )
    {
        $video = Video::findOne( $id );
        $this->checkAccess( $video );
        \Yii::$app->response->sendFile( $video->getVideoPath(), $video->name );
    }

    public function actionList()
    {
        $userId = \Yii::$app->user->identity->getId();
        return Video::find()->where( [ 'user_id' => $userId ] )->all();
    }

    public function actionDelete( $id )
    {
        $video = Video::findOne( $id );
        $this->checkAccess( $video );
        if ( !$video->delete() )
        {
            throw new ServerErrorHttpException( $video->getFirstError() );
        }
    }

    private function checkAccess( Video $video )
    {
        if ( ( $video === null ) || ( $video->userId != $this->user->id ) )
        {
            throw new ForbiddenHttpException( 'You are not allowed to perform this action.' );
        }
    }
}