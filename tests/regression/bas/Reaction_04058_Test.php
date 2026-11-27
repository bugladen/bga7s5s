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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Wardog;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Wardoga;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
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

                // WHY: pay completion queues ONLY CharacterTargeted; Sorcerer start, the wound
                // and Sorcerer played wait for it to survive (Cloud Bottle / UL / Maryam).
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($foe->Id, $targeted[0]->targetId, 'target');
                Assert::same($risk->Id, $targeted[0]->sourceId, 'source');
                Assert::same($reaction->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound yet');
                Assert::count(0, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'no start yet');
                Assert::count(0, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'no played yet');

                $survived = $targeted[0];
                $survived->theah = $world->theah;
                $world->fireOn($risk, $survived);
                $redirected = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $redirected, 'wound released');
                Assert::same($foe->Id, $redirected[0]->characterId, 'wound moved');
                Assert::same($survived->batchId, $redirected[0]->batchId, 'shares batch');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::same(null, $reaction->chosenTargetId, 'state reset after survive');
                Assert::same(null, $reaction->performerId, 'performer reset');
            },

            // WHY: A canceled targeting drops the redirected wound entirely and clears sticky
            // state; the intercepted original wound is not re-released.
            'canceled CharacterTargeted drops the redirected wound and resets state' => function () {
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
                $wound->abilityId = $source->getActions()[0]->Id;
                $wound->wounds = 1;
                $world->fireOn($risk, $wound);
                Assert::true($wound->canceled, 'intercepted');

                $reaction->performReaction($world->game, 0, '', 'redirect-' . $foe->Id);
                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'redirect-' . $foe->Id;
                $world->fireOn($risk, $triggered);

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class)[0];
                $targeted->theah = $world->theah;
                $targeted->canceled = true;
                $world->theah->takeQueuedEvents();
                $world->fireOn($risk, $targeted);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::count(0, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'no start');
                Assert::count(0, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'no played');
                Assert::same(null, $reaction->chosenTargetId, 'state reset');
                Assert::same(null, $reaction->performerId, 'performer reset');
            },

            // WHY: Smuggled Long Rifle Covert Reaction wounds from an equipped attachment
            // (sourceId = attachment, abilityId = Reaction_05Wardoga). Shield Rite must treat
            // that as an opponent's ability — same ControllerId branch as hand Risks.
            'redirects a Smuggled Long Rifle reaction wound' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04058(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new _04041(), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                $host = $world->placeCharacter(new GenericCharacter('Sniper'), Game::LOCATION_CITY_DOCKS, 2);
                $rifle = $world->placeCard(new _05Wardog(), Game::LOCATION_CITY_DOCKS, 2);
                $rifle->AttachedToId = $host->Id;
                /** @var Reaction_05Wardoga $wardog */
                $wardog = $rifle->getReactions()[0];
                /** @var Reaction_04058 $reaction */
                $reaction = $risk->getReactions()[0];

                $wardog->EnemyIds = [$sorcerer->Id];
                $wardog->performReaction($world->game, 0, $wardog->Id, 'wound');
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'rifle wound');
                Assert::same($rifle->Id, $wounds[0]->sourceId, 'attachment source');
                Assert::same($wardog->Id, $wounds[0]->abilityId, 'reaction ability');

                $world->fireOn($risk, $wounds[0]);
                Assert::true($wounds[0]->canceled, 'shield rite intercepts rifle wound');
                Assert::same($sorcerer->Id, $reaction->performerId, 'performer');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');
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
