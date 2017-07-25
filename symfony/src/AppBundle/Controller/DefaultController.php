<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class DefaultController extends Controller
{

    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    public function loginAction(Request $request){

        $helpers = $this->get('helper_service');
        $jwt = $this->get('jwt_auth');

        // Recibir json por POST
        $json = $request->get('json', null);

        if($json){
            // hacer login
            $params = json_decode($json);

            $email = (isset($params->email)) ? $params->email : null;
            $password = (isset($params->password)) ? $params->password : null;
            $getHash = (isset($params->getHash)) ? $params->getHash : null;

            if($email && $password){
                $pwd = hash('sha256', $password);
                if(count($this->get('validator')->validate($email, new Assert\Email())) == 0){
                    if($token = $jwt->signup($email, $pwd, $getHash)){
                        $data = $helpers->createDataResponse($token, Helpers::STATUS_SUCCESS);
                    }else{
                        $data = $helpers->createDataResponse( "El email o la contraseña no son correctos!!", Helpers::STATUS_ERROR);
                    }
                }else{
                    $data = $helpers->createDataResponse( "El email: ".$email." no es un correo valido!!", Helpers::STATUS_ERROR);
                }
            }

        }else{
            $data = $helpers->createDataResponse( 'ERROR, Send json via post!!', Helpers::STATUS_ERROR);
        }


        return $helpers->json($data);
    }

    public function authorizationAction(Request $request){
        $token = $request->get('authorization', null);
        $helpers = $this->get('helper_service');
        $jwt = $this->get('jwt_auth');

        if($token && $jwt->checkToken($token)){
            $em = $this->getDoctrine()->getManager();
            $userRepo = $em->getRepository('BackendBundle:User');
            $users = $userRepo->findAll();
            $data = $helpers->createDataResponse($users, Helpers::STATUS_SUCCESS);
        }else{
            $data = $helpers->createDataResponse("Authotization not valid", Helpers::STATUS_ERROR);
        }

        return $helpers->json($data);
    }
}
