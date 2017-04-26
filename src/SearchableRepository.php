<?php
namespace vakata\orm;

interface SearchableRepository extends Repository
{
    public function search(string $q) : SearchableRepository;
}