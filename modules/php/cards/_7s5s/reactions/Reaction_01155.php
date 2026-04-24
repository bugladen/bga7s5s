<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// This reaction triggers from LOCATION_DUELING_LINE, not Location == Game::LOCATION_HAND
class Reaction_01155 extends RiskReaction
{
    private ?int $pendingActorId = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Equip to participant from dueling line");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may equip this card to your participant from the Duel card line ignoring all costs: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Equip'), 'equip');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelEndOfRound && $this->isAvailable())
        {
            $game = $event->theah->game;
            $inDuel = $game->globals->get(Game::IN_DUEL);
            $owner = $this->getOwningAttachment($event->theah);
            if ($owner && $owner->Location == Game::LOCATION_DUELING_LINE && $inDuel && $owner->ControllerId == $event->playerId)
            {
                $actor = $event->theah->getCharacterById($event->actorId);
                if ($actor)
                {
                    [$hasRestrictions] = $game->hasEquipRestrictions($actor, $owner);
                    if (! $hasRestrictions && $owner->canAttachTo($actor))
                    {
                        $this->pendingActorId = $event->actorId;
                        $owner->IsUpdated = true;
                        $reactionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionEvent);
                    }
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningAttachment($game->theah);

        if ($reactionId == 'equip' && $owner && $this->pendingActorId !== null)
        {
            $actor = $game->theah->getCharacterById($this->pendingActorId);
            if ($actor)
            {
                $actualTargetId = $owner->getRequiredAttachTargetId($game->theah, $actor->Id);

                $game->notify->all('message', clienttranslate('Improvised Weapon: ${player_name} equips to ${character_inject_code} from the Duel card line'), [
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $actor->getInjectCode(),
                ]);

                $equipEvent = EventFactory::createAttachmentEquippedEvent(
                    $owner->ControllerId, $actualTargetId, $owner->Id,
                    0, 0, false, '', false, $owner->Id, $this->Id
                );
                $game->theah->queueEvent($equipEvent);

                $this->setUsed($game->theah, true);
            }
        }

        $this->pendingActorId = null;
        if ($owner)
        {
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
