<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accidentologie - Mettre à jour</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

    <!-- Menu de navigation -->
    <div class="navbar">
        <a href="../index.html"><button>Accueil</button></a>
        <a href="description.php"><button>Description de la base</button></a>
        <a href="tableaubord.php"><button>Graphiques</button></a>
        <a href="update.php"  class="active"><button>Mettre à jour la BDD</button></a>
    </div>

    <!-- Boutons pour modifier ou supprimer un tuple -->
    <div style="display: flex; align-items: center; margin : auto;">
        <form action="update.php" method="POST" style="display: inline;">
            <button type="submit" name="action" value="modifier">Modifier un tuple</button>
            <button type="submit" name="action" value="supprimer">Supprimer un tuple</button>
        </form>
        
        <a href="infos/description.pdf" target="_blank" style="text-decoration: none; margin-left: auto; font-weight: bold;">
            PDF des descriptions des variables
        </a>
    </div>


    <?php
    if (isset($_POST['action'])) {
        include 'fcts/connexion.php';

        // Récupérer la liste des tables de la base de données
        $listeTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        if ($_POST['action'] == 'modifier' || $_POST['action'] == 'supprimer') {
            echo '<form method="POST" action="update.php">
                    <label for="table">Choisissez la table à ' . ($_POST['action'] == 'modifier' ? 'modifier' : 'supprimer') . ' :</label>
                    <select name="table" id="table">';
            
            foreach ($listeTables as $table) {
                echo '<option value="' . $table . '">' . $table . '</option>';
            }

            echo '</select>
                  <br>
                  <label for="num_accident">Numéro d\'accident (Num_acc) :</label>
                  <input type="text" name="num_accident" placeholder="ex : 202300000001" required>
                  <br>
                  <button type="submit" name="soumettre" value="' . $_POST['action'] . '">Valider</button>
                  </form>';
        }
    }

    function getColumnTypes($pdo, $table) {
        $columnTypes = [];
        $query = $pdo->query("DESCRIBE $table");
        $columns = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            $columnTypes[$column['Field']] = $column['Type'];
        }
        return $columnTypes;
    }

    if (isset($_POST['soumettre'])) {
        include 'fcts/connexion.php';
        
        $tableSelectionnee = $_POST['table'];
        $numeroAccident = $_POST['num_accident'];

        if ($_POST['soumettre'] == 'modifier') {
            $requete = $pdo->prepare("SELECT * FROM $tableSelectionnee WHERE Num_acc = :num_acc");
            $requete->execute(['num_acc' => $numeroAccident]);
            $tuple = $requete->fetch(PDO::FETCH_ASSOC);

            if ($tuple) {
                echo '<h3>Modifier le tuple :</h3>';
                echo '<form method="POST" action="update.php">';
                echo '<input type="hidden" name="table" value="' . $tableSelectionnee . '">';
                echo '<input type="hidden" name="num_accident" value="' . $numeroAccident . '">';
                
                foreach ($tuple as $colonne => $valeur) {
                    echo "<label for='$colonne'>$colonne :</label><br>";
                    echo "<input type='text' name='$colonne' value='$valeur'><br>";
                }
                echo '<button type="submit" name="mettre_a_jour" value="update">Mettre à jour</button>';
                echo '</form>';
            } else {
                echo "Aucun tuple trouvé avec Num_acc = $numeroAccident dans la table $tableSelectionnee.";
            }
        } elseif ($_POST['soumettre'] == 'supprimer') {
            $requete = $pdo->prepare("SELECT * FROM $tableSelectionnee WHERE Num_acc = :num_acc");
            $requete->execute(['num_acc' => $numeroAccident]);
            $tuple = $requete->fetch(PDO::FETCH_ASSOC);

            if ($tuple) {
                echo '<h3>Confirmez la suppression de ce tuple :</h3>';
                echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
                echo '<thead><tr><th>Colonne</th><th>Valeur</th></tr></thead>';
                echo '<tbody>';

                foreach ($tuple as $colonne => $valeur) {
                    echo "<tr><td><strong>$colonne</strong></td><td>$valeur</td></tr>";
                }

                echo '</tbody>';
                echo '</table>';

                echo '<form method="POST" action="update.php">
                        <input type="hidden" name="table" value="' . $tableSelectionnee . '">
                        <input type="hidden" name="num_accident" value="' . $numeroAccident . '">
                        <button type="submit" name="confirmer_suppression" value="oui">Confirmer la suppression</button>
                    </form>';
            } else {
                echo "Aucun tuple trouvé avec Num_acc = $numeroAccident dans la table $tableSelectionnee.";
            }
        }
    }

    if (isset($_POST['confirmer_suppression'])) {
        include 'fcts/connexion.php';
        $tableSelectionnee = $_POST['table'];
        $numeroAccident = $_POST['num_accident'];

        $requete = $pdo->prepare("DELETE FROM $tableSelectionnee WHERE Num_acc = :num_acc");
        $requete->execute(['num_acc' => $numeroAccident]);

        echo "Le tuple avec Num_acc $numeroAccident a été supprimé de la table $tableSelectionnee.";
    }

    if (isset($_POST['mettre_a_jour'])) {
        include 'fcts/connexion.php';
    
        $tableSelectionnee = $_POST['table'];
        $numeroAccident = $_POST['num_accident'];
        $columnTypes = getColumnTypes($pdo, $tableSelectionnee);
    
        $colonnes = '';
        $valeurs = [];
        $erreurs = [];
    
        foreach ($_POST as $cle => $valeur) {
            if (!in_array($cle, ['table', 'num_accident', 'mettre_a_jour'])) {
                $type = isset($columnTypes[$cle]) ? $columnTypes[$cle] : null;
    
                if ($type) {
                    if (strpos($type, 'int') !== false && !ctype_digit($valeur)) {
                        $erreurs[] = "Erreur : La colonne '$cle' doit être un entier.";
                    } elseif (strpos($type, 'varchar') !== false && !is_string($valeur)) {
                        $erreurs[] = "Erreur : La colonne '$cle' doit être une chaîne de caractères.";
                    } else {
                        $colonnes .= "`$cle` = :$cle, ";
                        $valeurs[":$cle"] = $valeur;
                    }
                }
            }
        }
    
        if (!empty($erreurs)) {
            foreach ($erreurs as $erreur) {
                echo "<p style='color: red;'>$erreur</p>";
            }
        } else {
            $colonnes = rtrim($colonnes, ', ');
            $valeurs[":num_acc"] = $numeroAccident;
    
            // Activer les erreurs PDO pour le débogage
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
            try {
                $requete = $pdo->prepare("UPDATE `$tableSelectionnee` SET $colonnes WHERE `Num_acc` = :num_acc");
    
                if ($requete->execute($valeurs)) {
                    echo "Le tuple avec Num_acc $numeroAccident a été mis à jour dans la table $tableSelectionnee.";
                } else {
                    echo "<p style='color: red;'>Erreur : La mise à jour n'a pas pu être effectuée.</p>";
                }
            } catch (PDOException $e) {
                echo "<p style='color: red;'>Erreur SQL : " . $e->getMessage() . "</p>";
            }
        }
    }
?>    

</body>
</html>
