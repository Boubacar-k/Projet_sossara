<?php

namespace App\Security\Voter;

use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;


class ConversationVoter extends Voter 
{

    public function __construct(ConversationRepository $conversationRepository){
        $this->conversationRepository = $conversationRepository;
    }
    const VIEW = 'view';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        return $attribute == self::VIEW && $subject instanceof Conversation;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $result = $this->conversationRepository->checkIfUserIsParticipant(
            $subject->getId(),
            $token->getUser()->getId()
        );

        return !!$result;
    }

}