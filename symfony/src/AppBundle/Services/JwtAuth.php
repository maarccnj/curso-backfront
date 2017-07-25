<?php

namespace AppBundle\Services;


use Doctrine\ORM\EntityManager;
use Firebase\JWT\JWT;
use Symfony\Component\VarDumper\Tests\Fixture\DumbFoo;

class JwtAuth
{

    /** @var EntityManager $entityManger */
    protected $entityManger;

    private $key="qwertyuiop123456987";

    /**
     * JwtAuth constructor.
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManger = $entityManager;
    }

    public function signup($email, $password, $getHash)
    {
        $user = $this->getEntityManger()->getRepository('BackendBundle:User')->findOneBy(array('email' => $email, 'password' => $password ));

        if ($user){
            $token = array(
                "sub" => $user->getId(),
                "email" => $user->getEmail(),
                "name" => $user->getName(),
                "surname" => $user->getSurname(),
                "iat" => time(),
                "exp" => time() + (7 *24 * 60 * 60)
            );
            $jwt = JWT::encode($token, $this->key, 'HS256');

            if($getHash){
//                $hash = JWT::decode($jwt, $this->key, array('HS256'));
                return $token;
            }else{
                return $jwt;
            }

        }

        return false;
    }

    public function checkToken($jwt, $getIdentity = false){

        try{
            $decoded = JWT::decode($jwt, $this->key, array('HS256'));

            if($decoded && is_object($decoded) && isset($decoded->sub)){
                if($getIdentity){
                    return $decoded;
                }else{
                    return true;
                }
            }
        }catch (\UnexpectedValueException $e){
            return false;
        }catch (\DomainException $domainException){
            return false;
        }

    }

    /**
     * @return EntityManager
     */
    public function getEntityManger()
    {
        return $this->entityManger;
    }
}