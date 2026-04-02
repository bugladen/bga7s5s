<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbilityTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class CardAction extends Action implements ICardAbility
{
    use CardAbilityTrait;

    public function __construct()
    {
        parent::__construct();
        $this->initializeAbility();
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);
        if ($owner->isControlled() && $owner->ControllerId != $playerId && ! $overrideInHandCheck)
        {
            return false;
        }

        return ! $this->Used;
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, Array &$explanations): int
    {
        return 0;
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $args["abnormalFlow"] = $game->globals->get(Game::ABNORMAL_FLOW, false);

        return $args;
    }


    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->setUsed($event->theah, false);
        }
    }

    public function announceAction(Game $game): void
    {
        $owner = $this->getOwningCard($game->theah);
        $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} has used the [${action}] Action.'), [
            'i18n' => ['action'],
            'player_name' => $game->getActivePlayerName(),
            'action' => $this->Name,
            'owner_inject_code' => $owner->getInjectCode(),
        ]);

        $activatedEvent = EventFactory::createActionActivatedEvent(
            (int)$game->getActivePlayerId(),
            $owner->Id,
            $this->Id
        );
        $game->theah->queueEvent($activatedEvent);
    }

    public function doCost(Game $game): void {}

    public function doEffect(Game $game): void {}
}