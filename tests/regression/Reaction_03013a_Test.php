<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03013a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03013a_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03013a';
    }

    /**
     * @return array{0:_03013,1:Reaction_03013a,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $daniella = $world->placeCharacter(new _03013(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_03013a $reaction */
        $reaction = $daniella->getReactions()[1];
        return [$daniella, $reaction, $foe];
    }

    public function tests(): array
    {
        return [
            'is a continuous card reaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_03013a(), 'card reaction');
            },

            // WHY: Transitions stamp sourceId for routing, not as an ability source.
            // Action_03013 is the sibling picker and must not double-prompt.
            // A technique she owns does prompt, even before a duel exists.
            'prompts when she uses an ability, and not for her own Sorcerer action' => function () {
                $world = new TestWorld();
                [$daniella, $reaction, $foe] = $this->scene($world);
                $technique = new EventTechniqueActivated();
                $technique->theah = $world->theah;
                $technique->ownerId = $daniella->Id;
                $technique->techniqueId = $daniella->getTechniques()[0]->Id;
                $reaction->handleEvent($technique);
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'technique');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(["grant-{$foe->Id}", 'pass'], $buttons, 'foe or pass');

                $world->theah->takeQueuedEvents();
                $transition = new EventTransition();
                $transition->theah = $world->theah;
                $transition->sourceId = $daniella->Id;
                $transition->transition = 'somewhere';
                $reaction->handleEvent($transition);
                Assert::count(0, $world->theah->queuedEvents, 'routing transition is not an ability');

                $action = new EventActionTriggered();
                $action->theah = $world->theah;
                $action->sourceId = $daniella->Id;
                $action->actionId = $daniella->getActions()[0]->Id;
                $reaction->handleEvent($action);
                Assert::count(0, $world->theah->queuedEvents, 'Action_03013 is ignored');
            },

            'grant adds Sorcerer and does not spend; the activation it stacks does not prompt again' => function () {
                $world = new TestWorld();
                [$daniella, $reaction, $foe] = $this->scene($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, "grant-{$foe->Id}");
                Assert::true($foe->hasTrait('Sorcerer'), 'granted');
                Assert::false($reaction->Used, 'continuous');
                $activated = $world->theah->queuedOfType(EventReactionActivated::class)[0];
                Assert::same($daniella->Id, $activated->sourceId, 'source is Daniella');

                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($activated);
                Assert::count(0, $world->theah->queuedEvents, 'own activation suppressed');
            },

            'pass, a friendly id, and Home do not grant' => function () {
                $world = new TestWorld();
                [$daniella, $reaction, $foe] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $reaction->performReaction($world->game, 0, $reaction->Id, "grant-{$ally->Id}");
                Assert::false($ally->hasTrait('Sorcerer'), 'not opposing');

                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($foe->hasTrait('Sorcerer'), 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventReactionActivated::class), 'pass emits nothing');

                $daniella->Location = Game::LOCATION_PLAYER_HOME;
                $foe->Location = Game::LOCATION_PLAYER_HOME;
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['pass'], $buttons, 'home has no opposing target');
            },

            'a challenge, or the granted character leaving, removes Sorcerer' => function () {
                $world = new TestWorld();
                [, $reaction, $foe] = $this->scene($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, "grant-{$foe->Id}");
                $issued = new EventChallengeIssued();
                $issued->theah = $world->theah;
                $reaction->handleEvent($issued);
                Assert::false($foe->hasTrait('Sorcerer'), 'challenge');

                $reaction->performReaction($world->game, 0, $reaction->Id, "grant-{$foe->Id}");
                $moved = new EventCardMoved();
                $moved->theah = $world->theah;
                $moved->cardId = $foe->Id;
                $moved->fromLocation = Game::LOCATION_CITY_DOCKS;
                $moved->toLocation = Game::LOCATION_CITY_FORUM;
                $reaction->handleEvent($moved);
                Assert::false($foe->hasTrait('Sorcerer'), 'left her location');
            },
        ];
    }
}
