<?php

namespace humhub\modules\smartVillage\components;

use Exception;
use Firebase\JWT\JWT;
use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\content\models\Content;
use humhub\modules\rest\controllers\auth\AuthController;
use humhub\modules\rest\models\ConfigureForm;
use humhub\modules\rest\Module;
use humhub\modules\user\models\User;
use Yii;
use yii\data\Pagination;
use yii\db\ActiveQuery;
use yii\web\HttpException;
use yii\web\JsonParser;

abstract class AuthBaseController extends Controller
{

    public static $moduleId = '';

    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @inerhitdoc
     * Do not enforce authentication.
     */
    public $access = ControllerAccess::class;

    /**
     * @inheritdoc
     */
    protected $doNotInterceptActionIds = ['*'];

    /**
     * @inheritdoc
     */

    public function beforeAction($action){
        Yii::$app->response->format = 'json';

        Yii::$app->request->setBodyParams(null);
        Yii::$app->request->parsers['application/json'] = JsonParser::class;

        return parent::beforeAction($action);
    }


    /**
     * Checks if users is allowed to use the Rest API
     *
     * @param User $user
     * @return bool
     */
    public function isUserEnabled(User $user){
        $config = new ConfigureForm();
        $config->loadSettings();

        if (!empty($config->enabledForAllUsers)) {
            return true;
        }

        if (in_array($user->guid, (array)$config->enabledUsers)) {
            return true;
        }

        return false;
    }

    /**
     * Generates error response
     *
     * @param int $statusCode
     * @param string $message
     * @param array $additional
     * @return array
     */
    protected function returnError($statusCode = 400, $message = 'Invalid request', $additional = []){
        Yii::$app->response->statusCode = $statusCode;
        return array_merge(['code' => $statusCode, 'message' => $message], $additional);
    }


    /**
     * Generates success response
     *
     * @param string $message
     * @param int $statusCode
     * @param array $additional
     * @return array
     */
    protected function returnSuccess($message = 'Request successful', $statusCode = 200, $additional = []){
        Yii::$app->response->statusCode = $statusCode;
        return array_merge(['code' => $statusCode, 'message' => $message], $additional);
    }

    /**
     * Handles pagination
     *
     * @param ActiveQuery $query
     * @param int $limit
     * @return Pagination the pagination
     */
    protected function handlePagination(ActiveQuery $query, $limit = 100){
       $limit = (int)Yii::$app->request->get('limit' , $limit);
       $page = (int)Yii::$app->request->get('page' , 1);

       if($limit > 100){
           $limit = 100;
       }
       $page--;

       $countQuery = clone $query;
       $pagination = new Pagination(['totalCount' => $countQuery->count()]);
       $pagination->setPage($page);
       $pagination->setPageSize($limit);

       $query->offset($pagination->offset);
       $query->limit($pagination->limit);

       return $pagination;
    }

    /**
     * Generates pagination response
     *
     * @param ActiveQuery $query
     * @param Pagination $pagination
     * @param $data array
     * @return array
     */
    protected function returnPagination(ActiveQuery $query, Pagination $pagination, $data){

        return[
            'total' => $pagination->totalCount,
            'page' => $pagination->getPage() + 1,
            'pages' => $pagination->getPageCount(),
            'links' => $pagination->getLinks(),
            'results' => $data
        ];
    }

}