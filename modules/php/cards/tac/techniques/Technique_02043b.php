<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02043b extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Place a Flourish from discard on top of your deck");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
        {
            return false;
        }

        $flourishes = $this->getFlourishesInDiscard($theah, $playerId);
        return count($flourishes) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "02043b", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02043b)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $flourishes = $this->getFlourishesInDiscard($game->theah, $owner->ControllerId);
            $args['cards'] = array_map(fn($card) => $card->getPropertyArray($game), $flourishes);
        }

        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02043b)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $flourishes = $this->getFlourishesInDiscard($game->theah, $owner->ControllerId);
            $validIds = array_map(fn($card) => $card->Id, $flourishes);

            if (! in_array($id, $validIds))
            {
                throw new UserException($game->translate("Selected card is not a Flourish in your discard pile."));
            }

            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($owner->ControllerId, $id);
            $game->theah->queueEvent($removeEvent);

            $addEvent = EventFactory::createCardAddedToFactionDeckEvent($owner->ControllerId, $id, true);
            $game->theah->queueEvent($addEvent);

            $game->gamestate->nextState();
        }
    }

    private function getFlourishesInDiscard(Theah $theah, int $playerId): array
    {
        $discardName = $theah->game->getPlayerDiscardDeckName($playerId);
        $cards = $theah->getCardObjectsAtLocation($discardName);
        $flourishes = array_filter($cards, fn($card) => $card->hasTrait(clienttranslate('Flourish')));
        return array_values($flourishes);
    }
}
