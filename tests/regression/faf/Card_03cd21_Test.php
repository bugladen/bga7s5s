<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd21;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01078;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01172;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01078;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_03cd21_Test extends TestCase
{
    public function name(): string
    {
        return '_03cd21 Silver Spine';
    }

    /**
     * @return array{0:_03cd21,1:GenericCharacter,2:_01078}
     */
    private function equipped(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 2);
        $world->placeCharacter(new GenericCharacter('Friendly'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var _03cd21 $spine */
        $spine = $world->placeCard(new _03cd21(), Game::LOCATION_CITY_DOCKS, 2);
        $spine->AttachedToId = $host->Id;
        $host->Attachments[] = $spine->Id;
        $risk = $world->placeCard(new _01078(), Game::LOCATION_HAND, 1);
        return [$spine, $host, $risk];
    }

    public function tests(): array
    {
        return [
            // WHY: Forced is not a Reaction. Defending Honor targets the equipped
            // character (they issue the challenge). That is the first Risk of the Day
            // and must cancel before they choose a defender. No Decline.
            'Defending Honor targeting cancels and removes the choose-target transition' => function () {
                $world = new TestWorld();
                [$spine, $host, $risk] = $this->equipped($world);
                /** @var Action_01078 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeted queued');
                $spine->handleEvent($targeted[0]);

                Assert::true($targeted[0]->canceled, 'canceled');
                Assert::true($spine->hasCondition(Game::SILVER_SPINE_ABILITY_USED), 'spent');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no choose-target');
                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::count(1, $resolved, 'action resolved');
                Assert::same(1, $resolved[0]->playerId, 'risk controller');
            },

            'a later Risk the same Day does not cancel' => function () {
                $world = new TestWorld();
                [$spine, $host] = $this->equipped($world);
                $spine->addCondition(Game::SILVER_SPINE_ABILITY_USED);
                $pull = $world->placeCard(new _01172(), Game::LOCATION_HAND, 1);

                $move = new EventCardMoving();
                $move->cardId = $host->Id;
                $move->sourceId = $pull->Id;
                $move->fromLocation = Game::LOCATION_CITY_DOCKS;
                $move->toLocation = Game::LOCATION_CITY_FORUM;
                $move->theah = $world->theah;
                $spine->handleEvent($move);

                Assert::false($move->canceled, 'second risk stands');
            },

            'Pull is the cancel when it is the first opponent Risk' => function () {
                $world = new TestWorld();
                [$spine, $host] = $this->equipped($world);
                $pull = $world->placeCard(new _01172(), Game::LOCATION_HAND, 1);

                $move = new EventCardMoving();
                $move->cardId = $host->Id;
                $move->sourceId = $pull->Id;
                $move->fromLocation = Game::LOCATION_CITY_DOCKS;
                $move->toLocation = Game::LOCATION_CITY_FORUM;
                $move->theah = $world->theah;
                $spine->handleEvent($move);

                Assert::true($move->canceled, 'canceled');
                Assert::true($spine->hasCondition(Game::SILVER_SPINE_ABILITY_USED), 'spent');
            },

            'does not cancel the equipped character\'s own Risk' => function () {
                $world = new TestWorld();
                [$spine, $host] = $this->equipped($world);
                $own = $world->placeCard(new _01172(), Game::LOCATION_HAND, 2);

                $move = new EventCardMoving();
                $move->cardId = $host->Id;
                $move->sourceId = $own->Id;
                $move->theah = $world->theah;
                $spine->handleEvent($move);

                Assert::false($move->canceled, 'own risk');
                Assert::false($spine->hasCondition(Game::SILVER_SPINE_ABILITY_USED), 'unused');
            },

            'challenge issued with the equipped character as challenger sets CHALLENGE_CANCELLED' => function () {
                $world = new TestWorld();
                [$spine, $host, $risk] = $this->equipped($world);
                $defender = $world->theah->getCharactersInPlay();
                $defenderId = 0;
                foreach ($defender as $character) {
                    if ($character->Id !== $host->Id) {
                        $defenderId = $character->Id;
                    }
                }

                $issued = new EventChallengeIssued();
                $issued->challengerId = $host->Id;
                $issued->defenderId = $defenderId;
                $issued->sourceId = $risk->Id;
                $issued->theah = $world->theah;
                $spine->handleEvent($issued);

                Assert::true($issued->canceled, 'canceled');
                Assert::true($world->game->globals->get(Game::CHALLENGE_CANCELLED, false), 'flag');
                Assert::true($spine->hasCondition(Game::SILVER_SPINE_ABILITY_USED), 'spent');
            },

            // WHY: Celerity prints "These effects cannot be cancelled." Framework
            // target-pick fires CharacterTargeted first. Spine used to announce +
            // spend the Day while the challenge still proceeded (no CHALLENGE_CANCELLED
            // on that path either). Immunity must skip the Forced entirely.
            'Celerity targeting does not cancel or spend the Day' => function () {
                $world = new TestWorld();
                [$spine, $host] = $this->equipped($world);
                $celerity = $world->placeCard(new _04047(), Game::LOCATION_HAND, 1);

                $targeted = new EventCharacterTargeted();
                $targeted->targetId = $host->Id;
                $targeted->sourceId = $celerity->Id;
                $targeted->abilityId = $celerity->getActions()[0]->Id;
                $targeted->playerId = 1;
                $targeted->theah = $world->theah;
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::CELERITY_CHALLENGE_TYPE);
                $spine->handleEvent($targeted);

                Assert::false($targeted->canceled, 'not canceled');
                Assert::false($spine->hasCondition(Game::SILVER_SPINE_ABILITY_USED), 'unused');
                Assert::false($world->game->globals->get(Game::CHALLENGE_CANCELLED, false), 'no flag');
            },

            'Celerity ChallengeIssued does not cancel or spend the Day' => function () {
                $world = new TestWorld();
                [$spine, $host] = $this->equipped($world);
                $celerity = $world->placeCard(new _04047(), Game::LOCATION_HAND, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);

                $issued = new EventChallengeIssued();
                $issued->challengerId = $challenger->Id;
                $issued->defenderId = $host->Id;
                $issued->sourceId = $celerity->Id;
                $issued->theah = $world->theah;
                $spine->handleEvent($issued);

                Assert::false($issued->canceled, 'not canceled');
                Assert::false($spine->hasCondition(Game::SILVER_SPINE_ABILITY_USED), 'unused');
                Assert::false($world->game->globals->get(Game::CHALLENGE_CANCELLED, false), 'no flag');
            },

            // WHY: Framework Risk challenges (Censure) fire CharacterTargeted before
            // TECHNIQUE_AVAILABLE. Cancel must set CHALLENGE_CANCELLED or the duel
            // still issues after Spine has already spent its Day.
            'Censure framework target-pick cancel sets CHALLENGE_CANCELLED' => function () {
                $world = new TestWorld();
                [$spine, $host] = $this->equipped($world);
                $censure = $world->placeCard(new _03057(), Game::LOCATION_HAND, 1);

                $targeted = new EventCharacterTargeted();
                $targeted->targetId = $host->Id;
                $targeted->sourceId = $censure->Id;
                $targeted->abilityId = $censure->getActions()[0]->Id;
                $targeted->playerId = 1;
                $targeted->batchId = 99;
                $targeted->theah = $world->theah;
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::CENSURE_CHALLENGE_TYPE);
                $spine->handleEvent($targeted);

                Assert::true($targeted->canceled, 'canceled');
                Assert::true($spine->hasCondition(Game::SILVER_SPINE_ABILITY_USED), 'spent');
                Assert::true($world->game->globals->get(Game::CHALLENGE_CANCELLED, false), 'flag');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'technique gate resolves');
            },

            'Dusk clears the once-per-Day condition' => function () {
                $world = new TestWorld();
                [$spine] = $this->equipped($world);
                $spine->addCondition(Game::SILVER_SPINE_ABILITY_USED);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $spine->handleEvent($dusk);

                Assert::false($spine->hasCondition(Game::SILVER_SPINE_ABILITY_USED), 'cleared');
            },
        ];
    }
}
