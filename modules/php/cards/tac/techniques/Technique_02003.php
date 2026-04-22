<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02003 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Draw a Card");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL);
        if (! $inDuel)
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if ($actor->ControllerId != $playerId)
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocation($actor->Location);
        $characters = array_filter($characters, fn($c) => $c->ControllerId == $playerId && $c->hasTrait("Strega"));
        if (count($characters) == 0)
        {
            return false;
        }

        $combatCards = $theah->getCombatCardsForCurrentRound();
        $sorceryPlayed = false;
        foreach ($combatCards as $combatCard)
        {
            if ($combatCard->hasTrait("Sorcery"))
            {
                $sorceryPlayed = true;
                break;
            }
        }

        return $sorceryPlayed;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
            $event->theah->queueEvent($drawEvent);
        }
    }
}