<?php
require 'fcts/connexion.php';

$region_to_code = [
    'Auvergne-Rhône-Alpes' => 'fr-ara',
    'Hauts-de-France' => 'fr-hdf',
    'Provence-Alpes-Côte d\'Azur' => 'fr-pac',
    'Île-de-France' => 'fr-idf',
    'Normandie' => 'fr-nor',
    'Nouvelle-Aquitaine' => 'fr-naq',
    'Bourgogne-Franche-Comté' => 'fr-bfc',
    'Occitanie' => 'fr-occ',
    'Pays de la Loire' => 'fr-pdl',
    'Bretagne' => 'fr-bre',
    'Centre-Val de Loire' => 'fr-cvl',
    'Grand Est' => 'fr-ges',
    'Corse' => 'fr-cor',
    'La Réunion' => 'fr-lre',
    'Mayotte' => 'fr-may',
    'Guyane' => 'fr-gf',
    'Martinique' => 'fr-mq',
    'Guadeloupe' => 'fr-gua',
    'Autres' => 'fr-99' // Pour gérer les cas sans correspondance
];

$query = "
    SELECT 
        CASE
            WHEN dep IN ('01', '03', '07', '26', '42', '38', '73', '74') THEN 'Auvergne-Rhône-Alpes'
            WHEN dep IN ('02', '59', '60', '62', '80') THEN 'Hauts-de-France'
            WHEN dep IN ('04', '05', '06', '13', '83', '84') THEN 'Provence-Alpes-Côte d\'Azur'
            WHEN dep IN ('75', '77', '78', '91', '92', '93', '94', '95') THEN 'Île-de-France'
            WHEN dep IN ('14', '27', '50', '61', '76') THEN 'Normandie'
            WHEN dep IN ('16', '17', '19', '23', '24', '33', '40', '47', '64', '79', '86', '87') THEN 'Nouvelle-Aquitaine'
            WHEN dep IN ('21', '25', '39', '52', '58', '70', '71', '90') THEN 'Bourgogne-Franche-Comté'
            WHEN dep IN ('09', '11', '12', '30', '31', '32', '34', '46', '48', '65', '66', '81', '82') THEN 'Occitanie'
            WHEN dep IN ('44', '49', '53', '72', '85') THEN 'Pays de la Loire'
            WHEN dep IN ('22', '29', '35', '56') THEN 'Bretagne'
            WHEN dep IN ('18', '28', '36', '37', '41', '45') THEN 'Centre-Val de Loire'
            WHEN dep IN ('08', '10', '51', '52', '54', '55', '57', '67', '68', '88') THEN 'Grand Est'
            WHEN dep IN ('2A', '2B') THEN 'Corse'
            WHEN dep IN ('971') THEN 'Guadeloupe'
            WHEN dep IN ('972') THEN 'Martinique'
            WHEN dep IN ('973') THEN 'Guyane'
            WHEN dep IN ('974') THEN 'La Réunion'
            WHEN dep IN ('976') THEN 'Mayotte'
            ELSE 'Autres'
        END AS region,
        COUNT(*) AS nombre_accidents
    FROM caracteristiques
    GROUP BY region
    ORDER BY nombre_accidents DESC;
";

$req = $pdo->query($query);
$accidents_par_region = $req->fetchAll(PDO::FETCH_ASSOC);

// Préparer les données de la carte
$map_data = [];
foreach ($accidents_par_region as $row) {
    // Associer le nom de la région avec son code
    $region_code = isset($region_to_code[$row['region']]) ? $region_to_code[$row['region']] : 'fr-99';
    // Préparer le tableau avec le code de la région et le nombre d'accidents
    $map_data[] = [$region_code, (int)$row['nombre_accidents']];
}

$query_type_collision = "
    SELECT 
        CASE col
            WHEN '-1' THEN 'Non renseigné'
            WHEN '1' THEN 'Deux véhicules - frontale'
            WHEN '2' THEN 'Deux véhicules – par l’arrière'
            WHEN '3' THEN 'Deux véhicules – par le côté'
            WHEN '4' THEN 'Trois véhicules et plus – en chaîne'
            WHEN '5' THEN 'Trois véhicules et plus - collisions multiples'
            WHEN '6' THEN 'Autre collision'
            WHEN '7' THEN 'Sans collision'
            ELSE 'Valeur inconnue'
        END AS type_collision,
        COUNT(*) AS nombre_accidents
    FROM caracteristiques
    GROUP BY type_collision
    ORDER BY nombre_accidents DESC;
    ";

