<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;
use BackendBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;

class UserController extends Controller
{
    public function newAction(Request $request)
    {
        $helpers = $this->get('helper_service');
        $json = $request->get('json', null);

        $params = json_decode($json);


        if($json){
            $email = (isset($params->email)) ? $params->email : null;
            $name = (isset($params->name)) ? $params->name : null;
            $surname = (isset($params->surname)) ? $params->surname : null;
            $password = (isset($params->password)) ? $params->password : null;
            $emailConstraint = new Email();
            $validate_email = $this->get('validator')->validate($email, $emailConstraint);

            if($email && count($validate_email) == 0 && $password && $name && $surname){
                $user = new User();
                $user->setCreatedAt(new \DateTime('now'));
                $user->setEmail($email);
                $user->setName($name);
                $user->setRole("user");
                $user->setSurname($surname);
                // Cifrar pasdword
                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);

                $em = $this->getDoctrine()->getManager();
                $issetUser = $em->getRepository('BackendBundle:User')->findBy(array("email" => $email));
                if(!$issetUser){
                    $em->persist($user);
                    $em->flush();
                    $data = $helpers->createDataResponse($user, Helpers::STATUS_SUCCESS);
                }else{
                    $data = $helpers->createDataResponse("User not created, duplicated!!", Helpers::STATUS_ERROR);
                }
            }else{
                $data = $helpers->createDataResponse("User not created, params fail", Helpers::STATUS_ERROR);
            }
        }else{
            $data = $helpers->createDataResponse("User not created, not have request", Helpers::STATUS_ERROR);
        }

        return $helpers->json($data);
    }

    public function editAction(Request $request){
        $helpers = $this->get('helper_service');
        $jwtAuth = $this->get('jwt_auth');

        $token = $request->get('authorization', null);
        $json = $request->get('json', null);
        $params = json_decode($json);

        if($jwtAuth->checkToken($token)){
            $em = $this->getDoctrine()->getManager();
            // A partir del token devuelve el usuario
            $identity = $jwtAuth->checkToken($token, true);
            $user = $em->getRepository('BackendBundle:User')->find($identity->sub);
            if($user && $params){
                $user->setName((isset($params->name)) ? $params->name : null);
                $user->setSurname((isset($params->surname)) ? $params->surname : null);
                // Cifrar pasdword
                if(isset($params->password) && $params->password != null){
                    $password = (isset($params->password)) ? hash('sha256', $params->password ): null;
                    $user->setPassword($password);
                }

                $em->persist($user);
                $em->flush();
                $data = $helpers->createDataResponse($user, Helpers::STATUS_SUCCESS);
            }else{
                $data = $helpers->createDataResponse("User not updated, params fail", Helpers::STATUS_ERROR);
            }

        }else{
            $data = $helpers->createDataResponse("Authorization not valid", Helpers::STATUS_ERROR);
        }

        return $helpers->json($data);
    }
}
