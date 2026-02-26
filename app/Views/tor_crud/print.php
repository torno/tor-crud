<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $entity['titulo'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2><?= $entity['titulo'] ?></h2>
        <p class="text-muted">Generado: <?= $fecha ?></p>
        
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <?php foreach ($data['fields'] as $field => $attrs): ?>
                        <th><?= $attrs['label'] ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['records'] as $record): ?>
                    <tr>
                        <?php foreach (array_keys($data['fields']) as $field): 
                            $value = $record[$field . '_texto'] ?? $record[$field] ?? '';
                            if (is_array($value)) $value = implode(', ', $value);
                        ?>
                            <td><?= $value ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>window.print();</script>
</body>
</html>