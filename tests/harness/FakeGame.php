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

    final const RECRUIT_TYPE = 'recruitType';
    final const NORMAL_RECRUIT_TYPE = 0;
    final const CIRILO_RECRUIT_TYPE = 2;

    final const CONSTANZO_ID = 'constanzoId';
    final const PRESSURE_TYPE = 'pressureType';
    final const NORMAL_PRESSURE_TYPE = 0;
    final const CONSTANZO_PRESSURE_TYPE = 16;

    final const CHOSEN_CARD = 'chosenCard';
    final const CHOSEN_PERFORMER = 'chosenPerformer';
    final const CHOSEN_ACTION = 'chosenAction';
    final const TRANSITION_INTERNAL_ID = 'transitionInternalId';
    final const ABNORMAL_FLOW = 'abnormalFlow';
    final const MULTI_STATE_INITIATING_PLAYER = 'multiStateInitiatingPlayer';
    final const PASS_COUNT = 'passCount';
    final const EQUIP_TYPE = 'equipType';
    final const SMUGGLED_ITEM_EQUIP_TYPE = 1;
    final const FIRST_PLAYER = 'firstPlayer';

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

    /** @var array<int, string> */
    public array $playerNames = [
        1 => 'Player One',
        2 => 'Player Two',
    ];

    public function __construct()
    {
        $this->globals = new FakeGlobals();
        $this->notify = new FakeNotify();
        $this->gamestate = new FakeGamestate();
        $this->globals->set(self::PRESSURE_TYPE, self::NORMAL_PRESSURE_TYPE);
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
        return false;
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
}
