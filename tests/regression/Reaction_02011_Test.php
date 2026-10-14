<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02011;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionProperty;

class Reaction_02011_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02011';
    }

    /**
     * Katain in city with hand card; Polished Flintlock attached (source of ranged ability).
     *
     * @return array{0:_02011,1:Reaction_02011,2:_01049,3:Action_01049,4:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $katain = $world->placeCharacter(new _02011(), Game::LOCATION_CITY_DOCKS, 1);
        $hand = $world->placeCharacter(new GenericCharacter('Hand'), Game::LOCATION_HAND, 1);
        $flint = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 1);
        $flint->AttachedToId = $katain->Id;
        $katain->Attachments[] = $flint->Id;
        /** @var Action_01049 $flintAction */
        $flintAction = $flint->getActions()[0];
        /** @var Reaction_02011 $reaction */
        $reaction = $katain->getReactions()[0];
        return [$katain, $reaction, $flint, $flintAction, $hand];
    }

    private function ranged(
        TestWorld $world,
        int $performerId,
        int $sourceId,
        string $abilityId,
        int $targetId = 0,
        string $targetLocation = ''
    ): EventRangedAbilityPlayed {
        $event = new EventRangedAbilityPlayed();
        $event->playerId = 1;
        $event->performerId = $performerId;
        $event->sourceId = $sourceId;
        $event->abilityId = $abilityId;
        $event->targetId = $targetId;
        $event->targetLocation = $targetLocation;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_02011(), 'CardReaction');
            },

            'offers when Katain plays a ranged ability and hand is nonempty' => function () {
                $world = new TestWorld();
                [$katain, $reaction, $flint, $flintAction] = $this->scene($world);

                $reaction->handleEvent($this->ranged(
                    $world,
                    $katain->Id,
                    $flint->Id,
                    $flintAction->Id
                ));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($katain->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same($flint->Id, $reaction->sourceId, 'stored source');
                Assert::same($flintAction->Id, $reaction->sourceAbilityId, 'stored ability');
            },

            'does not offer when performer is someone else' => function () {
                $world = new TestWorld();
                [, $reaction, $flint, $flintAction] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->ranged(
                    $world,
                    $other->Id,
                    $flint->Id,
                    $flintAction->Id
                ));
                Assert::count(0, $world->theah->queuedEvents, 'not Katain');
            },

            'does not offer when hand is empty' => function () {
                $world = new TestWorld();
                [$katain, $reaction, $flint, $flintAction, $hand] = $this->scene($world);
                $hand->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->ranged(
                    $world,
                    $katain->Id,
                    $flint->Id,
                    $flintAction->Id
                ));
                Assert::count(0, $world->theah->queuedEvents, 'empty hand');
            },

            'does not offer when reaction is Used' => function () {
                $world = new TestWorld();
                [$katain, $reaction, $flint, $flintAction] = $this->scene($world);
                // WHY: assign Used directly — setUsed() also queues ReactionUsed into theah.
                $reaction->Used = true;

                $reaction->handleEvent($this->ranged(
                    $world,
                    $katain->Id,
                    $flint->Id,
                    $flintAction->Id
                ));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'used');
            },

            'decline nextStates done without copying' => function () {
                $world = new TestWorld();
                [$katain, $reaction] = $this->scene($world);
                $before = count($katain->getActions());

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                Assert::same($before, count($katain->getActions()), 'no copy');
                Assert::false($reaction->Used, 'not used');
            },

            // WHY (Reaction_02011): Action_01049 copy keeps originalAttachmentId so
            // "engage this card" effect points at the real flintlock; Technique_01049
            // does NOT (engage is a cost skipped by IsEffectsOnlyCopy). Challenge
            // Techniques use sourceTargetId + skipBaseStatThreat outside duel.
            'copy Action_01049 discards hand, adds effects-only copy, queues ActionTriggered' => function () {
                $world = new TestWorld();
                [$katain, $reaction, $flint, $flintAction, $hand] = $this->scene($world);
                $reaction->sourceId = $flint->Id;
                $reaction->sourceAbilityId = $flintAction->Id;

                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    'copyRangedAbility-' . $hand->Id
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($hand->Id, $discards[0]->cardId, 'hand card');

                $copies = array_values(array_filter(
                    $katain->getActions(),
                    fn($a) => $a instanceof Action_01049 && $a->IsEffectsOnlyCopy
                ));
                Assert::count(1, $copies, 'copied action');
                Assert::true($copies[0]->IsEffectsOnlyCopy, 'effects only');
                Assert::same($flint->Id, $copies[0]->originalAttachmentId, 'originalAttachmentId');
                Assert::true((bool) $world->game->globals->get(Game::ABNORMAL_FLOW), 'ABNORMAL_FLOW');

                $triggered = $world->theah->queuedOfType(EventActionTriggered::class);
                Assert::count(1, $triggered, 'ActionTriggered');
                Assert::same($copies[0]->Id, $triggered[0]->actionId, 'copied id');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                Assert::count(1, $reaction->copiedActions, 'tracked');
            },

            'source-lock WHYs for Action vs Technique copy and challenge Techniques' => function () {
                $src = file_get_contents(
                    dirname(__DIR__, 2) . '/modules/php/cards/tac/reactions/Reaction_02011.php'
                );
                Assert::contains('originalAttachmentId = $source->Id', $src, 'Action keeps attachment');
                Assert::contains('WHY no originalAttachmentId', $src, 'Technique omits');
                Assert::contains('sourceTargetId', $src, 'challenge adversary');
                Assert::contains('skipBaseStatThreat = true', $src, 'no re-add base threat');
            },

            // WHY: turn end clears copies so they do not persist into the next turn.
            'EventPlayerTurnEnd clears copiedActions' => function () {
                $world = new TestWorld();
                [$katain, $reaction, $flint] = $this->scene($world);

                $dummy = new Action_01049();
                $dummy->setOwnerId($katain->Id);
                $dummy->IsEffectsOnlyCopy = true;
                $dummy->originalAttachmentId = $flint->Id;
                $katain->addAction($dummy, $world->game, false);

                $prop = new ReflectionProperty(Reaction_02011::class, 'copiedActions');
                $prop->setAccessible(true);
                $prop->setValue($reaction, [$dummy]);

                Assert::count(1, $katain->getActions(), 'seeded');
                Assert::count(1, $reaction->copiedActions, 'tracked');

                $event = new EventPlayerTurnEnd();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::same([], $reaction->copiedActions, 'cleared tracking');
                Assert::same([], $katain->getActions(), 'removed from host');
            },
        ];
    }
}
