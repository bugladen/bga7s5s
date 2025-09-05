<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01184 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Count Only Performers and En Garde Characters for Pressures');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to count only the Performer and En Garde Characters for this Pressure: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Count only Performer and En Garde Characters'), 'specialCount');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPressureOccuring && $this->isAvailable())
        {
            $claude = $this->getOwningCharacter($event->theah);
            if ($claude->isControlled())
            {
                if ($event->theah->cardInCity($claude) && $event->location == $claude->Location && $claude->ControllerId)
                {
                    $transition = EventFactory::createReactionTransitionEvent($claude->ControllerId, $claude->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'specialCount')
        {
            $claude = $this->getOwningCard($game->theah);
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::CLAUDE_PRESSURE_TYPE);
            $game->globals->set(Game::CLAUD_ID, $claude->Id);

            $game->notifyAllPlayers('message', clienttranslate('${owner_inject_code}: ${player_name} used Reaction to count only the Performer and En Garde Characters for this Pressure.'), [
                'owner_inject_code' => $claude->getInjectCode(),
                'player_name' => $game->getActivePlayerName(),
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");        
    }

}