<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01080 extends RiskReaction
{
    private int $DuelOpponentId = 0;
    private string $DuelLocation = '';
    
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

        $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Pressure %s'), $this->DuelLocation), 'pressure');
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
                                $this->DuelLocation = $dyingCharacter->Location;
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

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            if ($event->reactionId == "pressure")
            {
                $game = $event->theah->game;
                $performer = $game->theah->getCharacterById($this->DuelOpponentId);
    
                $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
                $pressureStats = $game->theah->getPressureStats($performer, Game::STAT_INFLUENCE);
                $event  = EventFactory::createPressureOccuringEvent($game->getActivePlayerId(), $performer->Id, $this->DuelLocation, $pressureStats);
                $game->theah->queueEvent($event);
    
                $owner = $this->getOwningCard($game->theah);
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used the Reaction to Pressure ${location_name}'), [
                    "i18n" => ["location_name"],
                    "reaction_inject_code" => $owner->getInjectCode(),
                    'player_name' => $game->getPlayerNameById($performer->ControllerId),
                    'location_name' => $performer->Location,
                ]);
    
                [$success, $totals, $difference] = $game->pressureLocation($performer->ControllerId, $performer, Game::STAT_INFLUENCE);

                $pressuredEvent = EventFactory::createLocationPressuredEvent($performer->ControllerId, $performer->Id, $this->DuelLocation, Game::STAT_INFLUENCE, $success, $totals, $difference);
                $pressuredEvent->abilityId = $this->Id;
                $game->theah->queueEvent($pressuredEvent);
            }
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id && $event->success)
        {
            $performer = $event->theah->getCharacterById($this->DuelOpponentId);
            $claimEvent = EventFactory::createLocationClaimedEvent($performer->ControllerId, $performer->Id, $this->DuelLocation);
            $event->theah->queueEvent($claimEvent);
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
}