<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01089;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01089 extends Leader implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Soline El Gato");
        $this->Image = "img/cards/7s5s/089.jpg";
        $this->ExpansionName = "_7";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 89;

        $this->Faction = "Castille";
        $this->Title = "Prince of Thieves";
        $this->Resolve = 7;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 2;
        $this->CrewCap = 6;
        $this->Panache = 6;

        $this->Traits = [
            "Leader",
            "Pirate",
            "Scoundrel",
            "Castille",
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01089(),
        ];
    }

    private function raiseFinesse(Character $character, Theah $theah)
    {
        $event = EventFactory::createCharacterFinesseModifedEvent($this->ControllerId, $character->Id, $character->ModifiedFinesse, $character->ModifiedFinesse + 1, $this->getInjectCode());
        $theah->queueEvent($event);
    }

    private function lowerFinesse(Character $character, Theah $theah)
    {
        $event = EventFactory::createCharacterFinesseModifedEvent($this->ControllerId, $character->Id, $character->ModifiedFinesse, $character->ModifiedFinesse - 1, $this->getInjectCode());
        $theah->queueEvent($event);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelStarted)
        {
            $challenger = $event->theah->getCharacterById($event->challengerId);
            $defender = $event->theah->getCharacterById($event->defenderId);

            if ($challenger->Location == $this->Location && $challenger->ControllerId == $this->ControllerId)
            {
                $this->lowerFinesse($defender, $event->theah);
            }
            else if ($defender->Location == $this->Location && $defender->ControllerId == $this->ControllerId)
            {
                $this->lowerFinesse($challenger, $event->theah);
            }
        }

        if ($event instanceof EventDuelEnd)
        {
            $challenger = $event->theah->getCharacterById($event->challengerId);
            $defender = $event->theah->getCharacterById($event->defenderId);

            if ($challenger->Location == $this->Location && $challenger->ControllerId == $this->ControllerId)
            {
                $this->raiseFinesse($defender, $event->theah);
            }
            else if ($defender->Location == $this->Location && $defender->ControllerId == $this->ControllerId)
            {
                $this->raiseFinesse($challenger, $event->theah);
            }
        }

        if ($event instanceof EventDefenderSwapped)
        {
            $challengerId = $event->theah->getDuelOpponentId($event->newDefenderId);
            $challenger = $event->theah->getCharacterById($challengerId);

            if ($challenger->Location == $this->Location && $challenger->ControllerId == $this->ControllerId)
            {
                $oldDefender = $event->theah->getCharacterById($event->oldDefenderId);
                $newDefender = $event->theah->getCharacterById($event->newDefenderId);
                $this->lowerFinesse($newDefender, $event->theah);
                $this->raiseFinesse($oldDefender, $event->theah);

            }
        }

        if ($event instanceof EventChallengerSwapped)
        {
            $defenderId = $event->theah->getDuelOpponentId($event->newChallengerId);
            $defender = $event->theah->getCharacterById($defenderId);

            if ($defender->Location == $this->Location && $defender->ControllerId == $this->ControllerId)
            {
                $oldChallenger = $event->theah->getCharacterById($event->oldChallengerId);
                $newChallenger = $event->theah->getCharacterById($event->newChallengerId);
                $this->lowerFinesse($newChallenger, $event->theah);
                $this->raiseFinesse($oldChallenger, $event->theah);
            }
        }
    }

}