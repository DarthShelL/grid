<?php


namespace DarthShelL\Grid;

class Column
{
    private $name = null;
    private $alias = null;
    private $filter_type = null;
    private $format = null;
    private $hidden = false;
    private $inline_editing = false;
    private $inline_edit_type = null;
    private $inline_edit_data = null;

    public function __construct(string $name, string $alias = null, int $filter_type = null, $format = null)
    {
        $this->setName($name);
        is_null($alias) ?: $this->setAlias($alias);
        is_null($filter_type) ?: $this->setFilterType($filter_type);
        is_null($format) ?: $this->setFormat($format);
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

    public function hasFormat(): bool
    {
        return is_null($this->format) ? false : true;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function hide()
    {
        $this->hidden = true;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function enableInlineEditing(int $inline_edit_type, array $data = null)
    {
        $this->inline_editing = true;
        $this->inline_edit_type = $inline_edit_type;

        if (!is_null($data)) {
            $this->inline_edit_data = $data;
        } elseif ($inline_edit_type === DataProvider::ENUM) {
            throw new \Exception("\$data argument can not be null for ENUM type");
        }
    }

    public function hasInlineEditing(): bool
    {
        return $this->inline_editing;
    }

    public function getInlineEditType(): int
    {
        return $this->inline_edit_type;
    }

    public function getInlineEditData()
    {
        return $this->inline_edit_data;
    }
}
