<?php


namespace DarthShelL\Grid;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class DataProvider
{
    const INTEGER = 0;
    const STRING = 1;
    const DECIMAL = 2;
    const ENUM = 3;

    private $builder = null;
    private $model;
    private $columns = [];
    private $conditions = [];
    private $has_filters = false;
    private $config = null;

    public $row_adding_enabled = false;
    public $perPage = 10;
    public $noskip = 2;

    public function __construct(Model $model, $config = null)
    {
        $this->model = $model;

        if (!$this->initFromConfig($config)) {
            $this->setColumns();
        }

        $this->processUpdate();
    }

    private function initFromConfig(array $config): bool
    {
        // check if config exists
        if (is_null($config)) return false;

        // set general parameters
        $this->perPage = $config['rows_per_page'] ?? $this->perPage;
        $this->noskip = $config['pages_in_paginator'] ?? $this->noskip;
        $this->row_adding_enabled = $config['row_adding_enabled'] ?? false;

        // set columns
        foreach ($config['columns'] as $attribute => $params) {
            if (isset($params['filter']) && !is_null($params['filter']))
                $this->has_filters = true;

            $_column = new Column(
                $attribute,
                $params['alias'] ?? null,
                $params['filter'] ?? null,
                $params['format'] ?? null,
                $params['validation_rule'] ?? null,
                $params['hidden'] ?? null
            );

            if (isset($params['inline_edit'])) {
                $ie_params = $params['inline_edit'];
                $_column->enableInlineEditing($ie_params['type'], $ie_params['data'] ?? null);
            }

            $this->columns[] = $_column;
        }

        // actions column
        if (isset($config['actions_column'])) {
            $this->addActionsColumn($config['actions_column']['name'] ?? null, $config['actions_column']['buttons'] ?? null);
        }

        $this->config = $config;

        return true;
    }

    public function processUpdate()
    {
        if (!Request::has('dsgrid_update'))
            return;

        $this->processNewRowAdding();
        $this->processInlineEditing();
        $this->parseConditions();
        $this->processConditions();
        $this->processSorting();
        $this->processRemoving();

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

    private function processInlineEditing()
    {
        $inline_edit = Request::input('inline_edit');
        $edit_id = Request::input('edit_id');
        $edit_attribute = Request::input('edit_attribute');
        $edit_value = urldecode(Request::input('edit_value'));

        if (!is_null($inline_edit) && !is_null($edit_id) && !is_null($edit_attribute) && !is_null($edit_value)) {
            $this->applyInlineEditing($edit_attribute, $edit_id, $edit_value);
        }
    }

    private function processRemoving()
    {
        $rr_flag = Request::input('rr_flag');
        $rrid = Request::input('rrid');

        if (!is_null($rr_flag) && !is_null($rrid)) {
            $model = $this->model::find($rrid);

            if (!$model->delete()) {
                echo json_encode(['error' => "Can't delete record."]);
                exit();
            }
        }
    }

    private function processNewRowAdding()
    {
        $nr_flag = Request::input('nr_flag');

        if (!is_null($nr_flag)) {
            $class = get_class($this->model);
            $model = new $class();

            // collect all validation rules
            $rules = [];
            foreach ($this->config['columns'] as $column => $params) {
                if (is_null($params['validation_rule']))
                    continue;
                $rules[$column] = $params['validation_rule'];
            }

            // collect all values
            $values = [];
            foreach (Request::all() as $attribute => $value) {
                if (strpos($attribute, 'nrv_') !== false) {
                    $name = str_replace('nrv_', '', $attribute);

                    if (!is_null($value)) {
                        $values[$name] = $value;
                    }
                }
            }

            // validate
            $validator = Validator::make($values, $rules);
            if ($validator->fails()) {
                echo json_encode($validator->errors());
                exit();
            }

            // setting
            foreach ($values as $attribute => $value) {
                $model->{$attribute} = urldecode($value);
            }

            // saving
            try {
                $model->save();
            } catch (\Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
                exit();
            }
            exit();
        }
    }

    private function applyInlineEditing($attribute, $id, $value)
    {
        $model = $this->model::find($id);

        if (!is_null($model)) {
            $column = $this->getColumnByName($attribute);
            if ($column->hasValidationRule()) {
                $validator = Validator::make([$attribute => $value], $column->getValidationRule());
            } else if (isset($model->rules[$attribute])) {
                $validator = Validator::make([$attribute => $value], [$attribute => $model->rules[$attribute]]);
            }

            if (isset($validator) && $validator->fails()) {
                echo json_encode($validator->errors());
                exit();
            }

            $model->{$attribute} = $value;

            try {
                $model->save();
            } catch (\Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
                exit();
            }
        } else {
            echo json_encode(['error' => "Can't find record with id: {$id}"]);
            exit();
        }
    }

    private function processSorting()
    {
        $order_by = Request::input('order_by');
        $order_direction = Request::input('order_direction');

        if (!is_null($order_by) && !is_null($order_by)) {
            $this->getBuilder()->orderBy($order_by, $order_direction);
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
        $columns = Schema::getColumnListing($this->model->getTable());

        foreach ($columns as $key => $name) {
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

    public function addCustomColumn(string $name, string $alias = null)
    {
        $this->columns[] = new Column($name, $alias);
    }

    public function addActionsColumn($name = null, $buttons = null)
    {
        $this->columns[] = new Column('actions', $name ?? null);

        $this->addFormat('actions', function ($row) use ($buttons) {
            return view('grid.actions_column_cell', compact('row', 'buttons'))->render();
        });
    }
}
