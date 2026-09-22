<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;

class _04cd11 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Let Bygones Be Bygones');
        $this->Image = '04cd11.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 0;

        $this->CityCardNumber = 11;

        $this->InPlayXImageOffset = 20;

        $this->Traits = [
            clienttranslate('Camaraderie'),
            clienttranslate('Revelry')
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> At the beginning of Dusk • Each player must choose one of their characters here. Those characters each heal a wound and do not move <b>Home</b> during Dusk. <i>(Characters normally move Home during Dusk)</i></p>");

        $this->resetCard();
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::DUSK_PHASE_BEGIN_04CD11)
        {
            $playerId = (int)$game->getActivePlayerId();
            $characters = $this->getEligibleCharactersForPlayer($game, $playerId);

            $args['sourceId'] = $this->Id;
            $args['ids'] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $actionId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $actionId, $id);

        if ($state == States::DUSK_PHASE_BEGIN_04CD11)
        {
            $playerId = (int)$game->getActivePlayerId();
            $eligibleIds = array_map(
                fn($character) => $character->Id,
                $this->getEligibleCharactersForPlayer($game, $playerId)
            );

            if (! in_array($id, $eligibleIds))
            {
                throw new UserException($game->translate('You must choose one of your characters at this location.'));
            }

            $selectedCharacter = $game->theah->getCharacterById($id);
            $selectedCharacter->addCondition(Game::LET_BYGONES_BE_BYGONES);
            $game->updateCardObjectInDb($selectedCharacter);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} chose ${character_inject_code}. That character heals a wound and will not move Home during Dusk.'), [
                "card_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
                "character_inject_code" => $selectedCharacter->getInjectCode(),
            ]);

            $healEvent = EventFactory::createCharacterBeingHealedEvent(
                $selectedCharacter->Id,
                $this->Id,
                1,
                $this->getInjectCode(),
                (string)$this->Id
            );
            $game->theah->queueEvent($healEvent);

            $game->gamestate->nextState();
        }
    }

    public function eventCheck($event)
    {
        parent::eventCheck($event);

        // WHY: Block EventCardMoved (not EventCardMoving) — same dusk stay-home path as Penya (_01177)
        // and Under Cover of the Night (_02053). queueEvent catches the throw so cleanup continues.
        if ($event instanceof EventCardMoved && $event->toLocation == Game::LOCATION_PLAYER_HOME)
        {
            $card = $event->theah->getCardById($event->cardId);
            if ($card instanceof Character && $card->hasCondition(Game::LET_BYGONES_BE_BYGONES))
            {
                $card->removeCondition(Game::LET_BYGONES_BE_BYGONES);
                $card->IsUpdated = true;
                throw new UserException($event->theah->game->translate("{$this->getInjectCode()}: {$card->getInjectCode()} does not move Home during Dusk."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskPhaseBegin && $event->theah->cardInCity($this))
        {
            $theah = $event->theah;
            $game = $theah->game;

            $sql = "SELECT player_id FROM player ORDER BY turn_order";
            $rows = $game->getCollectionFromDB($sql);

            $eligiblePlayerIds = [];
            foreach ($rows as $playerId => $_)
            {
                $playerId = (int)$playerId;
                if (count($this->getEligibleCharactersForPlayer($game, $playerId)) > 0)
                {
                    $eligiblePlayerIds[] = $playerId;
                }
            }

            if (count($eligiblePlayerIds) == 0)
            {
                return;
            }

            $game->notify->all("message", clienttranslate('${card_inject_code}: Each player with a character here must choose one. Those characters each heal a wound and will not move Home during Dusk.'), [
                "card_inject_code" => $this->getInjectCode(),
            ]);

            foreach ($eligiblePlayerIds as $playerId)
            {
                $transition = EventFactory::createTransitionEvent($playerId, $this->Id, "04cd11");
                $theah->queueEvent($transition);
            }
        }
    }

    /**
     * @return Character[]
     */
    public function getEligibleCharactersForPlayer(Game $game, int $playerId): array
    {
        $characters = $game->theah->getCharactersAtLocation($this->Location);
        $characters = array_values(array_filter(
            $characters,
            fn($character) => $character->ControllerId == $playerId
        ));

        return $characters;
    }
}
