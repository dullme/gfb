<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ResponseController extends Controller
{
    protected $statusCode = 200;

    /**
     * @return int
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * @param $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * 404
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseNotFound($message = '网络异常，请重试！') {

        return $this->setStatusCode(404)->responseError($message);
    }

    public function responseError($message = '网络拥堵，请稍后重试！') {

        return $this->response([
            'message' => $message,
            'code' => $this->getStatusCode() == 200 ? 422 : $this->getStatusCode()
        ], 200);
    }

    public function responseSuccess($data, $message = 'success') {

        return $this->response([
            'data' => $data,
            'code' => $this->getStatusCode(),
            'message' => $message
        ]);
    }

    public function response($data) {

        return response()->json($data, 200);
    }

}
