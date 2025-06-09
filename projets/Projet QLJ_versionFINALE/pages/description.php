<?php
// Inclure la connexion à la base de données
require 'fcts/connexion.php';  // Inclure le fichier db.php pour la connexion à la base de données

// Inclure les fonctions
require 'fcts/fonctions.php';  // Inclure le fichier contenant les fonctions

// Tables à compter
$tables = ['usagers', 'vehicules', 'lieux', 'caracteristiques'];  // Remplacez par vos noms de tables réels

// Appeler la fonction pour obtenir le comptage des lignes
$counts = getTableCounts($pdo, $tables);

// Récupérer les statistiques de la base de données
$stats = [];

// Moyenne d'âge
$query = "SELECT AVG(EXTRACT(YEAR FROM CURRENT_DATE) - an_nais) AS moyenne_age
FROM usagers
WHERE an_nais IS NOT NULL AND an_nais != 0;";

$stats['moyenne_age'] = $pdo->query($query)->fetchColumn();

// Calcul de la médiane
$query_count = "SELECT COUNT(*) AS total FROM usagers";
$total_usagers = $pdo->query($query_count)->fetchColumn();

// Si le nombre d'usagers est impair
if ($total_usagers % 2 == 1) {
    $offset = (int)($total_usagers / 2);
    $query_mediane = "SELECT (2024 - an_nais) AS age FROM usagers ORDER BY age LIMIT 1 OFFSET $offset";
    $stats['mediane_age'] = $pdo->query($query_mediane)->fetchColumn();
} else {
    $offset1 = (int)($total_usagers / 2) - 1;
    $offset2 = (int)($total_usagers / 2);
    $query_mediane = "
        SELECT AVG(age) AS mediane FROM (
            SELECT (2024 - an_nais) AS age FROM usagers ORDER BY age LIMIT 2 OFFSET $offset1
        ) AS median_subquery";
    $stats['mediane_age'] = $pdo->query($query_mediane)->fetchColumn();
}

// Nombre total d'usagers
$stats['total_usagers'] = $total_usagers;

// Répartition par sexe
$query_sexe = "
    SELECT CASE 
               WHEN sexe = 1 THEN 'Homme' 
               WHEN sexe = 2 THEN 'Femme' 
               ELSE 'Inconnu' 
           END AS sexe, 
           COUNT(*) AS nombre
    FROM usagers
    GROUP BY sexe;
";
$sexe_data = $pdo->query($query_sexe)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accidentologie - Description</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

    <div class="navbar">
        <a href="../index.html"><button>Accueil</button></a>
        <a href="description.php" class="active"><button>Description de la base</button></a>
        <a href="tableaubord.php"><button>Graphiques</button></a>
        <a href="update.php"><button>Mettre a jour la bdd</button></a>
    </div>

    <div class="container">
        <h1>Compte des tuples des tables</h1>
        
        <table>
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Nombre de lignes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($counts as $table => $count): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($table); ?></td>
                        <td><?php echo htmlspecialchars($count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <h1>Tableau de bord des statistiques des accidentés</h1>
                    
        <h2>Statistiques générales</h2>
        <br>
        <div class="stats-container">
            <div class="stat-item">
                <h3>Moyenne d'âge</h3>
                <p><?php echo round($stats['moyenne_age'], 2); ?></p>
            </div>
            <div class="stat-item">
                <h3>Médiane d'âge</h3>
                <p><?php echo $stats['mediane_age']; ?></p>
            </div>
            <div class="stat-item">
                <h3>Nombre total d'accidentés</h3>
                <p><?php echo $stats['total_usagers']; ?></p>
            </div>
        </div>

        <br><br>
        <h2>Répartition par sexe</h2>
        <table>
            <tr>
                <th>Sexe</th>
                <th>Nombre d'usagers</th>
            </tr>
            <?php foreach ($sexe_data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['sexe']); ?></td>
                    <td><?php echo $row['nombre']; ?></td>
                </tr>
            <?php endforeach; ?>
    </table>
    </div>

</body>
</html>
