<?php

namespace App\Service;

interface AiAssistantServiceInterface
{
    public function answer(string $question): string;
}