<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */
declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\GameFramework\Components\Deck;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

class Game extends \Bga\GameFramework\Table
{
    // Phases of the day
    final const SETUP_PHASE = 0;
    final const DAWN = 1;
    final const PLANNING = 2;
    final const HIGH_DRAMA = 3;
    final const PLUNDER = 4;
    final const DUSK = 5;

    final const THEAH_ID = 777777;
    final const PLAYERS_THAT_USED_OLES_INN = "playersThatUsedOlesInn";
    final const PLAYERS_THAT_USED_GOVERNORS_GARDEN = "playersThatUsedGovernorsGarden";

    //Game options
    final const OPTIONS_CITY_DECK = 100;
    final const OPTIONS_PLAYER_DECKS = 101;
    final const OPTIONS_PLAYER_DECKS_MANUAL = 0;
    final const OPTIONS_PLAYER_DECKS_RANDOM = 1;

    //Card locations
    final const LOCATION_CITY_DECK = 'City Deck';
    final const LOCATION_CITY_DISCARD = 'City Discard';
    final const LOCATION_CITY_DOCKS = 'City Docks';
    final const LOCATION_CITY_FORUM = 'City Forum';
    final const LOCATION_CITY_BAZAAR = 'The Grand Bazaar';
    final const LOCATION_CITY_OLES_INN = "Ole's Inn";
    final const LOCATION_CITY_GOVERNORS_GARDEN = "Governor's Garden";
    final const LOCATION_PLAYER_HOME = 'Player Home';
    final const LOCATION_APPROACH = 'Approach';
    final const LOCATION_HAND = 'hand';
    final const LOCATION_PURGATORY = 'Purgatory';
    final const LOCATION_PERMANENTLY_HIDDEN = 'Permanently Hidden';
    final const LOCATION_DUELING_LINE = 'Dueling Line';

    //Global variable names
    final const FIRST_PLAYER = "firstPlayer";
    final const CURRENT_PLAYER = "currentPlayer";
    final const DAY = "day";
    final const TURN_PHASE = "turnPhase";
    final const PLAYER_COUNT = "playerCount";
    final const DEBUG_INCLUDE_CITY_CARD = "debugIncludeCityCard";
    final const STAT_COMBAT = "Combat";
    final const STAT_FINESSE = "Finesse";
    final const STAT_INFLUENCE = "Influence";
    final const STAT_RESOLVE = "Resolve";
    final const PASS_COUNT = "passCount";
    final const MULTI_STATE_INITIATING_PLAYER = "multiStateInitiatingPlayer";
    final const EXTRA_ACTIONS = "extraActions";

    //Conditions
    final const ADVERSARY_OF_YEVGENI = "Adversary of Yevgeni";
    final const CRYSTAL_EYE_TARGET = "Crystal Eye Target";
    final const CATS_EMBARGO_TARGET = "Cats Embargo Target";
    final const MARYAM_BENU_PLEROMA_ABILITY_USED = "Maryam Benu Pleroma Ability Used";
    final const INDOMITABLE_WILL_CONDITION = "Indomitable Will Condition";
    
    //Equip global variables
    final const SMUGGLED_ITEM_ATTACHMENT_ID = 'smuggledItemId';
    
    final const EQUIP_TYPE = "equipType";
    final const NORMAL_EQUIP_TYPE = 0;
    final const SMUGGLED_ITEM_EQUIP_TYPE = 1;
    final const LETS_HAGGLE_EQUIP_TYPE = 2;
    
    //Recruit global variables
    final const RECRUIT_TYPE = "recruitType";
    final const NORMAL_RECRUIT_TYPE = 0;
    final const KASPAR_RECRUIT_TYPE = 1;
    final const CIRILO_RECRUIT_TYPE = 2;

    //Pressure global variables
    final const PRESSURING_PLAYER = "pressuringPlayer";
    final const CLAUD_ID = "claudeId"; //When Claude is in play, this is the ID of the card that caused the claim type
    final const CONSTANZO_ID = "constanzoId"; //When Contanzo is in play, this is the ID of the card that caused the claim type
    final const PRESSURE_STAT = "pressureStat";
    final const PRESSURE_TYPE = "pressureType";
    final const PRESSURE_BONUS = "pressureBonus";
    final const IS_BASIC_CLAIM_ACTION = "isBasicClaimAction";
    final const NORMAL_PRESSURE_TYPE = 0;
    //These must be binary flags
    final const CLAUDE_PRESSURE_TYPE = 1;
    final const CAPTAINS_COAT_PRESSURE_TYPE = 2;
    final const REPUTATION_MERITEE_PRESSURE_TYPE = 4;
    final const TABARD_PRESSURE_TYPE = 8;
    final const CONSTANZO_PRESSURE_TYPE = 16;
    final const CONTEMPT_AND_HATRED_PRESSURE_TYPE = 32;
    final const PACK_TACTICS_PRESSURE_TYPE = 64;
    final const PULL_THE_STRAND_PRESSURE_TYPE = 128;

