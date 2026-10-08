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
    final const EPEE_SANGLANTE_CONDITION = "Influence Modified by Épée Sanglante";
    final const FORGED_FOR_BATTLE_CONDITION = "Finesse Modified by Forged for Battle";
    final const ADRIFT_IN_THE_WIND_CONDITION = "Finesse Modified by Adrift in the Wind";
    final const DEAL_WITH_THE_DEVIL = "Deal with the Devil";

    final const PLAYER_COUNT = 'playerCount';
    final const STAT_COMBAT = 'Combat';
    final const STAT_INFLUENCE = 'Influence';
    final const STAT_FINESSE = 'Finesse';

    final const RECRUIT_TYPE = 'recruitType';
    final const NORMAL_RECRUIT_TYPE = 0;
    final const KASPAR_RECRUIT_TYPE = 1;
    final const CIRILO_RECRUIT_TYPE = 2;

    final const CONSTANZO_ID = 'constanzoId';
    final const PRESSURE_TYPE = 'pressureType';
    final const NORMAL_PRESSURE_TYPE = 0;
    // Must match Game.php — Montaigne scheme/attachment Influence pressures.
    final const REPUTATION_MERITEE_PRESSURE_TYPE = 4;
    final const TABARD_PRESSURE_TYPE = 8;
    final const CONSTANZO_PRESSURE_TYPE = 16;

    final const CHOSEN_CARD = 'chosenCard';
    final const CHOSEN_PERFORMER = 'chosenPerformer';
    final const CHOSEN_TARGET = 'chosenTarget';
    final const CHOSEN_LOCATION = 'chosenLocation';
    final const CHOSEN_ACTION = 'chosenAction';
    final const CHOSEN_ATTACHMENT = 'chosenAttachment';
    final const CHOSEN_TECHNIQUE = 'chosenTechnique';
    final const CHOSEN_TECHNIQUE_IS_MAIN = 'chosenTechniqueIsMain';
    final const TRANSITION_INTERNAL_ID = 'transitionInternalId';
    final const ABNORMAL_FLOW = 'abnormalFlow';
    // WHY: Maneuver_01077 parks the chosen combat card then sets NEXT_COMBAT_CARD + ABNORMAL_FLOW.
    final const NEXT_COMBAT_CARD = 'nextCombatCard';
    final const MULTI_STATE_INITIATING_PLAYER = 'multiStateInitiatingPlayer';
    final const PASS_COUNT = 'passCount';
    final const EQUIP_TYPE = 'equipType';
    final const SMUGGLED_ITEM_EQUIP_TYPE = 1;
    final const FIRST_PLAYER = 'firstPlayer';
    // WHY: Action_01090 / Action_01093 / Action_01095b first-player override + extra action.
    final const EXTRA_ACTIONS = 'extraActions';
    final const OVERRIDE_AS_NOT_FIRST_PLAYER = 'overrideAsNotFirstPlayer';

    final const CHALLENGE_TYPE = 'challengeType';
    final const NORMAL_CHALLENGE_TYPE = 0;
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
    final const TORVO_ESPADA_CHALLENGE_TYPE = 15;
    final const AJA_CHALLENGE_TYPE = 18;
    final const SWORN_SWORDS_CHALLENGE_TYPE = 21;
    final const RAVEN_CHALLENGE_TYPE = 27;
    final const CELERITY_CHALLENGE_TYPE = 30;
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

    final const PRESSURING_PLAYER = 'pressuringPlayer';
    final const PRESSURE_BONUS = 'pressureBonus';
    final const PACK_TACTICS_PRESSURE_TYPE = 64;
    final const PULL_THE_STRAND_PRESSURE_TYPE = 128;

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

    public function getActivePlayerName(): string
    {
        return $this->getPlayerNameById($this->activePlayerId);
    }

    public function getActivePlayerId(): int
    {
        return $this->activePlayerId;
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

    public function DbQuery(string $sql): void
    {
        // no-op for unit tests
    }

    public function getUniqueValueFromDB(string $sql): mixed
    {
        return null;
    }

    public function getObjectFromDB(string $sql): ?array
    {
        return null;
    }

    public function getCollectionFromDB(string $sql): array
    {
        return [];
    }

    public function getCardObjectFromDb(int|string $id): ?Card
    {
        $id = (int)$id;
        return $this->dbCards[$id] ?? null;
    }

    public function characterIsInDiscardOrLocker(Character $character): bool
    {
        return $this->forceInDiscardOrLocker;
    }

    public function getPlayerFactionDeckName(int $playerId): string
    {
        return 'Deck-' . $playerId;
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
        };
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

    public function loadPlayersBasicInfos(): array
    {
        $infos = [];
        foreach ($this->playerNames as $id => $name) {
            $infos[$id] = ['player_name' => $name];
        }
        return $infos;
    }

    public function handWealthCount(int $playerId): int
    {
        return 99;
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

    public function updateCardObjectInDb(?Card $card): void
    {
        if ($card !== null) {
            $this->dbCards[$card->Id] = $card;
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
}
