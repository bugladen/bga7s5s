<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IFactionCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02017 extends AttachmentReaction
{
    public ?int $targetCombatCardId = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("-1 Riposte");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . " -1 Riposte";
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, "-1 Riposte", "1Riposte");
        $array[] = $this->createButtonProperty($theah->game, "Pass", "pass");

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCombatCardAnnounced && $this->isAvailable() && $this->ownerIsAttached($event->theah))
        {
            $game = $event->theah->game;
            $inDuel = $game->globals->get(Game::IN_DUEL, false);
            if ($inDuel)
            {
                $owningCharacter = $this->getOwningCharacter($event->theah);
                $actor = $event->theah->getDuelRoundActor();
                $adversary = $event->theah->getCharacterById($event->theah->getDuelOpponentId($actor->Id));
                if ($owningCharacter->Id == $adversary->Id)
                {
                    $combatCard = $game->getCardObjectFromDb($event->cardId);

                    if ($combatCard instanceof IFactionCard && $combatCard->Riposte > 0)
                    {
                        $owner = $this->getOwningCard($event->theah);
                        $owner->IsUpdated = true;
                        $this->targetCombatCardId = $event->cardId;
                        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($transition);
                    }
                }
            }
        }

        if ($event instanceof EventDuelCalculateCombatCardStats && $this->targetCombatCardId == $event->combatCardId)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->removeRiposte(1);
            $event->explanations[] = sprintf($event->theah->game->translate("%s removes 1 Riposte."), $owner->getInjectCode());
        }
}

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == '1Riposte')
        {
            $owner = $this->getOwningCard($game->theah);
            $this->setUsed($game->theah, true);

            $game->notify->all("message", sprintf($game->translate("%s: %s uses Reaction to apply -1 Riposte to player's combat card."), $owner->getInjectCode(), $game->getPlayerNameById($owner->ControllerId)));
        }

        if ($reactionId == 'pass')
        {
            $owner = $this->getOwningCard($game->theah);
            $this->targetCombatCardId = null;
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}