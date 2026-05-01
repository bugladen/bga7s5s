<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01114 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Gamble For Free");
    }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, Array &$explanations): int
    {
        $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);
        $game = $theah->game;
        if ($game->globals->get(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_NORMAL) == Game::GAMBLE_TYPE_ROLL_THE_DICE)
        {
            $owner = $this->getOwningCard($theah);
            if ($owner !== null && $game->globals->get(Game::ROLL_THE_BONES_CARD_ID, 0) == $owner->Id)
            {
                $duelActor = $theah->getDuelRoundActor();
                if ($duelActor->hasTrait("Scoundrel"))
                {
                    $count += 1;
                    $explanations[] = sprintf($game->translate("%s: +1 because Actor is a Scoundrel."), $owner->getInjectCode());
                }
            }
        }
        return $count;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            // WHY: Defensive cleanup. deleteManeuverEvents normally removes the
            // queued EventResolveManeuver before cancel fires, so the globals
            // below are never set — but if the ordering changes we don't want
            // the ROLL_THE_DICE flags to leak into a normal gamble.
            $game = $event->theah->game;
            if ($game->globals->get(Game::ROLL_THE_BONES_CARD_ID, 0) == $this->OwnerId)
            {
                $game->globals->delete(Game::ROLL_THE_BONES_CARD_ID);
                $game->globals->delete(Game::ROLL_THE_BONES_ACTIVATED);
                $game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_NORMAL);
            }
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $game = $event->theah->game;
            $actor = $event->theah->getDuelRoundActor();
            $owner = $this->getOwningCard($event->theah);

            // WHY: Set GAMBLE_TYPE and ROLL_THE_BONES_CARD_ID before computing the
            // reveal count so getNumberOfGambleCardsToReveal can detect the active
            // Roll the Bones gamble via globals instead of an instance flag (which
            // would persist across rounds and silently leak the +1 Scoundrel bonus).
            $game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_ROLL_THE_DICE);
            $game->globals->set(Game::ROLL_THE_BONES_CARD_ID, $owner->Id);
            $game->globals->set(Game::ROLL_THE_BONES_ACTIVATED, true);

            [$cardCount, $explanations] = $event->theah->getNumberOfGambleCardsToReveal($actor);

            if ($explanations != '')
                $game->notify->player($actor->ControllerId, "message", clienttranslate('Private: Explanations for modification of number of gamble cards to reveal:<br>${explanations}'), [
                    "explanations" => $explanations,
                ]);

            $game->globals->set(Game::GAMBLE_REVEAL_COUNT, $cardCount);
            $game->globals->set(Game::GAMBLE_REVEAL_EXPLANATIONS, $explanations);
        }
    }

}
