<select class="dsg-ie-select">
    <option>-</option>
    @foreach($data as $key=>$val)
        @if($key === $value)
        <option value="{{$key}}" selected>{{$val}}</option>
        @else
        <option value="{{$key}}">{{$val}}</option>
        @endif
    @endforeach
</select>
