<?php

namespace app\helpers;

use PDO;

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

        $articles = $this->getArticleNews($person, $searchFrom);
        $news = array_merge($news, $articles);

        /*$events = $this->getEventNews($person, $searchFrom);
        $news = array_merge($news, $events);

        $messages = $this->getMessageNews($person, $searchFrom);
        $news = array_merge($news, $messages);

        $presentations = $this->getPresentationNews($person, $searchFrom);
        $news = array_merge($news, $presentations);*/

        // Trier par date décroissante
        usort($news, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $news;
    }

    #region Private functions

    private function getArticleNews($person, $searchFrom)
    {
        $articles = $this->fluent->from('Article a')
            ->select('a.id, a.title, a.LastUpdate')
            ->where('a.LastUpdate >= ?', $searchFrom)
            ->where('a.PublishedBy <> NULL')
            ->orderBy('a.LastUpdate DESC')
            ->fetchAll();
        $news = [];
        foreach ($articles as $article) {
            if ($this->authorizations->getArticle($article['id'], $person)) {
                $news[] = [
                    'type' => 'article',
                    'id' => $article['id'],
                    'title' => $article['title'],
                    'date' => $article->LastUpdate,
                    'url' => '/article/' . $article->id
                ];
            }
        }
        return $news;
    }

    private function getEventNews($person, $searchFrom)
    {
        $events = $this->fluent->from('Event e')
            ->select('e.id, e.Summary, e.LastUpdate')
            ->where('e.LastUpdate >= ?', $searchFrom)
            ->orderBy('e.LastUpdate DESC')
            ->fetchAll();

        $news = [];
        foreach ($events as $event) {
            $news[] = [
                'type' => 'event',
                'id' => $event['id'],
                'title' => $event['title'],
                'date' => $event->LastUpdate,
                'url' => '/event/' . $event['id']
            ];
        }

        return $news;
    }

    private function getMessageNews($person, $searchFrom)
    {
        $messages = $this->fluent->from('Message m')
            ->leftJoin('Person p ON p.Id = m.PersonId')
            ->select('m.Id, m.Text, m.LastUpdate, m.EventId')
            ->select('p.FirstName, p.LastName')
            ->where('m.LastUpdate >= ?', $searchFrom)
            ->where('(
                m.GroupId IN (SELECT GroupId FROM PersonGroup WHERE PersonEmail = ?) OR
                m.EventId IN (SELECT EventId FROM EventParticipant WHERE PersonEmail = ?)
            )', $person->Email, $person->Email)
            ->orderBy('m.LastUpdate DESC')
            ->fetchAll();

        $news = [];
        foreach ($messages as $message) {
            $fromName = !empty($message['firstname']) && !empty($message['lastname'])
                ? $message['firstname'] . ' ' . $message['lastname']
                : $message['From'];

            $news[] = [
                'type' => 'message',
                'action' => $message['action_type'],
                'id' => $message['id'],
                'title' => $message->Text ?: 'Message sans sujet',
                'from' => $fromName,
                'date' => $message['action_type'] === 'created' ? $message['created_at'] : $message['LastUpdate'],
                'url' => '/message/' . $message['id']
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
            $fullName = trim($presentation['firstname'] . ' ' . $presentation['lastname']);
            if (empty($fullName)) {
                $fullName = $presentation['email'];
            }

            $news[] = [
                'type' => 'presentation',
                'action' => 'updated',
                'id' => $presentation['id'],
                'title' => 'Présentation de ' . $fullName,
                'date' => $presentation['PresentationLastUpdate'],
                'url' => '/person/' . $presentation['id']
            ];
        }

        return $news;
    }

    #endregion
}
