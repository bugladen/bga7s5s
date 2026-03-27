<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _02005 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Decipher the Strands');
        $this->Image = "02005.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 5;

        $this->initializeFaction('Vodacce');
        $this->Initiative = 13;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate('Sorte'),
            clienttranslate('Weave'),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to any location. Then, add a Renown to a location with no Renown.</p><p>Look at the top three cards of an opponent's deck and an additional card for each <b>Strega</b> you control. Sink one or more of those cards and replace the rest in any order.</p><p>{BAR}</p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. 
            ${player_name} may first choose a city location to place Renown onto. 
            Then they will place Renown onto a location that has no Renown. 
            Lastly, they will manipulate the top cards of an opponent\'s deck.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose a location.
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "02005");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);            
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);
    
        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_3)
        {
            $players = $game->loadPlayersBasicInfos();
            $availablePlayers = array_filter($players, fn($player) => $player["player_id"] != $this->ControllerId);
            $args["opponents"] = array_map(fn($player) => ['id' => $player['player_id'], 'name' => $player['player_name']], array_values($availablePlayers));
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_4 || $state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_5)
        {
            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $opponentName = $game->getPlayerNameById($opponentId);
            
            $args['opponentName'] = $opponentName;

            $cards = json_decode($game->globals->get(Game::CHOSEN_CARD));
            $args['cards'] = $cards;
        }

        return $args;
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_2)
        {
            $locations = $game->theah->getCityLocations();
            $locations = array_filter($locations, fn($location) => $location->Reknown == 0);
            if (count($locations) > 0)
            {
                throw new UserException($game->translate("There are locations with no Renown."));
            }

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "02005_3");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("");
        }
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_3)
        {
            $players = $game->loadPlayersBasicInfos();
            if (! isset($players[$id]) && $id != 0)
            {
                throw new UserException($game->translate("Invalid opponent"));
            }

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);

            $characters = $game->theah->getCharactersInPlayByPlayerId($this->ControllerId);
            $characters = array_filter($characters, fn($character) => $character->hasTrait("Strega"));
            $count = 3 + count($characters);

            $game->notify->all("message", clienttranslate('${player_name} will manipulate the top cards of ${opponent_name}\'s deck. 
            They have ${strega_count} Strega(s) in play so will look at the top ${count} cards.'), [
                "player_name" => $players[$this->ControllerId]['player_name'],
                "opponent_name" => $players[$id]['player_name'],
                "strega_count" => count($characters),
                "count" => $count,
            ]);

            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($id, $count);

            $cards = [];
            foreach ($deckCards as $deckCard)
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }

            $game->globals->set(Game::CHOSEN_CARD, json_encode($cards));

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($this->ControllerId, $this->Id, $this->Id, $this->Id);
            $game->theah->queueEvent($sorceryStartEvent);

            $game->gamestate->nextState("");
        }
        
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02005)
        {
            $location = $ids[0];

            $event = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
    
            $game->notify->player($this->ControllerId, 'message', 
                clienttranslate('Private: You have chosen to place renown onto ${location}.  Per Decipher the Strands you must now add a Renown to a location that has no Renown.'), [
                'i18n' => ['location'],
                "location" => $location,
            ]);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "02005_2");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $game->theah->queueEvent($transition);
    
            $game->gamestate->nextState();    
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_2)
        {
            $location = $ids[0];     
            
            $loc = $game->theah->getCityLocation($location);
            if ($loc->Reknown > 0)
            {
                throw new UserException(sprintf($game->translate("%s already has Renown."), $location));
            }

            $reknownEvent = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($reknownEvent);
            $game->theah->queueEvent($reknownEvent);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "02005_3");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $game->theah->queueEvent($transition);
    
            $game->gamestate->nextState("");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_4)
        {
            if (count($ids) < 1)
            {
                throw new UserException($game->translate("You must sink at least one card."));
            }

            $deck = $game->getGameDeckObject();

            $playerId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $deckName = $game->getPlayerFactionDeckName($playerId);

            foreach ($ids as $id)
            {
                $card = $game->getCardObjectFromDb($id);
                if ($card == null)
                {
                    throw new UserException($game->translate("Invalid card id"));
                }

                if ($card->ControllerId != $playerId)
                {
                    throw new UserException(sprintf($game->translate("Card %s is not owned by the player"), $card->Name));
                }

                if ($card->Location != $deckName)
                {
                    throw new UserException(sprintf($game->translate("Card %s is not in the deck of the player"), $card->Name));
                }
            }

            $originalCards = json_decode($game->globals->get(Game::CHOSEN_CARD));

            foreach ($ids as $id)
            {
                $deck->insertCardOnExtremePosition($id, $deckName, false);
                //Remove the card from the original cards
                $originalCards = array_filter($originalCards, fn($originalCard) => $originalCard->id != $id);
            }

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} has chosen to sink ${card_count} cards.'), [
                "card_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
                "card_count" => count($ids),
            ]);

            if (count($originalCards) == 0)
            {
                $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($this->ControllerId, $this->Id, $this->Id, $this->Id);
                $game->theah->queueEvent($sorceryPlayedEvent);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($this->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $game->gamestate->nextState("allSunk");
            }
            else
            {
                $game->globals->set(Game::CHOSEN_CARD, json_encode(array_values($originalCards)));

                $game->gamestate->nextState("cardsChosen");
            }
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_5)
        {
            $playerId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $deckName = $game->getPlayerFactionDeckName($playerId);
            $opponentName = $game->getPlayerNameById($playerId);
            $deck = $game->getGameDeckObject();

            $remainingCards = json_decode($game->globals->get(Game::CHOSEN_CARD));

            $remainingIds = array_map(fn($remainingCard) => $remainingCard->id, $remainingCards);

            foreach ($ids as $id) 
            {
                if (!in_array($id, $remainingIds))
                {
                    throw new UserException(sprintf($game->translate("Card %s is not in the remaining cards."), $id));
                }

                //Move card to top of deck
                $deck->insertCardOnExtremePosition((int)$id, $deckName, true);                
            }

            $message = clienttranslate('${card_inject_code}: ${player_name} has chosen the order of the remaining cards in ${opponent_name}\'s Faction Deck.');
            $game->notify->all("message", $message, [
                "card_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getActivePlayerName(),
                "opponent_name" => $opponentName,
            ]);

            $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($this->ControllerId, $this->Id, $this->Id, $this->Id);
            $game->theah->queueEvent($sorceryPlayedEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($this->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}