    //Player action global variables
    //Delete these in stNextPlayer
    final const DISCOUNT = "discount";
    final const DISCOUNT_EXPLAINATIONS = "discountExplanations";
    final const CHOSEN_OPPONENT = "chosenOpponent";
    final const CHOSEN_CARD = "chosenCard";
    final const CHOSEN_CARD_COST = "chosenCardCost";
    final const NEXT_COMBAT_CARD = "nextCombatCard";
    final const CHOSEN_LOCATION = "chosenLocation";
    final const CHOSEN_ACTION = "chosenAction";
    final const CHOSEN_PERFORMER = "chosenPerformer";
    final const PERFORMER_PARLEYED = "performerParleyed";
    final const CHOSEN_ATTACHMENT = "chosenAttachment";
    final const CHOSEN_TARGET = "chosenTarget";
    final const CHOSEN_TECHNIQUE_IS_MAIN = "chosenTechniqueIsMain";
    final const CHOSEN_TECHNIQUE = "chosenTechnique";
    final const CHOSEN_MANEUVER = "chosenManeuver";
    final const CHALLENGER_THREAT = "challengerThreat";
    final const DEFENDER_THREAT = "defenderThreat";
    final const DEFENDER_THREAT_IS_LETHAL = "defenderThreatIsLethal";
    final const CHALLENGE_ACCEPTED = "challengeAccepted";
    final const TRANSITION_SOURCE_ID = "transitionSourceId";
    final const TRANSITION_INTERNAL_ID = "transitionInternalId";
    final const REACTION_ID = "reactionId";
    final const REVEALED_CARDS = "revealedCards";
    final const ABNORMAL_FLOW = "abnormalFlow";

    //Challenge global variables
    final const CHALLENGE_CANCELLED = "challengeCancelled";
    final const CHALLENGE_TYPE = "challengeType";
    final const NORMAL_CHALLENGE_TYPE = 0;
    final const TRISKELION_CHALLENGE_TYPE = 1;
    final const EPEE_SANGLANTE_CHALLENGE_TYPE = 2;
    final const CAVALIER_HAT_CHALLENGE_TYPE = 3;
    final const DEFENDING_HONOR_CHALLENGE_TYPE = 4;
    final const LEGENDARY_REPUTATION_CHALLENGE_TYPE = 5;
    final const DANIELA_DEITRICH_CHALLENGE_TYPE = 6;
    final const MOVE_ALONG_CHALLENGE_TYPE = 7;
    final const SERVO_SCARPA_CHALLENGE_TYPE = 8;
    final const VERONICAS_GUILLE_CHALLENGE_TYPE = 9;
    final const VALERI_MIKHAILOV_CHALLENGE_TYPE = 10;
    final const IRON_AND_VELVET_CHALLENGE_TYPE = 11;

    //Duel global variables
    //Duel Names
    //Delete these at the end of the duel
    final const DUEL_TYPE = "duelType";
    final const DUEL_CURRENT_PLAYER = "duelCurrentPlayer";
    final const NORMAL_DUEL_TYPE = 0;
    final const VLADISLAV_DUEL_TYPE = 1;
    final const CHALLENGE_STAT = "ChallengeStat";
    final const DUEL_CHALLENGER = "Challenger";
    final const DUEL_DEFENDER = "Defender";
    final const IN_DUEL = "inDuel";
    final const DUEL_ID = "duelId";
    final const DUEL_ROUND = "duelRound";
    final const DUEL_GAMBLED = "duelGambled";
    final const GAMBLE_REVEAL_COUNT = "gambleRevealCount";
    final const GAMBLE_REVEAL_EXPLANATIONS = "gambleRevealExplanations";

    final const ROLL_THE_BONES_ACTIVATED = "rollTheBonesActivated";
    final const GAMBLE_TYPE = "gambleType";
    final const GAMBLE_TYPE_NORMAL = 0;
    final const GAMBLE_TYPE_ROLL_THE_DICE = 1;

    //Pay state global variables
    final const PAY_STATE_IN_HAND_ACTION = 0;
    final const PAY_STATE_EQUIP_ATTACHMENT = 1;
    final const PAY_STATE_USE_MANEUVER_FROM_COMBAT_CARD = 2;
    final const PAY_STATE_IN_HAND_REACTION = 3;

    use DeckTrait;
    use StatesTrait;
    use FrameworkActionsTrait;
    use ArgumentsTrait;
    use DebugTrait;
    use UtilitiesTrait;
    use ZombieTrait;

    private Deck $cards;