$req_collision = $pdo->query($query_type_collision);
$data_collision = $req_collision->fetchAll(PDO::FETCH_ASSOC);

$query_trajet = "
    SELECT 
        CASE trajet
            WHEN '-1' THEN 'Non renseigné'
            WHEN '0' THEN 'Non renseigné'
            WHEN '1' THEN 'Domicile – travail'
            WHEN '2' THEN 'Domicile – école'
            WHEN '3' THEN 'Courses – achats'
            WHEN '4' THEN 'Utilisation professionnelle'
            WHEN '5' THEN 'Promenade – loisirs'
            WHEN '9' THEN 'Autre'
            ELSE 'Valeur inconnue'
        END AS type_trajet,
        COUNT(*) AS nombre_accidents
    FROM usagers
    GROUP BY type_trajet
    ORDER BY nombre_accidents DESC;
    ";

$req_trajet = $pdo->query($query_trajet);
$data_trajet = $req_trajet->fetchAll(PDO::FETCH_ASSOC);

$query_gravite = "
    SELECT 
        CASE grav
            WHEN '1' THEN 'Indemne'
            WHEN '2' THEN 'Tué'
            WHEN '3' THEN 'Blessé hospitalisé'
            WHEN '4' THEN 'Blessé léger'
            ELSE 'État inconnu'
        END AS gravite,
        COUNT(*) AS nombre_accidents
    FROM usagers
    GROUP BY gravite
    ORDER BY nombre_accidents DESC;
";

$req_gravite = $pdo->query($query_gravite);
$data_gravite = $req_gravite->fetchAll(PDO::FETCH_ASSOC);


// ********************************
// Requête SQL pour compter les lieux par type d'éclairage (luminosité)
$query_eclairage = "
    SELECT lum, COUNT(*) AS nombre
    FROM caracteristiques
    GROUP BY lum
";
$req = $pdo->query($query_eclairage);
$resultats = $req->fetchAll(PDO::FETCH_ASSOC);

// Regrouper les catégories similaires (par exemple, "Inconnu" qui peut apparaître plusieurs fois)
$data_eclairage = [];
foreach ($resultats as $row) {
    switch ($row['lum']) {
        case 1: $categorie = 'Plein jour'; break;
        case 2: $categorie = 'Crépuscule ou aube'; break;
        case 3: $categorie = 'Nuit sans éclairage public'; break;
        case 4: $categorie = 'Nuit avec éclairage public non allumé'; break;
        case 5: $categorie = 'Nuit avec éclairage public allumé'; break;
        default: $categorie = 'Inconnu'; break;
    }

    // Si la catégorie existe déjà dans le tableau, ajoute le nombre d'accidents à celle-ci
    if (isset($data_eclairage[$categorie])) {
        $data_eclairage[$categorie] += (int)$row['nombre'];
    } else {
        $data_eclairage[$categorie] = (int)$row['nombre'];
    }
}

// Conversion des résultats en un format adapté pour Highcharts
$data_eclairage_final = [];
foreach ($data_eclairage as $categorie => $nombre) {
    $data_eclairage_final[] = ['name' => $categorie, 'y' => $nombre];
}

// Convertir en JSON
$data_eclairage_json = json_encode($data_eclairage_final);


$query_sexe = "
    SELECT 
        CASE 
            WHEN sexe = 1 THEN 'Masculin'
            WHEN sexe = 2 THEN 'Féminin'
            ELSE 'Inconnu'
        END AS sexe,
        COUNT(*) AS nombre_usagers
    FROM usagers
    GROUP BY sexe;
";
$req = $pdo->query($query_sexe);
$usagers_par_sexe = $req->fetchAll(PDO::FETCH_ASSOC);

// Préparer les données pour Highcharts
$data_sexe = [];
$categories_sexe = [];
foreach ($usagers_par_sexe as $row) {
    $categories_sexe[] = $row['sexe'];
    $data_sexe[] = (int) $row['nombre_usagers'];
}

