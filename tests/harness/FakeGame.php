<?php

/**
 * Test stand-in for Game. Loaded instead of modules/php/Game.php.
 * WHY: Real Game extends BGA Table + huge traits; constants + a few methods
 * are enough for card/composite unit regression tests.
 */

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FakeGamestate;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FakeGlobals;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FakeNotify;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Game
{
    final const LOCATION_CITY_DECK = 'City Deck';
    final const LOCATION_CITY_DISCARD = 'City Discard';
    final const LOCATION_CITY_LOCKER = 'City Locker';
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

    final const PLAYERS_THAT_USED_OLES_INN = 'playersThatUsedOlesInn';
    // WHY mirrored: FrameworkActionsTrait::actPayForReaction reads/clears this global.
    final const INVALID_PAY_CARD_IDS = "invalidPayCardIds";
    final const PLAYERS_THAT_USED_GOVERNORS_GARDEN = 'playersThatUsedGovernorsGarden';

    final const FATES_SILENCE_CONDITION = "Fate's Silence (text box blank)";
    final const INDOMITABLE_WILL_CONDITION = "Indomitable Will Condition";
    final const HARPOON_CONDITION = "Harpooned (-1 Finesse; cannot swap or move)";
    final const SHACKLES_CONDITION = "Shackled (cannot move)";
    final const LODESTONE_CONDITION = "Lodestone (opponents cannot move Home)";
    final const UNDER_COVER_OF_THE_NIGHT = "Under Cover of the Night";
    final const DEAL_WITH_THE_DEVIL_GRANTED_MONSTER = "Deal with the Devil Granted Monster";
    final const HELPED_BY_PENYA = "Helped By Penya";
    final const ADVERSARY_OF_YEVGENI = "Adversary of Yevgeni";
    final const CRYSTAL_EYE_TARGET = "Crystal Eye Target";
    final const CATS_EMBARGO_TARGET = "Cat's Embargo Target";
    final const OLD_CATS_EMBARGO_TARGET = "Cats Embargo Target";
    final const MARYAM_BENU_PLEROMA_ABILITY_USED = "Maryam Benu Pleroma Ability Used";
    final const CARMELLA_ABILITY_USED = "Carmella Ability Used";
    final const SILVER_SPINE_ABILITY_USED = "Silver Spine Ability Used";
    final const LET_BYGONES_BE_BYGONES = "Let Bygones Be Bygones";
    final const CONTEMPT_AND_HATRED_CONDITION = "Influence Reduced by Contempt and Hatred";
    final const GIACINTO_INFLUENCE_REDUCTION_CONDITION = "Influence Reduced by Giacinto";
    final const SOLINE_EL_GATO_CONDITION = "Finesse Modified by Soline el Gato";
    final const TOMOE_SANGO_CONDITION = "Finesse Modified by Tomoe Sango";
    // WHY: _04043 destroy recreates the card and wipes AffectedCharacterId. stDuelEnd
    // flushes the adversary -1 through this global. Must match Game.php.
    final const TOMOE_SANGO_PENDING_DEBUFF_CHARACTER_ID = "tomoeSangoPendingDebuffCharacterId";
    final const EPEE_SANGLANTE_CONDITION = "Influence Modified by Épée Sanglante";
    final const FORGED_FOR_BATTLE_CONDITION = "Finesse Modified by Forged for Battle";
    final const ADRIFT_IN_THE_WIND_CONDITION = "Finesse Modified by Adrift in the Wind";
    final const DEAL_WITH_THE_DEVIL = "Deal with the Devil";

    // WHY: StatesTrait regression suites (tests/regression/framework) exercise these state-machine
    // globals / phase ids / victory types. Values must match Game.php.
    final const DAWN = 1;
    final const PLANNING = 2;
    final const HIGH_DRAMA = 3;
    final const PLUNDER = 4;
    final const VICTORY_ASSASSINATION = 1;
    final const VICTORY_DOMINANCE = 2;
    final const VICTORY_ECONOMIC = 3;
    final const VICTORY_FIFTH_DAY = 4;
    // WHY: UtilitiesTrait::recordEndOfGameStats / recordCharactersAtEndOfDayStat. Must match Game.php.
    final const STAT_DAY_ENDED = "day_ended";
    final const STAT_VICTORY_TYPE = "victory_type";
    final const STAT_RENOWN_ENDED = "renown_ended";
    final const STAT_CHARACTERS_DAY_1 = "characters_day_1";
    final const STAT_CHARACTERS_DAY_2 = "characters_day_2";
    final const STAT_CHARACTERS_DAY_3 = "characters_day_3";
    final const STAT_CHARACTERS_DAY_4 = "characters_day_4";
    final const STAT_CHARACTERS_DAY_5 = "characters_day_5";
    final const CHARACTERS_END_OF_DAY_RECORDED = "charactersEndOfDayRecorded";
    final const USER_PREFERENCES_AUTO_ACKNOWLEDGE_CARD_REVEALS = 110;
    final const DAY = "day";
    final const DEBUG_INCLUDE_CITY_CARD = "debugIncludeCityCard";
    final const WHEN_REVEALED_REMAINING_CARDS = "whenRevealedRemainingCards";
    final const IS_BASIC_CLAIM_ACTION = "isBasicClaimAction";
    final const NORMAL_EQUIP_TYPE = 0;
    final const PERFORMER_PARLEYED = "performerParleyed";
    final const DEFENDER_THREAT_IS_LETHAL = "defenderThreatIsLethal";
    final const DUEL_CURRENT_PLAYER = "duelCurrentPlayer";
    final const DUEL_MANUEVER_ID = "duelManeuverId";
    final const DUEL_PENDING_MANEUVER_CARD = "duelPendingManeuverCard";
    final const REACTION_ID = "reactionId";
    final const TRANSITION_SOURCE_ID = "transitionSourceId";

    final const PLAYER_COUNT = 'playerCount';
    final const STAT_COMBAT = 'Combat';
    final const STAT_INFLUENCE = 'Influence';
    final const STAT_FINESSE = 'Finesse';
    // WHY: Action_01105 Drinking Games pressures with Resolve (must match Game.php).
    final const STAT_RESOLVE = 'Resolve';

    final const RECRUIT_TYPE = 'recruitType';
    final const NORMAL_RECRUIT_TYPE = 0;
    final const KASPAR_RECRUIT_TYPE = 1;
    final const CIRILO_RECRUIT_TYPE = 2;
    // WHY: Action_04059 stamps this so the recruit flow still offers Parley. Must match Game.php (3).
    final const SILVER_TONGUE_RECRUIT_TYPE = 3;
    // WHY: Action_01035v2. Must match Game.php (4). Back routes to the v2 states.
    final const KASPAR_V2_RECRUIT_TYPE = 4;

    final const CONSTANZO_ID = 'constanzoId';
    // WHY: Reaction_01184 / Inauguration Day store the card whose location defines the
    // performer+en-garde-only pressure filter. Must match Game.php.
    final const CLAUD_ID = 'claudeId';
    final const PRESSURE_TYPE = 'pressureType';
    final const NORMAL_PRESSURE_TYPE = 0;
    // WHY: Reaction_01184 sets this binary flag; UtilitiesTrait filters the pressure set.
    // Must match Game.php (value 1).
    final const CLAUDE_PRESSURE_TYPE = 1;
    // Must match Game.php — Montaigne scheme/attachment Influence pressures.
    final const REPUTATION_MERITEE_PRESSURE_TYPE = 4;
    final const TABARD_PRESSURE_TYPE = 8;
    final const CONSTANZO_PRESSURE_TYPE = 16;
    // WHY: Action_01143 Contempt and Hatred sets this flag so pressure wins ties (UtilitiesTrait).
    final const CONTEMPT_AND_HATRED_PRESSURE_TYPE = 32;

    final const CHOSEN_CARD = 'chosenCard';
    final const CHOSEN_PERFORMER = 'chosenPerformer';
    final const CHOSEN_TARGET = 'chosenTarget';
    final const CHOSEN_LOCATION = 'chosenLocation';
    final const CHOSEN_ACTION = 'chosenAction';
    final const CHOSEN_ATTACHMENT = 'chosenAttachment';
    final const CHOSEN_TECHNIQUE = 'chosenTechnique';
    final const CHOSEN_TECHNIQUE_IS_MAIN = 'chosenTechniqueIsMain';
    // WHY: Reaction_01146b / Maneuver cancel clears this global; must match Game.php.
    final const CHOSEN_MANEUVER = 'chosenManeuver';
    // WHY: Action_01106 Improvising stores the chosen discard-pile opponent here.
    final const CHOSEN_OPPONENT = 'chosenOpponent';
    // WHY: Action/Maneuver_01113 pay step reads the attachment's printed cost from globals.
    final const CHOSEN_CARD_COST = 'chosenCardCost';
    final const TRANSITION_INTERNAL_ID = 'transitionInternalId';
    // WHY: Back from an in-play performer UI reopens that UI. Must match Game.php.
    final const IN_PLAY_ACTION_TRIGGER_PENDING = "inPlayActionTriggerPending";
    final const IN_PLAY_ACTION_ENTRY_TRANSITION = "inPlayActionEntryTransition";
    final const IN_PLAY_ACTION_ENTRY_PLAYER_ID = "inPlayActionEntryPlayerId";
    final const ABNORMAL_FLOW = 'abnormalFlow';
    // WHY: Maneuver_01077 parks the chosen combat card then sets NEXT_COMBAT_CARD + ABNORMAL_FLOW.
    final const NEXT_COMBAT_CARD = 'nextCombatCard';
    final const MULTI_STATE_INITIATING_PLAYER = 'multiStateInitiatingPlayer';
    final const PASS_COUNT = 'passCount';
    final const EQUIP_TYPE = 'equipType';
    // WHY: Action_01187 sets SMUGGLED_ITEM_ATTACHMENT_ID before the equip chooser so
    // FrameworkActionsTrait can destroy Smuggled Item on confirm. Must match Game.php.
    final const SMUGGLED_ITEM_ATTACHMENT_ID = 'smuggledItemId';
    final const SMUGGLED_ITEM_EQUIP_TYPE = 1;
    // WHY: Action_01147 sets EQUIP_TYPE to Let's Haggle before pay; _01147::getEquipDiscount
    // keys off CHOSEN_ACTION + this type. Must match Game.php.
    final const LETS_HAGGLE_EQUIP_TYPE = 2;
    // WHY: Scheme::getEquipDiscount compares action owner id to THEAH_ID for city actions.
    // Must match Game.php.
    final const THEAH_ID = 777777;
    final const FIRST_PLAYER = 'firstPlayer';
    // WHY: Action_01090 / Action_01093 / Action_01095b first-player override + extra action.
    final const EXTRA_ACTIONS = 'extraActions';
    // WHY: Action_03032 locks the optional follow-up to the wounded performer. Must match Game.php.
    final const EXTRA_ACTION_PERFORMER = 'extraActionPerformer';
    final const OVERRIDE_AS_NOT_FIRST_PLAYER = 'overrideAsNotFirstPlayer';

    // WHY: Maneuver_01114 Roll the Bones sets these before computing reveal count (globals, not instance flags).
    final const GAMBLE_TYPE = 'gambleType';
    final const GAMBLE_TYPE_NORMAL = 0;
    final const GAMBLE_TYPE_ROLL_THE_DICE = 1;
    final const GAMBLE_TYPE_FREE = 2;
    final const ROLL_THE_BONES_ACTIVATED = 'rollTheBonesActivated';
    final const ROLL_THE_BONES_CARD_ID = 'rollTheBonesCardId';
    final const GAMBLE_REVEAL_COUNT = 'gambleRevealCount';
    final const GAMBLE_REVEAL_EXPLANATIONS = 'gambleRevealExplanations';
    // WHY: Devil Jonah's Bones (_03cd05) writes this when the equipped character
    // chooses to reveal the gamble from the bottom. Real Game.php has the same constant.
    final const GAMBLE_REVEAL_FROM_BOTTOM = 'gambleRevealFromBottom';

    final const CHALLENGE_TYPE = 'challengeType';
    final const NORMAL_CHALLENGE_TYPE = 0;
    // WHY: Action_01198 Guild Triskelion stamps this; must match Game.php (= 1).
    final const TRISKELION_CHALLENGE_TYPE = 1;
    // Must match Game.php — Montaigne / Eisen challenge variants.
    final const EPEE_SANGLANTE_CHALLENGE_TYPE = 2;
    final const CAVALIER_HAT_CHALLENGE_TYPE = 3;
    // Must match Game.php — Action_01078 / Action_01083 special challenge types.
    final const DEFENDING_HONOR_CHALLENGE_TYPE = 4;
    final const LEGENDARY_REPUTATION_CHALLENGE_TYPE = 5;
    final const DANIELA_DEITRICH_CHALLENGE_TYPE = 6;
    final const MOVE_ALONG_CHALLENGE_TYPE = 7;
    final const SERVO_SCARPA_CHALLENGE_TYPE = 8;
    final const VERONICAS_GUILLE_CHALLENGE_TYPE = 9;
    // WHY: Theah::interventionCheck evaluates this whole else-if chain once the Legendary
    // Reputation (Leaders-only) branch passes; an undefined Game:: const is a fatal Error,
    // so Action_01083's "Leader may intervene" test needs them. Must match Game.php.
    final const VALERI_MIKHAILOV_CHALLENGE_TYPE = 10;
    // WHY: Action_01131 Iron and Velvet stamps this challenge type; must match Game.php.
    final const IRON_AND_VELVET_CHALLENGE_TYPE = 11;
    // WHY: Action_02001 Andriana issues Combat challenge after successful move; must match Game.php.
    final const ANDRIANA_DONDOLOS_CHALLENGE_TYPE = 12;
    // WHY: Action_02013 Wilhelm Combat challenge-as-Sorcerer; must match Game.php.
    final const WILHELM_DUNST_CHALLENGE_TYPE = 13;
    // WHY: Action_02028 Battle of Wits Influence challenge; must match Game.php (=14).
    final const BATTLE_OF_WITS_CHALLENGE_TYPE = 14;
    final const TORVO_ESPADA_CHALLENGE_TYPE = 15;
    // WHY: Action_02049 Justice Served Cold. Must match Game.php (16, between Torvo 15 and Aja 18).
    final const JUSTICE_SERVED_COLD_CHALLENGE_TYPE = 16;
    // WHY: Action_02061 Unsanctioned Duel. Must match Game.php (17).
    final const UNSANCTIONED_DUEL_CHALLENGE_TYPE = 17;
    final const AJA_CHALLENGE_TYPE = 18;
    final const SWORN_SWORDS_CHALLENGE_TYPE = 21;
    // WHY: Action_03037. Kept off the auto-engage list. Must match Game.php (22).
    final const SANJAY_CHALLENGE_TYPE = 22;
    // WHY: Action_03042. Engage is paid on the action, so this stays off auto-engage. Must match Game.php (23).
    final const WHEN_LEAST_EXPECTED_CHALLENGE_TYPE = 23;
    // WHY: Action_03057 sets this so a refused challenge can claim. Must match Game.php (24).
    final const CENSURE_CHALLENGE_TYPE = 24;
    // WHY: Action_04002 keys intervene wound-or-draw. Must match Game.php (25).
    final const DANILO_CHALLENGE_TYPE = 25;
    // WHY: Action_04009. Off auto-engage; the defender is the challenger. Must match Game.php (26).
    final const RATTLE_THE_RIGGING_CHALLENGE_TYPE = 26;
    // WHY: Action_03058 stays off the auto-engage list. Must match Game.php (33).
    final const COURAGEOUS_CHALLENGE_TYPE = 33;
    final const RAVEN_CHALLENGE_TYPE = 27;
    // Must match Game.php — Action_04019; off auto-engage (attachment Engage only).
    final const NO_MORE_WORDS_CHALLENGE_TYPE = 28;
    // WHY: Action_04045. Off auto-engage; the scheme is identified by this type plus
    // challenger controller. Must match Game.php (29).
    final const STAND_YOUR_GROUND_CHALLENGE_TYPE = 29;
    final const CELERITY_CHALLENGE_TYPE = 30;
    // WHY: Action_04057. Off auto-engage, and not NORMAL so Back stays hidden after the
    // stat is chosen. Must match Game.php (31).
    final const HONORABLE_CHALLENGE_TYPE = 31;
    // WHY: Action_05DabneyUS01 CAD Valeri post-move Back lock (refuse allowed). Must match Game.php.
    final const VALERI_CHALLENGE_TYPE = 32;
    // WHY: Action_03008 Arrogant. Must match Game.php (34). Off the auto-engage list.
    final const ARROGANT_CHALLENGE_TYPE = 34;
    // WHY: Action_05Hulk. Engage paid at announce; refuse blocked. Must match Game.php (35).
    final const RAGE_CHALLENGE_TYPE = 35;
    // WHY: _03021 Cornered stamps this on refuse/intervene. Must match Game.php (20).
    final const CORNERED_CHALLENGE_TYPE = 20;

    // WHY: _03015 dusk Resolve and Reaction_03016a (stay in the city) read the phase.
    // Must match Game.php. Tests set values via setGameStateValue.
    final const TURN_PHASE = 'turnPhase';
    final const DUSK = 5;
    // Must match Game.php — Thug challenge never engages (off auto-engage list).
    final const DON_CONSTANZO_CHALLENGE_TYPE = 19;
    final const CHALLENGE_STAT = 'ChallengeStat';
    final const CHALLENGE_CANCELLED = 'challengeCancelled';
    // Must match Game.php — Technique_01063Swap only rewrites DUEL_CHALLENGER once the challenge is accepted.
    final const CHALLENGE_ACCEPTED = 'challengeAccepted';
    // WHY: Action_01071 first-wound steal gates on DUEL_CHALLENGER/DUEL_DEFENDER conditions.
    final const DUEL_CHALLENGER = 'Challenger';
    final const DUEL_DEFENDER = 'Defender';
    final const IN_DUEL = 'inDuel';
    final const DUEL_ID = 'duelId';
    final const DUEL_ROUND = 'duelRound';
    // WHY: Gambling Maneuver/Technique gates (Maneuver_03023, etc.) read this. Must match Game.php.
    final const DUEL_GAMBLED = 'duelGambled';
    // WHY: Maneuver_03023 / Maneuver_02039 / Reaction_04046 stash next-round threat here.
    // Must match Game.php.
    final const PENDING_CHALLENGER_THREAT = 'pendingChallengerThreat';
    final const PENDING_DEFENDER_THREAT = 'pendingDefenderThreat';
    // WHY: _01188 Vladislav sets DUEL_TYPE = VLADISLAV_DUEL_TYPE on round-1 NewRound so
    // the UI only offers End Duel. Must match Game.php.
    final const DUEL_TYPE = 'duelType';
    final const NORMAL_DUEL_TYPE = 0;
    final const VLADISLAV_DUEL_TYPE = 1;
    // WHY: Maneuver_01135 parks deferred -2 Thrust in globals so locker/clone-removed
    // copies still apply via EventHub (must match Game.php).
    final const MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS = 'mirelisRevisionPendingThrustReductions';
    // WHY: Maneuver_01129 Borets arms a rest-of-duel Maneuver/Technique ban in globals
    // (survives Miyato locker). Must match Game.php.
    final const BORETS_MANEUVER_TECHNIQUE_LOCK = 'boretsManeuverTechniqueLock';

    final const PRESSURING_PLAYER = 'pressuringPlayer';
    // WHY: Action_01105 sets PRESSURE_STAT to STAT_RESOLVE before pressureLocation transition.
    final const PRESSURE_STAT = 'pressureStat';
    final const PRESSURE_BONUS = 'pressureBonus';
    // WHY: Action_01206 Captain's Coat OR's this flag onto PRESSURE_TYPE. Must match Game.php.
    final const CAPTAINS_COAT_PRESSURE_TYPE = 2;
    final const PACK_TACTICS_PRESSURE_TYPE = 64;
    final const PULL_THE_STRAND_PRESSURE_TYPE = 128;
    // WHY: Action_02014 Kaspar's Occupation succeeds on ties; must match Game.php.
    final const KASPARS_OCCUPATION_PRESSURE_TYPE = 256;
    // WHY: Reaction_02019 Trial of Faith OR's wound-count bonus onto pressure; must match Game.php.
    final const TRIAL_OF_FAITH_PRESSURE_TYPE = 512;
    // WHY: Action_02035 Castillian Caper OR's this onto PRESSURE_TYPE before pressure; must match Game.php.
    final const CASTILLIAN_CAPER_PRESSURE_TYPE = 1024;
    // WHY: _02044 Solomonia ORs this onto PRESSURE_TYPE and stores her id. Must match Game.php.
    final const SOLOMONIA_PRESSURE_TYPE = 2048;
    final const SOLOMONIA_ID = 'solomoniaId';
    // WHY: Reaction_02050 sets PRESSURE_TYPE to this (replaces, does not OR). Must match Game.php.
    final const USSURAN_INTRIGUE_PRESSURE_TYPE = 4096;
    // WHY: Reaction_03035 ORs this onto PRESSURE_TYPE. Must match Game.php (8192).
    final const LOYAL_PRESSURE_TYPE = 8192;
    final const LOYAL_PLAYER_ID = 'loyalPlayerId';
    // WHY: Action_03040 ORs this onto PRESSURE_TYPE. Must match Game.php (16384).
    final const SOLINE_PRESSURE_TYPE = 16384;
    // WHY: Reaction_04020 ORs this onto PRESSURE_TYPE and stores the opponent. Must match Game.php (32768).
    final const VANTAGE_POINT_PRESSURE_TYPE = 32768;
    final const VANTAGE_POINT_PLAYER_ID = 'vantagePointPlayerId';
    // WHY: Reaction_04035 ORs this onto PRESSURE_TYPE and stores the pressuring player. Must match Game.php (65536).
    final const MEETING_OF_THE_MINDS_PRESSURE_TYPE = 65536;
    final const MEETING_OF_THE_MINDS_PLAYER_ID = 'meetingOfTheMindsPlayerId';
    // WHY: Reaction_04010 Use writes the actor's controller so the gamble hub can see it.
    final const UNRAVEL_THE_THREAD_CONTROLLER_ID = 'unravelTheThreadControllerId';

    // WHY: Reaction_01203 / Reaction_04031 button labels read live threat from globals.
    final const CHALLENGER_THREAT = 'challengerThreat';
    final const DEFENDER_THREAT = 'defenderThreat';

    final const CURRENT_PLAYER = 'currentPlayer';
    final const REVEALED_CARDS = 'revealedCards';
    final const DISCOUNT = 'discount';
    final const DISCOUNT_EXPLAINATIONS = 'discountExplanations';

    final const PAY_STATE_IN_HAND_ACTION = 0;
    final const PAY_STATE_EQUIP_ATTACHMENT = 1;
    final const PAY_STATE_USE_MANEUVER_FROM_COMBAT_CARD = 2;
    final const PAY_STATE_IN_HAND_REACTION = 3;
    final const PAY_STATE_RECRUIT_MERCENARY = 4;
    final const PAY_STATE_PLAY_BRUTE = 5;

    public FakeGlobals $globals;
    public FakeNotify $notify;
    public FakeGamestate $gamestate;
    public ?Theah $theah = null;

    /** @var array<int, Card> */
    public array $dbCards = [];

    /** @var list<array{id:int}> */
    public array $topFactionCards = [];

    /**
     * WHY: _01149 When Revealed peeks city deck via DeckTrait::getCardsOnTopOfCityDeck (BGA Deck).
     * Tests inject top rows here so resolve can queue CityCardAddedToLocation without Deck DB.
     * Also used by Action_01035 city-deck reveal stubs.
     *
     * @var list<array{id:int}>
     */
    public array $topCityCards = [];

    public int $playerCount = 2;
    public int $activePlayerId = 1;
    public bool $forceInDiscardOrLocker = false;

    /** Stub return for Action_01035 city-deck reveal (real path hits Deck DB). */
    public ?Card $cityDeckRevealResult = null;

    /** @var array<int, string> */
    public array $playerNames = [
        1 => 'Player One',
        2 => 'Player Two',
    ];

    /** @var array<int, int> playerId => Renown/score (Leader destroy / victory tests). */
    public array $playerScores = [];

    // WHY: Character::handleEvent(EventCharacterWounded) bumps $game->bga->playerStats;
    // _01069 tests need the non-ignored (parent) wound path to run without BGA.
    public object $bga;
    final const STAT_WOUNDS_RECEIVED = 'wounds_received';

    public function __construct()
    {
        $this->bga = new class {
            public object $playerStats;

            public function __construct()
            {
                $this->playerStats = new class {
                    public function inc(string $name, int $delta = 1, ?int $playerId = null): void
                    {
                    }
                };
            }
        };
        $this->globals = new FakeGlobals();
        $this->notify = new FakeNotify();
        $this->gamestate = new FakeGamestate();
        $this->globals->set(self::PRESSURE_TYPE, self::NORMAL_PRESSURE_TYPE);
        // WHY: getAdjacentCityLocations branches on player count; default 2-player map.
        $this->globals->set(self::PLAYER_COUNT, $this->playerCount);
    }

    // WHY: Leader.php assassination / half-Renown paths use these instead of Theah DB.
    public function getPlayerReknown(int $playerId): int
    {
        return $this->playerScores[$playerId] ?? 0;
    }

    public function setPlayerReknown(int $playerId, int $reknown): void
    {
        $this->playerScores[$playerId] = $reknown;
    }

    // WHY: Action_01064 availability reads Game::getRenownForLocation (UtilitiesTrait → globals
    // "Reknown_<location>"). Mirror it via TestTheah city-location Renown so tests use setLocationRenown().
    public function getRenownForLocation($location): int
    {
        // Adjacency lists include Player Home, which has no CityLocation / Renown.
        if ($this->theah === null || !array_key_exists($location, $this->theah->getCityLocations())) {
            return 0;
        }
        return (int)$this->theah->getCityLocation($location)->Renown;
    }

    // WHY: Action_01187 (Smuggled Item) gates on hand attachments via UtilitiesTrait.
    // FakeGame has no $cards Deck property — walk dbCards (same source as getGameDeckObject).
    public function getAttachmentsInHand(int $playerId): array
    {
        $attachments = [];
        foreach ($this->dbCards as $card) {
            if (
                $card instanceof \Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment
                && $card->Location === self::LOCATION_HAND
                && (int) $card->ControllerId === $playerId
            ) {
                $attachments[] = $card;
            }
        }
        return $attachments;
    }

    public function notifyAllPlayers(string $type, string $message, array $args = []): void
    {
        $this->notify->all($type, $message, $args);
    }

    public function translate(string $text): string
    {
        return $text;
    }

    public function getPlayerNameById(int $playerId): string
    {
        return $this->playerNames[$playerId] ?? ('Player ' . $playerId);
    }

    // WHY: _01150 notifyInterveneList / getInterveneListData include playerColor for the UI chip.
    public function getPlayerColorById(int $playerId): string
    {
        return sprintf('%06x', $playerId);
    }

    public function getActivePlayerName(): string
    {
        return $this->getPlayerNameById($this->activePlayerId);
    }

    // WHY: _01149 / Action_01134 call DeckTrait::getCardsOnTopOfCityDeck; FakeGame has no BGA Deck.
    // Tests inject rows via $topCityCards.
    public function getCardsOnTopOfCityDeck(int $nbr): array
    {
        return array_slice($this->topCityCards, 0, $nbr);
    }

    public function getActivePlayerId(): int
    {
        return $this->activePlayerId;
    }

    /** @var array<string, int> */
    public array $gameStateValues = [];

    public function getGameStateValue(string $key): int
    {
        return (int) ($this->gameStateValues[$key] ?? 0);
    }

    public function setGameStateValue(string $key, int $value): void
    {
        $this->gameStateValues[$key] = $value;
    }

    // WHY: Action_01095b's multi-player discard step (and other "each opponent discards" states)
    // asks BGA for the acting player. Tests set $currentPlayerId to the discarding opponent.
    public int $currentPlayerId = 1;

    public function getCurrentPlayerId(): int
    {
        return $this->currentPlayerId;
    }

    public function getPlayerCount(): int
    {
        return $this->playerCount;
    }

    public function setGlobalFlag(string $variable, int $flag): void
    {
        $global = (int)$this->globals->get($variable, 0);
        $this->globals->set($variable, $global | $flag);
    }

    public function isGlobalFlagSet(string $variable, int $flag): bool
    {
        $global = (int)$this->globals->get($variable, 0);
        return ($global & $flag) === $flag;
    }

    /** @var list<string> SQL passed to DbQuery (tests assert absences like Mireli gambled clear). */
    public array $dbQueries = [];

    public function DbQuery(string $sql): void
    {
        $this->dbQueries[] = $sql;
    }

    public function getUniqueValueFromDB(string $sql): mixed
    {
        return null;
    }

    public function getObjectFromDB(string $sql): ?array
    {
        return null;
    }

    // WHY: _01151 actFromCardWithIds SELECTs player rows (ORDER BY turn_order) to queue
    // opponent 01151_2 transitions. Empty [] silently skipped every opponent.
    public function getCollectionFromDB(string $sql): array
    {
        if (stripos($sql, 'from player') !== false) {
            $rows = [];
            foreach ($this->playerNames as $id => $_) {
                $rows[$id] = [
                    'player_id' => $id,
                    'schemeId' => isset($this->chosenSchemes[$id]) ? $this->chosenSchemes[$id]->Id : 0,
                ];
            }
            return $rows;
        }
        return [];
    }

    public function getCardObjectFromDb(int|string $id): ?Card
    {
        $id = (int)$id;
        return $this->dbCards[$id] ?? null;
    }

    /** @var array<int, bool> cardId => discard/locker override (when unset, use $forceInDiscardOrLocker). */
    public array $discardOrLockerByCardId = [];

    // WHY: _01143 locker-aura cleanup writes +1 Influence directly for discard/locker rows
    // (they are not in $theah->cards for IsUpdated flush). Per-card map lets tests stamp
    // only Spend-to-Locker corpses while leaving in-play Mercenaries on the event path.
    public function characterIsInDiscardOrLocker(Character $character): bool
    {
        if (array_key_exists($character->Id, $this->discardOrLockerByCardId)) {
            return $this->discardOrLockerByCardId[$character->Id];
        }
        return $this->forceInDiscardOrLocker;
    }

    // WHY: _01143 clearAuraFromAllAffected / DeckTrait locker piles use Locker-{playerId}.
    public function getPlayerLockerName($playerId): string
    {
        return 'Locker-' . $playerId;
    }

    // WHY: _01144 fewest-Renown branch SELECTs player_score from the player table.
    // Mirror $playerScores (default 0 for named players) so actFromCardWithIds can branch.
    public function getObjectListFromDb(string $sql): array
    {
        $rows = [];
        foreach ($this->playerNames as $playerId => $_) {
            $rows[] = [
                'player_id' => (int)$playerId,
                'score' => (int)($this->playerScores[$playerId] ?? 0),
            ];
        }
        usort($rows, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
        return $rows;
    }

    // WHY: Reaction_01144 / _01145 fewest-characters gates call UtilitiesTrait::getPlayerControllingFewestCharacters.
    // Mirror the real algorithm against in-RAM Theah character counts (ties → null player).
    public function getPlayerControllingFewestCharacters(): array
    {
        $players = $this->loadPlayersBasicInfos();
        $lowestCount = 999;
        $lowestPlayerId = null;
        foreach ($players as $playerId => $player) {
            $count = $this->theah !== null
                ? $this->theah->getCharacterCountByPlayerId((int)$playerId, true)
                : 0;
            if ($count == $lowestCount) {
                $lowestPlayerId = null;
            } elseif ($count < $lowestCount) {
                $lowestCount = $count;
                $lowestPlayerId = (int)$playerId;
            }
        }
        return [$lowestPlayerId, $lowestCount];
    }

    /** @var list<array{recruitId:int,payWithCards:string}> */
    public array $recruitMercenaryCalls = [];

    // WHY: Reaction_01144 pay step calls FrameworkActionsTrait::actRecruitMercenary (DB + wealth).
    // Record the call so unit tests assert wiring without BGA Deck / player wealth tables.
    // WHY no `: void`: the real FrameworkActionsTrait::actRecruitMercenary has no return type, and
    // FrameworkActionsGame (tests/regression/framework) overrides this stub with the trait.
    // PHP rejects a trait method without `: void` overriding a parent that declares it.
    public function actRecruitMercenary(int $recruitId, string $payWithCards)
    {
        $this->recruitMercenaryCalls[] = [
            'recruitId' => $recruitId,
            'payWithCards' => $payWithCards,
        ];
    }

    public function getPlayerFactionDeckName(int $playerId): string
    {
        return 'Deck-' . $playerId;
    }

    // WHY: Reaction_03006 / Reaction_03007 sink a chosen hand card onto the faction deck.
    // Real DeckTrait also refreshes the deck-count icon; tests only need the insert record
    // (callers set Card->Location themselves).
    public function insertCardOnPlayerFactionDeckExtreme(int $cardId, int $playerId, bool $onTop): void
    {
        $this->getGameDeckObject()->insertCardOnExtremePosition($cardId, $this->getPlayerFactionDeckName($playerId), $onTop);
    }

    public function getPlayerDiscardDeckName(int $playerId): string
    {
        return 'Discard-' . $playerId;
    }

    public function getCardsOnTopOfPlayerFactionDeck(int $playerId, int $count): array
    {
        return array_slice($this->topFactionCards, 0, $count);
    }

    /** @var list<array{id:int,deck:string,onTop:bool}> */
    public array $deckInserts = [];

    /** @var list<string> locations passed to Deck::shuffle (Action_02045 search/pass). */
    public array $deckShuffles = [];

    // WHY: Maneuver_01077 sinks unchosen reveal cards via insertCardOnExtremePosition;
    // Action_01038 only assigns the deck object. Record inserts for assert, no-op otherwise.
    /**
     * WHY: _01098 Forced/locker paths call getCardsOfType / getCardsInLocation on the BGA Deck.
     * Tests set this to a small stand-in; null keeps the default insert-recording deck below.
     */
    public ?object $deckOverride = null;

    public function getGameDeckObject(): object
    {
        if ($this->deckOverride !== null) {
            return $this->deckOverride;
        }
        $game = $this;
        return new class($game) {
            public function __construct(private Game $game)
            {
            }

            public function insertCardOnExtremePosition($cardId, $location, $bOnTop): void
            {
                $this->game->deckInserts[] = [
                    'id' => (int)$cardId,
                    'deck' => (string)$location,
                    'onTop' => (bool)$bOnTop,
                ];
            }

            // WHY: Action_01163 Devotion peeks the faction deck via Deck::getCardsOnTop(n, location).
            // Mirror $topFactionCards (same rows as getCardsOnTopOfPlayerFactionDeck) — location is
            // unused in the stub because tests seed only the acting player's top cards.
            public function getCardsOnTop($n, $location): array
            {
                return array_slice($this->game->topFactionCards, 0, (int)$n);
            }

            // WHY: Technique_01090 (discard-to-play branch) validates the chosen card against the
            // BGA deck's hand rows. Mirror those rows from the in-RAM dbCards so tests control the hand.
            public function getCardsInLocation($location, $locationArg = null): array
            {
                $rows = [];
                foreach ($this->game->dbCards as $card) {
                    if ($card->Location !== $location) {
                        continue;
                    }
                    if ($locationArg !== null && (int)$card->ControllerId !== (int)$locationArg) {
                        continue;
                    }
                    $rows[$card->Id] = ['id' => $card->Id, 'location' => $location, 'location_arg' => $card->ControllerId];
                }
                return $rows;
            }

            public function getPlayerHand($playerId): array
            {
                return $this->getCardsInLocation(Game::LOCATION_HAND, $playerId);
            }

            // WHY: Action_02045 shuffles the faction deck after the Dar Matushki / Poluchatel search.
            public function shuffle($location): void
            {
                $this->game->deckShuffles[] = (string) $location;
            }

            // WHY: _03041 Forced and Action_03038a ask the deck how many cards can still be drawn.
            // Unseeded locations read as empty so "no cards left" is the default.
            public function countCardsInLocation($location): int
            {
                return (int) ($this->game->deckLocationCounts[(string) $location] ?? 0);
            }
        };
    }

    /** @var array<string, int> Deck location => countCardsInLocation (faction deck / discard). */
    public array $deckLocationCounts = [];

    /** @var list<int> ids passed to actGambleCardChosen (Proper Drama steals the chooser). */
    public array $gambleCardsChosen = [];

    // WHY: The real chooser reads the BGA deck and writes duel_round. Tests only need
    // proof that Maneuver_03047a handed the chosen id through after clearing its lock.
    // WHY no `: void`: same trait-override compatibility as actRecruitMercenary above.
    public function actGambleCardChosen(int $id)
    {
        $this->gambleCardsChosen[] = $id;
    }

    /** @var list<array{playerId:int,performerId:int|null,location:string,pressureType:string}> */
    public array $pressureLocationCalls = [];

    /** @var array{0:bool,1:string,2:int} [success, totals explanation, difference] returned by pressureLocation. */
    public array $pressureLocationResult = [true, 'test totals', 1];

    // WHY: Reaction_01080 resolves pressure by calling UtilitiesTrait::pressureLocation, which needs the
    // player table + influence totals (DB). The pressure math itself is source-locked elsewhere; this stub
    // records the call and returns a scripted outcome so the reaction's own wiring can be tested.
    public function pressureLocation(int $attemptingPlayerId, ?Character $performer, string $location, string $pressureType): array
    {
        $this->pressureLocationCalls[] = [
            'playerId' => $attemptingPlayerId,
            'performerId' => $performer?->Id,
            'location' => $location,
            'pressureType' => $pressureType,
        ];
        return $this->pressureLocationResult;
    }

    public function getNextEventBatchId(): int
    {
        $id = (int)$this->globals->get('eventBatchId', 0) + 1;
        $this->globals->set('eventBatchId', $id);
        return $id;
    }

    // WHY: Action_03045 reads claim control. Real UtilitiesTrait stores it in globals.
    public function getControllerLocationName($location): string
    {
        return 'Control_' . $location;
    }

    public function getControllerForLocation($location)
    {
        return $this->globals->get($this->getControllerLocationName($location)) ?? 0;
    }

    public function setControllerForLocation($location, $playerId): void
    {
        $this->globals->set($this->getControllerLocationName($location), $playerId);
    }

    // WHY: _01150 (and similar) walk loadPlayersBasicInfos and compare $player['player_id']
    // to ControllerId — BGA rows include that key; omitting it made every player look like
    // an opponent (undefined != controller).
    public function loadPlayersBasicInfos(): array
    {
        $infos = [];
        foreach ($this->playerNames as $id => $name) {
            $infos[$id] = [
                'player_id' => $id,
                'player_name' => $name,
            ];
        }
        return $infos;
    }

    public function handWealthCount(int $playerId): int
    {
        return 99;
    }

    // WHY: Action/Maneuver_01113 pay-with-ids validates exact Wealth; mirror UtilitiesTrait.
    public function isValidWealthPayment(int $totalWealth, int $cost, bool $hasWealthCard): bool
    {
        if ($totalWealth == $cost) {
            return true;
        }
        if ($hasWealthCard && $totalWealth == $cost + 1) {
            return true;
        }
        return false;
    }

    // WHY: Action_01069 step 1 parks the discarded hand card in Purgatory via DeckTrait.
    // Record only — real parkCard moves the BGA deck row, not Card->Location.
    /** @var list<int> */
    public array $parkedCardIds = [];

    public function parkCard(int $cardId, string $holdingLocation = self::LOCATION_PURGATORY, $locationArg = 0): void
    {
        $this->parkedCardIds[] = $cardId;
    }

    public function registerDbCard(Card $card): void
    {
        $this->dbCards[$card->Id] = $card;
    }

    /** @var list<int> card ids written via updateCardObjectInDb (Breastplate leftover-threat flag). */
    public array $updatedCardObjectIds = [];

    public function updateCardObjectInDb(?Card $card): void
    {
        if ($card !== null) {
            $this->dbCards[$card->Id] = $card;
            $this->updatedCardObjectIds[] = $card->Id;
        }
    }

    // WHY: RiskAttachmentTrait::removeRiskAttachment and createRiskAttachment need
    // location updates without DeckTrait / BGA deck object.
    public function moveCard(int $cardId, string $location, $locationArg = 0, ?Card $card = null): Card
    {
        $card ??= $this->getCardObjectFromDb($cardId);
        if ($card === null && $this->theah !== null) {
            $card = $this->theah->getCardById($cardId);
        }
        if ($card === null) {
            throw new \RuntimeException("moveCard: card {$cardId} not found");
        }
        $card->Location = $location;
        $this->registerDbCard($card);
        return $card;
    }

    // WHY: Leshiye (_01126) SchemeMovedToCity sends characters Home via DeckTrait::moveCardInDeck
    // (deck row only; Location comes from the queued CardMoving event). Mirror Location update
    // so unit tests see the same in-RAM board state without BGA Deck.
    public function moveCardInDeck(int $cardId, string $location, $locationArg = 0): void
    {
        $this->moveCard($cardId, $location, $locationArg);
    }

    // WHY: Theah::setLocationCanBeClaimed dual-writes globals + CityLocation. Leshiye / IW
    // (Action_01130) call the Theah helpers; FakeGame needs the persistence half.
    public function setCanBeClaimedForLocation(string $location, bool $canBeClaimed): void
    {
        $this->globals->set('CanBeClaimed_' . $location, $canBeClaimed);
    }

    public function setCanBecomeUncontrolledForLocation(string $location, bool $canBecomeUncontrolled): void
    {
        $this->globals->set('CanBecomeUncontrolled_' . $location, $canBecomeUncontrolled);
    }

    /** @var list<array{className:string,originalCardId:int,location:string,ownerId:int,controllerId:int,targetId:int,abilityId:string}> */
    public array $createdRiskAttachments = [];

    // WHY: Technique_01096 gates its steal on UtilitiesTrait::hasEquipRestrictions; the real method
    // currently always returns [false, ""] (duplicate-slot limits moved to Reaction_AttachmentTypeLimit).
    // Mirror that so equip legality in tests is decided by Attachment::canAttachTo only.
    public function hasEquipRestrictions(Character $character, \Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment $attachment): array
    {
        return [false, ''];
    }

    /** @var array<int, Card> playerId => selected Scheme (UtilitiesTrait reads player.selected_scheme_id). */
    public array $chosenSchemes = [];

    // WHY: _01098 Forced stamps its EmbargoedCardId on the controller's chosen Scheme; the real lookup
    // is a player-table SELECT. Tests register the scheme in $chosenSchemes instead.
    public function getPlayerChosenScheme($playerId)
    {
        return $this->chosenSchemes[(int)$playerId] ?? null;
    }

    // WHY: Action_01035 reveal walks the city deck via BGA Deck; unit tests inject the Mercenary.
    public function revealFirstCardTypeFromCityDeck(int $playerId, string $type, int $sourceId = 0): ?Card
    {
        return $this->cityDeckRevealResult;
    }

    // WHY: Action_01025 (and similar) call createRiskAttachment from UtilitiesTrait;
    // FakeGame records the call so act tests can assert without createCardInLocation/DB.
    public function createRiskAttachment(
        Game $game,
        string $className,
        int $originalCardId,
        string $location,
        int $ownerId,
        int $controllerId,
        int $targetId,
        string $abilityId = ''
    ): void {
        $this->createdRiskAttachments[] = [
            'className' => $className,
            'originalCardId' => $originalCardId,
            'location' => $location,
            'ownerId' => $ownerId,
            'controllerId' => $controllerId,
            'targetId' => $targetId,
            'abilityId' => $abilityId,
        ];
    }

    /** @var list<Card> */
    public array $createdCardsInLocation = [];

    // WHY: Action_01106 Improvising clones a stolen Risk via DeckTrait::createCardInLocation
    // (DB INSERT). Mirror instantiate + place into RAM so the clone/pay transition path
    // can be asserted without BGA Deck.
    public function createCardInLocation(string $className, string $location, int $ownerId, int $controllerId): Card
    {
        $set = substr($className, 0, 2);
        $expansion = match ($set) {
            '01' => '_7s5s',
            '02' => 'tac',
            '03' => 'faf',
            '04' => 'bas',
            '05' => 'cad',
            default => '_7s5s',
        };
        $fqcn = "\\Bga\\Games\\SeventhSeaCityOfFiveSails\\cards\\{$expansion}\\_{$className}";
        /** @var Card $card */
        $card = new $fqcn();
        $id = count($this->dbCards) > 0 ? max(array_keys($this->dbCards)) + 1 : 9000;
        $card->setId($id);
        $card->OwnerId = $ownerId;
        $card->ControllerId = $controllerId;
        $card->Location = $location;
        $this->registerDbCard($card);
        if ($this->theah !== null) {
            $this->theah->addCardToWorld($card);
        }
        $this->createdCardsInLocation[] = $card;
        return $card;
    }
}
