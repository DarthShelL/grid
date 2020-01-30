<div class="dsg-actions-cell">
    @if(!is_null($buttons))
        @foreach($buttons as $btn)
            {{$btn($row)}}
        @endforeach
    @endif
    <a href="#" class="dsg-btn remove-btn" data-id="{{$row->id}}">remove</a>
</div>
