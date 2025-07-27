<?php

namespace app\helpers;

use DateTime;
use app\helpers\Params;
use app\helpers\TranslationManager;

class PersonDataHelper extends Data
{
    private AuthorizationDataHelper $authorizationDataHelper;

    public function __construct()
    {
        $this->authorizationDataHelper = new AuthorizationDataHelper();
    }

    public function getPersonsInGroup(int $idGroup, bool $everybodyIfNoGroup = false): array
    {
        $innerJoin = $and = '';
        if (!empty($idGroup) || $everybodyIfNoGroup) {
            $innerJoin = 'INNER JOIN PersonGroup on PersonGroup.IdPerson = Person.Id';
            $and = 'AND PersonGroup.IdGroup = ' . $idGroup;
        }
        $query = $this->pdo->query("
            SELECT Person.Id, FirstName, LastName, Email, Preferences, Availabilities
            FROM Person
            $innerJoin
            WHERE Person.Inactivated = 0 $and
            ORDER BY FirstName, LastName
        ");
        return $query->fetchAll();
    }

    public function getPersonsInGroupForDirectory($groupId)
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.* 
            FROM Person p
            JOIN PersonGroup pg ON p.Id = pg.IdPerson
            WHERE pg.IdGroup = ? AND p.InPresentationDirectory = 1 AND p.Inactivated = 0
            ORDER BY p.LastName, p.FirstName
        ");
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    public function getPublisher($id): string|null
    {
        if ($id == null) return null;
        $person = $this->get('Person', ['Id' => $id], 'FirstName, LastName');
        return "publié par " . $person->FirstName . " " . $person->LastName;
    }

    public function getPerson(array $requiredAuthorisations = [], int $segment = 0): object|bool
    {
        $userEmail = $_SESSION['user'] ?? '';
        if (!$userEmail) {
            Params::setDefaultParams($_SERVER['REQUEST_URI']);
            return false;
        }

        $person = $this->get('Person', ['Email' => $userEmail]);
        if (!$person) {
            $this->application->error480($userEmail, __FILE__, __LINE__);
            return false;
        }

        $authorizations = $this->authorizationDataHelper->getsFor($person->Id);
        if ($requiredAuthorisations != [] && empty(array_intersect($authorizations, $requiredAuthorisations))) {
            $this->application->error403(__FILE__, __LINE__);
            return false;
        }

        $lang = TranslationManager::getCurrentLanguage();
        Params::setParams([
            'href' => $this->getHref($person->Email),
            'userImg' => $this->getUserImg($person),
            'userEmail' => $person->Email,
            'keys' => count($authorizations) > 0,
            'isEventManager' => $this->authorizationDataHelper->isEventManager(),
            'isPersonManager' => $this->authorizationDataHelper->isPersonManager(),
            'isRedactor' => $this->authorizationDataHelper->isRedactor(),
            'isEditor' => $this->authorizationDataHelper->isEditor(),
            'isWebmaster' => $this->authorizationDataHelper->isWebmaster(),
            'page' => explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'))[$segment],
            'currentVersion' => Application::getVersion(),
            'currentLanguage' => $lang,
            'supportedLanguages' => TranslationManager::getSupportedLanguages(),
            'flag' => TranslationManager::getFlag($lang),
        ]);

        return $person;
    }

    public function create()
    {
        $query = $this->pdo->prepare("SELECT Id FROM Person WHERE Email = ''");
        $query->execute();
        $id = $query->fetch()->Id ?? null;
        if ($id == null) {
            $query = $this->pdo->prepare("
                        INSERT INTO Person (Email, FirstName, LastName, Imported) 
                        VALUES ('', '', '', 0)
                    ");
            $query->execute([]);
            $id = $this->pdo->lastInsertId();
        }
        return $id;
    }

    public function setPassword($password, $personId)
    {
        $stmt = $this->pdo->prepare('UPDATE Person SET Password = ?, Token = null, TokenCreatedAt = null WHERE Id = ?');
        $stmt->execute($password, $personId);
    }

    public function setToken($personId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenCreatedAt = (new DateTime())->format('Y-m-d H:i:s');
        $query = $this->pdo->prepare('UPDATE Person SET Token = ?, TokenCreatedAt = ? WHERE Id = ?');
        $query->execute([$token, $tokenCreatedAt, $personId]);
        return $token;
    }

    public function updateActivity($email)
    {
        $lastActivity = $this->fluentForLog->from('Log')
            ->select(null)
            ->select('CreatedAt')
            ->where('Who COLLATE NOCASE', $email)
            ->orderBy('Id DESC')
            ->limit(1)
            ->fetch('CreatedAt');
        if ($lastActivity) $this->fluent->update('Person')->set('LastSignOut', $lastActivity)->where('Email COLLATE NOCASE', $email)->execute();
        $this->fluent->update('Person')->set(['LastSignIn' => date('Y-m-d H:i:s')])->where('Email COLLATE NOCASE', $email)->execute();
    }

    public function getWebmasterEmail()
    {
        $query = $this->pdo->query(
            '
            SELECT Email FROM Person
            INNER JOIN PersonGroup on Person.Id = PersonGroup.IdPerson
            INNER JOIN "Group" on "Group".Id = PersonGroup.IdGroup
            INNER JOIN GroupAuthorization on "Group".Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id
            WHERE Authorization.Name = "Webmaster";'
        );
        return $query->fetchColumn();
    }

    public function getPresentationNews($person, $searchFrom)
    {
        $presentations = $this->fluent->from('Person p')
            ->select('p.id, p.email, p.firstname, p.lastname, p.PresentationLastUpdate')
            ->where('p.InPresentationDirectory = 1')
            ->where('p.PresentationLastUpdate >= ?', $searchFrom)
            ->where('p.email != ?', $person->Email)
            ->orderBy('p.PresentationLastUpdate DESC')
            ->fetchAll();
        $news = [];
        foreach ($presentations as $presentation) {
            $fullName = trim($presentation->FirstName . ' ' . $presentation->LastName);
            if (empty($fullName)) $fullName = $presentation->email;

            $news[] = [
                'type' => 'presentation',
                'id' => $presentation->Id,
                'title' => 'Présentation de ' . $fullName,
                'date' => $presentation->PresentationLastUpdate,
                'url' => '/presentation/' . $presentation->Id
            ];
        }
        return $news;
    }

    #region Private functions
    private function getHref(string $userEmail): string
    {
        return $userEmail == '' ? '/user/sign/in' : '/user';
    }

    private function getUserImg(object $person): string
    {
        if ($person->UseGravatar === 'yes') return (new GravatarHandler())->getGravatar($person->Email);
        else {
            if (empty($person->Avatar)) return '/app/images/emojiPensif.png';
            else                        return '/app/images/' . $person->Avatar;
        }
    }
}
