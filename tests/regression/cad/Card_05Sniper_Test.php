<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Sniper;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Sniper;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Sniper;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_05Sniper_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Sniper';
    }

    /** @return array{0:_05Sniper,1:Reaction_05Sniper,2:Technique_05Sniper} */
    private function place(TestWorld $world, string $location, int $controllerId = 1): array
    {
        $eleanor = $world->placeCharacter(new _05Sniper(), $location, $controllerId);
        /** @var Reaction_05Sniper $reaction */
        $reaction = $eleanor->getReactions()[0];
        /** @var Technique_05Sniper $technique */
        $technique = $eleanor->getTechniques()[0];
        return [$eleanor, $reaction, $technique];
    }

    private function issued(TestWorld $world, int $challengerId, int $defenderId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        $event->playerId = 2;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'controller may treat in-play Eleanor as a Ranged Weapon' => function () {
                $world = new TestWorld();
                [$eleanor] = $this->place($world, Game::LOCATION_CITY_FORUM);

                Assert::true($eleanor->countsAsEquippedWithRangedWeapon($world->theah, 1), 'her player');
                Assert::false($eleanor->countsAsEquippedWithRangedWeapon($world->theah, 2), 'opponent');
            },

            'fiction does not apply from hand or when blanked' => function () {
                $world = new TestWorld();
                [$inHand] = $this->place($world, Game::LOCATION_HAND);
                Assert::false($inHand->countsAsEquippedWithRangedWeapon($world->theah, 1), 'hand');

                [$blanked] = $this->place($world, Game::LOCATION_CITY_DOCKS);
                $blanked->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($blanked->countsAsEquippedWithRangedWeapon($world->theah, 1), 'silenced');
            },

            'a real Ranged Weapon still counts for an opponent check' => function () {
                $world = new TestWorld();
                [$eleanor] = $this->place($world, Game::LOCATION_CITY_DOCKS);
                $flint = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 1);
                $flint->AttachedToId = $eleanor->Id;
                $eleanor->Attachments[] = $flint->Id;

                Assert::true($eleanor->countsAsEquippedWithRangedWeapon($world->theah, 2), 'real gun');
            },

            'Long Shot maneuver accepts Eleanor with no attachment' => function () {
                $world = new TestWorld();
                [$eleanor] = $this->place($world, Game::LOCATION_CITY_DOCKS);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $risk = $world->placeCard(new _01055(), Game::LOCATION_HAND, 1);
                $world->theah->duelActor = $eleanor;
                $world->theah->duelOpponent = $foe;

                /** @var Maneuver_01055 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'her maneuver');
                Assert::false($maneuver->isAvailableToPlayer(2, $world->theah), 'not their ability');
            },

            'offers when a challenge is issued at her location' => function () {
                $world = new TestWorld();
                [$eleanor, $reaction] = $this->place($world, Game::LOCATION_CITY_FORUM);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_FORUM, 2);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_FORUM, 1);

                $reaction->handleEvent($this->issued($world, $challenger->Id, $defender->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($eleanor->Id, $transitions[0]->sourceId, 'source');
            },

            'offers for an adjacent city and from Home, not for a far city or Home while she is in the city' => function () {
                $world = new TestWorld();
                [, $atDocks] = $this->place($world, Game::LOCATION_CITY_DOCKS);
                $forumChallenger = $world->placeCharacter(new GenericCharacter('Forum'), Game::LOCATION_CITY_FORUM, 2);
                $forumDefender = $world->placeCharacter(new GenericCharacter('ForumDef'), Game::LOCATION_CITY_FORUM, 1);
                $atDocks->handleEvent($this->issued($world, $forumChallenger->Id, $forumDefender->Id));
                Assert::count(1, $world->theah->takeQueuedEvents(), 'docks sees forum');

                $bazaarChallenger = $world->placeCharacter(new GenericCharacter('Bazaar'), Game::LOCATION_CITY_BAZAAR, 2);
                $bazaarDefender = $world->placeCharacter(new GenericCharacter('BazaarDef'), Game::LOCATION_CITY_BAZAAR, 1);
                $atDocks->handleEvent($this->issued($world, $bazaarChallenger->Id, $bazaarDefender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'docks does not see bazaar');

                $homeChallenger = $world->placeCharacter(new GenericCharacter('HomeC'), Game::LOCATION_PLAYER_HOME, 2);
                $homeDefender = $world->placeCharacter(new GenericCharacter('HomeD'), Game::LOCATION_PLAYER_HOME, 1);
                $atDocks->handleEvent($this->issued($world, $homeChallenger->Id, $homeDefender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'city does not see home');

                [, $atHome] = $this->place($world, Game::LOCATION_PLAYER_HOME);
                $atHome->handleEvent($this->issued($world, $forumChallenger->Id, $forumDefender->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'home sees forum');
            },

            'wound choice wounds that character and pass does not spend the reaction' => function () {
                $world = new TestWorld();
                [$eleanor, $reaction] = $this->place($world, Game::LOCATION_CITY_FORUM);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_FORUM, 2);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_FORUM, 1);
                $reaction->handleEvent($this->issued($world, $challenger->Id, $defender->Id));

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass keeps it');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');

                $reaction->performReaction($world->game, 0, $reaction->Id, "wound-{$challenger->Id}");
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($challenger->Id, $wounds[0]->characterId, 'challenger');
                Assert::same($eleanor->Id, $wounds[0]->sourceId, 'source');
                Assert::same($reaction->Id, $wounds[0]->abilityId, 'ability');
                Assert::true($reaction->Used, 'spent');
            },

            'does not offer again after it is used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->place($world, Game::LOCATION_CITY_FORUM);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_FORUM, 2);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_FORUM, 1);
                $reaction->Used = true;

                $reaction->handleEvent($this->issued($world, $challenger->Id, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'spent');
            },

            'technique grants lethal in a challenge and in her duel round' => function () {
                $world = new TestWorld();
                [$eleanor, , $technique] = $this->place($world, Game::LOCATION_CITY_FORUM);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);

                $threat = new EventGenerateChallengeThreat();
                $threat->techniqueId = $technique->Id;
                $threat->actorId = $eleanor->Id;
                $threat->theah = $world->theah;
                $technique->handleEvent($threat);
                Assert::true($threat->adversaryThreatIsLethal, 'challenge lethal');

                $world->theah->duelActor = $eleanor;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'her round');

                $world->theah->duelActor = $foe;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not her round');

                $world->theah->duelActor = $eleanor;
                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->actorId = $eleanor->Id;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);
                $modified = $world->theah->queuedOfType(EventThreatModified::class);
                Assert::count(1, $modified, 'duel lethal');
                Assert::true($modified[0]->defenderThreatIsLethal, 'adversary lethal');
                Assert::same(null, $modified[0]->challengerThreatIsLethal, 'her threat unchanged');
            },
        ];
    }
}
