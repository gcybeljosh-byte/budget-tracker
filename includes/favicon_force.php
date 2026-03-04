<?php
// includes/favicon_force.php
// Robustly forces the favicon to be circular using Canvas API
?>
<script>
    (function() {
        const faviconUrl = "<?php echo SITE_URL; ?>assets/images/favicon_rounded.png";
        const img = new Image();
        img.crossOrigin = "Anonymous";
        img.src = faviconUrl;

        img.onload = function() {
            const canvas = document.createElement('canvas');
            const size = 64; // Standard high-res favicon size
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');

            // Create circular clip
            ctx.beginPath();
            ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
            ctx.clip();

            // Draw image
            ctx.drawImage(img, 0, 0, size, size);

            // Update all favicon links
            const links = document.querySelectorAll("link[rel*='icon']");
            const circularDataUrl = canvas.toDataURL("image/png");

            if (links.length > 0) {
                links.forEach(link => {
                    link.href = circularDataUrl;
                    // Ensure type is png for the generated data
                    if (link.rel.includes('icon')) link.type = 'image/png';
                });
            } else {
                // Create one if it doesn't exist
                const link = document.createElement('link');
                link.rel = 'icon';
                link.type = 'image/png';
                link.href = circularDataUrl;
                document.head.appendChild(link);
            }
        };
    })();
</script>