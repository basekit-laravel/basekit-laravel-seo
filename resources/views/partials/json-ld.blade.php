{{--
    The JSON-LD blocks are pre-rendered by the Head component using the hardened
    Schema serializer (Schema::ENCODE_FLAGS), so angle brackets inside values can
    never terminate the surrounding <script> element. Raw output is safe here.
--}}
@foreach ($schemas as $schema)
    {!! $schema !!}
@endforeach