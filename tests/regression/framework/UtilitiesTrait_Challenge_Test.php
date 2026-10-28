<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\UtilitiesTraitHost;

/** UtilitiesTrait: challenge last-known snapshots and participant marker cleanup. */
class UtilitiesTrait_Challenge_Test extends TestCase
{
    public function name(): string
    {
        return 'UtilitiesTrait: challenge snapshots and markers';
    }

    private function host(): UtilitiesTraitHost
    {
        return new UtilitiesTraitHost();
    }

    public function tests(): array
    {
        return [
            'getChallengeLastKnownCharacter reads challenger and defender snapshots' => function () {
                $host = $this->host();
                $challenger = new GenericCharacter('Challenger');
                $challenger->setId(11);
                $defender = new GenericCharacter('Defender');
                $defender->setId(22);

                $host->globals->set(Game::CHALLENGE_LAST_KNOWN_CHALLENGER, serialize($challenger));
                $host->globals->set(Game::CHALLENGE_LAST_KNOWN_DEFENDER, serialize($defender));

                $found = $host->getChallengeLastKnownCharacter(22);
                Assert::instanceOf(GenericCharacter::class, $found, 'defender');
                Assert::same(22, $found->Id, 'id');
                Assert::same('Defender', $found->Name, 'name');

                Assert::same(11, $host->getChallengeLastKnownCharacter(11)->Id, 'challenger');
                Assert::same(null, $host->getChallengeLastKnownCharacter(99), 'missing');
            },

            'clearChallengeLastKnownParticipants deletes both globals' => function () {
                $host = $this->host();
                $host->globals->set(Game::CHALLENGE_LAST_KNOWN_CHALLENGER, 'x');
                $host->globals->set(Game::CHALLENGE_LAST_KNOWN_DEFENDER, 'y');
                $host->clearChallengeLastKnownParticipants();
                Assert::false($host->globals->has(Game::CHALLENGE_LAST_KNOWN_CHALLENGER), 'challenger cleared');
                Assert::false($host->globals->has(Game::CHALLENGE_LAST_KNOWN_DEFENDER), 'defender cleared');
            },

            'clearChallengeParticipantMarkers strips conditions and notifies' => function () {
                $host = $this->host();
                $challenger = $host->place(new GenericCharacter('C'), Game::LOCATION_CITY_DOCKS, 1);
                $defender = $host->place(new GenericCharacter('D'), Game::LOCATION_CITY_FORUM, 2);
                $challenger->addCondition(Game::DUEL_CHALLENGER);
                $defender->addCondition(Game::DUEL_DEFENDER);

                $host->clearChallengeParticipantMarkers($challenger->Id, $defender->Id);

                Assert::false($challenger->hasCondition(Game::DUEL_CHALLENGER), 'challenger cleared');
                Assert::false($defender->hasCondition(Game::DUEL_DEFENDER), 'defender cleared');
                Assert::count(1, $host->notify->messages, 'one notify');
                Assert::same('challengeMarkersCleared', $host->notify->messages[0]['type'], 'type');
                Assert::same($challenger->Id, $host->notify->messages[0]['args']['challengerId'], 'c id');
                Assert::same($defender->Id, $host->notify->messages[0]['args']['defenderId'], 'd id');
            },

            'clearChallengeParticipantMarkers tolerates missing cards and null ids' => function () {
                $host = $this->host();
                $survivor = $host->place(new GenericCharacter('S'), Game::LOCATION_CITY_DOCKS, 1);
                $survivor->addCondition(Game::DUEL_DEFENDER);

                $host->clearChallengeParticipantMarkers(9999, $survivor->Id);
                Assert::false($survivor->hasCondition(Game::DUEL_DEFENDER), 'survivor cleared');

                $host->clearChallengeParticipantMarkers(null, null);
                Assert::same(
                    'challengeMarkersCleared',
                    $host->notify->messages[1]['type'],
                    'null ids still notify'
                );
                Assert::same(0, $host->notify->messages[1]['args']['challengerId'], 'zero c');
                Assert::same(0, $host->notify->messages[1]['args']['defenderId'], 'zero d');
            },
        ];
    }
}
