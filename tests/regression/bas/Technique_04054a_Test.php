<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;

class Technique_04054a_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04054a';
    }

    public function tests(): array
    {
        return [
            // WHY: The printed +1 Thrust is the shared technique with this card's own id.
            // Challenge threat applies when the bearer is the actor. Another actor does not
            // get it while the sabre still has a host.
            'adds thrust, and challenge threat for the bearer' => function () {
                $world = new TestWorld();
                $sabre = $world->placeCard(new _04054(), Game::LOCATION_CITY_DOCKS, 1);
                $bearer = $world->placeCharacter(new GenericCharacter('Bearer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $sabre->AttachedToId = $bearer->Id;
                $technique = $sabre->getTechniques()[0];
                Assert::instanceOf(Technique_PlusOneThrust::class, $technique, 'shared thrust');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->thrust = 1;
                $world->fireOn($sabre, $calc);
                Assert::same(2, $calc->thrust, '+1 thrust');

                $threat = new EventGenerateChallengeThreat();
                $threat->techniqueId = $technique->Id;
                $threat->actorId = $bearer->Id;
                $threat->adversaryThreat = 0;
                $world->fireOn($sabre, $threat);
                Assert::same(1, $threat->adversaryThreat, 'bearer');

                $threat->actorId = $foe->Id;
                $threat->adversaryThreat = 0;
                $world->fireOn($sabre, $threat);
                Assert::same(0, $threat->adversaryThreat, 'not the bearer');
            },

            // WHY: Rules are once-per-duel. Challenge ActivateTechnique sets Used; after
            // EventDuelEnd the same Sabre must list again for a second challenge same Day.
            'EventDuelEnd reopens Used so a second challenge can announce +1 Thrust' => function () {
                $world = new TestWorld();
                $sabre = $world->placeCard(new _04054(), Game::LOCATION_CITY_DOCKS, 1);
                $bearer = $world->placeCharacter(new GenericCharacter('Bearer'), Game::LOCATION_CITY_DOCKS, 1);
                $bearer->Attachments[] = $sabre->Id;
                $sabre->AttachedToId = $bearer->Id;
                $technique = $sabre->getTechniques()[0];

                $activated = new EventTechniqueActivated();
                $activated->techniqueId = $technique->Id;
                $activated->ownerId = $sabre->Id;
                $activated->playerId = 1;
                $world->fireOn($sabre, $activated);
                Assert::false($technique->isAvailable(), 'used in first challenge');
                Assert::count(
                    0,
                    array_filter(
                        $world->theah->getAvailableCharacterTechniques($bearer),
                        fn(array $t) => $t['id'] === $technique->Id
                    ),
                    'filtered from challenge picker'
                );

                $end = new EventDuelEnd();
                $end->challengerId = $bearer->Id;
                $world->fireOn($sabre, $end);
                Assert::true($technique->isAvailable(), 'reopened after duel');
                Assert::count(
                    1,
                    array_filter(
                        $world->theah->getAvailableCharacterTechniques($bearer),
                        fn(array $t) => $t['id'] === $technique->Id
                    ),
                    'listed for second challenge'
                );
            },

            // WHY: Refuse never fires EventDuelEnd. Challenge Technique Used must still clear
            // or Sabre +1 Thrust stays crossed out for the rest of the Day.
            'EventChallengeRejected reopens Used when the challenge is refused' => function () {
                $world = new TestWorld();
                $sabre = $world->placeCard(new _04054(), Game::LOCATION_CITY_DOCKS, 1);
                $bearer = $world->placeCharacter(new GenericCharacter('Bearer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $bearer->Attachments[] = $sabre->Id;
                $sabre->AttachedToId = $bearer->Id;
                $technique = $sabre->getTechniques()[0];

                $activated = new EventTechniqueActivated();
                $activated->techniqueId = $technique->Id;
                $activated->ownerId = $sabre->Id;
                $activated->playerId = 1;
                $world->fireOn($sabre, $activated);
                Assert::false($technique->isAvailable(), 'used at challenge');

                $rejected = new EventChallengeRejected();
                $rejected->challengerId = $bearer->Id;
                $rejected->targetId = $foe->Id;
                $world->fireOn($sabre, $rejected);
                Assert::true($technique->isAvailable(), 'reopened after refuse');
            },
        ];
    }
}
