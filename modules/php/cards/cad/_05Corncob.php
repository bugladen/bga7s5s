<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Corncob;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Corncob;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;

class _05Corncob extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = "Cobb Roy";
        $this->Title = "The Great Corn";
        $this->Image = "05Corncob.v4.3.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        // WHY: Stub CardNumber=13. Snow White scheme also uses 13 but has no HD states — 405013 is free.
        $this->CardNumber = 13;

        $this->initializeFaction("Eisen");

        $this->Resolve = 3;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            "Scoundrel",
            "Smuggler",
            "Insimore"
        ];

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Text = "<p><b>Forced:</b> At the end of planning • Discard the top 3 cards of each deck.</p>
<p><b>Covert Action:</b> Take control of a <b>Weapon</b> in any discard pile. Equip it to your character at this location, paying all costs.</p>
<p><b>Covert Technique:</b> +1 [Parry].</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Corncob(),
        ];

        $technique = new Technique_05Corncob();
        $technique->setId("Technique_05Corncob");
        $this->Techniques = [
            $technique,
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Forced: At the end of planning • Discard the top 3 cards of each deck.
        // WHY "each deck" = every player's faction deck AND the City Deck (user: City was missing).
        if ($event instanceof EventPhasePlanningEnd
            && $this->ControllerId > 0
            && ! $event->theah->game->characterIsInDiscardOrLocker($this))
        {
            $game = $event->theah->game;

            $game->notify->all("message", '${card_inject_code}: At the end of Planning, discard the top 3 cards of each deck.', [
                "card_inject_code" => $this->getInjectCode(),
            ]);

            $players = $game->loadPlayersBasicInfos();
            foreach ($players as $playerId => $player)
            {
                $playerId = (int)$playerId;
                $deckName = $game->getPlayerFactionDeckName($playerId);
                $discardName = $game->getPlayerDiscardDeckName($playerId);
                $playerName = $game->getPlayerNameById($playerId);

                $toDiscard = [];
                foreach ($game->getCardsOnTopOfPlayerFactionDeck($playerId, 3) as $cardInfo)
                {
                    $cardId = (int)$cardInfo['id'];
                    $card = $game->getCardObjectFromDb($cardId);
                    // WHY: Still-in-this-deck gate — FakeGame shares one topFactionCards list across
                    // players; after player 1 mills, those Location values must not be re-milled
                    // into player 2's discard. Real decks are per-player so this is a no-op there.
                    if ($card === null || $card->Location != $deckName)
                    {
                        continue;
                    }
                    $toDiscard[] = $card;
                }

                if (count($toDiscard) == 0)
                {
                    continue;
                }

                // WHY: One announce per deck manipulated (not a silent mill).
                $game->notify->all("message", '${card_inject_code}: Discarding the top ${count} card(s) of ${player_name}\'s deck.', [
                    "card_inject_code" => $this->getInjectCode(),
                    "count" => count($toDiscard),
                    "player_name" => $playerName,
                ]);

                foreach ($toDiscard as $card)
                {
                    // WHY: No EventFactory for deck→player-discard. Mirror Reaction_04023 / Action_01134.
                    $game->notify->all("cardAddedToPlayerDiscardPile", '${discarded_inject_code} is discarded.', [
                        "discarded_inject_code" => $card->getInjectCode(),
                        "playerId" => $playerId,
                        "card" => $card->getPropertyArray($game),
                    ]);
                    $game->moveCard($card->Id, $discardName, 0, $card);
                }
            }

            $cityToDiscard = [];
            foreach ($game->getCardsOnTopOfCityDeck(3) as $cardInfo)
            {
                $cardId = (int)$cardInfo['id'];
                $card = $game->getCardObjectFromDb($cardId);
                if ($card === null || $card->Location != Game::LOCATION_CITY_DECK)
                {
                    continue;
                }
                $cityToDiscard[] = $card;
            }

            if (count($cityToDiscard) > 0)
            {
                $game->notify->all("message", '${card_inject_code}: Discarding the top ${count} card(s) of the City Deck.', [
                    "card_inject_code" => $this->getInjectCode(),
                    "count" => count($cityToDiscard),
                ]);

                foreach ($cityToDiscard as $card)
                {
                    // WHY: Same notify+moveCard shape as Action_01134 city look/discard (no queued event mid-Forced).
                    $game->notify->all("cardAddedToCityDiscardPile", '${discarded_inject_code} is discarded.', [
                        "discarded_inject_code" => $card->getInjectCode(),
                        "playerId" => 0,
                        "card" => $card->getPropertyArray($game),
                    ]);
                    $game->moveCard($card->Id, Game::LOCATION_CITY_DISCARD, 0, $card);
                }
            }
        }
    }
}
