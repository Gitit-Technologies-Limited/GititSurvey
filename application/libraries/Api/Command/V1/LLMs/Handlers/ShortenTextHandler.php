<?php

namespace LimeSurvey\Libraries\Api\Command\V1\LLMs\Handlers;

class ShortenTextHandler implements CommandHandlerInterface
{
    public function canHandle(Command $command)
    {
        if (str_contains(strtolower($command->getOperation()), 'short')) {
            return true;
        }
        return false;
    }

    public function execute(Command $command, AIClientInterface $client)
    {
        $command->setOperation('Shorten the text without altering the sentence type');
        return $client->generateContent();
    }
}
