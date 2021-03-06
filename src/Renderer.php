<?php


namespace DarthShelL\Grid;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class Renderer
{
    const FILTER_VIEWS = [
        0 => 'grid.filter.integer',
        1 => 'grid.filter.string'
    ];
    const EDITABLE_VIEWS = [
        0 => 'grid.editable.input',
        1 => 'grid.editable.input',
        2 => 'grid.editable.input',
        3 => 'grid.editable.select'
    ];
    private $provider;
    private $method;

    public function __construct(DataProvider $provider)
    {
        $this->provider = $provider;
        $this->method = strtolower(Request::method());
    }

    private function renderHeaderCell($cell): string
    {
        return view('grid.header_cell', $cell)->render();
    }

    private function renderFilter(Column $column): string
    {
        $data = null;

        if ($column->hasFilter()) {
            $data = [
                'attribute' => $column->getName(),
                'type' => $column->getFilterType(),
            ];
            return view(self::FILTER_VIEWS[$column->getFilterType()], $data)->render();
        } else
            return view(self::FILTER_VIEWS[$column->getFilterType()])->render();
    }

    private function prepareHeaderTemplate(): array
    {
        $cells = [];

        foreach ($this->provider->getColumns() as $column) {
            $attribute = $column->getName();
            $cell = [
                'attribute' => $attribute,
                'name' => $column->hasAlias() ? $column->getAlias() : $attribute
            ];
            $cells[] = $this->renderHeaderCell($cell);
        }

        $data = ['cells' => $cells];
        if ($this->provider->hasFilters())
            $data['filters'] = $this->prepareFilters();

        return $data;
    }

    private function prepareFilters(): array
    {
        $filters = [];

        foreach ($this->provider->getColumns() as $column) {
            if ($column->hasFilter()) {
                $filters[] = $this->renderFilter($column);
            } else {
                $filters[] = '';
            }
        }

        return $filters;
    }

    private function renderHeader(): string
    {
        $cells = $this->prepareHeaderTemplate();
        return view('grid.header', $cells)->render();
    }

    private function renderCell(Column $column, Model $row): string
    {
        if ($column->hasFormat()) {
            $value = $column->getFormat()($row);
        } else {
            $value = $row->{$column->getName()};
        }

        $data = [
            'value' => $value
        ];

        return view('grid.body_cell', $data)->render();
    }

    private function renderEditableCell(Column $column, Model $row): string
    {
        if ($column->hasFormat()) {
            $value = $column->getFormat()($row);
        } else {
            $value = $row->{$column->getName()};
        }

        $data = [
            'value' => $value,
            'id' => $row->{$this->provider->getKeyName()},
            'attribute' => $column->getName(),
            'input' => view(self::EDITABLE_VIEWS[$column->getInlineEditType()], [
                'value' => $row->{$column->getName()},
                'data' => $column->getInlineEditData()
            ])->render()
        ];

        return view('grid.body_editable_cell', $data)->render();
    }

    private function renderTemplateCell(Column $column): string
    {
        if ($column->getName() == 'actions') {
            return view('grid.save_row_cell')->render();
        }else {
            $data = [
                'attribute' => $column->getName(),
                'input' => view(self::EDITABLE_VIEWS[$column->getInlineEditType()], [
                    'value' => '',
                    'data' => $column->getInlineEditData()
                ])->render()
            ];
            return view('grid.template_editable_cell', $data)->render();
        }
    }

    private function renderCellsByRow(Model $row): array
    {
        $cells = [];

        foreach ($this->provider->getColumns() as $column) {
            if ($column->hasInlineEditing()) {
                $cells[] = $this->renderEditableCell($column, $row);
            } else {
                $cells[] = $this->renderCell($column, $row);
            }
        }

        return $cells;
    }

    private function prepareBodyTemplate(): array
    {
        $rows = [];

        foreach ($this->provider->getRows() as $row) {
            $cells = $this->renderCellsByRow($row);
            $rows[] = view('grid.row', compact('cells'))->render();
        }

        if ($this->provider->row_adding_enabled) {
            $rows[] = $this->renderTemplateRow();
        }

        return compact('rows');
    }

    public function renderPagination(): string
    {
        $c = $this->provider->getCollection();
        $data = [
            'noskip' => $this->provider->noskip,
            'pages_num' => $c->lastPage(),
            'current_page' => $c->currentPage()
        ];
        return view('grid.pagination.main', $data)->render();
    }

    public function renderBody(): string
    {
        $rows = $this->prepareBodyTemplate();
        return view('grid.body', $rows)->render();
    }

    private function prepareMainTemplate(): array
    {
        return [
            'method' => $this->method,
            'header' => $this->renderHeader(),
            'body' => $this->renderbody(),
            'pagination' => $this->renderPagination()
        ];
    }

    public function renderTemplateRow(): string
    {
        $cells = [];

        foreach ($this->provider->getColumns() as $column) {
            $cells[] = $this->renderTemplateCell($column);
        }

        return view('grid.template_row', compact('cells'))->render();
    }

    public function render(): string
    {
        $template = $this->prepareMainTemplate();
        return view('grid.table', $template)->render();
    }
}
