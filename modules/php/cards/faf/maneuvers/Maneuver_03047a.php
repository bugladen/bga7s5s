<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGambleCardsRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03047a extends Maneuver
{
    public bool $ChooseAdversaryGambleCombatCard;

    public int $BlockedAdversaryCharacterId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Riposte; Choose Adversary Combat Card if They Gamble");
        $this->ChooseAdversaryGambleCombatCard = false;
        $this->BlockedAdversaryCharacterId = 0;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        return $actor->hasTrait("Scoundrel");
    }

    private function clearChooseLock(Theah $theah): void
    {
        $this->ChooseAdversaryGambleCombatCard = false;
        $this->BlockedAdversaryCharacterId = 0;
        $owner = $this->getOwningCard($theah);
        if ($owner)
        {
            $owner->IsUpdated = true;
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $adversary = $event->theah->getDuelRoundOpponent();
            $this->ChooseAdversaryGambleCombatCard = true;
            $this->BlockedAdversaryCharacterId = $adversary->Id;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Riposte."), $owner->getInjectCode());
        }

        // WHY: Fire on revealed (not AttemptGamble) so the adversary still commits to
        // gambling and reveal count/reactions resolve before we steal the chooser seat.
        // Transition priority (8) is after reaction transitions (6), so Ivy-style
        // "before choosing" reactions still run first.
        if ($event instanceof EventDuelGambleCardsRevealed
            && $this->ChooseAdversaryGambleCombatCard
            && $event->actorId == $this->BlockedAdversaryCharacterId)
        {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;
            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} must choose the adversary\'s combat card from the revealed gamble cards.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);
            $transition = EventFactory::createTransitionEvent(
                $owner->ControllerId,
                $owner->Id,
                "03047",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }

        // Effect spent its round once the blocked adversary's round ends (gambled or not).
        if ($event instanceof EventDuelEndOfRound
            && $this->ChooseAdversaryGambleCombatCard
            && $event->actorId == $this->BlockedAdversaryCharacterId)
        {
            $this->clearChooseLock($event->theah);
        }

        $owner = $this->getOwningCard($event->theah);
        if ($event instanceof EventDuelNewRound
            && $this->ChooseAdversaryGambleCombatCard
            && $owner
            && $event->theah->getCharacterById($event->actorId)->ControllerId == $owner->ControllerId)
        {
            $this->clearChooseLock($event->theah);
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->clearChooseLock($event->theah);
        }

        if ($event instanceof EventDuelEnd && $this->ChooseAdversaryGambleCombatCard)
        {
            $this->clearChooseLock($event->theah);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        // WHY: Public cards for this state — every player (and spectators) sees the
        // revealed gamble while Proper Drama owns the chooser seat. Routed via
        // Card::argsFromCard → getArgsFromManeuver (argsForState).
        if ($state == States::DUEL_CHOOSE_GAMBLE_CARD_03047)
        {
            $gamblingPlayerId = $game->theah->getDuelRoundActor()->ControllerId;
            $count = $game->globals->get(Game::GAMBLE_REVEAL_COUNT, 2);
            $fromBottom = $game->globals->get(Game::GAMBLE_REVEAL_FROM_BOTTOM, false);
            $deckCards = $fromBottom
                ? $game->getCardsOnBottomOfPlayerFactionDeck($gamblingPlayerId, $count)
                : $game->getCardsOnTopOfPlayerFactionDeck($gamblingPlayerId, $count);
            $cards = [];
            foreach ($deckCards as $deckCard)
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }

            $args['cards'] = $cards;
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_GAMBLE_CARD_03047)
        {
            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} chooses the adversary\'s combat card.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            // One-shot for this gamble; clear before choose completes the round flow.
            $this->clearChooseLock($game->theah);
            $game->actGambleCardChosen($id);
        }
    }
}
