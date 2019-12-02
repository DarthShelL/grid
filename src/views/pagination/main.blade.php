<div class="links">
    <button class="first-page-btn" @if($current_page == 1) disabled @endif> <<</button>
    <button class="prev-page-btn" @if($current_page == 1) disabled @endif> <</button>
    @for($i=1;$i<=$pages_num;$i++)
        @if($current_page == $i)
            <button class="page-btn current" disabled> {{$i}} </button>
        @else
            <button class="page-btn"> {{$i}} </button>
        @endif
    @endfor
    <button class="next-page-btn" @if($current_page == $pages_num) disabled @endif> ></button>
    <button class="last-page-btn" data-last="{{$pages_num}}" @if($current_page == $pages_num) disabled @endif> >></button>
</div>
