<?php

class CommentRepository
{
    private $wpdb;
    private $table = 'cs_comments';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }


    public function find(int $id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM $this->table WHERE id = %d",
            $id
        );

        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return null;
        }

        return $row;
    }

    public function findByCalendar(int $calendarId): array
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM $this->table WHERE calendar_id = %d ORDER BY date DESC",
            $calendarId
        );

        return $this->wpdb->get_results($query) ?? [];
    }

    public function findByCalendarAndDate(int $calendarId, string $date): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM cs_comments WHERE calendar_id = %d AND date = %s",
                $calendarId,
                $date
            ),
            ARRAY_A
        );
    }


    public function create(CreateCommentDto $dto): int|false
    {
        $result = $this->wpdb->insert(
            $this->table,
            $dto->getDataValues(),
            $dto->getDataTypes()
        );

        if ($result === false) {
            return false;
        }

        return (int) $this->wpdb->insert_id;
    }

    public function update(UpdateCommentDto $dto): bool
    {
        $result = $this->wpdb->update(
            $this->table,
            $dto->getDataValues(),
            ['id' => $dto->getId()],
            $dto->getDataTypes(),
            ['%d']
        );

        return $result !== false;
    }

    public function delete(int $id): bool
    {
        $result = $this->wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }
}