    public Theah $theah;

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
            Game::DAY => 10,
            Game::TURN_PHASE => 11,
        ]);

        $this->cards = $this->deckFactory->createDeck('card');
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
        //Progression will only happen each day.  There are 5 days in the game.
        $day = (int) $this->getGameStateValue(Game::DAY);
        return round(($day - 1) / 5 * 100);
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
    protected function getAllDatas(): array
    {
        $this->theah->buildCity();

        // WARNING: We must only return information visible by the current player.
        $currentPlayerId = $this->getCurrentPlayerId();

        $players = $this->getCollectionFromDb("SELECT player_id, player_score score, leader_card_id FROM player");
        
        // Add the leader card into the player array
        foreach ($players as $player_id => $player) {
            if ($player['leader_card_id'] != null)
            {
                $card = $this->theah->getCardById($player['leader_card_id']);
                $player['leader'] = $card->getPropertyArray($this);
            }
            $location = $this->getPlayerDiscardDeckName($player_id);
            $player['discard'] = $this->getCardPropertiesInLocation($location);

            $location = $this->getPlayerLockerName($player_id);
            $player['locker'] = $this->getCardPropertiesInLocation($location);

            $player['handCount'] = count($this->cards->getPlayerHand($player_id));

            //Set updated player data back into the array
            $players[$player_id] = $player;
        }        
        $result["players"] = $players;

        $result["day"] = $this->getGameStateValue(Game::DAY);
        $result["turnPhase"] = (int) $this->getGameStateValue(Game::TURN_PHASE);
        $result["firstPlayer"] = $this->globals->get(Game::FIRST_PLAYER, 0);
        $result["homeCards"] = $this->theah->getCardPropertiesAtLocation(Game::LOCATION_PLAYER_HOME);
        $result["oleCards"] = $this->theah->getCardPropertiesAtLocation(Game::LOCATION_CITY_OLES_INN);
        $result["dockCards"] = $this->theah->getCardPropertiesAtLocation(Game::LOCATION_CITY_DOCKS);
        $result["forumCards"] = $this->theah->getCardPropertiesAtLocation(Game::LOCATION_CITY_FORUM);
        $result["bazaarCards"] = $this->theah->getCardPropertiesAtLocation(Game::LOCATION_CITY_BAZAAR);
        $result["gardenCards"] = $this->theah->getCardPropertiesAtLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);

        $result["approachDeck"] = $this->getCardPropertiesInLocation(Game::LOCATION_APPROACH, $currentPlayerId);
        $result["factionHand"] = $this->getCardPropertiesInLocation(Game::LOCATION_HAND, $currentPlayerId);

        $result['cityDiscard'] = $this->getCardPropertiesInLocation(Game::LOCATION_CITY_DISCARD);

        $result["locationReknown"] = $this->theah->getCityLocationReknown();
        $result["locationControllers"] = $this->theah->getCityLocationControllers();

        $inDuel = $this->globals->get(Game::IN_DUEL, false);
        $result["inDuel"] = $inDuel;
        if ($inDuel)
        {
            $round = $this->globals->get(Game::DUEL_ROUND);
            $result["duelRound"] = $round;

            $performerId = $this->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $this->getCardObjectFromDb($performerId);
            $result["challengingPlayerId"] = $performer->ControllerId;

            $targetId = $this->globals->get(Game::CHOSEN_TARGET);
            $target = $this->getCardObjectFromDb($targetId);
            $result["defendingPlayerId"] = $target->ControllerId;
    
            $result["duelRounds"] = $this->getDuelRows();
        }

        return $result;
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

        $this->setGameStateInitialValue(Game::DAY, 0);
        $this->setGameStateInitialValue(Game::TURN_PHASE, Game::SETUP_PHASE);

        $playerCount = count($players);
        $this->globals->set(Game::PLAYER_COUNT, $playerCount);

        //Setup the reknown for the city locations
        $this->setReknownForLocation(Game::LOCATION_CITY_DOCKS, 0);
        $this->setReknownForLocation(Game::LOCATION_CITY_FORUM, 0);
        $this->setReknownForLocation(Game::LOCATION_CITY_BAZAAR, 0);
        if ($playerCount > 2) {
            $this->setReknownForLocation(Game::LOCATION_CITY_OLES_INN, 0);
        }
        if ($playerCount > 3) {
            $this->setReknownForLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN, 0);
        }

        //Setup the controller for the city locations
        $this->setControllerForLocation(Game::LOCATION_CITY_DOCKS, null);
        $this->setControllerForLocation(Game::LOCATION_CITY_FORUM, null);
        $this->setControllerForLocation(Game::LOCATION_CITY_BAZAAR, null);
        if ($playerCount > 2) {
            $this->setControllerForLocation(Game::LOCATION_CITY_OLES_INN, null);
        }
        if ($playerCount > 3) {
            $this->setControllerForLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN, null);
        }

        //Set the initial duel round number
        $this->globals->set(Game::DUEL_ID, 0);

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

    protected function zombieTurn(array $state, int $active_player): void
    {
        $this->doZombieTurn($state, $active_player);
    }

    public function translate($text) 
    {
        return self::_($text);
    }
}
