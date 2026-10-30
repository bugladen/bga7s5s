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
            clienttranslate('Artifact'),
            clienttranslate('Syrneth'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> After you equip this card • Look at an opponent's Approach Deck and choose a card in it.</p><p><b>Reaction:</b> After an opponent plays the chosen card during any Day • Gain a Renown.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01200(),
        ];
    }

    // WHY: Approach Deck empties across Days; by Day 5 an opponent may have nothing left.
    // Forced "choose a card in it" cannot resolve — queuing the chooser soft-locks.
    private function opponentHasApproachCards(Game $game, int $playerId): bool
    {
        $deck = $game->getGameDeckObject();
        return count($deck->getCardsInLocation(Game::LOCATION_APPROACH, $playerId)) > 0;
    }

    private function anyOpponentHasApproachCards(Game $game): bool
    {
        foreach ($game->loadPlayersBasicInfos() as $playerId => $player)
        {
            if ((int)$playerId == $this->ControllerId)
            {
                continue;
            }
            if ($this->opponentHasApproachCards($game, (int)$playerId))
            {
                return true;
            }
        }
        return false;
    }

    private function isOpponentChooserState(int $state): bool
    {
        return $state == States::HIGH_DRAMA_PLAYER_TURN_01200
            || $state == States::DUEL_END_OF_ROUND_01200
            || $state == States::DUEL_RESOLVE_MANEUVER_01200
            || $state == States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_01200
            || $state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01200;
    }

    private function isCardChooserState(int $state): bool
    {
        return $state == States::HIGH_DRAMA_PLAYER_TURN_01200_2
            || $state == States::DUEL_END_OF_ROUND_01200_2
            || $state == States::DUEL_RESOLVE_MANEUVER_01200_2
            || $state == States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_01200_2
            || $state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01200_2;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventAttachmentEquipped && $this->Id == $event->attachmentId)
        {
            $game = $event->theah->game;

            // WHY: Cat's Embargo (_01098) same shape — skip Forced when the chooser would be empty.
            if (! $this->anyOpponentHasApproachCards($game))
            {
                $game->notify->all("message", clienttranslate('${card_inject_code}: No opponent has cards in their Approach Deck. The Forced ability is skipped.'), [
                    "card_inject_code" => $this->getInjectCode(),
                ]);
                return;
            }

            $game->notify->all("message", clienttranslate('Crystal Eye has been equipped and its Forced Ability will trigger.'), []);

            $transition = EventFactory::createTransitionEvent($game->getActivePlayerId(), $this->Id, "01200");
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
                    $game->notify->player($character->ControllerId, 'crystalEyeTargetRemoved', clienttranslate('${skull_inject_code}: ${card_inject_code} has been removed as a target.'), [
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

        if ($this->isOpponentChooserState($state))
        {
            $opponents = [];
            $players = $game->loadPlayersBasicInfos();
            foreach ( $players as $playerId => $player ) 
            {
                if ((int)$playerId == $this->ControllerId)
                {
                    continue;
                }
                // WHY: empty Approach Deck has nothing to choose — omit so the UI cannot
                // send the player into a soft-locked _2 state (Day 5 equip).
                if (! $this->opponentHasApproachCards($game, (int)$playerId))
                {
                    continue;
                }
                $opponents[] = ['id' => (int)$playerId, 'name' => $player['player_name']];
            }        

            $args['opponents'] = $opponents;
        }

        if ($this->isCardChooserState($state))
        {
            $playerId = $game->globals->get(Game::CHOSEN_OPPONENT);

            $players = $game->loadPlayersBasicInfos();
            $args['playerName'] = $players[$playerId]['player_name'];

            $deck = $game->getGameDeckObject();
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

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        // WHY: Pass is only an escape hatch when Forced cannot be fulfilled (empty Approach).
        // Live tables already stuck in _2 after a Day-5 equip need this to unstick.
        if ($this->isOpponentChooserState($state))
        {
            if ($this->anyOpponentHasApproachCards($game))
            {
                throw new \BgaUserException($game->translate("An opponent still has cards in their Approach Deck."));
            }

            $game->notify->all("message", clienttranslate('${card_inject_code}: No opponent has cards in their Approach Deck. The Forced ability is skipped.'), [
                "card_inject_code" => $this->getInjectCode(),
            ]);
            $game->gamestate->nextState("pass");
            return;
        }

        if ($this->isCardChooserState($state))
        {
            $playerId = (int)$game->globals->get(Game::CHOSEN_OPPONENT);
            if ($playerId > 0 && $this->opponentHasApproachCards($game, $playerId))
            {
                throw new \BgaUserException($game->translate("That opponent still has cards in their Approach Deck."));
            }

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} finds no cards in the Approach Deck to choose.'), [
                "card_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getActivePlayerName(),
            ]);
            // WHY: _2 keeps a sole "" transition — both choose and empty-deck Pass exit to EVENTS.
            $game->gamestate->nextState();
        }
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $actionId, int $id): void 
    {
        parent::actFromCardWithId($game, $state, $stateName, $actionId, $id);
        

        if ($this->isOpponentChooserState($state))
        {
            $players = $game->loadPlayersBasicInfos();
            if ( ! isset($players[$id]))
            {
                throw new \BgaUserException($game->translate("Invalid opponent"));
            }

            if (! $this->opponentHasApproachCards($game, $id))
            {
                throw new \BgaUserException($game->translate("That opponent has no cards in their Approach Deck."));
            }

            $game->notify->all("message", clienttranslate('${player_name} has chosen to look at <strong>${opponentName}</strong>\'s Approach Deck.'), [
                'player_name' => $game->getActivePlayerName(),
                'opponentName' => $players[$id]['player_name'],
            ]);

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);
            $game->gamestate->nextState("opponentChosen");
        }

        if ($this->isCardChooserState($state))
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