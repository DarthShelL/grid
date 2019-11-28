<?php


namespace DarthShelL\Grid;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

class DataProvider
{
    private $collection;
    private $model;
    private $columns = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->collection = $this->model::all();

        // set columns
        $this->setColumns();
    }

    public function processUpdate()
    {
        // Order By
        if ( !Request::has('dsgrid_update') )
            return;

        $order_by = Request::input('order_by');
        $order_direction = Request::input('order_direction');
        if (!is_null($order_by) && !is_null($order_by)) {
            $this->collection = $this->model::orderBy($order_by, $order_direction);
        }

        $this->collection = $this->collection->get();
        echo $this->renderBody();
        exit(0);
    }

    private function setColumns()
    {
        // get Collection

        foreach ($this->collection->first()->getAttributes() as $name => $value) {
            $this->columns[] = new Column($name);
        }
    }

    public function renderBody(): string
    {
        return (new Renderer($this))->renderBody();
    }

    public function renderGrid(): string
    {
        return (new Renderer($this))->render();
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumnByName(string $name): Column
    {
        foreach ($this->columns as $column) {
            if ($column->getName() === $name) {
                return $column;
            }
        }

        throw new \Exception("Column with name '{$name}' not found.");
    }

    public function setAlias(string $attribute, string $alias)
    {
        $column = $this->getColumnByName($attribute);
        $column->setAlias($alias);
    }

    public function setAliases(array $attributes)
    {
        foreach ($attributes as $name => $alias) {
            $column = $this->getColumnByName($name);
            $column->setAlias($alias);
        }
    }

    public function getRows(): array
    {
        return $this->collection->all();
    }
}
