<script>
    var host = '{{ config('notifier.host') }}';
    var port = '{{ config('notifier.port') }}';
    var socket = new WebSocket('{{ config('notifier.connection') }}://' + host + ':' + port);
    socket.onopen = function(e) {
        @if($route = \Route::current()->getName())
        socket.send('{{ $route }}');
        @endif
        console.log("Connection established!");
    };
</script>