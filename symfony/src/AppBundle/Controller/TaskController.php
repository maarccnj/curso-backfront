<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;
use BackendBundle\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TaskController extends Controller
{
    public function newAction(Request $request, $id = null)
    {
        $helpers = $this->get('helper_service');
        $jwt = $this->get('jwt_auth');

        $token = $request->get('authorization', null);
        $authCheck = $jwt->checkToken($token);

        if($authCheck){
            $identity = $jwt->checkToken($token, true);
            $json = $request->get('json', null);
            if($json){
                //Crear tarea
                $params = json_decode($json);
                $user_id = ($identity->sub != null) ? $identity->sub : null;
                $em = $this->getDoctrine()->getEntityManager();
                $user = $em->getRepository('BackendBundle:User')->find($user_id);
                if($user && $params){
                    if($id == null){
                        $task = new Task();
                        $task->setUser($user);
                        $task->setCreatedAt( new \DateTime('now'));
                    }else{
                        $task = $em->getRepository('BackendBundle:Task')->find($id);
                        if($identity->sub != $task->getUser()->getId()){
                            return $helpers->createDataResponse("Not have permisions to edit this task", Helpers::STATUS_ERROR);
                        }
                    }

                    $task->setDescription((isset($params->description)) ? $params->description: null);
                    $task->setStatus((isset($params->status)) ? $params->status: null);
                    $task->setTitle((isset($params->title)) ? $params->title: null);
                    $task->setUpdatedAt(new \DateTime('now'));
                    $em->persist($task);
                    $em->flush();
                    $data = $helpers->createDataResponse($task, Helpers::STATUS_SUCCESS);
                }else{
                    $data = $helpers->createDataResponse("Task not created not user or params failed", Helpers::STATUS_ERROR);
                }

            }else{
                $data = $helpers->createDataResponse("Task not created, params failed", Helpers::STATUS_ERROR);
            }

        }else{
            $data = $helpers->createDataResponse("Authorization not valid", Helpers::STATUS_ERROR);
        }

        return $helpers->json($data);
    }

    public function listAction(Request $request){
        $helpers = $this->get('helper_service');
        $jwt = $this->get('jwt_auth');

        $token = $request->get('authorization', null);
        $authCheck = $jwt->checkToken($token);

        if($authCheck) {
            $identity = $jwt->checkToken($token, true);

            if($identity){
                $em = $this->getDoctrine()->getEntityManager();

                $dql = 'SELECT t FROM BackendBundle:Task t WHERE t.user = :user ORDER BY t.id DESC';
                $query = $em->createQuery($dql)
                    ->setParameter("user", $identity->sub);

                $page = $request->query->getInt('page', 1);
                $paginator = $this->get('knp_paginator');
                $items_per_page = 10;
                $pagination = $paginator->paginate($query, $page, $items_per_page);
                $total_items_count = $pagination->getTotalItemCount();

                $params = array(
                    'total_items_count' => $total_items_count,
                    'page_actual' => $page,
                    'items_per_page' => $items_per_page,
                    'total_pages' => ceil($total_items_count / $items_per_page),
                    'tareas' => $pagination
                );


                $data = $helpers->createDataResponse($params, Helpers::STATUS_SUCCESS);
            }else{
                $data = $helpers->createDataResponse("Identity fail", Helpers::STATUS_ERROR);
            }
        }else{
            $data = $helpers->createDataResponse("Authorization not valid", Helpers::STATUS_ERROR);
        }

        return $helpers->json($data);
    }

    public function detailAction(Request $request, $id = null){
        $helpers = $this->get('helper_service');
        $jwt = $this->get('jwt_auth');

        $token = $request->get('authorization', null);
        $authCheck = $jwt->checkToken($token);

        if($authCheck) {
            $identity = $jwt->checkToken($token, true);
            $em = $this->getDoctrine()->getEntityManager();
            $task = $em->getRepository('BackendBundle:Task')->find($id);

            if($task && $identity->sub == $task->getUser()->getId()){
                $data = $helpers->createDataResponse($task, Helpers::STATUS_SUCCESS);
            }else{
                $data = $helpers->createDataResponse("Permission denied to view this task", Helpers::STATUS_ERROR);
            }
        }else{
            $data = $helpers->createDataResponse("Authorization not valid", Helpers::STATUS_ERROR);
        }

        return $helpers->json($data);
    }

    public function searchAction(Request $request, $search = null){
        $helpers = $this->get('helper_service');
        $jwt = $this->get('jwt_auth');

        $token = $request->get('authorization', null);
        $authCheck = $jwt->checkToken($token);

        if($authCheck) {
            $identity = $jwt->checkToken($token, true);
            $em = $this->getDoctrine()->getEntityManager();
            //Filtro
            $filter = $request->get('filter', null);
            if($filter == 1){
                $filter = "new";
            }elseif($filter == 2){
                $filter = "todo";
            }elseif($filter == 3){
                $filter = "finished";
            }
            $order = $request->get('order', null);
            if(empty($order) || $order == 2){
                $order = "DESC";
            }else{
                $order = "ASC";
            }
            //busqueda
            if($search){
                $dql =  "SELECT t FROM BackendBundle:Task t ".
                        "WHERE t.user = $identity->sub AND ".
                        "(t.title LIKE :search OR t.description LIKE :search) ";
            }else{
                $dql =  "SELECT t FROM BackendBundle:Task t ".
                    "WHERE t.user = $identity->sub ";
            }
            if($filter){
                $dql .= "AND t.status = :filter";
            }
            $dql .= " ORDER BY t.id $order";

            $query = $em->createQuery($dql);

            if($filter){
                $query->setParameter('filter', $filter);
            }
            if($search){
                $query->setParameter('search', "%$search%");
            }

            $tasks = $query->getResult();
            $data = $helpers->createDataResponse($tasks, Helpers::STATUS_SUCCESS);
        }else{
            $data = $helpers->createDataResponse("Authorization not valid", Helpers::STATUS_ERROR);
        }

        return $helpers->json($data);
    }

    public function removeAction(Request $request, $id = null){
        $helpers = $this->get('helper_service');
        $jwt = $this->get('jwt_auth');

        $token = $request->get('authorization', null);
        $authCheck = $jwt->checkToken($token);

        if($authCheck) {
            $identity = $jwt->checkToken($token, true);
            $em = $this->getDoctrine()->getEntityManager();
            $task = $em->getRepository('BackendBundle:Task')->find($id);

            if($task && $identity->sub == $task->getUser()->getId()){
                $em->remove($task);
                $em->flush();
                $data = $helpers->createDataResponse($task, Helpers::STATUS_SUCCESS);
            }else{
                $data = $helpers->createDataResponse("Permission denied to delete this task or not found this task", Helpers::STATUS_ERROR);
            }
        }else{
            $data = $helpers->createDataResponse("Authorization not valid", Helpers::STATUS_ERROR);
        }

        return $helpers->json($data);
    }

}
