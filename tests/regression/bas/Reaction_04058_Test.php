<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04058;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04058_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04058';
    }

    public function tests(): array
    {
        return [
            // WHY: Opponent ability wound on your en garde Sorcerer, with an opposing
            // character here. Empty abilityId is not an ability. Decline puts the wound
            // back on the performer and skips the requeue. Redirect pays, then the
            // triggered reaction releases the wound onto the chosen character.
            'redirects the wound onto an opposing character' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04058(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new _04041(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $source = $world->placeCard(new _04047(), Game::LOCATION_HAND, 2);
                /** @var Reaction_04058 $reaction */
                $reaction = $risk->getReactions()[0];

                $wound = new EventCharacterBeingWounded();
                $wound->characterId = $sorcerer->Id;
                $wound->sourceId = $source->Id;
                $wound->abilityId = '';
                $wound->wounds = 1;
                $world->fireOn($risk, $wound);
                Assert::false($wound->canceled, 'threat wound');

                $wound->abilityId = $source->getActions()[0]->Id;
                $world->fireOn($risk, $wound);
                Assert::true($wound->canceled, 'intercepted');
                Assert::same($sorcerer->Id, $reaction->performerId, 'performer');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'decline');
                Assert::false($reaction->Used, 'decline');
                $released = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($sorcerer->Id, $released->characterId, 'back on the performer');
                $world->theah->takeQueuedEvents();
                $world->fireOn($risk, $released);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'skip once');

                $wound->canceled = false;
                $sorcerer->Engaged = true;
                $world->fireOn($risk, $wound);
                Assert::false($wound->canceled, 'engaged sorcerer');
                $sorcerer->Engaged = false;

                $world->fireOn($risk, $wound);
                $reaction->performReaction($world->game, 0, '', 'redirect-' . $foe->Id);
                Assert::same($foe->Id, $reaction->chosenTargetId, 'stored before pay');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'pay');

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'redirect-' . $foe->Id;
                $world->fireOn($risk, $triggered);
                Assert::true($reaction->Used, 'spent');
                $redirected = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::same($foe->Id, end($redirected)->characterId, 'wound moved');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
            },

            // WHY: A Cesca copy has no intercepted wound. Decline must not wound the performer.
            'a cesca copy decline does not wound the performer' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04058(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new _04041(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_04058 $reaction */
                $reaction = $risk->getReactions()[0];
                $reaction->beginCopy($world->game, $sorcerer->Id);
                Assert::true($reaction->isCescaCopy, 'copy');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'chooser');

                $reaction->performReaction($world->game, 0, '', 'decline');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no synthetic wound released');
                Assert::false($reaction->isCescaCopy, 'reset');
            },
        ];
    }
}
