<?php

namespace app\helpers;

class News
{
    private $pdo;
    private $fluent;
    private $authorizations;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
        $this->authorizations = new Authorization($this->pdo);
    }

    public function getNewsForPerson($person, $searchFrom)
    {
        $news = [];
        return array_merge(
            $news,
            $this->getArticleNews($person, $searchFrom),
            $this->getEventNews($person, $searchFrom),
            $this->getMessageNews($person, $searchFrom),
            $this->getPresentationNews($person, $searchFrom)
        );
    }

    public function anyNews($person)
    {
        $news = $this->getNewsForPerson($person, $person->LastSignIn ?? '');
        return is_array($news) && count($news) > 0;
    }

    #region Private functions
    private function getArticleNews($person, $searchFrom)
    {
        $articles = $this->fluent->from('Article a')
            ->select('a.Id, a.Title, a.LastUpdate')
            ->where('a.LastUpdate >= ?', $searchFrom)
            ->where('a.PublishedBy IS NOT NULL')
            ->orderBy('a.LastUpdate DESC')
            ->fetchAll();
        $news = [];
        foreach ($articles as $article) {
            if ($this->authorizations->getArticle($article->Id, $person)) {
                $news[] = [
                    'type'  => 'article',
                    'id'    => $article->Id,
                    'title' => $article->Title,
                    'date'  => $article->LastUpdate,
                    'url'   => '/articles/' . $article->Id
                ];
            }
        }
        return $news;
    }

    private function getEventNews($person, $searchFrom)
    {
        $sql = "
            SELECT e.Id, e.Summary, e.LastUpdate
            FROM Event e
            JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN PersonGroup pg ON et.IdGroup = pg.IdGroup AND pg.IdPerson = :personId
            WHERE e.LastUpdate >= :searchFrom AND pg.Id IS NOT NULL
            ORDER BY e.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':personId'   => $person->Id,
            ':searchFrom' => $searchFrom
        ]);
        $events = $stmt->fetchAll();
        $news = [];
        foreach ($events as $event) {
            $news[] = [
                'type' => 'event',
                'id' => $event->Id,
                'title' => $event->Summary,
                'date' => $event->LastUpdate,
                'url' => '/event/' . $event->Id
            ];
        }

        return $news;
    }

    private function getMessageNews($person, $searchFrom)
    {
        $sql = "
            SELECT m.Id, m.Text, m.LastUpdate, m.EventId, p.FirstName, p.LastName, p.NickName, e.Summary, e.StartTime
            From Message m
            JOIN Person p ON p.Id = m.PersonId
            JOIN Event e ON e.Id = m.EventId
            WHERE m.LastUpdate > :searchFrom AND m.'From' = 'User' 
            AND m.EventId IN (SELECT IdEvent FROM Participant WHERE IdPerson = $person->Id)
            ORDER BY m.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':searchFrom' => $searchFrom
        ]);
        $messages = $stmt->fetchAll();
        $news = [];
        foreach ($messages as $message) {
            $news[] = [
                'type' => 'message',
                'id' => $message->EventId,
                'title' => $message->Text,
                'from' => $message->FirstName . ' ' . $message->LastName,
                'date' => $message->LastUpdate,
                'url' => '/event/chat/' . $message->EventId
            ];
        }
        return $news;
    }

    private function getPresentationNews($person, $searchFrom)
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
            if (empty($fullName)) {
                $fullName = $presentation->email;
            }

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

    #endregion
}
