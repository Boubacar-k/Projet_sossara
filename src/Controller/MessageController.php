<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mercure\PublisherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Conversation;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Repository\ParticipantRepository;
use App\Entity\User;

#[Route('/api', name: 'api_')]
class MessageController extends AbstractController
{
    const ATTRIBUTES_TO_SERIALIZE = ['id','content','createdAt','updateAt', 'is_read'];
    public function __construct(EntityManagerInterface $entityManager,MessageRepository $messageRepository,UserRepository $userRepository,
    ParticipantRepository $participantRepository,PublisherInterface $publisher){
        $this->entityManager = $entityManager;
        $this->messageRepository = $messageRepository;
        $this->userRepository = $userRepository;
        $this->participantRepository = $participantRepository;
        $this->publisher = $publisher;
    }


    #[Route('/message/{id}', name: 'app_message',methods: ['GET'])]
    public function index(Request $request, Conversation $conversation): Response
    {
        $this->denyAccessUnlessGranted('view',$conversation);

        // $message = $conversation->getMessages();

        $messages = $this->messageRepository->findMessageByConversationId($conversation->getId());

        array_map(function ($message){
            $message->setMine(
                $message->getUtilisateur()->getId() === $this->getUser()->getId() ? true:false
            );
        },$messages);

        return $this->json($messages, Response::HTTP_OK,[
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);

        // $response = new Response(json_encode( array( 'message' => $messages,) ) );
        // return $response;
    }

    #[Route('/message/new/{id}', name: 'app_new_message',methods: ['POST'])]
    public function newMessage(#[CurrentUser] User $user,Request $request,Conversation $conversation, SerializerInterface $serializer){

        $recipient = $this->participantRepository->findParticipantByConversationIdAndUserId(
            $conversation->getId(),
            $user->getId()
        );

        // return $this->json($recipient);

        $data = json_decode($request->getContent(), true);

        $content = $data['content'];

        $message = new Message();

        $message->setContent($content);
        $message->setUtilisateur($user);
        $message->setMine(true);

        $conversation->addMessage($message);
        $conversation->setLastMessage($message);

        $this->entityManager->getConnection()->beginTransaction();

        try {

            $this->entityManager->persist($message);
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();
            $this->entityManager->commit();
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $message->setMine(false);
        
        $messageSerialized = $serializer->serialize($message,'json', [
            'attributes' => ['id','content','createdAt','updateAt', 'mine','conversation'=>['id']]
        ]);

        $update = new Update(
            [
                sprintf("/api/conversation/%s",$conversation->getId()),
                sprintf("/api/conversation/%s",$recipient->getUtilisateur()->getEmail())
            ],
            $messageSerialized,
            true
        );

        $this->publisher->__invoke($update);

        return $this->json($message,Response::HTTP_CREATED,[],[
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }
}