// Récupérer les mois les plus accidentés
$query_mois_acc = "
    SELECT 
        CASE mois
            WHEN 1 THEN 'Janvier'
            WHEN 2 THEN 'Février'
            WHEN 3 THEN 'Mars'
            WHEN 4 THEN 'Avril'
            WHEN 5 THEN 'Mai'
            WHEN 6 THEN 'Juin'
            WHEN 7 THEN 'Juillet'
            WHEN 8 THEN 'Août'
            WHEN 9 THEN 'Septembre'
            WHEN 10 THEN 'Octobre'
            WHEN 11 THEN 'Novembre'
            WHEN 12 THEN 'Décembre'
            ELSE 'Inconnu'
        END AS nom_mois,
        COUNT(*) AS nombre_accidents
    FROM caracteristiques
    GROUP BY mois
    ORDER BY mois ASC;
";
$data_mois = $pdo->query($query_mois_acc)->fetchAll(PDO::FETCH_ASSOC);

// Préparer les données pour Highcharts
$categories_mois = [];
$values_mois = [];

foreach ($data_mois as $row) {
    $categories_mois[] = $row['nom_mois'];
    $values_mois[] = (int)$row['nombre_accidents'];
}

$query_meteo = "
SELECT 
    count(*) AS nb_acc, 
    CASE 
        WHEN atm = -1 THEN 'Non renseignée'
        WHEN atm = 1 THEN 'Normale'
        WHEN atm = 2 THEN 'Pluie légère'
        WHEN atm = 3 THEN 'Pluie forte'
        WHEN atm = 4 THEN 'Neige - grêle'
        WHEN atm = 5 THEN 'Brouillard - fumée'
        WHEN atm = 6 THEN 'Vent fort - tempête'
        WHEN atm = 7 THEN 'Temps éblouissant'
        WHEN atm = 8 THEN 'Temps couvert'
        WHEN atm = 9 THEN 'Autre'
        ELSE 'Inconnu'
    END AS meteo
FROM 
    caracteristiques
GROUP BY 
    atm
ORDER BY
    nb_acc desc;
";
$data_meteo = $pdo->query($query_meteo)->fetchAll(PDO::FETCH_ASSOC);


// Séparer les données pour les utiliser dans Highcharts
$categories_meteo = array(); // Pour les catégories (les conditions météorologiques)
$meteoaccidents = array();  // Pour le nombre d'accidents

foreach ($data_meteo as $row) {
    $categories_meteo[] = $row['meteo'];  // Ajouter la condition météorologique
    $meteoaccidents[] = (int) $row['nb_acc'];  // Ajouter le nombre d'accidents
}
// données pour le graphique nb acc par Obstacle mobile heurté
$query_obsm = "
SELECT 
    count(*) AS nb_acc, 
    CASE 
        WHEN obsm = -1 THEN 'Non renseigné'
        WHEN obsm = 0 THEN 'Aucun'
        WHEN obsm = 1 THEN 'Piéton'
        WHEN obsm = 2 THEN 'Véhicule'
        WHEN obsm = 4 THEN 'Véhicule sur rail'
        WHEN obsm = 5 THEN 'Animal domestique'
        WHEN obsm = 6 THEN 'Animal sauvage'
        WHEN obsm = 9 THEN 'Autre'
        ELSE 'Inconnu'
    END AS obstacle
FROM 
    vehicules
GROUP BY 
    obsm
ORDER BY 
    obsm ASC;
";

// Exécution de la requête
$data_obsm = $pdo->query($query_obsm)->fetchAll(PDO::FETCH_ASSOC);

// Préparation des données pour un graphique ou tableau
$categories = array();  // Pour les catégories (les types d'obstacle)
$obsm_accidents = array();  // Pour le nombre d'accidents

