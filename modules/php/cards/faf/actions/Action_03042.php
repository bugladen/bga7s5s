<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03042 extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue a Finesse Challenge");
        $this->RequiresPerformerSelected = true;
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

        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->canChallenge($theah)
                && ! $performer->Engaged
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

            if ($performer === null || ! $performer->canChallenge($event->theah) || $performer->Engaged)
            {
                throw new UserException($game->translate("Performer cannot issue a challenge."));
            }

            if (count($event->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId)) == 0)
            {
                throw new UserException($game->translate("No opposing character at the performer's location."));
            }

            // WHY: Engage printed on the card. Keep WHEN_LEAST_EXPECTED out of
            // stIssueChallenge's auto-engage list so we do not double-engage.
            if (! $performer->Engaged)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id);
                $event->theah->queueEvent($engageEvent);
            }

            $owner = $this->getOwningCard($event->theah);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::WHEN_LEAST_EXPECTED_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03042", $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        // WHY: Duelist-performer refuse cost — discard one hand card, then reject.
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03042)
        {
            $playerId = $game->getActivePlayerId();
            $card = $game->theah->getCardById($id);
            if ($card === null || $card->Location != Game::LOCATION_HAND || $card->ControllerId != $playerId)
            {
                throw new UserException($game->translate("Card must be in your hand."));
            }

            $performer = $game->getCardObjectFromDb($game->globals->get(Game::CHOSEN_PERFORMER));
            $target = $game->getCardObjectFromDb($game->globals->get(Game::CHOSEN_TARGET));

            $owner = $this->getOwningCard($game->theah);
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                $playerId,
                $card->Id,
                $owner->Id,
                $asPayment = false,
                $asPlayed = false,
                $asEffect = true
            );
            $game->theah->queueEvent($discardEvent);

            $rejectEvent = EventFactory::createChallengeRejectedEvent($performer->Id, $target->Id);
            $game->theah->eventCheck($rejectEvent);
            $game->theah->queueEvent($rejectEvent);

            $game->globals->set(Game::CHALLENGE_ACCEPTED, false);

            $game->notify->all("message", clienttranslate('${player_name} discards a card to refuse the challenge from ${scheme_inject_code}.'), [
                "player_name" => $game->getPlayerNameById($playerId),
                "scheme_inject_code" => $owner->getInjectCode(),
            ]);

            $game->gamestate->nextState("cardDiscarded");
        }
    }
}
