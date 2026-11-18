<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Excalibur;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Excalibur;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Excalibur;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_05Excalibur_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Excalibur';
    }

    /** @return array{0:GenericCharacter,1:_05Excalibur,2:Reaction_05Excalibur,3:Technique_05Excalibur} */
    private function attach(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), $location, 1);
        $blade = $world->placeCard(new _05Excalibur(), $location, 1);
        $blade->AttachedToId = $host->Id;
        /** @var Reaction_05Excalibur $reaction */
        $reaction = $blade->getReactions()[0];
        /** @var Technique_05Excalibur $technique */
        $technique = $blade->getTechniques()[0];
        return [$host, $blade, $reaction, $technique];
    }

    private function issued(TestWorld $world, int $challengerId, int $defenderId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        $event->playerId = 1;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Eisen FactionAttachment with Reaction and Technique' => function () {
                $blade = new _05Excalibur();
                Assert::instanceOf(FactionAttachment::class, $blade, 'FactionAttachment');
                Assert::instanceOf(IHasReactions::class, $blade, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $blade, 'techniques');
                Assert::same(1, $blade->WealthCost, 'cost');
                Assert::same(1, $blade->Riposte, 'riposte');
                Assert::same(1, $blade->Parry, 'parry');
                Assert::same(2, $blade->Thrust, 'thrust');
                Assert::true($blade->hasTrait('Weapon'), 'Weapon');
                Assert::true($blade->hasTrait('Sword'), 'Sword');
                Assert::true($blade->hasTrait('Relic'), 'Relic');
                Assert::instanceOf(Reaction_05Excalibur::class, $blade->getReactions()[0], 'reaction');
                Assert::instanceOf(Technique_05Excalibur::class, $blade->getTechniques()[0], 'technique');
            },

            'equipping grants Knight, and unequipping removes it' => function () {
                $world = new TestWorld();
                $blade = $world->placeCard(new _05Excalibur(), Game::LOCATION_CITY_DOCKS, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $equipped = new EventAttachmentEquipped();
                $equipped->theah = $world->theah;
                $equipped->attachmentId = $blade->Id;
                $equipped->characterId = $host->Id;
                $blade->handleEvent($equipped);
                Assert::true($host->hasTrait('Knight'), 'gained Knight');

                $unequipped = new EventAttachmentUnequipped();
                $unequipped->theah = $world->theah;
                $unequipped->attachmentId = $blade->Id;
                $unequipped->characterId = $host->Id;
                $blade->handleEvent($unequipped);
                Assert::false($host->hasTrait('Knight'), 'removed');
            },

            'offers when equipped performer challenges with an opposing character present' => function () {
                $world = new TestWorld();
                [$host, $blade, $reaction] = $this->attach($world);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->issued($world, $host->Id, $target->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($blade->Id, $transitions[0]->sourceId, 'source');
            },

            'offers when only a weaker opposing character is present' => function () {
                $world = new TestWorld();
                [$host, , $reaction] = $this->attach($world);
                $host->ModifiedCombat = 3;
                $weak = $world->placeCharacter(new GenericCharacter('Weak'), Game::LOCATION_CITY_DOCKS, 2);
                $weak->ModifiedCombat = 1;

                $reaction->handleEvent($this->issued($world, $host->Id, $weak->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'no combat gate');
            },

            'does not offer when no opposing character is at the location' => function () {
                $world = new TestWorld();
                [$host, , $reaction] = $this->attach($world);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 2);

                $reaction->handleEvent($this->issued($world, $host->Id, $elsewhere->Id));
                Assert::count(0, $world->theah->queuedEvents, 'empty location');
            },

            'does not offer when a different character issues the challenge' => function () {
                $world = new TestWorld();
                [$host, , $reaction] = $this->attach($world);
                $other = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->issued($world, $other->Id, $target->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not equipped challenger');
            },

            'does not offer when not attached' => function () {
                $world = new TestWorld();
                $blade = $world->placeCard(new _05Excalibur(), Game::LOCATION_HAND, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05Excalibur $reaction */
                $reaction = $blade->getReactions()[0];

                $reaction->handleEvent($this->issued($world, $challenger->Id, $target->Id));
                Assert::count(0, $world->theah->queuedEvents, 'hand');
            },

            'buttons list every opposing character at the location, not only the defender' => function () {
                $world = new TestWorld();
                [$host, , $reaction] = $this->attach($world);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
                $bystander = $world->placeCharacter(new GenericCharacter('Bystander'), Game::LOCATION_CITY_DOCKS, 2);
                $weak = $world->placeCharacter(new GenericCharacter('Weak'), Game::LOCATION_CITY_DOCKS, 2);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 2);

                $reaction->handleEvent($this->issued($world, $host->Id, $defender->Id));
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array("wound-{$defender->Id}", $ids, true), 'defender');
                Assert::true(in_array("wound-{$bystander->Id}", $ids, true), 'bystander');
                Assert::true(in_array("wound-{$weak->Id}", $ids, true), 'weak');
                Assert::false(in_array("wound-{$elsewhere->Id}", $ids, true), 'elsewhere');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            'wounding a bystander wounds performer and that bystander' => function () {
                $world = new TestWorld();
                [$host, $blade, $reaction] = $this->attach($world);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
                $bystander = $world->placeCharacter(new GenericCharacter('Bystander'), Game::LOCATION_CITY_DOCKS, 2);
                $reaction->handleEvent($this->issued($world, $host->Id, $defender->Id));

                $reaction->performReaction($world->game, 0, $reaction->Id, "wound-{$bystander->Id}");

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(2, $wounds, 'both wounded');
                Assert::same($host->Id, $wounds[0]->characterId, 'performer first');
                Assert::same($bystander->Id, $wounds[1]->characterId, 'bystander');
                Assert::same($blade->Id, $wounds[0]->sourceId, 'source');
                Assert::same($reaction->Id, $wounds[0]->abilityId, 'ability');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass does not spend the reaction' => function () {
                $world = new TestWorld();
                [$host, , $reaction] = $this->attach($world);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                $reaction->handleEvent($this->issued($world, $host->Id, $target->Id));

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass keeps it');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },

            'technique adds one Riposte in duel only' => function () {
                $world = new TestWorld();
                [$host, , , $technique] = $this->attach($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');

                $world->theah->duelActor = $host;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'in duel');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->actorId = $host->Id;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->riposte, 'riposte');
            },
        ];
    }
}
