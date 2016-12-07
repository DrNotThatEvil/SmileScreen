<?php
namespace SmileScreen\Session;

use SmileScreen\Database as Database;
use SmileScreen\Base\Singleton as Singleton;
use SmileScreen\Exceptions as Exceptions;

class SessionSystem extends Singleton 
{

    protected function __construct() 
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new Exceptions\SessionSmileScreenException('Session not started!');
        }
    }

    public function getLoggedInModel($model) 
    {
        if (!isset($_SESSION['model_id'])) {
            return false; 
        }

        if ($_SESSION['model_id'] < -1) {
            return false; 
        }

        if (!is_subclass_of($model, 'SmileScreen\Base\BaseModel')) {
            // again we need a model that extends BaseModel here
            throw new Exceptions\SessionSmileScreenException(
                'Model needs to extend BaseModel. Not be a instance of BaseModel.'
            );
        }

        if (!$model->getCanBeLoggedIn()) {
            throw new Exceptions\SessionSmileScreenException(
                'This model can not be loaded from the session. Set canBeLoggedIn.'
            );
        }

        $results = $model::simpleWhere(['id' => ['=', $_SESSION['model_id']]]);

        if (count($results) != 1) {
            return false; 
        }

        return $results[0];
    }

    public function setLoggedInModel($model) 
    {
        if (!is_subclass_of($model, 'SmileScreen\Base\BaseModel')) {
            // again we need a model that extends BaseModel here
            throw new Exceptions\SessionSmileScreenException(
                'Model needs to extend BaseModel. Not be a instance of BaseModel.'
            );
        }


        if (!$model->getCanBeLoggedIn()) {
            throw new Exceptions\SessionSmileScreenException(
                'This model can not be saved in the session. Set canBeLoggedIn.'
            );
        }

        if (isset($_SESSION['model_id'])) {
            unset($_SESSION['model_id']);
        }

        $_SESSION['model_id'] = $model->id;

        return true;
    }
}
