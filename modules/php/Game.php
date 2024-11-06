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

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

class Game extends \Table
{
    // Phases of the day
    final const SETUP_PHASE = 0;
    final const DAWN = 1;
    final const PLANNING = 2;
    final const HIGH_DRAMA = 3;
    final const PLUNDER = 4;
    final const DUSK = 5;

    //Card locations
    final const LOCATION_CITY_DECK = 'City Deck';
    final const LOCATION_CITY_DOCKS = 'City Docks';
    final const LOCATION_CITY_FORUM = 'City Forum';
    final const LOCATION_CITY_BAZAAR = 'The Grand Bazaar';
    final const LOCATION_CITY_OLES_INN = "Ole's Inn";
    final const LOCATION_CITY_GOVERNORS_GARDEN = "Governor's Garden";
    final const LOCATION_PLAYER_HOME = 'Player Home';

    use DeckTrait;
    use StatesTrait;
    use ActionsTrait;
    use ArgumentsTrait;

    private \Deck $cards;
    private Theah $theah;

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

        $this->theah = new Theah($this);
    }
       
    /**
     * Player action, example content.
     *
     * In this scenario, each time a player plays a card, this method will be called. This method is called directly
     * by the action trigger on the front side with `bgaPerformAction`.
     *
     * @throws BgaUserException
     */

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
