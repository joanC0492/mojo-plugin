<?php

require_once __DIR__ . '/../dtos/CreateCommentDto.php';
require_once __DIR__ . '/../dtos/UpdateCommentDto.php';
require_once __DIR__ . '/../repositories/CommentRepository.php';

class CommentService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new CommentRepository();
    }

    public function createComment(
        int $calendarId,
        string $date,
        string $title,
        string $description
    ): int|false {
        $dto = new CreateCommentDto(
            $calendarId,
            $title,
            $description,
            $date
        );

        return $this->repository->create($dto);
    }

    public function getCommentsByCalendar(int $calendarId): array
    {
        return $this->repository->findByCalendar($calendarId);
    }

    public function getCommentsByCalendarAndDate(int $calendarId, string $date): array
    {
        return $this->repository->findByCalendarAndDate($calendarId, $date);
    }

    public function getComment(int $id)
    {
        return $this->repository->find($id);
    }

    public function updateComment(
        int $id,
        string $title,
        string $description
    ): bool {
        $dto = new UpdateCommentDto($id, $title, $description);
        return $this->repository->update($dto);
    }

    public function deleteComment(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
