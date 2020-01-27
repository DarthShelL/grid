<tbody>
    @foreach($rows as $row)
        {!! $row !!}
    @endforeach
    {!! $row_template ?? '' !!}
</tbody>
