<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

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
        $this->Image = "img/cards/7s5s/098.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 98;

        $this->Faction = "Castille";
        $this->Initiative = 75;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Logistics", 
            "Sabotage",
        ];

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

            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose two city locations to place reknown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = '01098';
            }
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventPhasePlanningEnd && $this->Location == Game::LOCATION_PLAYER_HOME) 
        {
            $playerName = $event->theah->game->getPlayerNameById($this->ControllerId);

            //Pick an opponent. That opponent will reveal a random card from their hand.
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} triggers a Forced Reaction for the End of Planning Phase.  ${player_name} must choose an opponent to reveal a random card from their hand.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $playerName,
            ]);

            //Transition to the state where player chooses an opponent.
            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "01098");
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            $game = $event->theah->game;
            $pickedCard = $game->getCardObjectFromDb($this->EmbargoedCardId);

            $class = $pickedCard::class;
            $class = substr($class, strrpos($class, '\\') + 2);

            $deck = $game->getGameDeckObject();
            $cards = $deck->getCardsOfType($class);

            foreach ($cards as $card) {
                $card = $game->getCardObjectFromDb($card['id']);
                $card->removeCondition(Game::CATS_EMBARGO_TARGET);
                $game->updateCardObjectInDb($card);
                $game->theah->addCardToWorld($card);
    
                $game->notify->player($pickedCard->ControllerId, "catsEmbargoTargetRemoved", "", [
                    "cardId" => $card->Id,
                ]);
            }
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_END_01098)
        {
            $opponents = [];
            $players = $game->loadPlayersBasicInfos();
            $currentPlayerId = $game->getActivePlayerId();
            foreach ( $players as $playerId => $player ) 
            {
                if ($playerId != $currentPlayerId)
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
    
            //Get the chosen player's name
            $chosenPlayerName = $game->getPlayerNameById($chosenPlayerId);
    
            //Get the chosen player's hand
            $deck = $game->getGameDeckObject();
            $hand = $deck->getCardsInLocation(Game::LOCATION_HAND, $chosenPlayerId);
    
            //Randomly select a card from the hand
            $card = $hand[array_rand($hand)];
            $pickedCard = $game->getCardObjectFromDb($card['id']);
    
            $playerName = $game->getActivePlayerName();
    
            //Get the chosen scheme card for the active player and updated it with the chosen card
            $scheme = $game->getPlayerChosenScheme($game->getActivePlayerId());
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
                $card->addCondition(Game::CATS_EMBARGO_TARGET);
                $game->updateCardObjectInDb($card);
    
                $game->notify->player($pickedCard->ControllerId, "catsEmbargoTargetChosen", "", [
                    "cardId" => $card->Id,
                ]);
            }
    
            $game->notify->all('message', 
                clienttranslate('${card_inject_code} reveals ${picked_card} randomly from <strong>${chosen_player_name}</strong>\'s hand.'), [
                "card_inject_code" => $scheme->getInjectCode(),
                "player_name" => $playerName,
                "chosen_player_name" => $chosenPlayerName,
                "picked_card" => $pickedCard->getInjectCode(),
                "card" => $pickedCard->getPropertyArray($game),
            ]);
    
            $game->gamestate->nextState();
        }
    }
}