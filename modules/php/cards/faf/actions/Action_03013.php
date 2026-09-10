<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03013 extends CharacterAction
{
    /** @var int[] characterIds currently tagged Sorcerer by this Action */
    private array $TaggedOpposingIds = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("(Continuous) Choose an opposing character at to gain Sorcerer Trait");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $daniella = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($daniella))
        {
            return false;
        }

        return count($this->getEligibleTargets($theah, $daniella)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $daniella = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $daniella->Id, "03013", $this->Id);
            $event->theah->queueEvent($transition);

            // WHY: Continuous Action — grant an extra High Drama action so this use
            // does not consume the player's normal action (same as Action_01090).
            $event->theah->game->globals->set(Game::EXTRA_ACTIONS, 1);

            // WHY: central confirm sets Used=true; flip back immediately so the
            // Action stays available (Action_01090 Continuous shape).
            $this->setUsed($event->theah, false);
        }

        $owner = $this->getOwningCharacter($event->theah);

        // WHY location-scoped (not turn-scoped): printed text is opposing Daniella —
        // co-location. Eddie: remove Sorcerer when the granted character leaves her location.
        if ($event instanceof EventCardMoved && $owner !== null)
        {
            if ($event->cardId === $owner->Id)
            {
                // Daniella left — no one at her old spot still "opposes" her there.
                $this->untagOpposingSorcerers($event->theah);
            }
            elseif (in_array($event->cardId, $this->TaggedOpposingIds, true)
                && $event->toLocation !== $owner->Location)
            {
                // Tagged character moved away from Daniella.
                // WHY toLocation vs owner.Location: EventCardMoved.runEventHubAfterCards
                // means the mover's Location is still fromLocation during handleEvent;
                // Daniella's Location is current (she is not the mover).
                $this->untagCharacter($event->theah, $event->cardId);
            }
        }

        if ($event instanceof EventCharacterDestroyed && $owner !== null)
        {
            if ($event->characterId === $owner->Id)
            {
                $this->untagOpposingSorcerers($event->theah);
            }
            elseif (in_array($event->characterId, $this->TaggedOpposingIds, true))
            {
                $this->untagCharacter($event->theah, $event->characterId);
            }
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03013)
        {
            $daniella = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $daniella->Id;

            // WHY array_values: getOpposingCharactersAtLocation is keyed by card id;
            // non-sequential keys JSON-encode as an object and break ids.forEach.
            $args['ids'] = array_values(array_map(
                fn(Character $c) => $c->Id,
                $this->getEligibleTargets($game->theah, $daniella)
            ));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03013)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isEligibleTarget($game->theah, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $daniella = $this->getOwningCharacter($game->theah);

            $this->grantSorcerer($game, $daniella, $character);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($daniella->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("targetChosen");
        }
    }

    /**
     * Duel hub opt-in: available when Daniella shares the actor's location (same
     * controller) and the adversary is not already considered a Sorcerer.
     *
     * WHY a duelChooseAction button (not only Reaction_03013a): Technique availability
     * (e.g. Technique_03018) is evaluated when the player opens Technique from the hub.
     * Reaction_03013a fires after Maneuver/Technique activate — too late to unlock a
     * Sorcerer-gated Technique for this round. Tagging at the hub first fixes that.
     *
     * WHY not require Daniella to be the duel participant: printed ability scopes to
     * "while using your abilities" while characters oppose Daniella. A crewmate can
     * be the actor; Daniella only needs to be at that location so the adversary opposes her.
     */
    public function isAvailableAsDuelAction(Theah $theah): bool
    {
        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $daniella = $this->getOwningCharacter($theah);
        if ($daniella === null || $theah->game->characterIsInDiscardOrLocker($daniella))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if ($actor === null)
        {
            return false;
        }

        // Same player ("your abilities") and same location (adversary opposes Daniella).
        if ($daniella->ControllerId !== $actor->ControllerId
            || $daniella->Location !== $actor->Location)
        {
            return false;
        }

        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary === null)
        {
            return false;
        }

        return ! $adversary->hasTrait("Sorcerer")
            && ! in_array($adversary->Id, $this->TaggedOpposingIds, true);
    }

    /**
     * Find Daniella's Action at the current duel actor's location (may not be the actor).
     */
    public static function findAvailableDuelAction(Theah $theah): ?self
    {
        $actor = $theah->getDuelRoundActor();
        if ($actor === null)
        {
            return null;
        }

        foreach ($theah->getCharactersAtLocation($actor->Location) as $character)
        {
            if ($character->ControllerId !== $actor->ControllerId)
            {
                continue;
            }

            if (! ($character instanceof IHasActions))
            {
                continue;
            }

            foreach ($character->getActions() as $action)
            {
                if ($action instanceof self && $action->isAvailableAsDuelAction($theah))
                {
                    return $action;
                }
            }
        }

        return null;
    }

    public function actDuelConsiderAdversarySorcerer(Game $game): void
    {
        if (! $this->isAvailableAsDuelAction($game->theah))
        {
            throw new UserException($game->translate("That Duel Action is not available."));
        }

        $daniella = $this->getOwningCharacter($game->theah);
        $adversary = $game->theah->getDuelRoundOpponent();

        $this->grantSorcerer($game, $daniella, $adversary);

        // WHY: self-loop nextState does not run the event hub, so IsUpdated cards
        // (adversary trait + Daniella's TaggedOpposingIds on this Action) would not
        // flush unless we write them here. High Drama path relies on ActionResolved.
        $game->updateCardObjectInDb($adversary);
        $game->updateCardObjectInDb($daniella);
    }

    private function grantSorcerer(Game $game, Character $daniella, Character $character): void
    {
        $character->addTrait($game, "Sorcerer");
        $this->TaggedOpposingIds[] = $character->Id;
        $daniella->IsUpdated = true;

        $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} grants ${character_inject_code} Sorcerer while at Daniella\'s location.'), [
            "card_inject_code" => $daniella->getInjectCode(),
            "player_name" => $game->getPlayerNameById($daniella->ControllerId),
            "character_inject_code" => $character->getInjectCode(),
        ]);
    }

    /**
     * @return Character[] opposing characters at Daniella's location who do not already have Sorcerer
     */
    private function getEligibleTargets(Theah $theah, Character $owner): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($owner->Location, $owner->ControllerId);
        return array_values(array_filter(
            $opposing,
            fn(Character $c) => ! $c->hasTrait("Sorcerer")
                && ! in_array($c->Id, $this->TaggedOpposingIds, true)
        ));
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function isEligibleTarget(Theah $theah, Character $character): array
    {
        $owner = $this->getOwningCharacter($theah);

        if ($character->ControllerId === $owner->ControllerId || $character->ControllerId === 0)
        {
            return [false, $theah->game->translate("Character must be controlled by an opponent.")];
        }

        if ($character->Location !== $owner->Location)
        {
            return [false, $theah->game->translate("Character must be at Daniella's location.")];
        }

        if ($character->hasTrait("Sorcerer") || in_array($character->Id, $this->TaggedOpposingIds, true))
        {
            return [false, $theah->game->translate("Character is already considered a Sorcerer.")];
        }

        return [true, ""];
    }

    private function untagCharacter(Theah $theah, int $characterId): void
    {
        $index = array_search($characterId, $this->TaggedOpposingIds, true);
        if ($index === false)
        {
            return;
        }

        unset($this->TaggedOpposingIds[$index]);
        $this->TaggedOpposingIds = array_values($this->TaggedOpposingIds);

        $character = $theah->getCharacterById($characterId);
        if ($character !== null)
        {
            $character->removeTrait($theah->game, "Sorcerer");
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    private function untagOpposingSorcerers(Theah $theah): void
    {
        if (empty($this->TaggedOpposingIds))
        {
            return;
        }

        foreach (array_values($this->TaggedOpposingIds) as $cid)
        {
            $this->untagCharacter($theah, $cid);
        }
    }
}
