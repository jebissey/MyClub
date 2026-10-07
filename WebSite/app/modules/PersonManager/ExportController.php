<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use RuntimeException;
use app\helpers\Application;
use app\helpers\MemberCustomFields;
use app\helpers\OdsWriter;
use app\models\PersonDataHelper;
use app\modules\Common\AbstractController;
use app\modules\PersonManager\valueObjects\CustomFieldDefinition;
use app\modules\PersonManager\viewModels\UsersExportViewModel;

final class ExportController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly PersonDataHelper $personDataHelper
    ) {
        parent::__construct($application);
    }


    public function download(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            return;
        }

        $definitions = MemberCustomFields::parseDefinitions(
            $this->dataHelper->getSetting(MemberCustomFields::SETTING_KEY, '[]')
        );

        $header = [
            'Id',
            ($this->t)('export.column.active'),
            ($this->t)('import.form.email'),
            ($this->t)('import.form.firstname'),
            ($this->t)('import.form.lastname'),
            ($this->t)('import.form.phone'),
            ...array_map(static fn(CustomFieldDefinition $d) => $d->label, $definitions),
        ];

        $rows = [];
        foreach ($this->personDataHelper->getMembersForExport() as $member) {
            $rows[] = [
                (int)$member->Id,
                !(bool)$member->Inactivated,
                $member->Email,
                $member->FirstName,
                $member->LastName,
                $member->Phone,
                ...MemberCustomFields::toExportCells($definitions, MemberCustomFields::decodeValues($member->CustomFields)),
            ];
        }

        $content = OdsWriter::build($header, $rows, ($this->t)('export.sheet_name'), hiddenLeadingColumns: 1);
        $this->renderOds($content, $this->defaultFileName() . '.ods');
    }

    public function showExportForm(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $viewModel = new UsersExportViewModel(
                defaultFileName: $this->defaultFileName(),
                layout: $this->getLayout(),
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );

            $this->render('PersonManager/views/users_export.latte', $viewModel->toArray());
        }
    }

    #private functions
    private function defaultFileName(): string
    {
        return ($this->t)('export.default_file_name') . '-' . date('Y-m-d');
    }
}
