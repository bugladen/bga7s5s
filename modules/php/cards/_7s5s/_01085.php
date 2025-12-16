<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01085;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class _01085 extends Risk implements IHasActions, ISorcererAbility
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Porté Travel");
        $this->Image = "img/cards/7s5s/085.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Montaigne";
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            'Sorcery',
            'Porté',
            'Unique',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01085(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateCombatCardStats && $event->combatCardId == $this->Id)
        {
            $characters = $event->theah->getCharactersInPlayByPlayerId($this->ControllerId);
            $characters = array_filter($characters, fn($character) => $character->HasTrait("Sorcerer"));
            if (count($characters) > 0)
            {
                $transitionEvent = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "01085");
                $event->queueEvent($transitionEvent);
            }
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::DUEL_APPLY_COMBAT_CARD_STATS_01085)
        {
            $characters = $game->theah->getCharactersInPlayByPlayerId($this->ControllerId);
            $characters = array_values(array_filter($characters, fn($character) => $character->HasTrait("Sorcerer")));
            $args["characterIds"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::DUEL_APPLY_COMBAT_CARD_STATS_01085)
        {
            $sorcerer = $game->theah->getCharacterById($id);
            if ( ! $sorcerer)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            if ($sorcerer->ControllerId != $this->ControllerId)
            {
                throw new \BgaUserException($game->translate("Character is not owned by you"));
            }

            if ( ! $sorcerer->HasTrait("Sorcerer"))
            {
                throw new \BgaUserException($game->translate("Character is not a Sorcerer"));
            }

            $game->notify->all("message", clienttranslate('${player_name} chose ${sorcerer_name} as their Porté Travel Sorcerer.'), [
                'i18n' => ['sorcerer_name'],
                "player_name" => $game->getActivePlayerName(),
                "sorcerer_name" => $sorcerer->Name,
            ]);

            $actor = $game->theah->getDuelRoundActor();

            $event = EventFactory::createCharacterWoundedEvent($sorcerer->Id, $this->Id, 1, $this->getInjectCode());
            $game->theah->queueEvent($event);

            $event = EventFactory::createCardMovedEvent($this->ControllerId, $actor->Id, $actor->Location, $sorcerer->Location, $engage = false, $this->Id);
            $game->theah->queueEvent($event);

            // Set the threat to 0;
            $challengerId = $game->theah->getDuelChallengerId();
            $defenderId = $game->theah->getDuelDefenderId();

            $challengerThreat = $game->theah->getCurrentDuelThreat($challengerId);
            $defenderThreat = $game->theah->getCurrentDuelThreat($defenderId);

            $modifiedChallengerThreat = $actor->Id == $challengerId ? $challengerThreat * -1 : $challengerThreat;
            $modifiedDefenderThreat = $actor->Id == $defenderId ? $defenderThreat * -1 : $defenderThreat;

            $event = EventFactory::createThreatModifiedEvent($modifiedChallengerThreat, $modifiedDefenderThreat);
            $game->theah->queueEvent($event);
            
            $game->gamestate->nextState();
        }
    }
}