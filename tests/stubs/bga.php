<?php

/**
 * Minimal BGA framework stubs so game PHP can load outside Studio.
 * WHY: _ide_helper.php dies on include; we only need what card/composite
 * constructors and handleEvent paths touch under regression tests.
 */

namespace Bga\GameFramework {
    enum StateType: string
    {
        case ACTIVE_PLAYER = 'activeplayer';
        case MULTIPLE_ACTIVE_PLAYER = 'multipleactiveplayer';
        case PRIVATE = 'private';
        case GAME = 'game';
        case MANAGER = 'manager';
    }

    class UserException extends \Exception
    {
    }
}

namespace {
    if (!function_exists('clienttranslate')) {
        function clienttranslate(string $text): string
        {
            return $text;
        }
    }

    // WHY: StatesTrait::stPlanningPhaseDetermineFirstPlayer picks a random tied player with the
    // BGA global bga_rand(). Tests only assert the winner is one of the tied players.
    if (!function_exists('bga_rand')) {
        function bga_rand(int $min, int $max): int
        {
            return random_int($min, $max);
        }
    }

    if (!function_exists('totranslate')) {
        function totranslate(string $text): string
        {
            return $text;
        }
    }

    if (!class_exists('BgaUserException', false)) {
        class BgaUserException extends \Exception
        {
        }
    }
}
