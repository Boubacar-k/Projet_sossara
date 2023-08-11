<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Repository\UserRepository;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Conversation;
use App\Entity\Participant;
use App\Entity\User;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\HttpHeaderSerializer;
use Symfony\Component\WebLink\Link;


#[Route('/api', name: 'api_')]
class ConversationController extends AbstractController
{
    private $entityManager;
    private $userRepository;
    private $conversationRepository;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager,ConversationRepository $conversationRepository){
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->conversationRepository = $conversationRepository;
    }
    #[Route('/conversation', name: 'newConversation')]
    public function index(#[CurrentUser] User $user,Request $request): Response
    {
        $otherUser = $request->get('otherUser',0);
        $otherUser = $this->userRepository->find($otherUser);

        if(is_null($otherUser)){
            throw new \Exception("Utilisateur non trouve");
        }

        if($otherUser->getId() == $user->getId()){
            throw new \Exception("Vous ne pouvez pas creer cette conversation");
        }

        $conversation = $this->conversationRepository->findConversationByParticipants(
            $otherUser->getId(),
            $user->getId()
        );

        if(count($conversation)){
            throw new \Exception("La conversation existe deja");
        }

        $conversation = new Conversation();

        $participant = new Participant();
        
        $participant->setUtilisateur($user);
        $participant->setConversation($conversation);

        $otherparticipant = new Participant();
        
        $otherparticipant->setUtilisateur($otherUser);
        $otherparticipant->setConversation($conversation);

        $this->entityManager->getConnection()->beginTransaction();

        try {

            $this->entityManager->persist($conversation);
            $this->entityManager->persist($participant);
            $this->entityManager->persist($otherparticipant);
            $this->entityManager->flush();
            $this->entityManager->commit();
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $this->json([
            'id' => $conversation->getId()
        ],Response::HTTP_CREATED,[],[]);
    }

    #[Route('/conversation/get', name: 'getConversation')]
    public function getConvs(#[CurrentUser] User $user,Request $request){
        $conversation = $this->conversationRepository->findConversationByUser($user->getId());

        $hubUrl = $this->getParameter('mercure.default_hub');
        $this->addLink($request, new Link('mercure',$hubUrl));
        return $this->json([
            'conversation' =>$conversation
        ]);
    }

    
}