foreach ($data_obsm as $row) {
    $categories[] = $row['obstacle'];  // Ajouter le type d'obstacle
    $obsm_accidents[] = (int) $row['nb_acc'];  // Ajouter le nombre d'accidents
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accidentologie - Tableau de bord</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/maps/modules/map.js"></script>
</head>
<body>

    <div class="navbar">
        <a href="../index.html"><button>Accueil</button></a>
        <a href="description.php"><button>Description de la base</button></a>
        <a href="tableaubord.php" class="active"><button>Graphiques</button></a>
        <a href="update.php"><button>Mettre a jour la bdd</button></a>
    </div>

    <div class="container">
        <h1>Tableau de bord des accidents routiers en France</h1>

        <div class = "row">
            <!-- Carte des Accidents par Région -->
            <div id="map" class="chart" style="width: 50%; height: 600px;"></div>
            
            <!-- Graphique Type de Collision -->
            <div id="collision-chart" class="chart" style="width: 50%; height: 600px; "></div>
        </div>
        <div class = "row">
            <!-- Graphique Type de Trajet -->
            <div id="trajet-chart" class="chart" style="width: 50%; height: 500px; "></div>

            <!-- Graphique Gravité des Accidents -->
            <div id="gravite-chart"  class="chart" style="width: 50%; height: 500px; "></div>
        </div>
        <div class = "row">
            <!-- Graphique Type eclairage -->
            <div id="eclairage-chart" class="chart" style="width: 50%; height: 500px; "></div>
            <!-- Graphique sexe usagers -->
            <div id="sexe-chart" class="chart" style="width: 50%; height: 500px; "></div>
        </div>
        <div class = "row">
            <!-- Graphique Type eclairage -->
            <div id="graphique-mois" class="chart" style="width: 50%; height: 600px; "></div>
        </div>
        <div class = "row">
            <!-- Graphique Type météo -->
            <div id="graphique_meteo" class="chart" style="width: 50%; height: 500px; "></div>
            <!-- Graphique sexe a changer -->
            <div id="graph_obsm_accidents" class="chart" style="width: 50%; height: 500px; "></div>
        </div>
    </div>

    <script>
        Highcharts.setOptions({
    chart: {
        borderRadius: 10 // Appliquer aux graphiques de type "chart"
    }
});
    // Préparer la carte des régions
    (async () => {
    const topology = await fetch(
        'https://code.highcharts.com/mapdata/countries/fr/fr-all.topo.json'
    ).then(response => response.json());
    console.log(topology); 
    const mapData = <?php echo json_encode($map_data); ?>;

    Highcharts.mapChart('map', {
        chart: { map: topology },
        title: { text: 'Accidents par Région' },
        colorAxis: {
            min: 0,
            stops: [
                [0, '#F9E04B'],
                [0.5, '#F37042'],
                [1, '#d81832']
            ]
        },
        series: [{
            data: mapData,
            joinBy: 'hc-key',
            name: 'Nombre d\'accidents',
            states: { hover: { color: '#FF6347' } },
            dataLabels: {
                enabled: true,
                format: '{point.name}'
            },
        }]
    });
})();
    // Graphique Type de Collision
    var dataCollision = <?php echo json_encode($data_collision); ?>;
    var collisionCategories = dataCollision.map(item => item.type_collision);
    var collisionValues = dataCollision.map(item => parseInt(item.nombre_accidents));

    Highcharts.chart('collision-chart', {
        chart: { type: 'bar' },
        title: { text: 'Répartition des accidents par type de collision' },
        xAxis: { categories: collisionCategories },
        yAxis: { title: { text: 'Nombre d\'accidents' } },
        series: [{
            name: 'Nombre d\'accidents routiers en France',
            data: collisionValues,
            color: '#D81832' 
        }]
    });

    // Graphique Type de Trajet
    var dataTrajet = <?php echo json_encode($data_trajet); ?>;
    var trajetCategories = dataTrajet.map(item => item.type_trajet);
    var trajetValues = dataTrajet.map(item => parseInt(item.nombre_accidents));

    Highcharts.chart('trajet-chart', {
        chart: { type: 'column' },
        title: { text: 'Répartition des accidents par type de trajet' },
        xAxis: { categories: trajetCategories },
        yAxis: { title: { text: 'Nombre d\'accidents' } },
        series: [{
            name: 'Nombre d\'accidents routiers en France',
            data: trajetValues,
            color: '#D81832' 
        }]
    });

    // Graphique Gravité
    var dataGravite = <?php echo json_encode($data_gravite); ?>;
    var graviteCategories = dataGravite.map(item => item.gravite);
    var graviteValues = dataGravite.map(item => parseInt(item.nombre_accidents));
    var couleurs = ['#FF0000', '#00FF00', '#0000FF', '#FFFF00']; // Ajouter plus de couleurs si nécessaire

    Highcharts.chart('gravite-chart', {
        chart: { type: 'pie' },
        title: { text: 'Répartition des accidents par gravité de l\'état de l\'usager' },
        series: [{
            name: 'Accidents',
            data: graviteCategories.map((category, index) => ({ name: category, y: graviteValues[index],
                color: (category === 'Indemne' ? '#65A165' :
                    category === 'Tué' ? '#D81832' :
                    category === 'Blessé hospitalisé' ? '#F37042' :
                    category === 'Blessé léger' ? '#F9E04B' :
                    'ASABB6')   }))
        }]
    });

// graphique éclairage
    var dataEclairage = <?php echo $data_eclairage_json; ?>;
    var customColors = ['#f7BAC2','#D81832', '#e82f48', '#ec5166', '#f07485', '#f397A3'];

    Highcharts.chart('eclairage-chart', {
        chart: { type: 'pie' },
        title: { text: 'Répartition des accidents par type d’éclairage des lieux' },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.y}</b> ({point.percentage:.1f}%)'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '{point.name}: {point.y} ({point.percentage:.1f}%)',
                }
            }
        },
        colors: customColors, 
        series: [{
            name: 'Lieux',
            colorByPoint: true,
            data: dataEclairage
        }]
    });

    // Graphique Répartion des sexes
    var categoriesSexe = <?php echo json_encode($categories_sexe); ?>;
    var dataSexe = <?php echo json_encode($data_sexe); ?>;

    Highcharts.chart('sexe-chart', {
        chart: { type: 'pie' },
        title: { text: 'Répartition des usagers impliqués par sexe' },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.y}</b> ({point.percentage:.1f}%)'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '{point.name}: {point.y} ({point.percentage:.1f}%)',
                }
            }
        },
        series: [{
            name: 'Usagers',
            colorByPoint: true,
            data: categoriesSexe.map((category, index) => ({
                name: category,
                y: dataSexe[index],
                color: (category === 'Masculin' ? '#3498DB' :
                        category === 'Féminin' ? '#E74C3C' :
                        '#95A5A6') // Couleur par défaut pour 'Inconnu'
            }))
        }]
    });

    // Graphique Accidents par Mois
    var categoriesMois = <?php echo json_encode($categories_mois); ?>;
    var valuesMois = <?php echo json_encode($values_mois); ?>;

    Highcharts.chart('graphique-mois', {
        chart: { type: 'line' },
        title: { text: 'Évolution du nombre d\'accidents par mois de l\'année 2023' },
        xAxis: {
            categories: categoriesMois,
            title: { text: 'Mois' }
        },
        yAxis: {
            min: 0,
            title: { text: 'Nombre d\'accidents' }
        },
        plotOptions: {
        line: {
            dataLabels: {
                enabled: true,
                format: '{y}', // Afficher la valeur des accidents
                style: {
                    fontSize: '12px',
                    fontWeight: 'bold',
                    color: '#000'
                }
            },
            enableMouseTracking: true // Pour afficher les info-bulles au survol
        }
    },
        series: [{
            name: 'Nombre d\'accidents',
            data: valuesMois,
            color: '#d81832'
        }]
    });


    // Préparer les données pour le graphique de la météo
