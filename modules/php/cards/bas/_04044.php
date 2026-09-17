<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04044 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Adrift in the Wind');
        $this->Image = '04044.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 44;

        $this->initializeFaction("Ussura");

        $this->Initiative = 65;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Brawl"),
            clienttranslate("Relentless")
        ];

        $this->Text = clienttranslate("<p>Add a Renown to two different locations.</p>
<hr />
<p>While your <b>Leader</b> is at an uncontrolled location, they gain +1[Finesse].</p>
<p><b>Leader Reaction:</b> When your performer issues a challenge • Their location becomes uncontrolled.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04044(),
        ];
    }

    // WHY: Continuous aura only while the scheme is the day's revealed scheme at Home.
    // Once EventCardSentToLocker moves us to Locker-*, Location is no longer Home.
    private function isSchemeInPlay(): bool
    {
        return $this->Location == Game::LOCATION_PLAYER_HOME;
    }

    private function leaderIsAtUncontrolledLocation(Theah $theah, string $location): bool
    {
        if (! $theah->locationInCity($location))
        {
            return false;
        }

        $cityLocation = $theah->getCityLocation($location);
        return $cityLocation !== null && $cityLocation->Controller == 0;
    }

    private function applyLeaderFinesseBonus(Theah $theah, Leader $leader): void
    {
        if ($leader->hasCondition(Game::ADRIFT_IN_THE_WIND_CONDITION))
        {
            return;
        }

        if ($theah->game->characterIsInDiscardOrLocker($leader))
        {
            return;
        }

        $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
            $leader->ControllerId,
            $leader->Id,
            $leader->ModifiedFinesse,
            $leader->ModifiedFinesse + 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($finesseEvent);

        $leader->addCondition(Game::ADRIFT_IN_THE_WIND_CONDITION);
        $theah->game->updateCardObjectInDb($leader);

        $theah->game->notify->all("adriftInTheWindConditionStarted", '', [
            "cardId" => $leader->Id,
        ]);
    }

    private function removeLeaderFinesseBonus(Theah $theah, Leader $leader): void
    {
        if (! $leader->hasCondition(Game::ADRIFT_IN_THE_WIND_CONDITION))
        {
            return;
        }

        if (! $theah->game->characterIsInDiscardOrLocker($leader))
        {
            $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
                $leader->ControllerId,
                $leader->Id,
                $leader->ModifiedFinesse,
                $leader->ModifiedFinesse - 1,
                $this->getInjectCode()
            );
            $theah->queueEvent($finesseEvent);
        }

        $leader->removeCondition(Game::ADRIFT_IN_THE_WIND_CONDITION);
        $theah->game->updateCardObjectInDb($leader);

        $theah->game->notify->all("adriftInTheWindConditionEnded", '', [
            "cardId" => $leader->Id,
        ]);
    }

    private function recomputeLeaderFinesseBonus(Theah $theah, ?string $leaderLocationOverride = null): void
    {
        $leader = $theah->getLeaderByPlayerId($this->ControllerId);
        if ($leader === null)
        {
            return;
        }

        $shouldHave = $this->isSchemeInPlay()
            && ! $theah->game->characterIsInDiscardOrLocker($leader)
            && $this->leaderIsAtUncontrolledLocation(
                $theah,
                $leaderLocationOverride ?? $leader->Location
            );

        if ($shouldHave)
        {
            $this->applyLeaderFinesseBonus($theah, $leader);
        }
        else
        {
            $this->removeLeaderFinesseBonus($theah, $leader);
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose two city locations to place Renown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            // WHY: Apply after resolve — scheme is the day's chosen scheme at Home.
            $this->recomputeLeaderFinesseBonus($event->theah);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "04044");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            $leader = $event->theah->getLeaderByPlayerId($this->ControllerId);
            if ($leader !== null)
            {
                $this->removeLeaderFinesseBonus($event->theah, $leader);
            }
        }

        // WHY: EventCardMoved has runEventHubAfterCards=true — Leader.Location is still
        // the OLD location during card handleEvent. Use toLocation for the recompute.
        if ($event instanceof EventCardMoved && $this->isSchemeInPlay())
        {
            $leader = $event->theah->getLeaderByPlayerId($this->ControllerId);
            if ($leader !== null && $event->cardId == $leader->Id)
            {
                $this->recomputeLeaderFinesseBonus($event->theah, $event->toLocation);
            }
        }

        // WHY: Claim/Uncontrolled hub runs first (runEventHubAfterCards=false) — Controller
        // is already updated when we read getCityLocation.
        if (($event instanceof EventLocationClaimed || $event instanceof EventLocationBecomesUncontrolled)
            && $this->isSchemeInPlay())
        {
            $leader = $event->theah->getLeaderByPlayerId($this->ControllerId);
            if ($leader !== null && $leader->Location == $event->location)
            {
                $this->recomputeLeaderFinesseBonus($event->theah);
            }
        }
    }
}
