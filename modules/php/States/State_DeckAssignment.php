<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\StarterDecks;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_DeckAssignment extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::DECK_ASSIGNMENT,
            type: StateType::GAME,
            name: "deckAssignment",

            // optional
            transitions: [
                "pickDecks" => States::PICK_DECKS,
                "buildTable" => States::BUILD_TABLE,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId)
    {
        $isTournament = $this->game->bga->tournament->isTournament();
        $players = $this->game->loadPlayersBasicInfos();

        // Restore any decks already stored for this tournament. Track which
        // players still need a deck so the manual/random branches can handle them.
        $playersNeedingDeck = $players;
        if ($isTournament)
        {
            foreach ($players as $playerId => $player)
            {
                $deck = $this->game->bga->tournament->retrievePlayerGameData($playerId, 'deck_source');
                if ($deck)
                {
                    // WHY: framework decodes JSON on retrieve, so $deck is already
                    // the structured deck. Re-encode for the SQL column.
                    $deck_json = addslashes(json_encode($deck));
                    $sql = "UPDATE player SET deck_source = '$deck_json' WHERE player_id='$playerId'";
                    $this->game->DbQuery($sql);

                    $deck_name = is_array($deck) ? ($deck['name'] ?? '') : ($deck->name ?? '');
                    $this->game->notify->player($playerId, 'message', clienttranslate('Private: Deck ${deck_name} is restored for the tournament.'), [
                        'deck_name' => $deck_name,
                    ]);

                    unset($playersNeedingDeck[$playerId]);
                }
            }

            if (empty($playersNeedingDeck))
            {
                return "buildTable";
            }
        }

        $playerDeckChoice = $this->tableOptions->get(Game::OPTIONS_PLAYER_DECKS);

        if ($playerDeckChoice === Game::OPTIONS_PLAYER_DECKS_MANUAL)
        {
            return "pickDecks";
        }

        $starter_decks = json_decode(StarterDecks::$decksJson);

        foreach ($playersNeedingDeck as $playerId => $player)
        {
            $random_index = array_rand($starter_decks->decks);
            $chosen_deck = $starter_decks->decks[$random_index];

            $this->game->notify->player($playerId, 'message', clienttranslate('Private: You have been randomly assigned ${deck_name} as your Starter Deck.'), [
                'deck_name' => $chosen_deck->name,
            ]);

            $deck_json = addslashes(json_encode($chosen_deck));

            $sql = "UPDATE player SET deck_source = '$deck_json' WHERE player_id='$playerId'";
            $this->game->DbQuery($sql);

            // Lock the random assignment in for the rest of the tournament.
            if ($isTournament)
            {
                $this->game->bga->tournament->storePlayerGameData($playerId, 'deck_source', $chosen_deck);
            }

            //Remove the deck from the array
            $starter_decks->decks = array_filter($starter_decks->decks, fn($deck) => $deck->id !== $chosen_deck->id);
        }

        return "buildTable";
    }
}   