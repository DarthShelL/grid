@section('styles')
    @parent
    <link rel="stylesheet" href="{{ URL::asset('css/grid/style.css') }}"/>
@endsection
@section('scripts')
    @parent
    <script src="{{ URL::asset('js/grid/grid.js') }}"></script>
    <script src="{{ URL::asset("js/grid/main-{$method}.js") }}"></script>
@endsection
<table class="dsg-table">
    {!! $header !!}
    {!! $body ?? '' !!}
</table>
