<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04045 extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue an Unrefusable Combat Challenge");
        $this->RequiresPerformerSelected = true;
    }

    // WHY: CHALLENGE_TYPE is table-global. Mirror match: both schemes sit at Home.
    // This action always makes the owner the challenger, so "this scheme issued it"
    // = owner controls the challenger of a STAND_YOUR_GROUND duel.
    // Do NOT stamp a persisted Action flag: EventActionResolved fires with
    // !IN_DUEL after the HD confirm / challenge pipeline and would wipe it
    // before EventGenerateChallengeThreat.
    private function thisSchemeIssuedThisChallenge(Theah $theah, int $challengerId): bool
    {
        if ($theah->game->globals->get(Game::CHALLENGE_TYPE) != Game::STAND_YOUR_GROUND_CHALLENGE_TYPE)
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);
        $challenger = $theah->getCharacterById($challengerId);
        return $owner !== null
            && $challenger !== null
            && $challenger->ControllerId == $owner->ControllerId;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        // WHY: En Garde = unengaged precondition (not an Engage cost). Duelist is a
        // mechanical trait gate, not ISorcererAbility. Full legality needs a target.
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Duelist")
                && ! $performer->Engaged
                && $performer->canChallenge($theah)
                && count($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId)) > 0
        ));
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer === null)
        {
            return [false, $game->translate("Performer not chosen.")];
        }

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target must be at your performer's location.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if ($performer === null
                || ! $performer->hasTrait("Duelist")
                || $performer->Engaged
                || ! $performer->canChallenge($event->theah))
            {
                throw new UserException($game->translate("Performer cannot issue this challenge."));
            }

            if (count($event->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId)) == 0)
            {
                throw new UserException($game->translate("No opposing character at the performer's location."));
            }

            // WHY: En Garde printed, not Engage. Keep STAND_YOUR_GROUND off
            // stIssueChallenge's auto-engage list and off Unsanctioned's
            // stSetupChallenge engage so the performer stays En Garde.
            $owner = $this->getOwningCard($event->theah);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::STAND_YOUR_GROUND_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04045", $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }

        if ($event instanceof EventGenerateChallengeThreat
            && $this->thisSchemeIssuedThisChallenge($event->theah, $event->actorId))
        {
            $owner = $this->getOwningCard($event->theah);
            $event->actorThreat += 1;
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s: Adds 1 Threat to your participant when the challenge is accepted."),
                $owner->getInjectCode()
            );
        }

        if ($event instanceof EventCharacterDestroyed)
        {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;
            if ($owner->Location != Game::LOCATION_PLAYER_HOME)
            {
                return;
            }

            if (! $game->globals->get(Game::IN_DUEL))
            {
                return;
            }

            $challengerId = $event->theah->getDuelChallengerId();
            if (! $this->thisSchemeIssuedThisChallenge($event->theah, (int)$challengerId))
            {
                return;
            }

            // WHY: Only the issuer's adversary. Do not pay the defending mirror scheme
            // when the challenger dies — that player did not issue from their scheme.
            $defenderId = $event->theah->getDuelDefenderId();
            if ($event->characterId != $defenderId)
            {
                return;
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name}\'s adversary was destroyed during the duel. They gain a Renown.'), [
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $gainEvent = EventFactory::createPlayerGainsReknownEvent($owner->ControllerId, 1);
            $event->theah->queueEvent($gainEvent);
        }
    }
}
