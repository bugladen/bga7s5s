<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01098;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01098 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public int $EmbargoedCardId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("The Cat's Embargo");
        $this->Image = "01098.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 98;

        $this->initializeFaction("Castille");
        $this->Initiative = 75;
        $this->PanacheModifier = 1;

        $this->Traits = [
            clienttranslate("Logistics"), 
            clienttranslate("Sabotage"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to two different locations.</p><hr><p><b>Forced:</b> At the end of Planning • Reveal a card at random from an opponent's hand.</p><p><b>Reaction:</b> After an opponent plays or discards a card with the revealed card's name • Gain a Renown.</p>");
        
        $this->resetCard();

        $this->Reactions = [
            new Reaction_01098(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        //Two locations will each get one Reknown.
        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose two city locations to place Renown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "01098");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventPhasePlanningEnd && $this->Location == Game::LOCATION_PLAYER_HOME) 
        {
            $game = $event->theah->game;
            $playerName = $game->getPlayerNameById($this->ControllerId);

            // WHY: if every opponent hand is empty, the chooser would soft-lock (no legal pick).
            $deck = $game->getGameDeckObject();
            $players = $game->loadPlayersBasicInfos();
            $hasRevealableOpponent = false;
            foreach ($players as $playerId => $player)
            {
                if ((int)$playerId == $this->ControllerId)
                {
                    continue;
                }
                if (count($deck->getCardsInLocation(Game::LOCATION_HAND, (int)$playerId)) > 0)
                {
                    $hasRevealableOpponent = true;
                    break;
                }
            }

            if (! $hasRevealableOpponent)
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code}: No opponent has cards in hand. The Forced reveal is skipped.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                ]);
                return;
            }

            //Pick an opponent. That opponent will reveal a random card from their hand.
            $game->notify->all("message", clienttranslate('${scheme_inject_code} triggers a Forced Reaction for the End of Planning Phase.  ${player_name} must choose an opponent to reveal a random card from their hand.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $playerName,
            ]);

            //Transition to the state where player chooses an opponent.
            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "01098");
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            // WHY: Forced may never have stamped a card (empty hands, scheme locked early).
            // getCardObjectFromDb(0) returns null and `$pickedCard::class` TypeErrors.
            if ($this->EmbargoedCardId == 0)
            {
                return;
            }

            $game = $event->theah->game;
            $pickedCard = $game->getCardObjectFromDb($this->EmbargoedCardId);
            if ($pickedCard == null)
            {
                return;
            }

            $class = $pickedCard::class;
            $class = substr($class, strrpos($class, '\\') + 2);

            $deck = $game->getGameDeckObject();
            $cards = $deck->getCardsOfType($class);

            foreach ($cards as $card) {
                $card = $game->getCardObjectFromDb($card['id']);
                $card->removeCondition(Game::CATS_EMBARGO_TARGET);
                $card->removeCondition(Game::OLD_CATS_EMBARGO_TARGET);
                $game->updateCardObjectInDb($card);
                $game->theah->addCardToWorld($card);
    
                $game->notify->player($pickedCard->ControllerId, "catsEmbargoTargetRemoved", "", [
                    "cardId" => $card->Id,
                ]);
            }
        }
    }

    public function getCatsEmbargoData(Game $game): ?array
    {
        if ($this->EmbargoedCardId == 0) {
            return null;
        }

        $embargoedCard = $game->getCardObjectFromDb($this->EmbargoedCardId);
        return [
            'cardId' => $this->Id,
            'embargoedCardName' => $embargoedCard->Name,
        ];
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_END_01098)
        {
            $opponents = [];
            $players = $game->loadPlayersBasicInfos();
            $currentPlayerId = $game->getActivePlayerId();
            $deck = $game->getGameDeckObject();
            foreach ( $players as $playerId => $player ) 
            {
                if ($playerId == $currentPlayerId)
                {
                    continue;
                }

                // WHY: empty-hand opponents cannot satisfy "reveal a card at random"; hide them
                // so the chooser cannot submit a pick that would crash on array_rand.
                $hand = $deck->getCardsInLocation(Game::LOCATION_HAND, $playerId);
                if (count($hand) == 0)
                {
                    continue;
                }

                $opponents[] = ['id' => $playerId, 'name' => $player['player_name']];
            }        
    
            $args['opponents'] = $opponents;
    
            return [
                "args" => $args
            ];
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_END_01098)
        {
            $chosenPlayerId = $id;
            $activePlayerId = (int)$game->getActivePlayerId();

            if ($chosenPlayerId == $activePlayerId)
            {
                throw new UserException($game->translate("You must choose an opponent."));
            }

            $players = $game->loadPlayersBasicInfos();
            if (! isset($players[$chosenPlayerId]))
            {
                throw new UserException($game->translate("Invalid player."));
            }
    
            //Get the chosen player's name
            $chosenPlayerName = $game->getPlayerNameById($chosenPlayerId);
    
            //Get the chosen player's hand
            $deck = $game->getGameDeckObject();
            $hand = $deck->getCardsInLocation(Game::LOCATION_HAND, $chosenPlayerId);

            // WHY: array_rand on an empty array warns/fatals in PHP; Forced cannot reveal.
            if (count($hand) == 0)
            {
                throw new UserException($game->translate("That opponent has no cards in hand."));
            }
    
            //Randomly select a card from the hand
            $card = $hand[array_rand($hand)];
            $pickedCard = $game->getCardObjectFromDb($card['id']);
    
            $playerName = $game->getActivePlayerName();
    
            //Get the chosen scheme card for the active player and updated it with the chosen card
            $scheme = $game->getPlayerChosenScheme($activePlayerId);
            if ($scheme instanceof _01098) {
                $scheme->EmbargoedCardId = $pickedCard->Id;
                $game->updateCardObjectInDb($scheme);
                $game->theah->addCardToWorld($pickedCard);
            }        
    
            $game->globals->set(Game::CHOSEN_CARD, $pickedCard->Id);
    
            //All cards in the game that have the name of the chosen card will get a condition added to them
            $class = $pickedCard::class;
            $class = substr($class, strrpos($class, '\\') + 2);
            $cards = $deck->getCardsOfType($class);
    
            foreach ($cards as $card) {
                $card = $game->getCardObjectFromDb($card['id']);
                if ($card->ControllerId != $this->ControllerId)
                {
                    $card->addCondition(Game::CATS_EMBARGO_TARGET);
                    $game->updateCardObjectInDb($card);

                    $game->notify->player($pickedCard->ControllerId, "catsEmbargoTargetChosen", "", [
                        "cardId" => $card->Id,
                    ]);
                }    
            }
    
            $game->notify->all('message', 
                clienttranslate('${card_inject_code} reveals ${picked_card} randomly from <strong>${chosen_player_name}</strong>\'s hand.'), [
                "card_inject_code" => $scheme->getInjectCode(),
                "player_name" => $playerName,
                "chosen_player_name" => $chosenPlayerName,
                "picked_card" => $pickedCard->getInjectCode(),
                "card" => $pickedCard->getPropertyArray($game),
            ]);

            $game->notify->all('catsEmbargoUpdated', '', [
                'cardId' => $scheme->Id,
                'embargoedCardName' => $pickedCard->Name,
            ]);
    
            $game->gamestate->nextState();
        }
    }
}