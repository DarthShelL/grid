<?php


namespace DarthShelL\Grid;


use phpDocumentor\Reflection\Types\Boolean;

class Column
{
    private $name = null;
    private $alias = null;
    private $filter = null;

    #TODO: deside what filter is
    public function __construct(string $name, string $alias = null, $filter = null)
    {
        $this->setName($name);
        is_null($alias) ?: $this->setAlias($alias);
        is_null($filter) ?: $this->setFilter($filter);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getFilter(): string
    {
        #TODO: filter getter
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setAlias(string $alias)
    {
        $this->alias = $alias;
    }

    public function setFilter($filter)
    {
        #TODO: filter setter
        $this->filter = $filter;
    }

    public function hasAlias(): bool
    {
        return is_null($this->alias)?false:true;
    }

    public function hasFilter(): bool
    {
        return is_null($this->filter)?false:true;
    }
}