var categoriesMeteo = <?php echo json_encode($categories_meteo); ?>;
var meteoAccidents = <?php echo json_encode($meteoaccidents); ?>;

// Créer le graphique Météo
Highcharts.chart('graphique_meteo', {
    chart: { type: 'column' },
    title: { text: 'Répartition des accidents par condition météorologique' },
    xAxis: { categories: categoriesMeteo },
    yAxis: { title: { text: 'Nombre d\'accidents' } },
    series: [{
        name: 'Nombre d\'accidents',
        data: meteoAccidents,
        color: '#D81832'
    }]
});


    var categories = <?php echo json_encode($categories); ?>;
    var obsm_accidents = <?php echo json_encode($obsm_accidents); ?>;

    // Création du graphique
    Highcharts.chart('graph_obsm_accidents', {
        chart: {
            type: 'bar'  // Type de graphique (barres horizontales)
        },
        title: {
            text: 'Répartition des accidents par type d\'obstacle mobile heurté'
        },
        subtitle: {
            text: 'Données sur une échelle ordinaire'  // Sous-titre du graphique
        },
        xAxis: {
            categories: categories,  // Les catégories sont les types d'obstacle
            title: {
                text: 'Types d\'obstacles mobiles heurtés'  // Titre de l\'axe X
            }
        },
        yAxis: {
            title: {
                text: 'Nombre d\'accidents'  // Titre de l\'axe Y
            }
        },
        series: [{
            name: 'Accidents',
            data: obsm_accidents,// Données sur le nombre d'accidents
            color: '#d81832' 
        }]
    });
    </script>

</body>
</html>