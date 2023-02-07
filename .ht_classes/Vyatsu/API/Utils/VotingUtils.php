<?php

namespace Vyatsu\API\Utils;

trait VotingUtils
{
    public function clearText(string $text = '')
    {
        return str_replace(["\r", "\n", "\t"], '', strip_tags($text));
    }

    private function includeModuleOrFail()
    {
        if (\CModule::IncludeModule("vote")) {
            return;
        }

        $user = new \CUser();
        $user->Authorize($this->oldUserId);

        throw new \RuntimeException('Unable to include voting module');
    }
}
