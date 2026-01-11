<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTableSetup;

class _01006 extends Leader implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Don Constanzo Scarpa";
        $this->Image = "01006.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 6;

        $this->initializeFaction("Vodacce");
        $this->Title = "Unrepentant Patriarch";
        $this->Resolve = 7;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 3;
        $this->CrewCap = 6;
        $this->Panache = 6;
       
        $this->Traits = [
            "Leader",
            "Villain",
            "Red Hand",
            "Vodacce",
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01006(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventTableSetup)
        {
            $game = $event->theah->game;
            $game->notify->all("message", clienttranslate('${don_inject_code}: ${player_name} is choosing a Red Hand Thug from their Faction Deck to reveal and place in their Hand.'), [
                'don_inject_code' => $this->getInjectCode(),
                'player_name' => $game->getPlayerNameById($this->ControllerId),
            ]);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "01006");
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventPressureOccuring)
        {
            $game = $event->theah->game;
            $characters = $game->theah->getCharactersAtLocation($event->location);
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $this->ControllerId && $character->hasTrait("Thug"));
            if (count($characters) > 0)
            {
                $game->notify->all("message", clienttranslate('There is a Thug at the pressure location. ${don_inject_code} will add +1 to ${player_name}\'s value for each Pressure Type.'), [
                    'don_inject_code' => $this->getInjectCode(),
                    'player_name' => $game->getPlayerNameById($this->ControllerId),
                ]);

                $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::CONSTANZO_PRESSURE_TYPE);
                $game->globals->set(Game::CONSTANZO_ID, $this->Id);
            }
    }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::SETUP_TABLE_01006)
        {
            $args['performerIds'] = $this->Id;

            $deck = $game->getGameDeckObject();
            $deckName = $game->getPlayerFactionDeckName($this->ControllerId);
            $cards = $deck->getCardsInLocation($deckName);
            $thugs = [];

            foreach ($cards as $cardObject)
            {
                $card = $game->theah->getCardById($cardObject['id']);
                if ($card->HasTrait("Red Hand") && $card->HasTrait("Thug"))
                {
                    $thugs[] = $card->getPropertyArray($game);
                }
            }

            $args['thugs'] = $thugs;
        }

        if ($state == States::SETUP_TABLE_01006_2)
        {
            $id = $game->globals->get(Game::CHOSEN_CARD);
            $card = $game->theah->getCardById($id);
            $args['card'] = $card->getPropertyArray($game);
        }

        return $args;
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::SETUP_TABLE_01006)
        {
            $deck = $game->getGameDeckObject();
            $deckName = $game->getPlayerFactionDeckName($this->ControllerId);
            $cards = $deck->getCardsInLocation($deckName);

            $count = 0;
            foreach ($cards as $cardObject)
            {
                $card = $game->theah->getCardById($cardObject['id']);
                if ($card->HasTrait("Red Hand") && $card->HasTrait("Thug"))
                {
                    $count++;
                }
            }

            if ($count > 0)
            {
                throw new \BgaUserException($game->translate("There are Red Hand Thugs in your Faction Deck."));
            }

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::SETUP_TABLE_01006)
        {
            $card = $game->theah->getCardById($id);
            if ($card == null)
            {
                throw new \BgaUserException($game->translate("Invalid card ID."));
            }

            $deckName = $game->getPlayerFactionDeckName($this->ControllerId);
            if ($card->Location != $deckName)
            {
                throw new \BgaUserException($game->translate("Card is not in your Faction Deck."));
            }

            $game->globals->set(Game::MULTI_STATE_INITIATING_PLAYER, $this->ControllerId);
            $game->globals->set(Game::CHOSEN_CARD, $card->Id);

            $cardEvent = EventFactory::createCardAddedToHandEvent($this->ControllerId, $card->Id);
            $game->theah->queueEvent($cardEvent);

            $game->notify->all("message", clienttranslate('${player_name} revealed ${card_inject_code}.'), [
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "card_inject_code" => $card->getInjectCode(),
            ]);

            $game->gamestate->nextState("cardChosen");
        }
    }

}