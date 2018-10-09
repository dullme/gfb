<div class="btn-group" data-toggle="buttons">
    @foreach($options as $option => $label)
        <label class="btn btn-default btn-sm {{ \Request::get('withdraw', 'search') == $option ? 'active' : '' }}">
            <input type="radio" class="withdraw-type" value="{{ $option }}">{{$label}}
        </label>
    @endforeach
</div>