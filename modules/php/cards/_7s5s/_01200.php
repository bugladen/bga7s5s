<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01200;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class _01200 extends CityAttachment implements IHasReactions
{
    use ReactionTrait;

    public int $ChosenCard;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Crystal Eye');
        $this->Image = "01200.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 200;
        
        $this->CityCardNumber = 24;
        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 1;

        $this->ChosenCard = 0;

        $this->Traits = [
            'Artifact',
            'Syrneth',
            'Unique',
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01200(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventAttachmentEquipped && $this->Id == $event->attachmentId)
        {
            $event->theah->game->notify->all("message", clienttranslate('Crystal Eye has been equipped and its Forced Ability will trigger.'), []);

            $transition = EventFactory::createTransitionEvent($event->theah->game->getActivePlayerId(), $this->Id, "01200");
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventAttachmentUnequipped && $this->Id == $event->attachmentId)
        {
            $game = $event->theah->game;
            if ($this->ChosenCard != 0)
            {
                $character = $game->getCardObjectFromDb($this->ChosenCard);
                if ($character != null)
                {
                    $character->removeCondition(Game::CRYSTAL_EYE_TARGET);
                    $game->updateCardObjectInDb($character);
                    $game->notify->player($character->ControllerId, 'crystalEyeTargetRemoved', clienttranslate('${skull_inject_code}: ${card_inject_code} has been been removed as a target.'), [
                        "skull_inject_code" => $this->getInjectCode(),
                        "card_inject_code" => $character->getInjectCode(),
                        "cardId" => $character->Id,
                        "playerId" => $character->ControllerId,
                    ]);
                }

                $this->ChosenCard = 0;
                $this->IsUpdated = true;
            }
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01200 || $state == States::DUEL_END_OF_ROUND_01200 || $state == States::DUEL_RESOLVE_MANEUVER_01200)
        {
            $opponents = [];
            $players = $game->loadPlayersBasicInfos();
            foreach ( $players as $playerId => $player ) 
            {
                if ($playerId != $this->ControllerId)
                    $opponents[] = ['id' => $playerId, 'name' => $player['player_name']];
            }        

            $args['opponents'] = $opponents;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01200_2 || $state == States::DUEL_END_OF_ROUND_01200_2 || $state == States::DUEL_RESOLVE_MANEUVER_01200_2)
        {
            $playerId = $game->globals->get(Game::CHOSEN_OPPONENT);

            $players = $game->loadPlayersBasicInfos();
            $args['playerName'] = $players[$playerId]['player_name'];

            $deck = $game->getGameDeckObject($playerId);
            $cardObjects = $deck->getCardsInLocation(Game::LOCATION_APPROACH, $playerId);
            $cards = [];
            foreach ($cardObjects as $cardObject)
            {
                $card = $game->getCardObjectFromDb($cardObject['id']);
                $cards[] = $card->getPropertyArray($game);
            }
            $args['cards'] = $cards;
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $actionId, int $id): void 
    {
        parent::actFromCardWithId($game, $state, $stateName, $actionId, $id);
        

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01200 || $state == States::DUEL_END_OF_ROUND_01200 || $state == States::DUEL_RESOLVE_MANEUVER_01200)
        {
            $players = $game->loadPlayersBasicInfos();
            if ( ! isset($players[$id]))
            {
                throw new \BgaUserException($game->translate("Invalid opponent"));
            }

            $game->notify->all("message", clienttranslate('${player_name} has chosen to look at <strong>${opponentName}</strong>\'s Approach Deck.'), [
                'player_name' => $game->getActivePlayerName(),
                'opponentName' => $players[$id]['player_name'],
            ]);

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);
            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01200_2 || $state == States::DUEL_END_OF_ROUND_01200_2 || $state == States::DUEL_RESOLVE_MANEUVER_01200_2)
        {
            $playerId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $activePlayerId = $game->getActivePlayerId();
            $activePlayerName = $game->getActivePlayerName();
            $card = $game->getCardObjectFromDb($id);
            if ( ! isset($card))
            {
                throw new \BgaUserException($game->translate("Invalid card id"));
            }

            $this->ChosenCard = $card->Id;
            $game->updateCardObjectInDb($this);
            
            $card->addCondition(Game::CRYSTAL_EYE_TARGET);            
            $game->updateCardObjectInDb($card);

            $players = $game->loadPlayersBasicInfos();
            $game->notify->all('crystalEyeTargetMessage', clienttranslate('${player_name} has chosen a card from ${opponentName}\'s Approach Deck as the target for Crystal Eye.'), [
                "player_name" => $activePlayerName,
                "opponentName" => $players[$playerId]['player_name'],
                "choosingPlayerId" => $activePlayerId,
                "targetplayerId" => $playerId,
            ]);

            $game->notify->player($activePlayerId, "message", 
            clienttranslate('Private: You have chosen ${card_inject_code} as the target for Crystal Eye.'), [
                "card_inject_code" => $card->getInjectCode(),
                ]);
    

            $game->notify->player($playerId, 'crystalEyeTargetChosen', 
            clienttranslate('Private: ${player_name} has chosen ${card_inject_code} as the target for Crystal Eye.'), [
                "player_name" => $activePlayerName,
                "card_inject_code" => $card->getInjectCode(),
                "cardId" => $card->Id,
                "playerId" => $playerId,
            ]);
            $game->gamestate->nextState();
        }
    }
}