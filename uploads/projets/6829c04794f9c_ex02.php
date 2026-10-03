<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Table de multiplication</title>
    <style>
        table {
            border-collapse: collapse;
            margin: 20px auto;
        }
        th, td {
            border: 1px solid #666;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #4FC3F7;
            color: white;
        }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Table de multiplication (0 à 9)</h2>
    <table>
        <tr>
            <th></th>
            <?php
            // En-têtes de colonnes
            for ($i = 0; $i <= 9; $i++) {
                echo "<th>$i</th>";
            }
            ?>
        </tr>
        <?php
        // Lignes du tableau
        for ($i = 0; $i <= 9; $i++) {
            echo "<tr>";
            echo "<th>$i</th>"; // En-tête de ligne
            for ($j = 0; $j <= 9; $j++) {
                echo "<td>" . ($i * $j) . "</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>
