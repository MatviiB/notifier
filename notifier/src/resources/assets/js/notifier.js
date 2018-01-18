var host = 'dev.scribble';
var port = '3000';

var socket = new WebSocket('ws://' + host + ':' + port);

socket.onopen = function(e) {
    console.log("Connection established!");
};

socket.addEventListener('message', function (event) {
    console.log('Message from server', event.data);
});