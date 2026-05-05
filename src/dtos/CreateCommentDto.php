<?php

class CreateCommentDto
{
    public function __construct(
        private int $calendarId,
        private string $title,
        private string $description,
        private string $date
    ) {}

    public function getDataValues(): array
    {
        return [
            'calendar_id' => $this->calendarId,
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date,
        ];
    }

    public function getDataTypes(): array
    {
        return ['%d', '%s', '%s', '%s'];
    }
}
