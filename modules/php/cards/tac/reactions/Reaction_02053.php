<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02053 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Performer stays in city, send cards to The Locker');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . clienttranslate('At the beginning of Dusk, your performer does not move Home. Then, send up to one card from each discard pile to The Locker: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCard($theah);
        $characters = $theah->getCharactersInCityByPlayerId($owner->ControllerId);
        foreach ($characters as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, $character->Name, "performer-$character->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskPhaseBegin && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null || $owner->Location != Game::LOCATION_PLAYER_HOME)
                return;

            // City Reaction requires at least one character in the city
            $characters = $event->theah->getCharactersInCityByPlayerId($owner->ControllerId);
            if (count($characters) == 0)
                return;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'decline')
        {
            $owner = $this->getOwningCard($game->theah);
            $performerId = (int) str_replace("performer-", "", $reactionId);
            $performer = $game->theah->getCharacterById($performerId);

            if ($performer !== null)
            {
                // Add condition to prevent moving home during Dusk
                $performer->addCondition(Game::UNDER_COVER_OF_THE_NIGHT);
                $game->updateCardObjectInDb($performer);

                $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${character_inject_code} will not move Home during Dusk. ${player_name} may now send cards from discard piles to The Locker.'), [
                    "scheme_inject_code" => $owner->getInjectCode(),
                    "character_inject_code" => $performer->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                ]);

                // Queue one transition per non-empty discard pile (players + city)
                $playerIds = $game->theah->getDBObject()->getPlayerIds();
                foreach ($playerIds as $player)
                {
                    $discardName = $game->getPlayerDiscardDeckName($player['id']);
                    $discardCards = $game->theah->getCardObjectsAtLocation($discardName);
                    if (count($discardCards) > 0)
                    {
                        $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02053");
                        $game->theah->queueEvent($transition);
                    }
                }

                // City discard pile
                $cityDiscardCards = $game->theah->getCardObjectsAtLocation(Game::LOCATION_CITY_DISCARD);
                if (count($cityDiscardCards) > 0)
                {
                    $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02053");
                    $game->theah->queueEvent($transition);
                }
            }

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
