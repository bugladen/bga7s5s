<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01202;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01202;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_01202_Test extends TestCase
{
    public function name(): string
    {
        return '_01202 Object of Wonder';
    }

    public function tests(): array
    {
        return [
            'constructs Unique Syrneth CityAttachment with Reaction_01202' => function () {
                $wonder = new _01202();
                Assert::instanceOf(CityAttachment::class, $wonder, 'CityAttachment');
                Assert::instanceOf(IHasReactions::class, $wonder, 'reactions');
                Assert::same(2, $wonder->WealthCost, 'Wealth');
                Assert::same(26, $wonder->CityCardNumber, 'city #');
                Assert::same(0, $wonder->ResolveModifier, 'Resolve');
                Assert::same(0, $wonder->CombatModifier, 'Combat');
                Assert::same(1, $wonder->FinesseModifier, '+1 Finesse');
                Assert::same(1, $wonder->InfluenceModifier, '+1 Influence');
                Assert::true($wonder->hasTrait('Artifact'), 'Artifact');
                Assert::true($wonder->hasTrait('Syrneth'), 'Syrneth');
                Assert::true($wonder->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Reaction_01202::class, $wonder->getReactions()[0], 'Reaction');
            },

            // WHY (journal 2026-04-08-11): Forced always equips to the controller's Leader.
            'getRequiredAttachTargetId redirects to the controller\'s Leader' => function () {
                $world = new TestWorld();
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->leadersByPlayerId[1] = $leader;
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $wonder = $world->placeCard(new _01202(), Game::LOCATION_HAND, 1);

                Assert::same($leader->Id, $wonder->getRequiredAttachTargetId($world->theah, $crew->Id), 'Leader');
            },

            'getRequiredAttachTargetId throws when Leader is missing or in the Locker' => function () {
                $world = new TestWorld();
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $wonder = $world->placeCard(new _01202(), Game::LOCATION_HAND, 1);

                $threw = false;
                try {
                    $wonder->getRequiredAttachTargetId($world->theah, $crew->Id);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'no Leader');

                $leader = $world->placeCharacter(
                    new GenericLeader('Dead Leader'),
                    $world->game->getPlayerLockerName(1),
                    1
                );
                $world->theah->leadersByPlayerId[1] = $leader;

                $threw = false;
                try {
                    $wonder->getRequiredAttachTargetId($world->theah, $crew->Id);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Leader in Locker');
            },

            'eventCheck refuses equip when Leader is unavailable' => function () {
                $world = new TestWorld();
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $wonder = $world->placeCard(new _01202(), Game::LOCATION_HAND, 1);

                $event = new EventAttachmentEquipping();
                $event->attachmentId = $wonder->Id;
                $event->characterId = $crew->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $wonder->eventCheck($event);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'no Leader');
            },

            'eventCheck allows equip when Leader is in play' => function () {
                $world = new TestWorld();
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->leadersByPlayerId[1] = $leader;
                $wonder = $world->placeCard(new _01202(), Game::LOCATION_HAND, 1);

                $event = new EventAttachmentEquipping();
                $event->attachmentId = $wonder->Id;
                $event->characterId = $leader->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $wonder->eventCheck($event);
                Assert::true(true, 'no throw');
            },
        ];
    }
}
