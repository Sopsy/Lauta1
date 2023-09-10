<?php
declare(strict_types=1);

namespace TVGuide\Module\Channel\Repository;

use TVGuide\Contract\Channel;
use TVGuide\Module\Channel\Model\ChannelGroup;
use TVGuide\Module\Channel\Model\StoredChannel;
use TVGuide\Module\Database\DbConnection;

final class ChannelRepository
{
    private $db;
    /** @var Channel[] */
    private $channels;

    public function __construct(DbConnection $db)
    {
        $this->db = $db;
        $this->loadChannels();
    }

    public function exists(Channel $channel): bool
    {
        return isset($this->channels[$channel->dataId()]);
    }

    public function store(Channel $channel): void
    {
        $query = $this->db->prepare('INSERT INTO channel (data_id, name, url_name)
            VALUES (:data_id, :name, :url_name)
            ON DUPLICATE KEY UPDATE url_name = VALUES(url_name)');
        $query->bindValue('data_id', $channel->dataId());
        $query->bindValue('name', $channel->name());
        $query->bindValue('url_name', $channel->urlSafeName());
        $query->execute();
        $this->loadChannels();
    }

    public function getStored(Channel $channel): StoredChannel
    {
        return $this->channels[$channel->dataId()];
    }

    public function getChannelGroups(): array
    {
        $groups = [];
        $query = $this->db->query('SELECT * FROM `group`');
        while ($row = $query->fetchObject()) {
            $group = new ChannelGroup(
                (int)$row->id,
                $row->name,
                ...$this->getChannelsByGroupId((int)$row->id)
            );
            $groups[] = $group;
        }
        return $groups;
    }

    public function getChannelsByGroupId(int $id): array
    {
        $channels=[];
        $query = $this->db->prepare('SELECT *, (SELECT GROUP_CONCAT(group_id) FROM group_channel WHERE channel_id = b.id) AS groupsString FROM group_channel a LEFT JOIN channel b ON a.channel_id = b.id WHERE a.group_id = :group_id');
        $query->bindValue('group_id', $id);
        $query->execute();
        while ($row = $query->fetchObject()) {
            $channel = new StoredChannel(
                (int)$row->id,
                $row->data_id,
                $row->name,
                $row->url_name,
                (string)$row->groupsString
            );
            $channels[] = $channel;
        }
        return $channels;
    }

    /**
     * @return void
     */
    private function loadChannels(): void
    {
        $this->channels = [];

        $query = $this->db->query('SELECT *, (SELECT GROUP_CONCAT(group_id) FROM group_channel WHERE channel_id = a.id) AS groupsString FROM channel a ORDER BY `order` ASC');
        while ($row = $query->fetchObject()) {
            $channel = new StoredChannel(
                (int)$row->id,
                $row->data_id,
                $row->name,
                $row->url_name,
                (string)$row->groupsString
            );

            $this->channels[$row->data_id] = $channel;
        }
    }
}