<?php
namespace vakata\orm;

use \vakata\database\DBInterface;
use \vakata\database\schema\TableQuery;

/**
 * Manager ORM class implementing Unit Of Work, so that all changes are persisted in a single transaction
 */
class UnitOfWorkManager extends Manager
{
    /**
     * @var UnitOfWork
     */
    protected $uow;

    /**
     * Create an instance
     *
     * @param DBInterface $db the database access object
     * @param UnitOfWork $uow the unit of work object
     */
    public function __construct(DBInterface $db, UnitOfWork $uow)
    {
        parent::__construct($db);
        $this->uow = $uow;
    }
    /**
     * Get a repository from a table query
     *
     * @param TableQuery $query
     * @return Repository
     */
    public function fromQuery(TableQuery $query) : Repository
    {
        return new UnitOfWorkRepository(parent::fromQuery($query), $this->uow);
    }
    /**
     * Save all the pending changes
     *
     * @return void
     */
    public function save()
    {
        $this->uow->save();
    }
}
