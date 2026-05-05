<?php

class UpdateCommentDto
{
    public function __construct(
        private int $id,
        private string $title,
        private string $description
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getDataValues(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
        ];
    }

    public function getDataTypes(): array
    {
        return ['%s', '%s'];
    }
}
