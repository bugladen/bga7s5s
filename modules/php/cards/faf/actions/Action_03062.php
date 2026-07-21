<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03062 extends SchemeCityAction
{
    // WHY: Grant Monster/Undead after EventCharacterMustered so trait notifies
    // fire once the character is in play, not while still in The Locker.
    public int $pendingMusterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound performer; Muster from The Locker");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    public function getEligibleLockerCharacters(int $playerId, Theah $theah): array
    {
        $lockerName = $theah->game->getPlayerLockerName($playerId);
        $lockerCards = $theah->getCardObjectsAtLocation($lockerName);
        $eligible = [];

        foreach ($lockerCards as $card)
        {
            if (! ($card instanceof Character))
            {
                continue;
            }

            if ($card->OwnerId != $playerId)
            {
                continue;
            }

            if ($card->hasTrait("Undead") || $card->hasTrait("Mercenary"))
            {
                continue;
            }

            $eligible[] = $card;
        }

        return $eligible;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0
            && count($this->getEligibleLockerCharacters($playerId, $theah)) > 0;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        // WHY: "Villain City Action" is a mechanical trait gate, not ISorcererAbility
        // (same discipline as Hero / Strega / Diplomat prefixes on scheme actions).
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Villain")
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $performerId = (int)$event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if ($performer === null || $performer->ControllerId != $event->playerId)
            {
                throw new UserException($event->theah->game->translate("Invalid performer"));
            }

            if (! $performer->hasTrait("Villain"))
            {
                throw new UserException($event->theah->game->translate("Performer must be a Villain."));
            }

            if (! $event->theah->cardInCity($performer))
            {
                throw new UserException($event->theah->game->translate("Performer must be at a City location."));
            }

            if (count($this->getEligibleLockerCharacters($event->playerId, $event->theah)) == 0)
            {
                throw new UserException($event->theah->game->translate("No eligible character in The Locker."));
            }

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03062", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCharacterMustered && $this->pendingMusterId != 0 && $event->characterId == $this->pendingMusterId)
        {
            $game = $event->theah->game;
            $mustered = $event->theah->getCharacterById($event->characterId);
            if ($mustered !== null)
            {
                if (! $mustered->hasTrait("Monster"))
                {
                    $mustered->addTrait($game, "Monster");
                    $mustered->addCondition(Game::DEAL_WITH_THE_DEVIL_GRANTED_MONSTER);
                }

                $mustered->addTrait($game, "Undead");
                $mustered->addCondition(Game::DEAL_WITH_THE_DEVIL);
                $mustered->IsUpdated = true;
            }

            $this->pendingMusterId = 0;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03062)
        {
            $playerId = $game->getActivePlayerId();
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;
            $args['ids'] = array_map(
                fn(Character $character) => $character->Id,
                $this->getEligibleLockerCharacters($playerId, $game->theah)
            );
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03062)
        {
            $owner = $this->getOwningCard($game->theah);
            $playerId = $game->getActivePlayerId();
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found"));
            }

            if (! $performer->hasTrait("Villain"))
            {
                throw new UserException($game->translate("Performer must be a Villain."));
            }

            if (! $game->theah->cardInCity($performer))
            {
                throw new UserException($game->translate("Performer must be at a City location."));
            }

            $location = $performer->Location;

            $eligible = $this->getEligibleLockerCharacters($playerId, $game->theah);
            $muster = null;
            foreach ($eligible as $character)
            {
                if ($character->Id == $id)
                {
                    $muster = $character;
                    break;
                }
            }

            if ($muster === null)
            {
                throw new UserException($game->translate("Invalid character to Muster from The Locker."));
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} wounds ${performer_inject_code} and Musters ${character_inject_code} from The Locker at ${location}.'), [
                'i18n' => ['location'],
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
                "performer_inject_code" => $performer->getInjectCode(),
                "character_inject_code" => $muster->getInjectCode(),
                "location" => $location,
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $performerId,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundEvent);

            $game->theah->addCardToWorld($muster);

            $removedFromLockerEvent = EventFactory::createCardRemovedFromLockerEvent($playerId, $muster->Id);
            $game->theah->queueEvent($removedFromLockerEvent);

            // WHY: Explicit DB write — stRunEvents rebuilds city from DB; IsUpdated alone
            // is not flushed before nextState. Same discipline as Action_03029::$MoveMode.
            $this->pendingMusterId = $muster->Id;
            $game->updateCardObjectInDb($owner);

            $musterEvent = EventFactory::createCharacterMusteredEvent($playerId, $muster->Id, $location);
            $game->theah->queueEvent($musterEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("");
        }
    }
}
