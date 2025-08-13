<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01080 extends RiskReaction
{
    private int $DuelOpponentId = 0;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pressure Location After Duel");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to pressure your participant\'s location after the duel: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $performer = $theah->getCharacterById($this->DuelOpponentId);
        $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Pressure %s'), $performer->Location), 'pressure');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterDestroyed)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $game = $event->theah->game;

                //The character has to be in a duel
                $inDuel = $game->globals->get(Game::IN_DUEL);
                if ($inDuel)
                {
                    $actor = $game->theah->getDuelRoundActor();
                    $adversaryId = $game->theah->getDuelOpponentId($actor->Id);

                    //Is the dying character in the duel?
                    if ($event->characterId == $actor->Id || $event->characterId == $adversaryId)
                    {
                        $adversary = $game->theah->getCharacterById($adversaryId);

                        //Do we have a character in the duel?
                        if ($adversary->ControllerId == $owner->ControllerId || $actor->ControllerId == $owner->ControllerId)
                        {
                            $dyingCharacter = $game->theah->getCharacterById($event->characterId);

                            //Is the dying character not mine?
                            if ($dyingCharacter->ControllerId != $owner->ControllerId)
                            {
                                //Save our participant so we can choose to claim the location later
                                $this->DuelOpponentId = $game->theah->getDuelOpponentId($event->characterId);
                                $owner->IsUpdated = true;
                            }
                        }
                    }
                }
            }
        }

        if ($event instanceof EventDuelEnd && $this->isAvailable() && $this->DuelOpponentId != 0)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == "pressure")
        {
            $game->gamestate->nextState("pay");
            return;
        }

        $game->gamestate->nextState("done");
    }

    public function reactionPaidFor(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::reactionPaidFor($game, $state, $internalId, $reactionId);

        if ($reactionId == "pressure")
        {
            $performer = $game->theah->getCharacterById($this->DuelOpponentId);

            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $pressureTypes = $game->theah->getPressureTypes($performer, Game::STAT_INFLUENCE);
            $event  = EventFactory::createPressureOccuringEvent($game->getActivePlayerId(), $performer->Id, $performer->Location, $pressureTypes);
            $game->theah->queueEvent($event);

            $owner = $this->getOwningCard($game->theah);
            $game->notifyAllPlayers("message", clienttranslate('${reaction_inject_code}: ${player_name} used the Reaction to Pressure ${location_name}'), [
                "i18n" => ["location_name"],
                "reaction_inject_code" => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($performer->ControllerId),
                'location_name' => $performer->Location,
            ]);

            $game->gamestate->nextState("01080");
        }
    }

    public function stateFromReaction(Game $game, int $state, string $stateName): void
    {
        parent::stateFromReaction($game, $state, $stateName);

        if ($state == States::DUEL_END_01080)
        {
            $performer = $game->theah->getCharacterById($this->DuelOpponentId);

            [$success, $totals] = $game->pressureLocation($performer->ControllerId, $performer, Game::STAT_INFLUENCE);

            $pressuredEvent = EventFactory::createLocationPressuredEvent($performer->ControllerId, $performer->Id, $performer->Location, Game::STAT_INFLUENCE, $success, $totals);
            $game->theah->queueEvent($pressuredEvent);

            if ($success)
            {
                $event = EventFactory::createLocationClaimedEvent($performer->ControllerId, $performer->Id, $performer->Location);
                $game->theah->queueEvent($event);
            }

            $game->gamestate->nextState();
        }
    }
}