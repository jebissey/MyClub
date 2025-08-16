<?php

namespace app\interfaces;

interface AuthorizationServiceInterface
{
    public function getUserEmail(): string;
    public function getUserId(): int;
    public function isEventDesigner(): bool;
    public function isEventManager(): bool;
    public function isHomeDesigner(): bool;
    public function isVisitorInsights(): bool;
    public function isWebmaster(): bool;
}
