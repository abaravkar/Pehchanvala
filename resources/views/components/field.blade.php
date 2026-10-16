@props(['name','label','type'=>'text','value'=>'','required'=>false])
<div class="field"><label for="{{ $name }}">{{ $label }}{{ $required?' *':'' }}</label><input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type==='password'?'':old($name,$value) }}" @required($required) {{ $attributes }}>@error($name)<span class="field-error">{{ $message }}</span>@enderror</div>
