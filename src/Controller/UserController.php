<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class UserController extends AbstractController
{
    /**
     * @Route("/user/{id}", methods={"GET","HEAD"})
     */
    public function getUserById(int $id, UserRepository $userRepository): Response
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $user = $userRepository->find($id);

        $json = $serializer->serialize($user,  'json');
        return $this->json($json);
    }

    /**
     * @Route("/user/add", methods={"POST","HEAD"})
     */
    public function add(int $id, UserRepository $userRepository): Response
    {

      //  return $this->respondWithSuccess(sprintf('Modification réussi'));

    }
}
