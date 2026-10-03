<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use app\enums\CustomFieldType;
use app\helpers\Application;
use app\helpers\MemberCustomFields;
use app\modules\Common\AbstractController;
use app\modules\PersonManager\valueObjects\CustomFieldDefinition;
use app\modules\PersonManager\viewModels\MemberFieldsViewModel;

final class MemberFieldController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function edit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $viewModel = new MemberFieldsViewModel(
                fields: array_map(
                    static fn(CustomFieldDefinition $d) => $d->toArray(),
                    $this->loadDefinitions()
                ),
                types: array_map(
                    fn(CustomFieldType $t) => ['value' => $t->value, 'label' => ($this->t)($t->translationKey())],
                    CustomFieldType::cases()
                ),
                i18n: [
                    'delete_confirm' => ($this->t)('custom_fields.delete_confirm'),
                ],
                layout: $this->getLayout(),
                layoutParams: $this->getAllParams([]),
            );

            $this->render('PersonManager/views/memberFields.latte', $viewModel->toArray());
        }
    }

    public function save(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $data = $this->flight->request()->data->getData();
            $rows = is_array($data['fields'] ?? null) ? $data['fields'] : [];

            $definitions = MemberCustomFields::buildDefinitions($rows, $this->loadDefinitions());
            $this->dataHelper->setSetting(
                MemberCustomFields::SETTING_KEY,
                MemberCustomFields::encodeDefinitions($definitions)
            );

            $this->redirect('/personManager');
        }
    }

    /** @return list<CustomFieldDefinition> */
    private function loadDefinitions(): array
    {
        return MemberCustomFields::parseDefinitions(
            $this->dataHelper->getSetting(MemberCustomFields::SETTING_KEY, '[]')
        );
    }
}
