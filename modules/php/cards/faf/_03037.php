<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class _03037 extends Leader implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Sanjay");
        $this->Title = clienttranslate("Daring Tomcat");
        $this->Image = '03037.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 37;

        $this->initializeFaction("Castille");

        $this->Resolve = 8;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->CrewCap = 5;
        $this->Panache = 6;

        $this->Traits = [
            clienttranslate("Leader"),
            clienttranslate("Villain"),
            clienttranslate("Pirate"),
            clienttranslate("Duelist"),
            clienttranslate("Aragosta")
        ];

        $this->Text = clienttranslate("<p>Sanjay's gambled combat cards have +1[Riposte].</p>
<p><b>Reaction:</b> When Sanjay's challenge is refused • Collect a Renown from his location.</p>
<p><b>City Action:</b> Target an opposing character • If their controller has fewer cards in hand than you, Sanjay issues an [Influence] challenge to that character.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03037(),
        ];

        $this->Reactions = [
            new Reaction_03037(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY gate on $event->gambled (duel_round.gambled for this round) rather
        // than Game::DUEL_GAMBLED alone: the calculate-stats event already carries
        // the authoritative per-round flag, including Roll-the-Bones paths.
        if ($event instanceof EventDuelCalculateCombatCardStats
            && $event->actorId == $this->Id
            && $event->gambled)
        {
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s's gambled combat card gains +1 Riposte"),
                $this->getInjectCode()
            );
            $event->addRiposte(1);
        }
    }
}
