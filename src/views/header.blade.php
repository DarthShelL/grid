<thead>
    <tr>
        @foreach($cells as $cell)
            {!! $cell !!}
        @endforeach
    </tr>
    @if (isset($filters))
    <tr>
        @foreach($filters as $filter)
            <td>
                {!! $filter !!}
            </td>
        @endforeach
    </tr>
    @endif
</thead>
