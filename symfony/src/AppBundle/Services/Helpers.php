<?php

namespace AppBundle\Services;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

class Helpers
{
    CONST STATUS_ERROR = 0;
    CONST STATUS_SUCCESS = 1;

    public function json($data){
        $normalizers = array(new GetSetMethodNormalizer());
        $encoders = array("json" => new JsonEncoder());

        $serializer = new Serializer($normalizers, $encoders);
        $json = $serializer->serialize($data, 'json');

        $response = new Response();
        $response->setContent($json);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function createDataResponse($data, $status){
        $dataResponse = array(
            'status' => $status,
            'data' => $data
        );

        return $dataResponse;
    }
}