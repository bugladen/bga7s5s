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
        $playerDeckChoice = $this->tableOptions->get(Game::OPTIONS_PLAYER_DECKS);
        
        if ($playerDeckChoice === Game::OPTIONS_PLAYER_DECKS_MANUAL) 
        {
            return "pickDecks";
        }

        $starter_decks = json_decode(StarterDecks::$decksJson);

        $players = $this->game->loadPlayersBasicInfos();
        foreach ($players as $playerId => $player) 
        {
            $random_index = array_rand($starter_decks->decks);
            $chosen_deck = $starter_decks->decks[$random_index];

            $this->game->notify->player($playerId, 'message', clienttranslate('Private: You have been randomly assigned ${deck_name} as your Starter Deck.'), [
                'deck_name' => $chosen_deck->name,
            ]);

            $deck_json = addslashes(json_encode($chosen_deck));

            $sql = "UPDATE player SET deck_source = '$deck_json' WHERE player_id='$playerId'";
            $this->game->DbQuery($sql);

            //Remove the deck from the array
            $starter_decks->decks = array_filter($starter_decks->decks, fn($deck) => $deck->id !== $chosen_deck->id);
        }

        return "buildTable";
    }
}   