<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02033;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class _02033 extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('“Prima” Rosa');
        $this->Image = '02033.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 33;

        $this->initializeFaction('Castille');
        $this->Title = clienttranslate('La Virtuosa  Vibrante');
        $this->Resolve = 5;
        $this->Combat = 0;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate('Scoundrel'),
            clienttranslate('Bard'),
            clienttranslate('Zealot'),
            clienttranslate('Castille'),
        ];

        $this->Text = clienttranslate("<p>When Rosa's combat card is a <b>Revelry</b>, gain Lethal.</p></p><p><b>City Action:</b> Move target adjacent City Card to Rosa's Location.</p><p><b>Reaction:</b> After a character moves to Rosa's location • Their controller discards a card.</p></p> ");

        $this->resetCard();

        $this->Actions = [
            new Action_02033(),
        ];

        $this->Reactions = [
            new Reaction_02033(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateCombatCardStats && $event->actorId == $this->Id) 
        {
            $combatCard = $event->theah->game->getCardObjectFromDb($event->combatCardId);
            if ($combatCard && $combatCard->hasTrait(clienttranslate('Revelry'))) 
            {
                $lethalEvent = EventFactory::createGainLethalEvent($this->Id, $event->theah);
                $event->theah->queueEvent($lethalEvent);
                $event->explanations[] = sprintf(
                    $event->theah->game->translate('%s: Combat card is Revelry — Threat is Lethal.'),
                    $this->getInjectCode()
                );
            }
        }
    }
}
