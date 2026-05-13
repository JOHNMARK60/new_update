<?php
$pageTitle = $pageTitle ?? 'Cashiering Inventory System';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($pageTitle); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script>
    window.tailwind = window.tailwind || {};
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Poppins', 'ui-sans-serif', 'system-ui']
                },
                colors: {
                    ink: '#172033',
                    brand: '#2563eb',
                    mint: '#059669',
                    amber: '#d97706'
                }
            }
        }
    };
</script>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?php echo e(app_url('assets/styles.css')); ?>">
