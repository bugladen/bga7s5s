<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _02046 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Winter's Wind");
        $this->Image = '02046.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 46;

        $this->initializeFaction('Ussura');
        $this->Initiative = 44;
        $this->PanacheModifier = 1;

        $this->Traits = [
            clienttranslate("Tură's Touch"),
            clienttranslate("Demoralize")
        ];

        $this->Text = clienttranslate("<p>Add a Renown to any location. Then, you may add a Renown to a different location.</p><hr><p>After any character enters play during High Drama, engage them. <i>(Characters enter play when they are recruited, played from hand, or mustered.)</i></p>");
        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose a City Location to place Renown onto. Then they may choose a different location for a second Renown.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "02046");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        // WHY: Recruiting only happens during High Drama, so no phase check needed.
        if ($event instanceof EventCharacterRecruited)
        {
            $character = $event->theah->getCardById($event->characterId);
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code}: ${card_inject_code} enters play during High Drama and is Engaged.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "card_inject_code" => $character->getInjectCode(),
            ]);

            $engageEvent = EventFactory::createCardEngagedEvent($this->ControllerId, $event->characterId, $this->Id);
            $event->theah->queueEvent($engageEvent);
        }

        if ($event instanceof EventCharacterMustered && $this->isHighDramaPhase($event))
        {
            $character = $event->theah->getCardById($event->characterId);
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code}: ${card_inject_code} enters play during High Drama and is Engaged.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "card_inject_code" => $character->getInjectCode(),
            ]);

            $engageEvent = EventFactory::createCardEngagedEvent($this->ControllerId, $event->characterId, $this->Id);
            $event->theah->queueEvent($engageEvent);
        }
    }

    private function isHighDramaPhase(Event $event): bool
    {
        $stateId = $event->theah->game->gamestate->getCurrentMainStateId();
        $prefix = (int)substr((string)$stateId, 0, 2);
        return $prefix >= 30 && $prefix < 70;
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02046_2)
        {
            $args["chosenLocation"] = $game->globals->get(Game::CHOSEN_LOCATION);
        }

        return $args;
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02046_2)
        {
            $game->gamestate->nextState("");
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02046)
        {
            $location = $ids[0];

            $reknownEvent = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($reknownEvent);
            $game->theah->queueEvent($reknownEvent);

            $game->globals->set(Game::CHOSEN_LOCATION, $location);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "02046_2");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02046_2)
        {
            $location = $ids[0];
            $chosenLocation = $game->globals->get(Game::CHOSEN_LOCATION);

            if ($location == $chosenLocation)
            {
                throw new UserException($game->translate("You must choose a different location."));
            }

            $reknownEvent = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($reknownEvent);
            $game->theah->queueEvent($reknownEvent);

            $game->gamestate->nextState("");
        }
    }
}
