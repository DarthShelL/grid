<div class="dsg-actions-cell">
    @if(!is_null($buttons))
        @foreach($buttons as $btn)
            <a href="{{$btn['href']}}" class="dsg-btn {{$btn['class']}}" {{$btn['attribute']}} data-id="{{$row->id}}">{{$btn['text']}}</a>
        @endforeach
    @endif
    <a href="#" class="dsg-btn remove-btn" data-id="{{$row->id}}">remove</a>
</div>
