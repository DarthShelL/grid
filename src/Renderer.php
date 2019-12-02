<?php


namespace DarthShelL\Grid;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class Renderer
{
    const FILTER_VIEWS = [
        0 => 'grid.filter.integer',
        1 => 'grid.filter.string',
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
        $data = [
            'attribute' => $column->getName(),
            'type' => $column->getFilterType(),
        ];

        return view(self::FILTER_VIEWS[$column->getFilterType()], $data)->render();
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
            if ($column->hasFilter()) {
                $cell['filter'] = $this->renderFilter($column);
            }
            $cells[] = $this->renderHeaderCell($cell);
        }

        return compact('cells');
    }

    private function renderHeader(): string
    {
        $cells = $this->prepareHeaderTemplate();
        return view('grid.header', $cells)->render();
    }

    private function renderCellsByRow(Model $row): array
    {
        $cells = [];

        foreach ($this->provider->getColumns() as $column) {
            $value = $row->{$column->getName()};
            $cells[] = view('grid.body_cell', compact('value'))->render();
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

        return compact('rows');
    }

    public function renderSummary(): string
    {
//        return "<span class='summary'>{$this->provider->getCollection()->count()}</span>";
        return '';
    }

    public function renderPagination(): string
    {
        $c = $this->provider->getCollection();
        $data = [
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
            'summary' => $this->renderSummary(),
            'pagination' => $this->renderPagination()
        ];
    }

    public function render(): string
    {
        $template = $this->prepareMainTemplate();
        return view('grid.table', $template)->render();
    }
}
