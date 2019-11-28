<?php


namespace DarthShelL\Grid;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class Renderer
{
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
            'body' => $this->renderbody()
        ];
    }

    public function render(): string
    {
        $template = $this->prepareMainTemplate();
        return view('grid.table', $template)->render();
    }
}
