<?php
// Fonction pour obtenir le nombre de lignes dans chaque table
function getTableCounts($pdo, $tables) {
    $counts = [];
    
    // Requête SQL pour compter les lignes de chaque table
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $counts[$table] = $row['count'];
    }
    
    return $counts;
}
?>
<?php
require 'connexion.php';

if (isset($_POST['table'])) {
    $table = $_POST['table'];

    // Récupérer les colonnes de la table sélectionnée
    $query = "DESCRIBE $table";
    $stmt = $pdo->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Générer les champs du formulaire pour chaque colonne
    foreach ($columns as $column) {
        echo "<label for='$column'>$column :</label><br>";
        echo "<input type='text' id='$column' name='columns[]' value='' required><br>";
    }
}
?>
