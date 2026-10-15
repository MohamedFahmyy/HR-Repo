<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'طباعة مستند') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            padding: 2rem 0;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }
        @media print {
            body { background: #ffffff; padding: 0; }
            .print-container { box-shadow: none; border: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="d-flex justify-content-end mb-4 no-print gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-printer me-1"></i> طباعة القسيمة
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-x-lg me-1"></i> إغلاق
            </button>
        </div>
        <?= $content ?>
    </div>
</body>
</html>
