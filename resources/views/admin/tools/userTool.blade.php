<div class="btn-group" data-toggle="buttons">
    @foreach($options as $option => $label)
        <label class="btn btn-default btn-sm {{ \Request::get('user', '2') == $option ? 'active' : '' }}">
            <input type="radio" class="user-type" value="{{ $option }}">{{$label}}
        </label>
    @endforeach
</div>