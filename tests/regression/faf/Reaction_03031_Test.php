<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03031;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03031_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03031';
    }

    /**
     * @return array{0:_03031,1:Reaction_03031,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03031(), Game::LOCATION_HAND, 1);
        $victim = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_03031 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $victim, $ally];
    }

    private function wound(TestWorld $world, int $sourceId, int $characterId): EventCharacterBeingWounded
    {
        $event = new EventCharacterBeingWounded();
        $event->theah = $world->theah;
        $event->sourceId = $sourceId;
        $event->abilityId = 'untargeted';
        $event->characterId = $characterId;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: A non-targeting opponent ability still redirects. Pay is queued on the
            // click; the wound is rewritten onto the chosen performer only after the Risk resolves.
            'redirects a wound onto the other character at that location' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $victim, $ally] = $this->scene($world);
                $source = $world->placeCard(new _03032(), Game::LOCATION_HAND, 2);
                $wound = $this->wound($world, $source->Id, $victim->Id);
                $reaction->handleEvent($wound);
                Assert::true($wound->canceled, 'original wound held');
                Assert::same(
                    ['redirect-' . $ally->Id, 'decline'],
                    array_column($reaction->getReactionButtonProperties($world->theah), 'reaction'),
                    'ally or decline'
                );

                $reaction->performReaction($world->game, 0, $reaction->Id, 'redirect-' . $ally->Id);
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'in-hand pay');
                Assert::false($reaction->Used, 'not spent until the risk resolves');

                $world->theah->takeQueuedEvents();
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'redirect-' . $ally->Id;
                $reaction->handleEvent($triggered);
                Assert::same($ally->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'ally suffers it');
                Assert::true($reaction->Used, 'spent');
                unset($risk);
            },

            // WHY: Decline = do not redirect, not cancel. Cancel-first already held the
            // wound; re-release onto the original or leftover threat (and real ability
            // Declines) vanish with no log — Léontine / Blood Mark report 2026-10-30.
            'decline re-releases the wound onto the original, and a friendly or uncontrolled source never offers' => function () {
                $world = new TestWorld();
                [, $reaction, $victim] = $this->scene($world);
                $source = $world->placeCard(new _03032(), Game::LOCATION_HAND, 2);
                $wound = $this->wound($world, $source->Id, $victim->Id);
                $reaction->handleEvent($wound);
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');
                $released = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $released, 'decline re-releases the wound');
                Assert::same($victim->Id, $released[0]->characterId, 'original target');
                Assert::false($reaction->Used, 'decline');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');

                $world->theah->takeQueuedEvents();
                $echo = $this->wound($world, $source->Id, $victim->Id);
                $reaction->handleEvent($echo);
                Assert::false($echo->canceled, 'echo skipped via skipNextEvent');

                $source->ControllerId = 1;
                $friendly = $this->wound($world, $source->Id, $victim->Id);
                $reaction->handleEvent($friendly);
                Assert::false($friendly->canceled, 'own ability');

                $source->ControllerId = 0;
                $wild = $this->wound($world, $source->Id, $victim->Id);
                $reaction->handleEvent($wild);
                Assert::false($wild->canceled, 'uncontrolled source');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no offer');
            },

            // WHY: stDuelEndOfRound leftover threat uses abilityId '' + sourceId = adversary.
            // Controller-only isOpponentAbility treated that as an ability and offered redirect;
            // Decline then ate the wound (Shield Rite journal 2026-10-03-04 latent sibling).
            'empty abilityId leftover-threat wound is not an opponent ability' => function () {
                $world = new TestWorld();
                [, $reaction, $victim] = $this->scene($world);
                $adversary = $world->placeCharacter(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
                $threat = new EventCharacterBeingWounded();
                $threat->theah = $world->theah;
                $threat->sourceId = $adversary->Id;
                $threat->abilityId = '';
                $threat->characterId = $victim->Id;
                $threat->wounds = 2;
                $reaction->handleEvent($threat);
                Assert::false($threat->canceled, 'framework leftover threat');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no Altruistic offer');
            },

            'the risk must be in hand, and there must be someone else at the location' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $victim, $ally] = $this->scene($world);
                $source = $world->placeCard(new _03032(), Game::LOCATION_HAND, 2);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                $home = $this->wound($world, $source->Id, $victim->Id);
                $reaction->handleEvent($home);
                Assert::false($home->canceled, 'not in hand');

                $risk->Location = Game::LOCATION_HAND;
                $ally->Location = Game::LOCATION_CITY_FORUM;
                $alone = $this->wound($world, $source->Id, $victim->Id);
                $reaction->handleEvent($alone);
                Assert::false($alone->canceled, 'nobody to redirect to');
            },

            // WHY: Card text is effect-based. A targeting ability that rejects the new
            // character cancels instead of redirecting, and the Risk is still spent.
            'a targeting ability that rejects the performer cancels the effect' => function () {
                $world = new TestWorld();
                [, $reaction, $victim, $ally] = $this->scene($world);
                $source = $world->placeCard(new _03034(), Game::LOCATION_HAND, 2);
                $diplomat = $world->placeCharacter(new GenericCharacter('Diplomat', ['Diplomat']), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);
                $actionId = $source->getActions()[0]->Id;
                $wound = $this->wound($world, $source->Id, $victim->Id);
                $wound->abilityId = $actionId;
                $reaction->handleEvent($wound);

                $world->theah->takeQueuedEvents();
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'redirect-' . $ally->Id;
                $reaction->handleEvent($triggered);
                // WHY: Must not arm CHALLENGE_CANCELLED — Altruistic never holds a
                // challenge. A sticky true kills the next hub-exit challenge at
                // stTechniqueAvailable (Sanjay / Vittoria lesson).
                Assert::same(false, (bool) $world->game->globals->get(Game::CHALLENGE_CANCELLED, false), 'not a challenge');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'not redirected');
                Assert::true($reaction->Used, 'still spent');
            },

            'engage and move redirect the same way' => function () {
                $world = new TestWorld();
                [, $reaction, $victim, $ally] = $this->scene($world);
                $source = $world->placeCard(new _03032(), Game::LOCATION_HAND, 2);

                $engaged = new EventCardEngaged();
                $engaged->theah = $world->theah;
                $engaged->sourceId = $source->Id;
                $engaged->abilityId = 'untargeted';
                $engaged->cardId = $victim->Id;
                $reaction->handleEvent($engaged);
                Assert::true($engaged->canceled, 'engage held');

                $world->theah->takeQueuedEvents();
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'redirect-' . $ally->Id;
                $reaction->handleEvent($triggered);
                Assert::same($ally->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'ally engaged');

                $reaction->Used = false;
                $moving = new EventCardMoving();
                $moving->theah = $world->theah;
                $moving->sourceId = $source->Id;
                $moving->abilityId = 'untargeted';
                $moving->cardId = $victim->Id;
                $moving->fromLocation = Game::LOCATION_CITY_DOCKS;
                $moving->toLocation = Game::LOCATION_CITY_FORUM;
                $reaction->handleEvent($moving);
                Assert::true($moving->canceled, 'move held');
            },

            // WHY: Declining an intervene puts the defender chip back on the original
            // intervener and skips the event that putting it back would raise.
            'declining an intervene restores the original defender and skips the echo' => function () {
                $world = new TestWorld();
                [, $reaction, $victim, $ally] = $this->scene($world);
                $victim->addCondition(Game::DUEL_DEFENDER);
                $intervened = new EventCharacterIntervened();
                $intervened->theah = $world->theah;
                $intervened->newTargetId = $victim->Id;
                $intervened->oldTargetId = 0;
                $reaction->handleEvent($intervened);
                Assert::true($intervened->canceled, 'intervene held');

                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');
                $released = $world->theah->queuedOfType(EventCharacterIntervened::class)[0];
                Assert::same($victim->Id, $released->newTargetId, 'original intervener');
                Assert::same($victim->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target restored');
                Assert::true($victim->hasCondition(Game::DUEL_DEFENDER), 'defender chip');
                Assert::false($ally->hasCondition(Game::DUEL_DEFENDER), 'ally was not chosen');

                $echo = new EventCharacterIntervened();
                $echo->theah = $world->theah;
                $echo->newTargetId = $victim->Id;
                $reaction->handleEvent($echo);
                Assert::false($echo->canceled, 'echo skipped');
            },
        ];
    }
}
