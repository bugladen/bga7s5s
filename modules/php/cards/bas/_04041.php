<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class _04041 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Jak-Sen');
        $this->Title = clienttranslate('Tranquil Sorcerer');
        $this->Image = '04041.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 41;

        $this->initializeFaction("Ussura");
        $this->InPlayXImageOffset = 10;

        $this->Resolve = 4;
        $this->Combat = 0;
        $this->DashedCombat = true;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Hero"),
            clienttranslate("Sorcerer"),
            clienttranslate("Shenzhou")
        ];

        $this->Text = clienttranslate("<p>When Jak-Sen's combat card is a <b>Sorcery</b>, gain Lethal.</p>
<p><b>City Reaction:</b> When your character at Jak-Sen's location would be moved or engaged by an opponent's effect • Cancel that move or engage.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04041(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Rosa _02033 shape — trait on the combat card itself during
        // EventDuelCalculateCombatCardStats (not a Technique availability gate).
        if ($event instanceof EventDuelCalculateCombatCardStats && $event->actorId == $this->Id)
        {
            $combatCard = $event->theah->game->getCardObjectFromDb($event->combatCardId);
            if ($combatCard && $combatCard->hasTrait("Sorcery"))
            {
                $lethalEvent = EventFactory::createGainLethalEvent($this->Id, $event->theah);
                $event->theah->queueEvent($lethalEvent);
                $event->explanations[] = sprintf(
                    $event->theah->game->translate('%s: Combat card is Sorcery — Threat is Lethal.'),
                    $this->getInjectCode()
                );
            }
        }
    }
}
