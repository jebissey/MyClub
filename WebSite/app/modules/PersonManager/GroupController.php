<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use stdClass;
use Throwable;
use app\enums\FilterInputRule;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ReservedIds;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\ChatMessageDataHelper;
use app\models\GroupDataHelper;
use app\models\MessageDataHelper;
use app\models\PersonGroupDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\valueObjects\GroupChatRow;
use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Common\viewModels\ChatViewModel;
use app\modules\PersonManager\valueObjects\GroupEditRow;
use app\modules\PersonManager\viewModels\GroupCreateViewModel;
use app\modules\PersonManager\viewModels\GroupEditViewModel;
use app\modules\PersonManager\viewModels\GroupIndexViewModel;

final class GroupController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly GroupDataHelper $groupDataHelper,
        private readonly PersonGroupDataHelper $personGroupDataHelper,
        private readonly MessageDataHelper $messageDataHelper,
        private readonly ChatMessageDataHelper $chatMessageDataHelper,
    ) {
        parent::__construct($application);
    }

    public function groupCreate(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isGroupManager(), __FILE__, __LINE__)) {
            $viewModel = new GroupCreateViewModel(
                availableAuthorizations: $this->getAssignableAuthorizations(),
                layout: $this->getLayout('personManager'),
                navItems: $this->getNavItems($this->application->getConnectedUser()->person?->email),
                layoutParams: $this->getAllParams([]),
            );

            $this->views->render('PersonManager/views/group_create.latte', $viewModel);
        }
    }

    public function groupCreateSave(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isGroupManager(), __FILE__, __LINE__)) {
            $schema = [
                'name' => FilterInputRule::HtmlSafeName->value,
                'selfRegistration' => FilterInputRule::Int->value,
                'authorizations' => FilterInputRule::ArrayInt->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            /** @var array{name?: string, selfRegistration?: int, authorizations?: array<int, int>} $input */
            $name = $input['name'] ?? '???';
            $selfRegistration = $input['selfRegistration'] ?? 0;
            if ($name === '???') {
                $viewModel = new GroupCreateViewModel(
                    availableAuthorizations: $this->getAssignableAuthorizations(),
                    layout: $this->getLayout('personManager'),
                    navItems: $this->getNavItems($this->application->getConnectedUser()->person?->email),
                    error: $this->translate('person_manager.group.name_required'),
                    layoutParams: $this->getAllParams([]),
                );

                $this->views->render('PersonManager/views/group_create.latte', $viewModel);
                return;
            }
            $selectedAuthorizations = $this->withoutReservedAuthorization($input['authorizations'] ?? []);
            $this->groupDataHelper->insert($name, $selfRegistration, $selectedAuthorizations);
            $this->redirect('/groups');
        }
    }

    public function groupDelete(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isGroupManager(), __FILE__, __LINE__)) {
            return;
        }
        if ($id === ReservedIds::WEBMASTER_GROUP) {
            $this->errors->badRequest("Group {$id} can't be deleted", __FILE__, __LINE__);
            return;
        }

        try {
            $this->groupDataHelper->inactive($id);
            $this->redirect('/groups');
        } catch (QueryException $e) {
            $this->errors->badRequest("Error {$e->getMessage()}", $e->getFile(), $e->getLine());
        } catch (Throwable $e) {
            $this->errors->error($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public function groupEdit(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isGroupManager(), __FILE__, __LINE__)) {
            return;
        }
        if ($id === ReservedIds::WEBMASTER_GROUP) {
            $this->errors->badRequest("Group ({$id}) can't be edited", __FILE__, __LINE__);
            return;
        }

        $group = $this->dataHelper->get('Group', ['Id' => $id], 'Name, SelfRegistration');
        if ($group === false) {
            $this->errors->badRequest("Unknown group {$id}", __FILE__, __LINE__);
            return;
        }
        /** @var object{Name: mixed, SelfRegistration: mixed} $group */

        $viewModel = new GroupEditViewModel(
            group: GroupEditRow::fromStdClass($group),
            availableAuthorizations: $this->getAvailableAuthorizations(),
            currentAuthorizations: $this->getGroupAuthorizationIds($id),
            layout: $this->getLayout('personManager'),
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('PersonManager/views/group_edit.latte', $viewModel);
    }

    public function groupEditSave(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isGroupManager(), __FILE__, __LINE__)) {
            if ($id === ReservedIds::WEBMASTER_GROUP) {
                $this->errors->badRequest("Group {$id} can't be updated", __FILE__, __LINE__);
                return;
            }
            $schema = [
                'name' => FilterInputRule::HtmlSafeName->value,
                'selfRegistration' => FilterInputRule::Int->value,
                'authorizations' => FilterInputRule::ArrayInt->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            /** @var array{name?: string, selfRegistration?: int, authorizations?: array<int, int>} $input */
            $name = $input['name'] ?? '???';
            $selfRegistration = $input['selfRegistration'] ?? 0;
            $selectedAuthorizations = $this->withoutReservedAuthorization($input['authorizations'] ?? []);

            if (empty($name)) {
                $group = $this->dataHelper->get('Group', ['Id' => $id], 'Name, SelfRegistration');
                if ($group === false) {
                    $this->errors->badRequest("Unknown group {$id}", __FILE__, __LINE__);
                    return;
                }
                /** @var object{Name: mixed, SelfRegistration: mixed} $group */

                $viewModel = new GroupEditViewModel(
                    group: GroupEditRow::fromStdClass($group),
                    availableAuthorizations: $this->getAvailableAuthorizations(),
                    currentAuthorizations: $this->getGroupAuthorizationIds($id),
                    layout: $this->getLayout('personManager'),
                    error: $this->translate('person_manager.group.name_required'),
                    layoutParams: $this->getAllParams([]),
                );

                $this->views->render('PersonManager/views/group_edit.latte', $viewModel);
            } else {
                $this->groupDataHelper->update($id, $name, $selfRegistration, $selectedAuthorizations);
                $this->redirect('/groups');
            }
        }
    }

    public function groupIndex(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isGroupManager(), __FILE__, __LINE__)) {
            $connectedUser = $this->application->getConnectedUser();

            $viewModel = new GroupIndexViewModel(
                groups: $this->groupDataHelper->getGroupsWithAuthorizations($connectedUser),
                layout: $this->getLayout('personManager'),
                navItems: $this->getNavItems($connectedUser->person?->email),
                layoutParams: $this->getAllParams([
                    'page' => $connectedUser->getPage(),
                ]),
            );

            $this->views->render('PersonManager/views/groups_index.latte', $viewModel);
        }
    }

    public function showGroupChat(int $groupId): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $group = $this->dataHelper->get('Group', ['Id' => $groupId], 'Id, Name');
        if (!$group) {
            $this->errors->badRequest("Unknown group {$groupId}", __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        if ($connectedUser->person === null || !$this->personGroupDataHelper->isPersonInGroup($connectedUser->person->id, $groupId)) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }
        $person = $connectedUser->person;
        $lastLogId = $_SESSION['last_log_id'] ?? 0;
        $lastLogId = is_numeric($lastLogId) ? (int)$lastLogId : 0;

        $viewModel = new ChatViewModel(
            article: null,
            event: null,
            group: GroupChatRow::fromRow($group),
            messages: $this->chatMessageDataHelper->getGroupMessages($groupId),
            person: $person,
            navItems: $this->getNavItems($person->email),
            newMessages: $this->messageDataHelper->hasNewMessages($person->id, $lastLogId),
            btnParent: "/user/directory?group={$groupId}",
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Common/views/chat.latte', $viewModel);
    }

    #region Privates functions
    /**
     * Authorizations a group can be given, sorted by name.
     * The reserved one is removed here because Data::gets() only accepts plain column names as conditions.
     *
     * @return list<stdClass>
     */
    private function getAssignableAuthorizations(): array
    {
        return array_values(array_filter(
            $this->dataHelper->gets('Authorization', [], 'Id, Name', 'Name'),
            static fn(stdClass $row): bool => To::int($row->Id) !== ReservedIds::WEBMASTER_AUTHORIZATION
        ));
    }

    /**
     * @return list<IdNameRow>
     */
    private function getAvailableAuthorizations(): array
    {
        return array_map(
            static fn(stdClass $row): IdNameRow => IdNameRow::fromStdClass($row),
            $this->getAssignableAuthorizations()
        );
    }

    /**
     * @return list<int>
     */
    private function getGroupAuthorizationIds(int $groupId): array
    {
        return array_values(array_map(
            static fn($row) => To::int($row->IdAuthorization),
            $this->dataHelper->gets('GroupAuthorization', ['IdGroup' => $groupId], 'IdAuthorization')
        ));
    }

    /**
     * Drops the reserved authorization from a submitted list: it can never be given to a group,
     * even if a crafted request sends it (the form does not offer it).
     *
     * @param array<int, int> $authorizationIds
     * @return list<int>
     */
    private function withoutReservedAuthorization(array $authorizationIds): array
    {
        return array_values(array_filter(
            $authorizationIds,
            static fn(int $id): bool => $id !== ReservedIds::WEBMASTER_AUTHORIZATION
        ));
    }
    #endregion
}
