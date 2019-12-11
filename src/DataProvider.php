<?php


namespace DarthShelL\Grid;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class DataProvider
{
    const INTEGER = 0;
    const STRING = 1;
    const DECIMAL = 2;
    const ENUM = 3;

    private $builder = null;
    private $collection = null;
    private $model;
    private $columns = [];
    private $conditions = [];
    private $has_filters = false;

    public $perPage = 10;
    public $noskip = 2;

    public function __construct(Model $model)
    {
        $this->model = $model;

        // set columns
        $this->setColumns();
    }

    public function processUpdate()
    {
        if (!Request::has('dsgrid_update'))
            return;

        $this->parseConditions();
        $this->processConditions();
        $this->processSorting();

        echo $this->renderGrid();

        exit(0);
    }

    private function parseConditions()
    {
        foreach (Request::input() as $param => $value) {
            $m = [];
            if (preg_match('/f[atv]_(\d)/', $param, $m)) {
                $this->addCondition(Request::input('fa_' . $m[1]), (int)Request::input('ft_' . $m[1]), Request::input('fv_' . $m[1]));
            }
        }
    }

    private function processSorting()
    {
        $inline_edit = Request::input('inline_edit');
        $edit_id = Request::input('edit_id');
        $edit_attribute = Request::input('edit_attribute');
        $edit_value = urldecode(Request::input('edit_value'));
        $order_by = Request::input('order_by');
        $order_direction = Request::input('order_direction');

        if (!is_null($order_by) && !is_null($order_by)) {
            $this->getBuilder()->orderBy($order_by, $order_direction);
        }
        if (!is_null($inline_edit) && !is_null($edit_id) && !is_null($edit_attribute) && !is_null($edit_value)) {
            $this->applyInlineEditing($edit_attribute, $edit_id, $edit_value);
        }
    }

    private function applyInlineEditing($attribute, $id, $value)
    {
        $model = $this->model::find($id);

        if (!is_null($model)) {
            $model->{$attribute} = $value;

            if (!$model->save()) {
                throw new \Exception("Can't save record.");
            }
        }
    }

    private function processConditions()
    {
        foreach ($this->conditions as $condition) {
            $this->getBuilder()->where($condition->attribute, $condition->operator, $condition->value, 'and');
        }
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

    public function getBuilder()
    {
        if (is_null($this->builder)) {
            $this->builder = $this->model::query();
        }
        return $this->builder;
    }

    public function getCollection()
    {
        return $this->getBuilder()->paginate($this->perPage);
    }

    private function setColumns()
    {
        foreach ($this->getBuilder()->first()->getAttributes() as $name => $value) {
            $this->columns[] = new Column($name);
        }
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
        $this->has_filters = true;
        $this->getColumnByName($attribute)->setFilterType($filter_type);
    }

    public function getRows(): array
    {
        return $this->getCollection()->all();
    }

    public function addFormat(string $attribute, $format)
    {
        $this->getColumnByName($attribute)->setFormat($format);
    }

    public function hasFilters(): bool
    {
        return $this->has_filters;
    }

    public function enableInlineEditing(string $attribute, int $edit_type, array $data = null)
    {
        $this->getColumnByName($attribute)->enableInlineEditing($edit_type, $data);
    }

    public function getKeyName(): string
    {
        return $this->model->getKeyName();
    }
}
