<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CivilityRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Routing\Annotation\Route;


class UserController extends ApiController
{
    /**
     * @Route("/register", methods={"POST"})
     * create new user
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder, CivilityRepository $civilityRepository): JsonResponse
    {
        /* init parameters */
        $em = $this->getDoctrine()->getManager();
        $request = $this->transformJsonBody($request);
        $civility = $civilityRepository->find($request->get('civility_id'));

        $user = new User();

        /* set data */
        $user->setPassword($encoder->encodePassword($user, $request->get('password')));
        $user->setEmail($request->get('email'));
        $user->setPseudo($request->get('pseudo'));
        $user->setLastname($request->get('lastname'));
        $user->setFirstname($request->get('firstname'));
        $user->setBirthAt(date_create($request->get('birthAt')));
        $user->setCivility($civility);

        /* sql action */
        $em->persist($user);

        try {
            $em->flush();
            return $this->respondCreated(sprintf('User successfully created'));
        } catch (\Doctrine\DBAL\Exception $e){
            return $this->respondValidationError("Echec de la création de votre compte");
        }
    }

    /**
     * @Route("/user/getUserByEmail", methods={"POST"})
     * get one user
     */
    public function getUserByEmail(Request $request, UserRepository $userRepository): Response
    {
        /* init parameters */
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        /* get data */
        $request = $this->transformJsonBody($request);
        $email = $request->get('email');

        /* send data */
        $user = $userRepository->getByEmail($email);
        $json = $serializer->serialize($user,  'json');
        return $this->json($json);
    }

    /**
     * @Route("/user/getAllEmail/{email}",methods={"GET","HEAD"})
     * get all user -> email & pseudo
     */
    public function getAllEmail(string $email, UserRepository $userRepository): Response
    {
        /* init parameters */
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        /* get data */
        $user = $userRepository->getAllEmail($email);

        /* send data */
        $json = $serializer->serialize($user,  'json');
        return $this->json($json);
    }
}