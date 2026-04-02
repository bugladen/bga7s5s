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
    private bool $IsActivated;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Gamble For Free");
        $this->IsActivated = false;
    }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, Array &$explanations): int
    {
        $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);
        if ($this->IsActivated)
        {
            $actor = $theah->getDuelRoundActor();
            if ($actor->hasTrait("Scoundrel"))
            {
                $count += 1;
                $owner = $this->getOwningCard($theah);
                $explanations[] = sprintf($theah->game->translate("%s: +1 because Actor is a Scoundrel."), $owner->getInjectCode());
            }
        }
        return $count;    
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->IsActivated = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $game = $event->theah->game;
            $actor = $event->theah->getDuelRoundActor();
            $this->IsActivated = true;
            [$cardCount, $explanations] = $event->theah->getNumberOfGambleCardsToReveal($actor);

            if ($explanations != '')
                $game->notify->player($actor->ControllerId, "message", clienttranslate('Private: Explanations for modification of number of gamble cards to reveal:<br>${explanations}'), [
                    "explanations" => $explanations,
                ]);
    
            $game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_ROLL_THE_DICE);
            $game->globals->set(Game::GAMBLE_REVEAL_COUNT, $cardCount);
            $game->globals->set(Game::GAMBLE_REVEAL_EXPLANATIONS, $explanations);
            $game->globals->set(Game::ROLL_THE_BONES_ACTIVATED, true);
        }
    }

}