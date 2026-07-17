<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03053 extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Spend a Renown, Claim Location");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        // WHY: Cost is "Spend a Renown" (player score), same as Action_01168 / Action_01139.
        if ($theah->game->getPlayerReknown($playerId) < 1)
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

        // WHY: Sole payoff is Claim — gate performers to claimable locations so the
        // picker never offers a dead action (same discipline as Action_01103a / 03cd13).
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $theah->canLocationBeClaimedBy($playerId, $performer->Location)
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $playerId = $event->playerId;
            $owner = $this->getOwningCard($event->theah);

            if ($game->getPlayerReknown($playerId) < 1)
            {
                throw new UserException($game->translate("You do not have a Renown to spend."));
            }

            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);
            if ($performer === null || $performer->ControllerId != $playerId)
            {
                throw new UserException($game->translate("Invalid performer"));
            }

            if (! $event->theah->cardInCity($performer))
            {
                throw new UserException($game->translate("Performer must be at a City location."));
            }

            $loseEvent = EventFactory::createPlayerLosesReknownEvent($playerId, 1);
            $event->theah->queueEvent($loseEvent);

            if ($event->theah->canLocationBeClaimedBy($playerId, $performer->Location))
            {
                $claimEvent = EventFactory::createLocationClaimedEvent($playerId, $performerId, $performer->Location);
                $event->theah->queueEvent($claimEvent);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${location} cannot be claimed.'), [
                    'i18n' => ['location'],
                    'location' => $performer->Location,
                ]);
            }

            $players = $game->loadPlayersBasicInfos();
            foreach ($players as $opponentId => $_)
            {
                $opponentId = (int)$opponentId;
                if ($opponentId == $playerId)
                {
                    continue;
                }

                $drawEvent = EventFactory::createCardDrawnEvent($opponentId, $owner->getInjectCode());
                $event->theah->queueEvent($drawEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
