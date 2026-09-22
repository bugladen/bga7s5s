<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04002 extends Character implements IHasActions
{
    use ActionTrait;

    // WHY: Track which Thugs currently hold the aura so we can ±1 without
    // clobbering attachment-driven Finesse/Resolve on recompute.
    /** @var list<int> */
    public array $BuffedThugIds = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Danilo Danini");
        $this->Title = clienttranslate("Il Diamante");
        $this->Image = "04002.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 2;

        $this->initializeFaction("Vodacce");

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Merchant"),
            clienttranslate("Diplomat"),
            clienttranslate("Red Hand"),
            clienttranslate("Vodacce")
        ];

        $this->Text = clienttranslate("<p>Your <b>Thugs</b> at <b>Home</b> and Danilo's location gain +1[Finesse] and +1 Resolve.</p>
<p><b>City Action:</b> Engage Danilo • He issues an [Influence] challenge to target opposing character. If another character intervenes, wound them or draw a card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04002(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($this->ControllerId == 0)
        {
            return;
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $this->clearAllThugBuffs($event->theah);
            return;
        }

        if ($event instanceof EventCardMoved
            || $event instanceof EventCharacterMustered
            || $event instanceof EventApproachCharacterPlayed
            || $event instanceof EventCharacterRecruited
            || ($event instanceof EventCharacterDestroyed && $event->characterId != $this->Id))
        {
            $moveEvent = $event instanceof EventCardMoved ? $event : null;
            $controllerOverride = null;
            $excludeCharacterId = null;
            $forceIncludeId = null;

            if ($event instanceof EventApproachCharacterPlayed && $event->characterId == $this->Id)
            {
                // WHY: Own approach — ControllerId may not be on the in-memory card yet.
                $controllerOverride = $event->playerId;
            }

            // WHY: runEventHubAfterCards on Destroyed — character still looks in-play during
            // card handleEvent. Exclude them from eligibility so the buff is stripped.
            if ($event instanceof EventCharacterDestroyed)
            {
                $excludeCharacterId = $event->characterId;
            }

            // WHY: Approach/Muster/Recruit may leave ControllerId unset on the new card,
            // so getCharactersInPlayByPlayerId can miss them — force-include when eligible.
            if (($event instanceof EventApproachCharacterPlayed
                    || $event instanceof EventCharacterMustered
                    || $event instanceof EventCharacterRecruited)
                && $event->characterId != $this->Id)
            {
                $newCharacter = $event->theah->getCharacterById($event->characterId);
                $playerId = $event->playerId;
                if ($newCharacter !== null
                    && $newCharacter->hasTrait("Thug")
                    && $playerId == ($controllerOverride ?? $this->ControllerId))
                {
                    if ($event instanceof EventApproachCharacterPlayed)
                    {
                        $newLocation = Game::LOCATION_PLAYER_HOME;
                    }
                    else if ($event instanceof EventCharacterMustered)
                    {
                        $newLocation = $event->location;
                    }
                    else
                    {
                        $newLocation = $newCharacter->Location;
                    }
                    $daniloLocation = $this->Location;
                    if ($newLocation == Game::LOCATION_PLAYER_HOME || $newLocation == $daniloLocation)
                    {
                        $forceIncludeId = $newCharacter->Id;
                    }
                }
            }

            $this->syncThugAura($event->theah, $moveEvent, $controllerOverride, $excludeCharacterId, $forceIncludeId);
        }
    }

    private function syncThugAura(
        Theah $theah,
        ?EventCardMoved $moveEvent = null,
        ?int $controllerIdOverride = null,
        ?int $excludeCharacterId = null,
        ?int $forceIncludeId = null
    ): void {
        if ($theah->game->characterIsInDiscardOrLocker($this) && $controllerIdOverride === null)
        {
            $this->clearAllThugBuffs($theah);
            return;
        }

        $controllerId = $controllerIdOverride ?? $this->ControllerId;
        $eligibleIds = $this->getEligibleThugIds(
            $theah,
            $moveEvent,
            $controllerId,
            $excludeCharacterId,
            $forceIncludeId
        );
        $currentlyBuffed = $this->BuffedThugIds;

        foreach ($currentlyBuffed as $thugId)
        {
            if (! in_array($thugId, $eligibleIds, true))
            {
                $thug = $theah->getCharacterById($thugId);
                if ($thug !== null)
                {
                    $this->removeThugBuff($thug, $theah, $controllerId);
                }
            }
        }

        foreach ($eligibleIds as $thugId)
        {
            if (! in_array($thugId, $currentlyBuffed, true))
            {
                $thug = $theah->getCharacterById($thugId);
                if ($thug !== null)
                {
                    $this->applyThugBuff($thug, $theah, $controllerId);
                }
            }
        }

        $this->BuffedThugIds = $eligibleIds;
        $this->IsUpdated = true;
    }

    /**
     * @return list<int>
     */
    private function getEligibleThugIds(
        Theah $theah,
        ?EventCardMoved $moveEvent = null,
        int $controllerId = 0,
        ?int $excludeCharacterId = null,
        ?int $forceIncludeId = null
    ): array {
        if ($controllerId == 0)
        {
            return [];
        }

        $daniloLocation = $this->getEffectiveLocation($this->Id, $this->Location, $moveEvent);
        $eligible = [];

        $characters = $theah->getCharactersInPlayByPlayerId($controllerId);
        foreach ($characters as $character)
        {
            if ($character->Id == $this->Id
                || $character->Id == $excludeCharacterId
                || ! $character->hasTrait("Thug")
                || $character->IsDying)
            {
                continue;
            }

            $location = $this->getEffectiveLocation($character->Id, $character->Location, $moveEvent);
            if ($location == Game::LOCATION_PLAYER_HOME || $location == $daniloLocation)
            {
                $eligible[] = $character->Id;
            }
        }

        // WHY: EventCardMoved fires before DB updates — a Thug moving INTO Home/Danilo
        // location is not yet listed at the destination.
        if ($moveEvent !== null
            && $moveEvent->cardId != $this->Id
            && $moveEvent->cardId != $excludeCharacterId
            && $moveEvent->fromLocation != $moveEvent->toLocation)
        {
            $moving = $theah->getCardById($moveEvent->cardId);
            if ($moving instanceof Character
                && $moving->ControllerId == $controllerId
                && $moving->hasTrait("Thug")
                && ! $moving->IsDying
                && ($moveEvent->toLocation == Game::LOCATION_PLAYER_HOME
                    || $moveEvent->toLocation == $daniloLocation)
                && ! in_array($moving->Id, $eligible, true))
            {
                $eligible[] = $moving->Id;
            }
        }

        if ($forceIncludeId !== null
            && $forceIncludeId != $excludeCharacterId
            && ! in_array($forceIncludeId, $eligible, true))
        {
            $eligible[] = $forceIncludeId;
        }

        return array_values($eligible);
    }

    private function getEffectiveLocation(int $cardId, string $currentLocation, ?EventCardMoved $moveEvent): string
    {
        if ($moveEvent !== null && $moveEvent->cardId == $cardId)
        {
            return $moveEvent->toLocation;
        }
        return $currentLocation;
    }

    private function applyThugBuff(Character $thug, Theah $theah, int $controllerId): void
    {
        $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
            $controllerId,
            $thug->Id,
            $thug->ModifiedFinesse,
            $thug->ModifiedFinesse + 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($finesseEvent);

        // WHY: No createCharacterResolveModifiedEvent factory — mutate ModifiedResolve
        // directly (Joern). Finesse has an EventHub+client notif; Resolve does not, so
        // we emit characterResolveModified ourselves or the chip stays flat.
        $oldResolve = $thug->ModifiedResolve;
        $thug->ModifiedResolve += 1;
        $thug->IsUpdated = true;

        $theah->game->notify->all("characterResolveModified", clienttranslate('The resolve of ${character_name} went from ${oldResolve} to ${newResolve} due to: ${reason}.'), [
            'i18n' => ['character_name'],
            "character_name" => $thug->Name,
            "characterId" => $thug->Id,
            "oldResolve" => $oldResolve,
            "newResolve" => $thug->ModifiedResolve,
            "reason" => $this->getInjectCode(),
        ]);
    }

    private function removeThugBuff(Character $thug, Theah $theah, int $controllerId): void
    {
        $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
            $controllerId,
            $thug->Id,
            $thug->ModifiedFinesse,
            $thug->ModifiedFinesse - 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($finesseEvent);

        $oldResolve = $thug->ModifiedResolve;
        $thug->ModifiedResolve -= 1;
        $thug->IsUpdated = true;

        $theah->game->notify->all("characterResolveModified", clienttranslate('The resolve of ${character_name} went from ${oldResolve} to ${newResolve} due to: ${reason}.'), [
            'i18n' => ['character_name'],
            "character_name" => $thug->Name,
            "characterId" => $thug->Id,
            "oldResolve" => $oldResolve,
            "newResolve" => $thug->ModifiedResolve,
            "reason" => $this->getInjectCode(),
        ]);

        // WHY: Resolve drop can cross wounds==resolve without a wound event.
        if ($thug->Wounds >= $thug->ModifiedResolve && ! $thug->IsDying)
        {
            $thug->IsDying = true;
            $thug->unEquipAllAttachments($theah);
            $destroyEvent = EventFactory::createCharacterDestroyedEvent(
                $thug->ControllerId,
                $thug->Id,
                $this->getInjectCode()
            );
            $theah->queueEvent($destroyEvent);
        }
    }

    private function clearAllThugBuffs(Theah $theah): void
    {
        $controllerId = $this->ControllerId;
        foreach ($this->BuffedThugIds as $thugId)
        {
            $thug = $theah->getCharacterById($thugId);
            if ($thug !== null)
            {
                $this->removeThugBuff($thug, $theah, $controllerId > 0 ? $controllerId : $thug->ControllerId);
            }
        }
        $this->BuffedThugIds = [];
        $this->IsUpdated = true;
    }
}
