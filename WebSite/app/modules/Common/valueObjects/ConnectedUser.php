<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\enums\Authorization;

final readonly class ConnectedUser
{
    /**
     * @param list<string> $authorizations
     */
    public function __construct(
        public Person $person,
        public array $authorizations = [],
    ) {
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function hasAuthorization(Authorization $authorization): bool
    {
        return in_array($authorization->value, $this->authorizations, true);
    }

    public function isAdministrator(): bool
    {
        return $this->isCommunicationManager()
            || $this->isDesigner()
            || $this->isEditor()
            || $this->isEventManager()
            || $this->isLoanManager()
            || $this->isPersonManager()
            || $this->isRedactor()
            || $this->isTranslator()
            || $this->isVisitorInsights()
            || $this->isWebmaster();
    }

    public function isCommunicationManager(): bool
    {
        return $this->hasAuthorization(Authorization::CommunicationManager);
    }

    public function isDesigner(): bool
    {
        return $this->isEventDesigner()
            || $this->isExerciseDesigner()
            || $this->isHomeDesigner()
            || $this->isKanbanDesigner()
            || $this->isLoanDesigner()
            || $this->isMenuDesigner();
    }

    public function isEditor(): bool
    {
        return $this->hasAuthorization(Authorization::Editor);
    }

    public function isEventDesigner(): bool
    {
        return $this->hasAuthorization(Authorization::EventDesigner);
    }

    public function isEventManager(): bool
    {
        return $this->hasAuthorization(Authorization::EventManager);
    }

    public function isExerciseDesigner(): bool
    {
        return $this->hasAuthorization(Authorization::ExerciseDesigner);
    }

    public function isGroupManager(): bool
    {
        return $this->isPersonManager() || $this->isWebmaster();
    }

    public function isHomeDesigner(): bool
    {
        return $this->hasAuthorization(Authorization::HomeDesigner);
    }

    public function isKanbanDesigner(): bool
    {
        return $this->hasAuthorization(Authorization::KanbanDesigner);
    }

    public function isLoan(): bool
    {
        return $this->isLoanDesigner() || $this->isLoanManager();
    }

    public function isLoanDesigner(): bool
    {
        return $this->hasAuthorization(Authorization::LoanDesigner);
    }

    public function isLoanManager(): bool
    {
        return $this->hasAuthorization(Authorization::LoanManager);
    }

    public function isMenuDesigner(): bool
    {
        return $this->hasAuthorization(Authorization::MenuDesigner);
    }

    public function isPersonManager(): bool
    {
        return $this->hasAuthorization(Authorization::PersonManager);
    }

    public function isRedactor(): bool
    {
        return $this->hasAuthorization(Authorization::Redactor);
    }

    public function isTranslator(): bool
    {
        return $this->hasAuthorization(Authorization::Translator);
    }

    public function isVisitorInsights(): bool
    {
        return $this->hasAuthorization(Authorization::VisitorInsights);
    }

    public function isWebmaster(): bool
    {
        return $this->hasAuthorization(Authorization::Webmaster);
    }

    public function hasAuthorizationAny(): bool
    {
        return $this->authorizations !== [];
    }

    public function hasOnlyOneAuthorization(): bool
    {
        return count($this->authorizations) === 1;
    }
}
