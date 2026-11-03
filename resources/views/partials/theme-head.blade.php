{{-- Applies the saved theme before first paint to avoid a flash. --}}
<script>
    (function () {
        try {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || t === 'light') {
                document.documentElement.setAttribute('data-theme', t);
            }
        } catch (e) {}
    })();
</script>
