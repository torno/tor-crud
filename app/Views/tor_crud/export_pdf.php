<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $entity['titulo'] ?></title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f0f0f0; text-align: left; padding: 5px; }
        td { padding: 3px 5px; border-bottom: 1px solid #ddd; }
        .header { margin-bottom: 20px; }
        .fecha { color: #666; font-size: 9pt; }
    </style>
</head>
<body>
    <div class="header">
        <h2><?= $entity['titulo'] ?></h2>
        <div class="fecha">Generado: <?= $fecha ?></div>
    </div>
    
    <table>
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
                        <td><?= strip_tags($value) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>