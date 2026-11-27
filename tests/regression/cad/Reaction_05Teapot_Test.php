<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Teapot;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Teapot;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_05Teapot_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_05Teapot';
    }

    private function moved(int $cardId, string $to, bool $engage = false): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->cardId = $cardId;
        $event->fromLocation = Game::LOCATION_PLAYER_HOME;
        $event->toLocation = $to;
        $event->engage = $engage;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: En garde Sorcerer performer moves into the city where an opposing character
            // already stands. Offer on the move, pick the target before paying, apply the move
            // Home (no engage) only after the pay step triggers the reaction.
            'moves the chosen opposing character Home after the performer moves' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Teapot(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new _04041(), Game::LOCATION_PLAYER_HOME, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05Teapot $reaction */
                $reaction = $risk->getReactions()[0];

                $world->fireOn($risk, $this->moved($sorcerer->Id, Game::LOCATION_CITY_DOCKS));
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');
                Assert::same($sorcerer->Id, $reaction->performerId, 'performer');

                // The hub applies the performer move after card handlers; mirror the post-move board
                // before the player picks, since the pick happens on a later event.
                $sorcerer->Location = Game::LOCATION_CITY_DOCKS;

                $reaction->performReaction($world->game, 0, '', 'character-' . $foe->Id);
                Assert::same($foe->Id, $reaction->chosenTargetId, 'stored before pay');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'pay');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move before pay');
                Assert::false($reaction->Used, 'not spent before pay');

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'character-' . $foe->Id;
                $world->fireOn($risk, $triggered);

                // Post-pay queues ONLY the targeting hook; Start / move / Played wait for it to
                // survive. Used is set here, and the sticky state stays alive until the hook runs.
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($foe->Id, $targeted[0]->targetId, 'hook target');
                Assert::same($reaction->Id, $targeted[0]->abilityId, 'hook ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move beside hook');
                Assert::count(0, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'no start beside hook');
                Assert::true($reaction->Used, 'spent at pay');
                Assert::same($foe->Id, $reaction->chosenTargetId, 'state kept for the hook');

                // WHY: Used is already true — the survive branch must not sit behind isAvailable().
                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $world->fireOn($risk, $hook);
                Assert::same(0, $reaction->chosenTargetId, 'state reset after hook');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::same($hook->batchId, $moves[0]->batchId, 'move shares batch');
                Assert::count(1, $moves, 'move');
                Assert::same($foe->Id, $moves[0]->cardId, 'foe moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from the city');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
                Assert::false($moves[0]->engage, 'move only');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::true($reaction->Used, 'spent');
            },

            // WHY: Cloud Bottle / UL cancel the hook — no Start, no move, and the sticky state is
            // reset (a canceled targeting must not leave a stale target behind). The Risk stays spent.
            'canceled CharacterTargeted queues nothing and resets the saved state' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Teapot(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new _04041(), Game::LOCATION_PLAYER_HOME, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05Teapot $reaction */
                $reaction = $risk->getReactions()[0];

                $world->fireOn($risk, $this->moved($sorcerer->Id, Game::LOCATION_CITY_DOCKS));
                $sorcerer->Location = Game::LOCATION_CITY_DOCKS;
                $reaction->performReaction($world->game, 0, '', 'character-' . $foe->Id);
                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'character-' . $foe->Id;
                $world->fireOn($risk, $triggered);

                $hook = $world->theah->queuedOfType(EventCharacterTargeted::class)[0];
                $world->theah->takeQueuedEvents();
                $hook->canceled = true;
                $world->fireOn($risk, $hook);

                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same(0, $reaction->chosenTargetId, 'target cleared');
                Assert::same(0, $reaction->performerId, 'performer cleared');
                Assert::true($reaction->Used, 'still spent');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Teapot(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new _04041(), Game::LOCATION_PLAYER_HOME, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05Teapot $reaction */
                $reaction = $risk->getReactions()[0];
                $reaction->chosenTargetId = $foe->Id;
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = 'someone-else';
                $world->fireOn($risk, $hook);
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
                Assert::same($foe->Id, $reaction->chosenTargetId, 'state untouched');
            },

            // WHY: Pass leaves the Risk in hand for a later qualifying move.
            'pass clears the offer without spending' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Teapot(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new _04041(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05Teapot $reaction */
                $reaction = $risk->getReactions()[0];

                $world->fireOn($risk, $this->moved($sorcerer->Id, Game::LOCATION_CITY_DOCKS));
                $reaction->performReaction($world->game, 0, '', 'pass');

                Assert::false($reaction->Used, 'pass');
                Assert::same(0, $reaction->performerId, 'cleared');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
            },

            // WHY: En Garde is a precondition. EventCardMoved handlers run before the hub applies
            // engage, so a move that engages must not offer even though Engaged is still false.
            'does not offer when the performer is or will be engaged' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Teapot(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new _04041(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $world->fireOn($risk, $this->moved($sorcerer->Id, Game::LOCATION_CITY_DOCKS, true));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'move engages');

                $sorcerer->Engaged = true;
                $world->fireOn($risk, $this->moved($sorcerer->Id, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'already engaged');
            },

            'does not offer for a non-Sorcerer or an opposing mover' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Teapot(), Game::LOCATION_HAND, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_PLAYER_HOME, 1);
                $enemySorcerer = $world->placeCharacter(new _04041(), Game::LOCATION_PLAYER_HOME, 2);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $world->fireOn($risk, $this->moved($plain->Id, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not a Sorcerer');

                $world->fireOn($risk, $this->moved($enemySorcerer->Id, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not your performer');
            },

            'does not offer to Home, without an opposing character, or from outside the hand' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Teapot(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new _04041(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $world->fireOn($risk, $this->moved($sorcerer->Id, Game::LOCATION_PLAYER_HOME));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'Home is not a City location');

                $world->fireOn($risk, $this->moved($sorcerer->Id, Game::LOCATION_CITY_FORUM));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'nobody to target');

                $risk->Location = Game::LOCATION_PURGATORY;
                $world->fireOn($risk, $this->moved($sorcerer->Id, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'Risk not in hand');
            },

            'rejects a target that is not an opposing character at the new location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Teapot(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new _04041(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $faraway = $world->placeCharacter(new GenericCharacter('Faraway'), Game::LOCATION_CITY_FORUM, 2);
                /** @var Reaction_05Teapot $reaction */
                $reaction = $risk->getReactions()[0];

                $world->fireOn($risk, $this->moved($sorcerer->Id, Game::LOCATION_CITY_DOCKS));
                $sorcerer->Location = Game::LOCATION_CITY_DOCKS;

                Assert::false($reaction->isValidTargetForAbility($world->game, $ally)[0], 'own character');
                Assert::false($reaction->isValidTargetForAbility($world->game, $faraway)[0], 'wrong location');
            },
        ];
    }
}
