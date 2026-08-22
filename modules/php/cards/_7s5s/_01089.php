<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01089;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01089 extends Leader implements IHasReactions
{
    use ReactionTrait;

    public int $AffectedCharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Soline el Gato");
        $this->Image = "01089.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 89;

        $this->InPlayXImageOffset = 15;

        $this->initializeFaction("Castille");
        $this->Title = clienttranslate("Prince of Thieves");
        $this->Resolve = 7;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 2;
        $this->CrewCap = 6;
        $this->Panache = 6;

        $this->Traits = [
            clienttranslate("Leader"),
            clienttranslate("Pirate"),
            clienttranslate("Scoundrel"),
            clienttranslate("Castille"),
        ];

        $this->Text = clienttranslate("<p>Your adversaries at Soline's location have -1 [Finesse].</p><p><b>City Reaction:</b> After an Action resolves • Move Soline to an adjacent City location.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01089(),
        ];
    }

    private function raiseFinesse(Character $character, Theah $theah)
    {
        $event = EventFactory::createCharacterFinesseModifedEvent($this->ControllerId, $character->Id, $character->ModifiedFinesse, $character->ModifiedFinesse + 1, $this->getInjectCode());
        $theah->queueEvent($event);

        $character->removeCondition(Game::SOLINE_EL_GATO_CONDITION);
        $theah->game->updateCardObjectInDb($character);

        $theah->game->notify->all("solineElGatoConditionEnded", '', [
            "cardId" => $character->Id,
        ]);
    }

    private function lowerFinesse(Character $character, Theah $theah)
    {
        $event = EventFactory::createCharacterFinesseModifedEvent($this->ControllerId, $character->Id, $character->ModifiedFinesse, $character->ModifiedFinesse - 1, $this->getInjectCode());
        $theah->queueEvent($event);

        $character->addCondition(Game::SOLINE_EL_GATO_CONDITION);
        $theah->game->updateCardObjectInDb($character);

        $theah->game->notify->all("solineElGatoConditionStarted", '', [
            "cardId" => $character->Id,
        ]);
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
                $this->AffectedCharacterId = $defender->Id;
                $this->IsUpdated = true;
            }
            else if ($defender->Location == $this->Location && $defender->ControllerId == $this->ControllerId)
            {
                $this->lowerFinesse($challenger, $event->theah);
                $this->AffectedCharacterId = $challenger->Id;
                $this->IsUpdated = true;
            }
        }

        if ($event instanceof EventDuelEnd)
        {
            if ($this->AffectedCharacterId > 0)
            {
                $affectedCharacter = $event->theah->getCharacterById($this->AffectedCharacterId);
                if (!$event->theah->game->characterIsInDiscardOrLocker($affectedCharacter))
                {
                    $this->raiseFinesse($affectedCharacter, $event->theah); 
                }

                $this->AffectedCharacterId = 0;
                $this->IsUpdated = true;
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

                $this->AffectedCharacterId = $newDefender->Id;
                $this->IsUpdated = true;
            }
        }

        if ($event instanceof EventChallengerSwapped)
        {
            $inDuel = $event->theah->game->globals->get(Game::IN_DUEL, false);
            if (!$inDuel)
            {
                return;
            }

            $defenderId = $event->theah->getDuelOpponentId($event->newChallengerId);
            $defender = $event->theah->getCharacterById($defenderId);

            if ($defender->Location == $this->Location && $defender->ControllerId == $this->ControllerId)
            {
                $oldChallenger = $event->theah->getCharacterById($event->oldChallengerId);
                $newChallenger = $event->theah->getCharacterById($event->newChallengerId);
                $this->lowerFinesse($newChallenger, $event->theah);
                $this->raiseFinesse($oldChallenger, $event->theah);

                $this->AffectedCharacterId = $newChallenger->Id;
                $this->IsUpdated = true;
            }
        }
    }

}