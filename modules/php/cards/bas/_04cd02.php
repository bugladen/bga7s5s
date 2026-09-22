<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04cd02 extends CityCharacter
{
    /** @var string[] Traits currently granted by this passive (subset of COPYABLE_TRAITS). */
    public array $CopiedTraits = [];

    private const COPYABLE_TRAITS = ['Diplomat', 'Duelist', 'Pirate', 'Zealot'];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Jack Trades');
        $this->Title = clienttranslate('Proven Polymath');
        $this->Image = '04cd02.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 0;

        $this->InPlayXImageOffset = -15;

        $this->CityCardNumber = 2;

        $this->Resolve = 2;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->WealthCost = 2;
        $this->Negotiable = false;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Hero'),
            clienttranslate('Factotum')
        ];

        $this->Text = clienttranslate("<p>While you control a <b>Diplomat</b>, <b>Duelist</b>, <b>Pirate</b>, or <b>Zealot</b> at Jack's location, he gains that trait.</p>");

        $this->resetCard();
    }

    public function resetCard()
    {
        parent::resetCard();
        $this->CopiedTraits = [];
    }

    /**
     * Recompute which of Diplomat/Duelist/Pirate/Zealot Jack should have from
     * controlled characters at $location, then add/remove only the delta.
     *
     * WHY track CopiedTraits rather than naively removeTrait whenever absent:
     * removeTrait would also strip the same trait if an attachment had granted it.
     * CopiedTraits is the source-of-truth for *this* ability's grants only.
     */
    private function updateCopiedTraits(
        Theah $theah,
        string $location,
        ?EventCardMoved $moveEvent = null,
        ?int $controllerIdOverride = null,
        ?int $excludeCardId = null
    ): void
    {
        $controllerId = $controllerIdOverride ?? $this->ControllerId;
        if ($controllerId == 0)
        {
            $this->clearCopiedTraits($theah->game);
            return;
        }

        if ($location == Game::LOCATION_PLAYER_HOME)
        {
            $characters = $theah->getCharactersAtHomeByPlayerId($controllerId);
        }
        else
        {
            $characters = $theah->getCharactersAtLocation($location);
        }

        $desired = [];
        foreach ($characters as $character)
        {
            // WHY: EventCardMoved fires before the hub updates Location, so a card
            // moving out is still listed at $location. Exclude it from the count.
            if ($moveEvent !== null
                && $character->Id == $moveEvent->cardId
                && $moveEvent->fromLocation == $location
                && $moveEvent->toLocation != $location)
            {
                continue;
            }

            // WHY: EventCharacterDestroyed runs card handlers before the hub moves
            // the destroyed character to the locker — exclude them explicitly.
            if ($excludeCardId !== null && $character->Id == $excludeCardId)
            {
                continue;
            }

            if ($character->Id == $this->Id)
            {
                continue;
            }

            if ($character->ControllerId != $controllerId)
            {
                continue;
            }

            if ($theah->game->characterIsInDiscardOrLocker($character))
            {
                continue;
            }

            foreach (self::COPYABLE_TRAITS as $trait)
            {
                if ($character->hasTrait($trait))
                {
                    $desired[$trait] = true;
                }
            }
        }

        // WHY: Same stale-DB reason for a card moving in — it isn't listed at
        // $location yet, so check the moving card directly.
        if ($moveEvent !== null
            && $moveEvent->cardId != $this->Id
            && $moveEvent->toLocation == $location
            && $moveEvent->fromLocation != $location)
        {
            $movingCard = $theah->getCardById($moveEvent->cardId);
            if ($movingCard !== null
                && $movingCard instanceof Character
                && $movingCard->ControllerId == $controllerId
                && ! $theah->game->characterIsInDiscardOrLocker($movingCard))
            {
                foreach (self::COPYABLE_TRAITS as $trait)
                {
                    if ($movingCard->hasTrait($trait))
                    {
                        $desired[$trait] = true;
                    }
                }
            }
        }

        $game = $theah->game;

        foreach (self::COPYABLE_TRAITS as $trait)
        {
            $shouldHave = isset($desired[$trait]);
            $currentlyGranted = in_array($trait, $this->CopiedTraits, true);

            if ($shouldHave && ! $currentlyGranted)
            {
                $this->addTrait($game, $trait);
                $this->CopiedTraits[] = $trait;
                $this->IsUpdated = true;
            }
            else if (! $shouldHave && $currentlyGranted)
            {
                $this->removeTrait($game, $trait);
                $this->CopiedTraits = array_values(array_filter(
                    $this->CopiedTraits,
                    fn(string $t) => $t !== $trait
                ));
                $this->IsUpdated = true;
            }
        }
    }

    private function clearCopiedTraits(Game $game): void
    {
        foreach ($this->CopiedTraits as $trait)
        {
            $this->removeTrait($game, $trait);
        }
        $this->CopiedTraits = [];
        $this->IsUpdated = true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Passive only applies while Jack is controlled (in play), not while sitting
        // as an available Mercenary in the city deck.
        if (! $this->isControlled()
            && ! ($event instanceof EventCharacterMustered && $event->characterId == $this->Id)
            && ! ($event instanceof EventCharacterRecruited && $event->characterId == $this->Id))
        {
            return;
        }

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
        {
            $this->updateCopiedTraits($event->theah, $event->toLocation, $event);
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->toLocation == $this->Location)
        {
            if ($event->toLocation != Game::LOCATION_PLAYER_HOME
                || $event->theah->getCardById($event->cardId)->ControllerId == $this->ControllerId)
            {
                $this->updateCopiedTraits($event->theah, $this->Location, $event);
            }
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->fromLocation == $this->Location)
        {
            if ($event->fromLocation != Game::LOCATION_PLAYER_HOME
                || $event->theah->getCardById($event->cardId)->ControllerId == $this->ControllerId)
            {
                $this->updateCopiedTraits($event->theah, $this->Location, $event);
            }
        }

        if ($event instanceof EventCharacterMustered
            && ($event->characterId == $this->Id || $event->location == $this->Location))
        {
            // WHY: On Jack's own muster, ControllerId is set by the hub before card
            // handlers run, but pass playerId as override for safety (Angeline pattern).
            $override = $event->characterId == $this->Id ? $event->playerId : null;
            $this->updateCopiedTraits($event->theah, $event->location, null, $override);
        }

        if ($event instanceof EventCharacterRecruited)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($event->characterId == $this->Id || $character->Location == $this->Location)
            {
                $override = $event->characterId == $this->Id ? $event->playerId : null;
                $location = $event->characterId == $this->Id ? $this->Location : $character->Location;
                $this->updateCopiedTraits($event->theah, $location, null, $override);
            }
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId != $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->Location == $this->Location)
            {
                $this->updateCopiedTraits(
                    $event->theah,
                    $this->Location,
                    null,
                    null,
                    $event->characterId
                );
            }
        }
    }
}
