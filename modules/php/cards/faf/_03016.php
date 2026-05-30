<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03016a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03016b;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _03016 extends Character implements IHasReactions
{
    use ReactionTrait;

    public bool $WoundedCombatBonusApplied = false;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Schwester Ise');
        $this->Title = clienttranslate("Moonlit Interrogator");

        $this->Image = '03016.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 16;

        $this->initializeFaction('Eisen');

        $this->Resolve = 5;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate('Academic'),
            clienttranslate('Hunter'),
            clienttranslate('Zealot'),
            clienttranslate('Eisen')
        ];

        $this->Text = clienttranslate("<p>During Dusk, you may choose not to move Ise <b>Home</b>.</p>
<p>Ise has +1 [Combat] while wounded.</p>
<p><b>Reaction:</b> After an enemy character moves to this location • Move another character you control to this location.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03016a(),
            new Reaction_03016b(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (($event instanceof EventCharacterWounded || $event instanceof EventCharacterHealed)
            && $event->characterId == $this->Id)
        {
            $this->recomputeWoundedCombatBonus($event->theah);
        }
    }

    private function recomputeWoundedCombatBonus(Theah $theah): void
    {
        if ($this->ControllerId == 0) return;
        if ($theah->game->characterIsInDiscardOrLocker($this)) return;
        if ($this->IsDying) return;

        $shouldHaveBonus = $this->Wounds > 0;

        if ($shouldHaveBonus && ! $this->WoundedCombatBonusApplied)
        {
            $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
                $this->ControllerId,
                $this->Id,
                $this->ModifiedCombat,
                $this->ModifiedCombat + 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($combatEvent);

            $this->WoundedCombatBonusApplied = true;
            $this->IsUpdated = true;
        }
        else if (! $shouldHaveBonus && $this->WoundedCombatBonusApplied)
        {
            $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
                $this->ControllerId,
                $this->Id,
                $this->ModifiedCombat,
                $this->ModifiedCombat - 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($combatEvent);

            $this->WoundedCombatBonusApplied = false;
            $this->IsUpdated = true;
        }
    }
}
