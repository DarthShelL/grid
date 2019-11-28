<?php


namespace DarthShelL\Grid;


use phpDocumentor\Reflection\Types\Boolean;

class Column
{
    private $name = null;
    private $alias = null;
    private $filter_type = null;
    private $hidden = false;

    public function __construct(string $name, string $alias = null, int $filter_type = null)
    {
        $this->setName($name);
        is_null($alias) ?: $this->setAlias($alias);
        is_null($filter_type) ?: $this->setFilterType($filter_type);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getFilterType(): int
    {
        return $this->filter_type;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setAlias(string $alias)
    {
        $this->alias = $alias;
    }

    public function setFilterType(int $filter_type)
    {
        $this->filter_type = $filter_type;
    }

    public function hasAlias(): bool
    {
        return is_null($this->alias) ? false : true;
    }

    public function hasFilter(): bool
    {
        return is_null($this->filter_type) ? false : true;
    }

    public function hide()
    {
        $this->hidden = true;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }
}
