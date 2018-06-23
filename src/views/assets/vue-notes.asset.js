window.Vue = require('vue');
window.vueNotification = require('vue-notification').default;

Vue.use(vueNotification);
const note = new Vue({el: '#notes'});

socket.addEventListener('message', function (event) {
    let data = JSON.parse(event.data).data;

    note.$notify({
        type: data.type ? data.type : 'success',
        title: data.title ? data.title : 'Note:',
        text: data.text ? data.text : ''
    });
});

