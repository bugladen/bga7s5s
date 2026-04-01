<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_01010 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Sink Cards from the Adversary's Faction Deck");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "01010", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_01010)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $adversary = $game->theah->getDuelRoundOpponent();

            $characters = $game->theah->getCharactersAtLocationByPlayerId($owner->Location, $owner->ControllerId);
            $characters = array_filter($characters, fn($character) => $character->hasTrait("Strega"));
            $count = count($characters) > 0 ? 2 : 1;

            $cardInfos = $game->getCardsOnTopOfPlayerFactionDeck($adversary->ControllerId, $count);
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

    public function actFromTechniqueWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromTechniqueWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_01010)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $characters = $game->theah->getCharactersAtLocationByPlayerId($owner->Location, $owner->ControllerId);
            $characters = array_filter($characters, fn($character) => $character->hasTrait("Strega"));
            $count = count($characters) > 0 ? 2 : 1;

            $adversary = $game->theah->getDuelRoundOpponent();
            $cardInfos = $game->getCardsOnTopOfPlayerFactionDeck($adversary->ControllerId, $count);
            $availableCards = [];
            foreach ($cardInfos as $cardInfo)
            {
                $card = $game->getCardObjectFromDb($cardInfo['id']);
                $availableCards[] = $card;
            }

            $availableIds = array_map(fn($card) => $card->Id, $availableCards);

            $deck = $game->getGameDeckObject();
            $deckName = $game->getPlayerFactionDeckName($adversary->ControllerId);
            foreach ($ids as $id)
            {
                if (! in_array($id, $availableIds))
                {
                    throw new \BgaUserException(sprintf($game->translate("Selected card is not in the top %s card(s) of your Adversary's Faction Deck"), $count));
                }

                $deck->insertCardOnExtremePosition($id, $deckName, false);
            }

            $game->notify->all("message", clienttranslate('${player_name} has chosen to sink ${count} card(s) from the top of the Adversary\'s Faction Deck.'), [
                'player_name' => $game->getActivePlayerName(),
                'count' => count($ids),
            ]);

            $game->gamestate->nextState();
        }
    }
}
