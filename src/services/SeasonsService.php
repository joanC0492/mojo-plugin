<?php

require_once __DIR__ . '/../repositories/SeasonsRepository.php';

class SeasonsService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new SeasonsRepository();
    }

    public function createSeason($date, $type, $year)
    {
        $dto = new CreateSeasonDto($date, $type, $year);
        return $this->repository->insert($dto);
    }

    public function updateSeason($id, $date = null, $type = null, $year = null)
    {
        $dto = new UpdateSeasonDto($id, $date, $type, $year);
        return $this->repository->update($dto);
    }

    public function getSeason($date, $year)
    {
        return $this->repository->find($date, $year);
    }

    public function getSeasonsByYear($year)
    {
        $seasons = $this->repository->getAll($year);
        // return count($seasons) > 364;
        return count($seasons) > 359;
    }

    public function removeSeason($id)
    {
        return $this->repository->delete($id);
    }

}
