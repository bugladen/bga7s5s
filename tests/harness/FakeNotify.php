<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

class FakeNotify
{
    /** @var list<array{channel:string,type:string,message:string,args:array}> */
    public array $messages = [];

    public function all(string $type, string $message, array $args = []): void
    {
        $this->messages[] = [
            'channel' => 'all',
            'type' => $type,
            'message' => $message,
            'args' => $args,
        ];
    }

    public function player(int $playerId, string $type, string $message, array $args = []): void
    {
        $this->messages[] = [
            'channel' => 'player:' . $playerId,
            'type' => $type,
            'message' => $message,
            'args' => $args,
        ];
    }
}
