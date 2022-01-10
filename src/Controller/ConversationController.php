<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Routing\Annotation\Route;


class ConversationController extends ApiController
{
    /**
     * @Route("/conversation/add", methods={"POST"})
     * create new conversation
     */
    public function add(Request $request, UserRepository $userRepository): JsonResponse
    {
        /* init parameter */
        $em = $this->getDoctrine()->getManager();
        $request = $this->transformJsonBody($request);
        $oConversation = new Conversation();

        /* get data from request */
        $oConversation->setName($request->get("name"));
        $lUsers = $request->get("lUsers");

        /* get users object */
        foreach($lUsers as $user){
            $oUser = $userRepository->findOneBy(["email" =>$user["email"]]);
            $oConversation->addUser($oUser);
        }

        /* sql action */
        $em->persist($oConversation);
        try {
            $em->flush();
            return $this->respondCreated($oConversation->getId());
        } catch (\Doctrine\DBAL\Exception $e){
            return $this->respondValidationError("Echec de la création de votre compte");
        }
    }

    /**
     * @Route("/conversation/my/{email}", methods={"GET", "HEAD"})
     * get list of conversation for one user
     */
    public function getConversationByEmail(string $email, UserRepository $userRepository, ConversationRepository $conversationRepository): Response
    {
        /* init parameter */
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        /* get data */
        $user = $userRepository->findOneBy(["email" => $email]);
        $lConversations = $conversationRepository->getByUser($user->getId());

        /* send data */
        $json = $serializer->serialize($lConversations,  'json');
        return $this->json($json);
    }

    /**
     * @Route("/conversation/{id}", methods={"GET", "HEAD"})
     * get one conversation
     */
    public function getConversation(int $id, UserRepository $userRepository, ConversationRepository $conversationRepository): Response
    {
        /* init parameter */
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        /* get data */
        $oConversation = $conversationRepository->findOneBy(["id" => $id]);
        $lUsers = $oConversation->getUsers();
        $lMessages = $oConversation->getMessages();

        /* set data for no circular references */
        $oConversation->setMessagesNotCircular();
        $oConversation->setUsersNotCircular();

        /* send data */
        $json = $serializer->serialize($oConversation, 'json');
        return $this->json($json);
    }

    /**
     * @Route("/conversation/set_msg", methods={"POST"})
     * post new message for conversation
     */
    public function setMsg(Request $request, UserRepository $userRepository, ConversationRepository $conversationRepository): Response
    {
        /* init parameter */
        $em = $this->getDoctrine()->getManager();
        $request = $this->transformJsonBody($request);
        $oConversation = $conversationRepository->findOneBy(["id" => $request->get("conversationId")]);
        $oConversation->setLastUpdatedAt(new \DateTime());
        $oMessage = new Message();

        /* set data */
        $oMessage->setMsg($request->get("msg"));
        $oMessage->setUser($userRepository->findOneBy(["email" => $request->get("email")]));
        $oMessage->setConversation($oConversation);

        /* sql action */
        $em->persist($oConversation);
        $em->persist($oMessage);

        try {
            $em->flush();
            return $this->respondCreated(sprintf("Message envoyé avec succes"));
        } catch (\Doctrine\DBAL\Exception $e){
            return $this->respondValidationError("Echec de l'envoie de votre message");
        }

    }
}
