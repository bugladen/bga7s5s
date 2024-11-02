<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */
declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

class Game extends \Table
{
    // Phases of the day
    const SETUP_PHASE = 0;
    const DAWN = 1;
    const PLANNING = 2;
    const HIGH_DRAMA = 3;
    const PLUNDER = 4;
    const DUSK = 5;

    //Card locations
    const LOCATION_CITY_DECK = 'City Deck';
    const LOCATION_CITY_DOCKS = 'City Docks';
    const LOCATION_CITY_FORUM = 'City Forum';
    const LOCATION_CITY_BAZAAR = 'The Grand Bazaar';
    const LOCATION_CITY_OLES_INN = "Ole's Inn";
    const LOCATION_CITY_GOVERNORS_GARDEN = "Governor's Garden";
    const LOCATION_PLAYER_HOME = 'Player Home';

    private $cards;

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If your game has options (variants), you also have to associate here a
     * label to the corresponding ID in `gameoptions.inc.php`.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([
            "day" => 10,
            "turnPhase" => 11,
        ]);

        $this->cards = $this->getNew( "module.common.deck" );
        $this->cards->init( "card" );    
    }
       
    /**
     * Player action, example content.
     *
     * In this scenario, each time a player plays a card, this method will be called. This method is called directly
     * by the action trigger on the front side with `bgaPerformAction`.
     *
     * @throws BgaUserException
     */

    public function actPickDeck(string $deck_type, string $deck_id): void
    {
        $playerId = $this->getCurrentPlayerId();

        $sql = "UPDATE player SET deck_source = '$deck_type', deck_id = '$deck_id'  WHERE player_id='$playerId'";
        $this->DbQuery($sql);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'deckPicked'); // deactivate player; if none left, transition to 'deckPicked' state
    }

    public function actPlayCard(int $card_id): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // check input values
        // $args = $this->argPlayerTurn();
        // $playableCardsIds = $args['playableCardsIds'];
        // if (!in_array($card_id, $playableCardsIds)) {
        //     throw new \BgaUserException('Invalid card choice');
        // }

        // Add your game logic to play a card here.
        // $card_name = $this->card_types[$card_id]['card_name'];

        // Notify all players about the card played.
        // $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} plays ${card_name}'), [
        //     "player_id" => $player_id,
        //     "player_name" => $this->getActivePlayerName(),
        //     "card_name" => $card_name,
        //     "card_id" => $card_id,
        //     "i18n" => ['card_name'],
        // ]);

        // at the end of the action, move to the next state
        // $this->gamestate->nextState("playCard");
    }

    public function actPass(): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Notify all players about the choice to pass.
        $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} passes'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState("pass");
    }

    /**
     * Game state arguments, example content.
     *
     * This method returns some additional information that is very specific to the `playerTurn` game state.
     *
     * @return array
     * @see ./states.inc.php
     */
    public function argAvailableDecks(): array
    {
        $starter_decks = json_decode($this->starter_decks);        
        $decks = array_map(function($deck) { 
            return [ 
                "id" => $deck->id,
                "name" => $deck->name
            ]; 
        }, $starter_decks->decks);

        return ["availableDecks" => $decks];
    }

    public function argPlanningPhase(): array
    {
        return [];
    }

    public function argPlayerTurn(): array
    {
        $player_id = (int)$this->getActivePlayerId();
        // $cards = $this->cards->getCardsInLocation('hand', $player_id);
        // $playableCardsIds = array_map(function($card) { return $card['id']; }, $cards);
        $playableCardsIds = [];

        return [
            "playableCardsIds" => $playableCardsIds,
        ];
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
//       if ($from_version <= 1404301345)
//       {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
//
//       if ($from_version <= 1405061421)
//       {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas()
    {
        // WARNING: We must only return information visible by the current player.
        $currentPlayerId = $this->getActivePlayerId();

        $players = $this->getCollectionFromDb("SELECT player_id, player_score score, leader_card_id FROM player");
        
        // Add the leader card into the player array
        foreach ($players as $player_id => $player) {
            if ($player['leader_card_id'] != null)
            {
                $card = $this->getCardObjectFromDb($player['leader_card_id']);
                $player['leader'] = $card->getPropertyArray();

                //Set updated player data back into the array
                $players[$player_id] = $player;
            }
        }
        $result["players"] = $players;

        // Get the cards at the all the home locations
        $location = self::LOCATION_PLAYER_HOME;
        $homeCardsResult = $this->getObjectListFromDB("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        $homeCards = [];
        foreach ($homeCardsResult as $homeCard) {
            $card = $this->getCardObjectFromDb($homeCard['id']);
            $homeCard['card'] = $card->getPropertyArray();
            $homeCards[] = $homeCard;
        }
        $result["homeCards"] = $homeCards;

        // Get all the cards at Ole's Inn
        $location = addslashes(self::LOCATION_CITY_OLES_INN);
        $oleCardsResult = $this->getObjectListFromDB("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        $oleCards = [];
        foreach ($oleCardsResult as $oleCard) {
            $card = $this->getCardObjectFromDb($oleCard['id']);
            $oleCard['card'] = $card->getPropertyArray();
            $oleCards[] = $oleCard;
        }
        $result["oleCards"] = $oleCards;

        // Get all the cards at the City Docks
        $location = self::LOCATION_CITY_DOCKS;
        $dockCardsResult = $this->getObjectListFromDB("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        $dockCards = [];
        foreach ($dockCardsResult as $dockCard) {
            $card = $this->getCardObjectFromDb($dockCard['id']);
            $dockCard['card'] = $card->getPropertyArray();
            $dockCards[] = $dockCard;
        }
        $result["dockCards"] = $dockCards;

        // Get all the cards at the City Forum
        $location = self::LOCATION_CITY_FORUM;
        $forumCardsResult = $this->getObjectListFromDB("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        $forumCards = [];
        foreach ($forumCardsResult as $forumCard) {
            $card = $this->getCardObjectFromDb($forumCard['id']);
            $forumCard['card'] = $card->getPropertyArray();
            $forumCards[] = $forumCard;
        }
        $result["forumCards"] = $forumCards;

        // Get all the cards at the Grand Bazaar
        $location = self::LOCATION_CITY_BAZAAR;
        $bazaarCardsResult = $this->getObjectListFromDB("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        $bazaarCards = [];
        foreach ($bazaarCardsResult as $bazaarCard) {
            $card = $this->getCardObjectFromDb($bazaarCard['id']);
            $bazaarCard['card'] = $card->getPropertyArray();
            $bazaarCards[] = $bazaarCard;
        }
        $result["bazaarCards"] = $bazaarCards;        

        // Get all the cards at the Governor's Garden
        $location = addslashes(self::LOCATION_CITY_GOVERNORS_GARDEN);
        $gardenCardsResult = $this->getObjectListFromDB("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        $gardenCards = [];
        foreach ($gardenCardsResult as $gardenCard) {
            $card = $this->getCardObjectFromDb($gardenCard['id']);
            $gardenCard['card'] = $card->getPropertyArray();
            $gardenCards[] = $gardenCard;
        }
        $result["gardenCards"] = $gardenCards;       

        // Get the approach deck for the current player
        $approachCards = $this->getCollectionFromDb("
        SELECT card_id, card_location_arg
        FROM card 
        WHERE card_location = 'approach'
        AND card_location_arg = $currentPlayerId");

        $approach = [];
        foreach ($approachCards as $cardId => $card) {
            $card = $this->getCardObjectFromDb($cardId);
            $approach[] = $card->getPropertyArray();
        }
        $result["approachDeck"] = $approach;

        $result["day"] = $this->getGameStateValue("day");
        $result["turnPhase"] = (int) $this->getGameStateValue("turnPhase");

        // TODO: Gather all information about current game situation (visible by player $current_player_id).

        return $result;
    }

    /**
     * Returns the game name.
     *
     * IMPORTANT: Please do not modify.
     */
    protected function getGameName()
    {
        return "seventhseacityoffivesails";
    }

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = [])
    {
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.

        $this->setGameStateInitialValue("day", 0);
        $this->setGameStateInitialValue("turnPhase", Self::SETUP_PHASE);

        //$converter = new JsonCardConverter();

        // Init game statistics.
        //
        // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.

        // Dummy content.
        // $this->initStat("table", "table_teststat1", 0);
        // $this->initStat("player", "player_teststat1", 0);

        // TODO: Setup the initial game situation here.

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default:
                {
                    $this->gamestate->nextState("zombiePass");
                    break;
                }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }

    public function stBuildTable() {

        // *** Create the city deck ***

        // Load the city deck JSON
        $city_decks = json_decode($this->city_decks);

        // TODO: City deck loaded should be based on option
        // Pull the city deck with id of '7s5s'
        $city = current(array_filter($city_decks->decks, 
            function($deck) {
                return $deck->id === '7s5s';
            }
        ));
        // Inject into card db
        foreach ($city->cards as $card) {
            $location = self::LOCATION_CITY_DECK;
            $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$card}', 0, '{$location}', 0)";
            $this->DbQuery($sql);

            //Store the card Id in the object, and serialize the card object to the db
            $id = $this->DbGetLastId();
            $card = $this->instantiateCard($card);
            $card->Id = $id;
            $this->updateCardObjectInDb($card);
        }
        $this->cards->shuffle(self::LOCATION_CITY_DECK);

        // Load the decks selected by the players
        $starter_decks = json_decode($this->starter_decks);
        $players = $this->loadPlayersBasicInfos();
        foreach ( $players as $playerId => $player ) {

            // Get the source and deck_id of the deck from the DB for the  player
            $result = $this->getObjectFromDB("SELECT deck_source, deck_id FROM player WHERE player_id = '$playerId'");
            $source = $result['deck_source'];
            $deck_id = $result['deck_id'];

            if ($source === 'starter') {
                $deck = current(array_filter($starter_decks->decks, 
                    function($deck) use ($deck_id) {
                        return $deck->id === $deck_id;
                    }
                ));
            }
            
            //Now that we have a deck, add the cards in the deck to the db

            // Leader
            $location = self::LOCATION_PLAYER_HOME;
            $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$deck->leader}', $playerId, '{$location}', $playerId)";
            $this->DbQuery($sql);
            $id = $this->DbGetLastId();

            //Instantiate the leader card and assign it the id from the db
            $card = $this->instantiateCard($deck->leader);
            if ($card instanceof Leader) {
                $leader = $card;
            }
            $leader->Id = $id;
            $leader->Location = $location;
            $this->updateCardObjectInDb($leader);

            //Set the id of the leader card in the player record
            $sql = "UPDATE player SET leader_card_id = $id WHERE player_id = $playerId";
            $this->DbQuery($sql);

            //Notify players about the leaders
            $this->notifyAllPlayers("playLeader", clienttranslate('${player_name} will play ${player_faction} and ${leader_name} as their leader.'), [
                "player_name" => $player['player_name'],
                "player_faction" => "<span style='font-weight:bold'>{$leader->Faction}</span>",
                "leader_name" => "<span style='font-weight:bold'>{$leader->Name}</span>",
                "player_id" => $playerId,
                "player_color" => $player['player_color'],
                "leader" => $leader->getPropertyArray(),
            ]);

            // *** Create the approach deck and send each card to the player ***
            $approachDeck = $deck->approach_deck;
            $cards = [];
            foreach ($approachDeck as $card) {
                $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$card}', $playerId, 'approach', $playerId)";
                $this->DbQuery($sql);

                //Create an instance of the card, set the ID, and save it back into the db
                $id = $this->DbGetLastId();
                $card = $this->instantiateCard($card);
                $card->Id = $id;
                $this->updateCardObjectInDb($card);

                $this->notifyPlayer($playerId, "approachCard", clienttranslate('Approach Deck: ${card_name}'), [
                    "card_name" => $card->Name,
                    "player_id" => $playerId,
                    "card" => $card->getPropertyArray(),
                ]);
                }

            // Create player's Faction deck
            $factionDeck = $deck->faction_deck;
            $cards = [];
            foreach ($factionDeck as $card) {
                $cards[] = ['type' => $card->id, 'type_arg' => $playerId, 'nbr' => $card->count];
            }
            $this->cards->createCards($cards, 'faction', $playerId);
        }

        $this->gamestate->nextState("");
    }

    public function stMorningPhase() {
        // Increment the day
        $day = $this->getGameStateValue("day") + 1;
        $this->setGameStateValue("day", $day);

        //Set the phase to morning
        $turnPhase = Self::DAWN;
        $this->setGameStateValue("turnPhase", $turnPhase);

        //Notify players that it is morning
        $this->notifyAllPlayers("dawn", clienttranslate('It is <span style="font-weight:bold">DAWN</span>, the start of Day #${day} in the city of Theah.'), [
            "day" => $day,
        ]);

        $city_locations = [self::LOCATION_CITY_DOCKS, self::LOCATION_CITY_FORUM, self::LOCATION_CITY_BAZAAR];
        if ($this->getPlayersNumber() > 2)
            array_unshift($city_locations, self::LOCATION_CITY_OLES_INN);
        if ($this->getPlayersNumber() > 3) 
            $city_locations[] = self::LOCATION_CITY_GOVERNORS_GARDEN;

        foreach ($city_locations as $location) {
            //Add a city card to each location
            $cityCard = $this->cards->getCardOnTop(self::LOCATION_CITY_DECK);
            $this->cards->moveCard($cityCard['id'], $location);

            $card = $this->getCardObjectFromDb($cityCard['id']);

            //Update the location on the card object
            $card->Location = $location;            
            $this->updateCardObjectInDb($card);

            $this->notifyAllPlayers("cityCardAddedToLocation", clienttranslate('${card_name} added to ${location} from the city deck'), [
                "card_name" => $card->Name,
                "location" => $location,
                "card" => $card->getPropertyArray()
            ]);
        }

        $this->gamestate->nextState("");
    }

    public function stPlanningPhase() {
        //Set the phase to planning
        $turnPhase = Self::PLANNING;
        $this->setGameStateValue("turnPhase", $turnPhase);

        //Notify players that it is planning phase
        $this->notifyAllPlayers("planningPhase", clienttranslate('<span style="font-weight:bold">PLANNING PHASE</span>.'), [
        ]);
        
        $this->gamestate->setAllPlayersMultiactive();
    }

    public function stMultiPlayerInit() {
        $this->gamestate->setAllPlayersMultiactive();
    }

    /**
     * Game state action, example content.
     *
     * The action method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
     */
    public function stNextPlayer(): void {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Give some extra time to the active player when he completed an action
        $this->giveExtraTime($player_id);
        
        $this->activeNextPlayer();

        // Go to another gamestate
        // Here, we would detect if the game is over, and in this case use "endGame" transition instead 
        $this->gamestate->nextState("nextPlayer");
    }

    protected function instantiateCard($cardId) : Card {

        //Pull the first to characters of the card id to get the set
        $set = substr($cardId, 0, 2);

        switch ($set) {
            case '01':
                $set = "_7s5s";
                break;
            default:
                $set = "_7s5s";
        }

        $className = "\Bga\Games\SeventhSeaCityOfFiveSails\cards\\$set\_$cardId";
        $card = new $className();

        return $card;
    }

    protected function getCardObjectFromDb($cardId) : Card {
        $data = $this->getObjectFromDB("SELECT card_serialized FROM card WHERE card_id = $cardId");
        $card = unserialize($data['card_serialized']);
        return $card;
    }

    protected function updateCardObjectInDb($card) {
        $serialized = addslashes(serialize($card));
        $sql = "UPDATE card set card_serialized = '{$serialized}' WHERE card_id = $card->Id";
        $this->DbQuery($sql);
    }
}
