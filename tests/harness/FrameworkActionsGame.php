<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\ArgumentsTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\FrameworkActionsTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

/**
 * StatesTraitGame + the real FrameworkActionsTrait. The act* handlers under test are the
 * production code; everything they reach for outside Theah / EventFactory (BGA Deck, raw SQL,
 * player table) is stubbed here and RECORDED so suites assert on behaviour.
 *
 * WHY extend StatesTraitGame: stHighDramaChallengeActionTechniqueActivated-style handlers call
 * StatesTrait::stIssueChallenge directly, so the same class needs both traits. Trait methods
 * override FakeGame's recording stubs of actRecruitMercenary / actGambleCardChosen, which is
 * exactly what we want here (card suites keep the stubs through plain FakeGame).
 */
class FrameworkActionsGame extends StatesTraitGame
{
    use FrameworkActionsTrait;
    // WHY: choose-next button labels live on ArgumentsTrait. FakeGame does not include that trait.
    use ArgumentsTrait;

    /** @var int result of `SELECT count(gambled) FROM duel_round ...` (gambles already spent this duel) */
    public int $gamblesCount = 0;

    /** @var int result of `SELECT count(*) FROM duel_round_combat_card ...` */
    public int $combatCardCount = 0;

    /** @var int result of `SELECT count(*) FROM duel_round_technique ... technique_is_main = 1` */
    public int $playedTechniquesCount = 0;

    /**
     * cardId => location the BGA Deck row says the card is in. WHY: actGambleCardChosen trusts
     * the deck row over the serialized card; tests make them disagree to prove the repair.
     *
     * @var array<int, string>
     */
    public array $deckRowLocations = [];

    /**
     * When set, hasEquipRestrictions reports this as the reason an equip is illegal.
     * WHY: FakeGame's stub always answers "no restriction" (the real method currently does too),
     * but actHighDramaEquipAttachment's rejection path still has to be provable.
     */
    public ?string $equipRestriction = null;

    public function hasEquipRestrictions(\Bga\Games\SeventhSeaCityOfFiveSails\cards\Character $character, \Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment $attachment): array
    {
        return $this->equipRestriction === null ? [false, ''] : [true, $this->equipRestriction];
    }

    public function __construct()
    {
        parent::__construct();

        // WHY: replaces StatesTraitGame's count-only hand stub. The act* handlers validate real
        // card ids (hand attachments, gamble picks, dusk discards), so rows come from the same
        // in-memory "DB" (dbCards) as getCardObjectFromDb.
        $this->cards = new class($this) {
            public function __construct(private FrameworkActionsGame $game)
            {
            }

            public function getCardsInLocation($location, $locationArg = null): array
            {
                if ($location === Game::LOCATION_PURGATORY && $this->game->purgatoryRows !== []) {
                    return $this->game->purgatoryRows;
                }
                if ($location === Game::LOCATION_DUELING_LINE && $this->game->duelingLineRows !== []) {
                    return $this->game->duelingLineRows;
                }

                $rows = [];
                foreach ($this->game->dbCards as $card) {
                    if ($card->Location !== $location) {
                        continue;
                    }
                    if ($locationArg !== null && (int) $card->ControllerId !== (int) $locationArg) {
                        continue;
                    }
                    $rows[$card->Id] = ['id' => $card->Id, 'location' => $location, 'location_arg' => $card->ControllerId];
                }
                return $rows;
            }

            public function getCard($id): ?array
            {
                $id = (int) $id;
                if (isset($this->game->deckRowLocations[$id])) {
                    return ['id' => $id, 'location' => $this->game->deckRowLocations[$id]];
                }
                $card = $this->game->dbCards[$id] ?? null;
                return $card === null ? null : ['id' => $id, 'location' => $card->Location];
            }

            public function insertCardOnExtremePosition($cardId, $location, $bOnTop): void
            {
                $this->game->deckInserts[] = ['id' => (int) $cardId, 'deck' => (string) $location, 'onTop' => (bool) $bOnTop];
            }
        };
    }

    // WHY: StatesTraitGame only routes the duel-state SQL shapes. ArgumentsTrait::argsChooseDuelAction
    // and act* handlers add count queries; everything else falls through to the parent routing.
    public function getUniqueValueFromDB(string $sql): mixed
    {
        if (preg_match('/count\s*\(\s*gambled\s*\)\s+from\s+duel_round/i', $sql)) {
            return $this->gamblesCount;
        }
        if (preg_match('/count\s*\(\s*\*\s*\)\s+from\s+duel_round_combat_card/i', $sql)) {
            return $this->combatCardCount;
        }
        if (preg_match('/count\s*\(\s*\*\s*\)\s+from\s+duel_round_technique/i', $sql)) {
            return $this->playedTechniquesCount;
        }
        return parent::getUniqueValueFromDB($sql);
    }

    // WHY mirrored instead of `use UtilitiesTrait`: importing the whole trait would override every
    // FakeGame stub the card suites rely on (loadPlayersBasicInfos, pressureLocation, ...). These
    // four are tiny and mirror UtilitiesTrait line for line; keep them in sync if it changes.

    public function getExtraActionPerformerId(): ?int
    {
        $id = (int) $this->globals->get(Game::EXTRA_ACTION_PERFORMER, 0);

        return $id > 0 ? $id : null;
    }

    public function assertIsExtraActionPerformer(int $characterId): void
    {
        $lockedId = $this->getExtraActionPerformerId();
        if ($lockedId !== null && $lockedId !== $characterId) {
            throw new \Bga\GameFramework\UserException($this->translate("You must perform this action with the same character."));
        }
    }

    /**
     * @param int[] $performerIds
     * @return int[]
     */
    public function filterPerformerIdsForExtraAction(array $performerIds): array
    {
        $lockedId = $this->getExtraActionPerformerId();
        if ($lockedId === null) {
            return $performerIds;
        }

        return in_array($lockedId, $performerIds, true) ? [$lockedId] : [];
    }

    public function handHasAttachments(int $playerId)
    {
        foreach ($this->cards->getCardsInLocation(Game::LOCATION_HAND, $playerId) as $handCard) {
            if ($this->getCardObjectFromDb($handCard['id']) instanceof \Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment) {
                return true;
            }
        }
        return false;
    }

    /** @return list<string> SQL passed to DbQuery that matches $needle (case-insensitive). */
    public function queriesMatching(string $needle): array
    {
        return array_values(array_filter($this->dbQueries, fn(string $sql) => stripos($sql, $needle) !== false));
    }
}
