<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01121 extends Character
{
    // WHY: -1 Parry applies at most once per duel round across combat card / Maneuver /
    // Technique sources. Store the round we successfully reduced so a later +Parry
    // Maneuver does not get a second hit, while a 0-Parry combat card still leaves
    // room for the first positive Parry source later in the same round.
    public int $ParryReductionAppliedInRound = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ren");
        $this->Image = "01121.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 121;

        $this->initializeFaction("Ussura");
        $this->Title = clienttranslate("Graven Pendant");
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 0;

        $this->Traits = [
            clienttranslate("Hero"),
            clienttranslate("Academic"),
            clienttranslate("Duelist"),
            clienttranslate("Shenzhou")
        ];

        $this->Text = clienttranslate("<p>While Ren's adversary controls equal or more characters than you, her adversary's combat cards have -1[Parry]. (Anything less than 0 is treated as a 0.)</p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Combat / Maneuver / Technique Parry reduction is applied from EventHub
        // (see applyAdversaryParryReduction*) so Maneuver/Technique +Parry from other
        // cards is visible — card-loop order is not stable vs those handlers.

        if ($event instanceof EventDuelEnd && $this->ParryReductionAppliedInRound != 0)
        {
            $this->ParryReductionAppliedInRound = 0;
            $this->IsUpdated = true;
        }
    }

    /**
     * WHY called from EventHub after card handlers (and Unravel +1 Parry on combat):
     * Technique/Maneuver Parry is written during the card loop by the ability owner;
     * Ren must see the final Parry on the event before the DB write. Combat uses the
     * same path so Unravel's +1 is included in the once-per-round reduction window.
     */
    public static function applyAdversaryParryReductionToCombatCard(EventDuelCalculateCombatCardStats $event): void
    {
        self::tryApplyAdversaryParryReduction(
            $event->theah,
            $event->actorId,
            $event->adversaryId,
            function (_01121 $ren) use ($event): bool {
                if ($event->parry <= 0)
                {
                    return false;
                }
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s decreases her Adversary's Parry values by -1 if her controller's Adversary controls equal or more characters."),
                    $ren->getInjectCode()
                );
                $event->removeParry(1);
                return true;
            }
        );
    }

    public static function applyAdversaryParryReductionToManeuver(EventDuelCalculateManeuverValues $event): void
    {
        self::tryApplyAdversaryParryReduction(
            $event->theah,
            $event->actorId,
            $event->adversaryId,
            function (_01121 $ren) use ($event): bool {
                if ($event->parry <= 0)
                {
                    return false;
                }
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s decreases her Adversary's Parry values by -1 if her controller's Adversary controls equal or more characters."),
                    $ren->getInjectCode()
                );
                $event->parry -= 1;
                return true;
            }
        );
    }

    public static function applyAdversaryParryReductionToTechnique(EventDuelCalculateTechniqueValues $event): void
    {
        self::tryApplyAdversaryParryReduction(
            $event->theah,
            $event->actorId,
            $event->adversaryId,
            function (_01121 $ren) use ($event): bool {
                if ($event->parry <= 0)
                {
                    return false;
                }
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s decreases her Adversary's Parry values by -1 if her controller's Adversary controls equal or more characters."),
                    $ren->getInjectCode()
                );
                $event->parry -= 1;
                return true;
            }
        );
    }

    /**
     * @param callable(_01121): bool $applyWhenParryPositive returns true if Parry was reduced
     */
    private static function tryApplyAdversaryParryReduction(
        Theah $theah,
        int $actorId,
        int $adversaryId,
        callable $applyWhenParryPositive
    ): void {
        $ren = $theah->getCharacterById($adversaryId);
        if (! ($ren instanceof _01121))
        {
            return;
        }

        if ($ren->abilitiesAreBlanked() || $theah->game->characterIsInDiscardOrLocker($ren))
        {
            return;
        }

        $round = (int) $theah->game->globals->get(Game::DUEL_ROUND, 0);
        // WHY !== 0: unset flag is 0; do not treat "never applied" as "already applied to round 0".
        if ($ren->ParryReductionAppliedInRound !== 0 && $ren->ParryReductionAppliedInRound === $round)
        {
            return;
        }

        $actor = $theah->getCharacterById($actorId);
        if ($actor === null)
        {
            return;
        }

        $adversaryCount = count($theah->getCharactersInPlayByPlayerId($actor->ControllerId));
        $characterCount = count($theah->getCharactersInPlayByPlayerId($ren->ControllerId));
        if ($adversaryCount < $characterCount)
        {
            return;
        }

        if ($applyWhenParryPositive($ren))
        {
            $ren->ParryReductionAppliedInRound = $round;
            $ren->IsUpdated = true;
        }
    }
}
