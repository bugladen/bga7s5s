<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _03061 extends Scheme implements IHasActions
{
    use ActionTrait;

    // WHY: Forced wounds one-at-a-time so order is player-chosen and wound/reactions
    // can fire between picks. Persist the remaining queue across HD-end event loops.
    /** @var list<int> */
    public array $remainingWoundIds = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Burn like Mice");
        $this->Image = '03061.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 61;

        $this->Initiative = 32;
        $this->PanacheModifier = -1;

        $this->initializeFaction('Neutral');

        $this->Traits = [
            clienttranslate('Heroic'),
            clienttranslate('Declaration')
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [City Forum] and [The Grande Bazaar].</p>
<hr />
<p><b>Hero City Action:</b> Target an en garde non-<b>Leader</b> character at a player's <b>Home</b> • Move them to your performer's location.</p>
<p><b>Forced:</b> At the end of High Drama, wound each character at each player's <b>Home</b> in an order of your choice.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03061(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The City Forum and The Grand Bazaar.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $forum = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($forum);

            $bazaar = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($bazaar);
        }

        // WHY: Chosen schemes sit at LOCATION_PLAYER_HOME until Dusk (same gate as
        // Planning-End Forced on _01098 / _03041). Trigger is EventHighDramaPhaseEnd
        // (not Dusk) — matches "end of High Drama" / Equal Claim _03cd12.
        if ($event instanceof EventHighDramaPhaseEnd && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $characters = $event->theah->getCharactersAtHome();
            if (count($characters) == 0)
            {
                return;
            }

            $playerName = $event->theah->game->getPlayerNameById($this->ControllerId);
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name}\'s Forced ability triggers. They must wound each character at Home in an order of their choice.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $playerName,
            ]);

            $ids = array_values(array_map(fn(Character $character) => $character->Id, $characters));

            // WHY: Single target has no order choice — wound immediately and skip the pick state.
            if (count($ids) == 1)
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                    $ids[0],
                    $this->Id,
                    1,
                    $this->getInjectCode(),
                    $this->Id
                );
                $event->theah->queueEvent($woundEvent);
                return;
            }

            $this->remainingWoundIds = $ids;
            $this->IsUpdated = true;

            // WHY: Empty internalId so actFromCardWithId stays on the scheme Forced path,
            // not Action_03061 (which shares the "03061" transition key on the HD-turn map).
            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "03061");
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::HIGH_DRAMA_END_03061)
        {
            $this->pruneRemainingWoundIds($game);
            $args['ids'] = array_values($this->remainingWoundIds);
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::HIGH_DRAMA_END_03061)
        {
            $this->pruneRemainingWoundIds($game);

            if (! in_array($id, $this->remainingWoundIds, true))
            {
                throw new UserException($game->translate("Character is not among those remaining to wound."));
            }

            $character = $game->theah->getCharacterById($id);
            if ($character === null || $character->Location != Game::LOCATION_PLAYER_HOME)
            {
                throw new UserException($game->translate("Character must be at a player's Home."));
            }

            $this->remainingWoundIds = array_values(array_filter(
                $this->remainingWoundIds,
                fn(int $remainingId) => $remainingId != $id
            ));
            $this->IsUpdated = true;

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $id,
                $this->Id,
                1,
                $this->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundEvent);

            // WHY: Re-queue after the wound so reactions between wounds can fire in
            // HIGH_DRAMA_END_EVENTS before the next pick (wound priority < transition).
            if (count($this->remainingWoundIds) > 0)
            {
                $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "03061");
                $game->theah->queueEvent($transition);
            }

            $game->gamestate->nextState("");
        }
    }

    private function pruneRemainingWoundIds(Game $game): void
    {
        // WHY: A prior wound (or intervening reaction) may have moved/destroyed a
        // queued character — drop them so the pick UI / zombie never soft-locks.
        $pruned = [];
        foreach ($this->remainingWoundIds as $remainingId)
        {
            $character = $game->theah->getCharacterById($remainingId);
            if ($character !== null && $character->Location == Game::LOCATION_PLAYER_HOME)
            {
                $pruned[] = $remainingId;
            }
        }

        if ($pruned !== $this->remainingWoundIds)
        {
            $this->remainingWoundIds = $pruned;
            $this->IsUpdated = true;
        }
    }
}

