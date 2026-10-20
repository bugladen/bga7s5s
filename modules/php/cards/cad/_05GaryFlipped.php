<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05GaryFlipped;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05GaryFlipped;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class _05GaryFlipped extends Leader implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Rena Klingenhalter";
        $this->Title = "Wrathful";
        $this->Image = "05GaryFlipped.v4.jpg";
        $this->FlippedImage = "05Gary.v4.jpg";
        $this->IsDoubleSided = true;
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 7;

        $this->initializeFaction("Eisen");

        $this->Resolve = 8;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->CrewCap = 5;
        $this->Panache = 5;

        $this->Traits = [
            "Leader",
            "Hero",
            "Hunter",
            "Berserker",
            "Eisen"
        ];

        $this->Text = "<p>When Rena's combat card is an attachment, it gains +1[Parry] and +1[Thrust].</p>
<p>Reaction: When Rena's adversary is destroyed • Claim this location.</p>
<p>Technique: Destroy an attachment equipped to Rena • +1[Thrust]. She heals a wound.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05GaryFlipped(),
        ];

        // Character already implements IHasTechniques / TechniqueTrait
        $this->Techniques = [
            new Technique_05GaryFlipped(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Printed "combat card is an attachment" — the chosen combat card itself
        // is an Attachment instance (not Eager Blade's "equipped attachment modifies" shape).
        if ($event instanceof EventDuelCalculateCombatCardStats && $event->actorId == $this->Id)
        {
            $combatCard = $event->theah->game->getCardObjectFromDb($event->combatCardId);
            if ($combatCard instanceof Attachment)
            {
                $event->explanations[] = sprintf(
                    "%s: Combat card is an attachment — +1 Parry and +1 Thrust.",
                    $this->getInjectCode()
                );
                $event->addParry(1);
                $event->addThrust(1);

                // WHY Maneuver column: So It Begins / Yevgeni — Combat Card column
                // only stores card ids; reuse duel_round_maneuver for a reload-safe
                // label. Skip dashed stats so the note does not claim a bonus that
                // addParry/addThrust refused. Plain Name matches other column labels.
                $parts = [];
                if (! $event->dashedParry)
                {
                    $parts[] = '+1 Parry';
                }
                if (! $event->dashedThrust)
                {
                    $parts[] = '+1 Thrust';
                }
                if (count($parts) > 0)
                {
                    $note = sprintf('%s: %s to combat card', $this->Name, implode(' and ', $parts));
                    $event->theah->recordDuelRoundColumnNote('maneuver', 'note_' . $this->Id, $note);
                }
            }
        }
    }
}
