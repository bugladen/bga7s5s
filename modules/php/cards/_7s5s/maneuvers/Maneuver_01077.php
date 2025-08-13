<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01077 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Play Additional Combat Card");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
            return false;

        $actor = $theah->getDuelRoundActor();
        if (!$actor->hasTrait("Duelist"))
            return false;

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01077", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state === States::DUEL_RESOLVE_MANEUVER_01077)
        {
            $actor = $game->theah->getDuelRoundActor();
            $cardInfos = $game->getCardsOnTopOfPlayerFactionDeck($actor->ControllerId, $actor->ModifiedFinesse);
            $cards = [];
            foreach ($cardInfos as $cardInfo)
            {
                $card = $game->getCardObjectFromDb($cardInfo['id']);
                $cards[] = $card->getPropertyArray($game);
            }

            $args['cards'] = $cards;
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state === States::DUEL_RESOLVE_MANEUVER_01077)
        {
            $actor = $game->theah->getDuelRoundActor();
            $cardInfos = $game->getCardsOnTopOfPlayerFactionDeck($actor->ControllerId, $actor->ModifiedFinesse);
            $cardsPresent = array_filter($cardInfos, fn($cardInfo) => $cardInfo['id'] == $id);
            if (count($cardsPresent) === 0)
            {
                throw new \BgaUserException(sprintf($game->translate("Selected card is not in the top %s cards of your Faction Deck"), $actor->ModifiedFinesse));
            }

            $cardsToSink = array_filter($cardInfos, fn($cardInfo) => $cardInfo['id'] != $id);
            $deck = $game->getGameDeckObject();
            $deckName = $game->getPlayerFactionDeckName($actor->ControllerId);
            foreach ($cardsToSink as $cardInfo)
            {
                $deck->insertCardOnExtremePosition($cardInfo['id'], $deckName, false);
            }

            $card = $game->getCardObjectFromDb($id);

            $owner = $this->getOwningCard($game->theah);
            $game->notifyAllPlayers("message", clienttranslate('${card_inject_code}: ${player_name} has chosen ${combat_card_inject_code} as a new combat card to play from ${count} choices.  The rest of the cards have been sunk.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($actor->ControllerId),
                "count" => $actor->ModifiedFinesse,
                "combat_card_inject_code" => $card->getInjectCode(),
            ]);

            $game->globals->set(Game::NEXT_COMBAT_CARD, $id);
            $game->gamestate->nextState();
        }
    }
}