<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01091;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01093;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatDependsOnNotBeingFirstPlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

// WHY: No shipped Technique/Maneuver implements IAbilityThatDependsOnNotBeingFirstPlayer yet (only Actions/Reactions),
// but Reaction_01090 has Technique/Maneuver branches. These stand-ins exercise those branches without production changes.
class Reaction01090TestTechnique extends Technique implements IAbilityThatDependsOnNotBeingFirstPlayer
{
    // EventTechniqueCanceled handler not needed
}

class Reaction01090TestManeuver extends Maneuver implements IAbilityThatDependsOnNotBeingFirstPlayer
{
    // EventManeuverCanceled handler not needed
}

class Reaction01090TestHost extends GenericCharacter implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct('Host');
        $this->Techniques[] = new Reaction01090TestTechnique();
        $this->Maneuvers[] = new Reaction01090TestManeuver();
    }
}

class Reaction_01090_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01090';
    }

    /** @return array{0:_01090,1:Reaction_01090,2:_01093} Lorenzo (P1), his Reaction, Maya (P1; Action_01093 depends on first-player status) */
    private function scene(TestWorld $world): array
    {
        $lorenzo = $world->placeCharacter(new _01090(), Game::LOCATION_CITY_DOCKS, 1);
        $maya = $world->placeCharacter(new _01093(), Game::LOCATION_CITY_FORUM, 1);
        /** @var Reaction_01090 $reaction */
        $reaction = $lorenzo->getReactions()[0];
        return [$lorenzo, $reaction, $maya];
    }

    private function actionActivated(TestWorld $world, $source, string $actionId, int $playerId = 1): EventActionActivated
    {
        $event = new EventActionActivated();
        $event->playerId = $playerId;
        $event->sourceId = $source->Id;
        $event->actionId = $actionId;
        $event->theah = $world->theah;
        return $event;
    }

    private function turnEnd(TestWorld $world): EventPlayerTurnEnd
    {
        $event = new EventPlayerTurnEnd();
        $event->playerId = 1;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'button set offers resolve-as-not-first-player and decline' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['resolveAbility', 'decline'], $ids, 'buttons');
            },

            'offers on announcing an ability that depends on being first player' => function () {
                $world = new TestWorld();
                [$lorenzo, $reaction, $maya] = $this->scene($world);

                $reaction->handleEvent($this->actionActivated($world, $maya, $maya->getActions()[0]->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction transition');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'this reaction');
                Assert::same($lorenzo->Id, $transitions[0]->sourceId, 'source is Lorenzo');
                Assert::same(1, $transitions[0]->playerId, 'Lorenzo\'s controller answers');
            },

            // WHY: offer is stacked (stackEvent -> front of queue) so it resolves before the ability's own events.
            'offer is stacked in front of already queued events' => function () {
                $world = new TestWorld();
                [, $reaction, $maya] = $this->scene($world);
                $filler = new EventPlayerTurnEnd();
                $world->theah->queueEvent($filler);

                $reaction->handleEvent($this->actionActivated($world, $maya, $maya->getActions()[0]->Id));

                Assert::instanceOf(EventTransition::class, $world->theah->queuedEvents[0], 'offer first');
                Assert::same($filler, $world->theah->queuedEvents[1], 'filler behind');
            },

            // WHY (journal 2026-03-27-01): don't offer when the ability wouldn't benefit from the override.
            'does not offer for an ability that is not first-player dependent' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $dolores = $world->placeCharacter(new _01091(), Game::LOCATION_CITY_FORUM, 1);

                $reaction->handleEvent($this->actionActivated($world, $dolores, $dolores->getActions()[0]->Id));

                Assert::count(0, $world->theah->queuedEvents, 'no offer');
            },

            'does not offer when an opponent announces the ability' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $theirMaya = $world->placeCharacter(new _01093(), Game::LOCATION_CITY_FORUM, 2);

                $reaction->handleEvent($this->actionActivated($world, $theirMaya, $theirMaya->getActions()[0]->Id, 2));

                Assert::count(0, $world->theah->queuedEvents, 'not Lorenzo\'s controller');
            },

            'does not offer once the Reaction is used' => function () {
                $world = new TestWorld();
                [, $reaction, $maya] = $this->scene($world);
                $reaction->setUsed($world->theah, true);
                $world->theah->takeQueuedEvents();

                $reaction->handleEvent($this->actionActivated($world, $maya, $maya->getActions()[0]->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'offers on a Reaction activation that is first-player dependent' => function () {
                $world = new TestWorld();
                [, $reaction, $maya] = $this->scene($world);
                // Action ids resolve through getAbilityById, so Maya's dependent Action stands in for a dependent Reaction id.
                $event = new EventReactionActivated();
                $event->playerId = 1;
                $event->sourceId = $maya->Id;
                $event->reactionId = $maya->getActions()[0]->Id;
                $event->theah = $world->theah;

                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offer');
            },

            'does not offer on a Reaction activation that is not first-player dependent' => function () {
                $world = new TestWorld();
                [$lorenzo, $reaction] = $this->scene($world);
                $event = new EventReactionActivated();
                $event->playerId = 1;
                $event->sourceId = $lorenzo->Id;
                $event->reactionId = $reaction->Id;
                $event->theah = $world->theah;

                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'own reaction is not dependent (no re-offer loop)');
            },

            'offers on a dependent Technique activation' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $host = $world->placeCharacter(new Reaction01090TestHost(), Game::LOCATION_CITY_DOCKS, 1);
                $event = new EventTechniqueActivated();
                $event->playerId = 1;
                $event->ownerId = $host->Id;
                $event->techniqueId = $host->getTechniques()[0]->Id;
                $event->copied = false;
                $event->theah = $world->theah;

                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offer');
            },

            'does not offer on a non-dependent Technique activation' => function () {
                $world = new TestWorld();
                [$lorenzo, $reaction] = $this->scene($world);
                $event = new EventTechniqueActivated();
                $event->playerId = 1;
                $event->ownerId = $lorenzo->Id;
                $event->techniqueId = $lorenzo->getTechniques()[0]->Id;
                $event->copied = false;
                $event->theah = $world->theah;

                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'not dependent');
            },

            'offers on a dependent Maneuver activation' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $host = $world->placeCharacter(new Reaction01090TestHost(), Game::LOCATION_CITY_DOCKS, 1);
                $event = new EventManeuverActivated();
                $event->playerId = 1;
                $event->ownerId = $host->Id;
                $event->maneuverId = $host->getManeuvers()[0]->Id;
                $event->theah = $world->theah;

                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offer');
            },

            'resolveAbility sets the override, spends the Reaction and finishes the state' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'resolveAbility');

                Assert::true($world->game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER) === true, 'override');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                $notes = array_filter($world->game->notify->messages, fn($m) => $m['type'] === 'overrideAsNotFirstPlayer');
                Assert::count(1, $notes, 'announced');
            },

            'decline leaves the override off and the Reaction available' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                Assert::same(null, $world->game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER), 'no override');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                Assert::count(0, $world->theah->queuedOfType(EventReactionActivated::class), 'decline is not an activation');
            },

            'resolveAbility stacks a ReactionActivated event for the reaction' => function () {
                $world = new TestWorld();
                [$lorenzo, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'resolveAbility');

                $activated = $world->theah->queuedOfType(EventReactionActivated::class);
                Assert::count(1, $activated, 'activated');
                // The CardReaction base carries the chosen button id (not the ability id) on the stacked event.
                Assert::same('resolveAbility', $activated[0]->reactionId, 'chosen button');
                Assert::same($lorenzo->Id, $activated[0]->sourceId, 'source Lorenzo');
            },

            // WHY: stNextPlayer only fires EventPlayerTurnEnd when EXTRA_ACTIONS is already 0.
            // Clear on any turn end while OVERRIDE is set — otherwise First Player keeps it all day.
            'turn end clears an active override' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);

                $reaction->handleEvent($this->turnEnd($world));

                Assert::true($world->game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER) === false, 'override cleared');
            },

            'turn end clears the override even when EXTRA_ACTIONS is still non-zero' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $world->game->globals->set(Game::EXTRA_ACTIONS, 1);
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);

                $reaction->handleEvent($this->turnEnd($world));

                Assert::true($world->game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER) === false, 'override cleared');
            },

            'turn end without an active override does nothing' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->turnEnd($world));

                Assert::same(null, $world->game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER), 'untouched');
            },

            'Dusk resets the Reaction' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->setUsed($world->theah, true);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $reaction->handleEvent($dusk);

                Assert::false($reaction->Used, 'reset');
            },
        ];
    }
}
