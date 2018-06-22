<div id="notes">
    <notifications
            position="{{ config('notifier.position.vertical') }}
            {{ config('notifier.position.horizontal') }}"
    />
</div>

{{ notifier_js() }}

<script src="vendor/notifier/vue-notes.min.js"></script>