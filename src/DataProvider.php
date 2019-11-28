<?php


namespace DarthShelL\Grid;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class DataProvider
{
    const INTEGER = 0;
    const STRING = 1;

    private $collection;
    private $model;
    private $columns = [];
    private $conditions = [];

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
        if (!Request::has('dsgrid_update'))
            return;

        $order_by = Request::input('order_by');
        $order_direction = Request::input('order_direction');
        if (!is_null($order_by) && !is_null($order_by)) {
            $this->collection = $this->model::orderBy($order_by, $order_direction);
        }

        foreach (Request::input() as $param => $value) {
            $m = [];
            if (preg_match('/f[atv]_(\d)/', $param, $m)) {
                $this->addCondition(Request::input('fa_' . $m[1]), (int)Request::input('ft_' . $m[1]), Request::input('fv_' . $m[1]));
            }
        }

        foreach ($this->conditions as $condition) {
            if (get_class($this->collection) == "Illuminate\Database\Eloquent\Collection") {
                $this->collection = $this->model::where($condition->attribute, $condition->operator, $condition->value, 'and');
            } else {
                $this->collection = $this->collection->where($condition->attribute, $condition->operator, $condition->value, 'and');
            }
        }

        if (get_class($this->collection) == "Illuminate\Database\Eloquent\Builder") {
            $this->collection = $this->collection->get();
        }
        echo $this->renderBody();
        exit(0);
    }

    private function addCondition(string $attribute, int $type, string $expression)
    {
        $operator = '=';
        switch ($type) {
            case self::INTEGER:
                $m = [];
                if (preg_match('/([=<>!]{0,2})(\d+)/', $expression, $m)) {
                    if (!empty($m[1])) {
                        $operator = $m[1];
                    }
                    $value = $m[2];
                }
                break;
            case self::STRING:
                $operator = 'LIKE';
                $value = $expression;
                break;
        }
        array_push($this->conditions, (object)compact('attribute', 'operator', 'value'));
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
        $columns = [];
        foreach ($this->columns as $column) {
            if (!$column->isHidden()) {
                $columns[] = $column;
            }
        }
        return $columns;
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

    public function setAlias()
    {
        $ac = func_num_args();
        $args = func_get_args();

        if ($ac == 1) {
            if (!is_array($args[0])) {
                throw new \Exception("Wrong argument type. Array of pairs, e.g. [\$column => \$alias] was expected.");
            }
            foreach ($args[0] as $name => $alias) {
                $this->getColumnByName($name)->setAlias($alias);
            }
        } elseif ($ac == 2) {
            $this->getColumnByName($args[0])->setAlias($args[1]);
        } else {
            throw new \Exception("Wrong number of arguments passed to the method 'setAlias'. Column and alias were expected or array of pairs, e.g. [\$column => \$alias].");
        }

    }

    public function hideColumn()
    {
        $ac = func_num_args();
        $args = func_get_args();

        foreach ($args as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $name) {
                    $this->getColumnByName($name)->hide();
                }
            } else {
                $this->getColumnByName($arg)->hide();
            }
        }
    }

    public function addFilter(string $attribute, int $filter_type)
    {
        $this->getColumnByName($attribute)->setFilterType($filter_type);
    }

    public function getRows(): array
    {
        return $this->collection->all();
    }
}
