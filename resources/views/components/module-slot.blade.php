{{--
    Slot mở rộng chung cho mọi view (core/module) — thay cho việc viết tay
    @foreach (module_registry()->layoutPartials(...)) ở từng nơi. Context
    truyền tường minh qua prop `context`, không dựa vào biến tự kế thừa từ
    view cha.
--}}
@props(['name', 'context' => []])

@foreach (module_registry()->layoutPartials($name) as $item)
    @include($item['view'], $context + $item['data'])
@endforeach
