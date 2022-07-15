<?php

namespace DevDr\ApiCrudGenerator\Controllers;

use App\Users;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Prophecy\Exception\Exception;
use Psy\Util\Json;

class BaseApiController extends Controller {

    /** @var Users $user */
    public $user;

    protected function _getStatusCodeMessage($status) {
        // these could be stored in a .ini file and loaded
        // via parse_ini_file()... however, this will suffice
        // for an example
        $codes = Array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }

    protected function _sendResponse($body = [], $message = '', $http_status = 200) {
        $content_type = 'text/json';
        $status_header = 'HTTP/1.1 ' . $http_status . ' ' . $this->_getStatusCodeMessage($http_status);
        header($status_header);
        header('Content-type: ' . $content_type);

        if (!is_array($body)) {
            $body = array('message' => $body);
        }

        $body = Json::encode([
                    "success" => true,
                    'responseCode' => $http_status,
                    'message' => $message,
                    "data" => $body,
                    "timestamp" => time()
        ]);

        echo $body;
        die;
    }

    protected function _sendErrorResponse($status = 400, $customMessage = null, $errorCode = null, $contentType = 'text/json') {
        $message = $this->_getStatusCodeMessage($status);
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);

        if ($customMessage !== null) {
            $message = $customMessage;
        }

        header($status_header);
        header('Content-type: ' . $contentType);

        if ($errorCode === null) {
            $code = $status;
        } else {
            $message = $customMessage;
            $code = $errorCode;
        }

        $body = Json::encode(
                        array(
                            "success" => false,
                            "responseCode" => $code,
                            'message' => $message
                        )
        );

        echo $body;
        die;
    }

    protected function _checkAuth() {
        if (!isset($_SERVER['HTTP_AUTH_TOKEN'])) {
            // Error: Unauthorized
            $this->_sendErrorResponse(401);
        }

        try {
            $token = $_SERVER['HTTP_AUTH_TOKEN'];
            $this->user = Users::findIdentityByAccessToken($token);
            if ($this->user == null) {
                $this->_sendErrorResponse(401);
            }

            /* if ($this->user->status == User::STATUS_DELETED) {
              $this->_sendErrorResponse(423, "The user is banned.");
              } */

            return $this->user;
        } catch (Exception $e) {
            $this->_sendErrorResponse(400);
        }
        return false;
    }

    /**
     * Generates new token
     */
    protected function _generateToken() {
        return Str::random(64) . '_' . time();
    }

    protected function _getToken() {
        return $_SERVER['HTTP_AUTH_TOKEN'];
    }

}
