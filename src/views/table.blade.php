@section('styles')
    @parent
    <link rel="stylesheet" href="{{ URL::asset('css/grid/style.css') }}"/>
@endsection
@section('scripts')
    @parent
    <script src="{{ URL::asset('js/grid/grid.js') }}"></script>
    <script src="{{ URL::asset("js/grid/main-{$method}.js") }}"></script>
@endsection
<div class="dsg-table-wrapper">
    <div class="dsg-action-panel">
        <a href="#" class="dsg-btn add-row-btn" title="Добавить строку">+</a>
    </div>
    <table class="dsg-table">
        {!! $header !!}
        {!! $body ?? '' !!}
    </table>
    <div class="dsg-pagination">
        {!! $pagination ?? '' !!}
    </div>
</div>
