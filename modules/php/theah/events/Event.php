<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
abstract class Event
{
    // WHY: Transitions / active-player changes must run after ActionResolved so trailing
    // multi-player effects (e.g. 04005 discard) still see HD wrap first. Distinct from
    // ACTION_RESOLVED — MySQL ORDER BY priority alone has no event_id tiebreak
    // (Corpse Speak 2026-06-05-01).
    const CHANGE_ACTIVE_PLAYER_PRIORITY = 9;
    const TRANSITION_PRIORITY = 9;
    // WHY: After CardMoved-window reactions (6) and deferred siblings (7) drain, then signal
    // action completion. Formerly LOWEST=5, which ran *before* reaction UIs and let Soline
    // (EventActionResolved) join Rosa (EventCardMoved) in the same chooseNext tier.
    const ACTION_RESOLVED_PRIORITY = 8;
    const DEFERRED_REACTION_PRIORITY = 7;
    const REACTION_PRIORITY = 6;
    // WHY: After First Player picks one reaction to resolve, siblings are demoted here so
    // that reaction's pay transition (still REACTION_PRIORITY) drains before chooseNext
    // offers the rest again.
    const LOWEST_PRIORITY = 5;
    const LOW_PRIORITY = 4;
    const MEDIUM_PRIORITY = 3;
    const HIGH_PRIORITY = 2;
    const HIGHEST_PRIORITY = 1;

    public Theah $theah;
    public int $priority;
    public bool $runEventHubAfterCards;
    public bool $canceled;
    /** @var bool */
    public bool $wasStacked;
    public ?int $batchId;
    public bool $runImmediately;

    public function __construct()
    {
        $this->priority = Event::MEDIUM_PRIORITY;
        $this->runEventHubAfterCards = false;
        $this->canceled = false;
        $this->wasStacked = false;
        $this->batchId = null;
        $this->runImmediately = false;
    }

    public function queueEvent(Event $event)
    {
        $this->theah->queueEvent($event);
    }

}