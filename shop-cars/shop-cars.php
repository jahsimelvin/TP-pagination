<?php
// Affiche les erreurs PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion à la base de données
$host = 'localhost:3306';
$username = 'root';
$password = '';
$dbname = 'shop-cars';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Utilisation de Faker pour générer des données aléatoires
// require_once 'vendor/autoload.php';

// $faker = Faker\Factory::create('fr_FR');

// Générer des voitures fictives (500 voitures)
// function generateProducts($pdo, $faker) {
//     for ($i = 0; $i < 500; $i++) {
//         $name = $faker->words(2, true);
//         $description = $faker->realText(50);
//         $price = $faker->randomFloat(2, 10, 500);
//         $image_url = 'https://via.placeholder.com/640x480.png?text=Produit+FR';

//         $query = $pdo->prepare("INSERT INTO products (name, description, price, image_url) VALUES (:name, :description, :price, :image_url)");
//         $query->bindParam(':name', $name);
//         $query->bindParam(':description', $description);
//         $query->bindParam(':price', $price);
//         $query->bindParam(':image_url', $image_url);
//         $query->execute();
//     }
// }


// Pagination et recherche
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$searchQuery = isset($_GET['q']) ? $_GET['q'] : '';
if ($searchQuery) {
    $query = $pdo->prepare("SELECT * FROM cars WHERE name LIKE :search LIMIT :limit OFFSET :offset");
    $query->bindValue(':search', "%$searchQuery%", PDO::PARAM_STR);
} else {
    $query = $pdo->prepare("SELECT * FROM cars LIMIT :limit OFFSET :offset");
}
$query->bindParam(':limit', $limit, PDO::PARAM_INT);
$query->bindParam(':offset', $offset, PDO::PARAM_INT);
$query->execute();
$cars = $query->fetchAll(PDO::FETCH_ASSOC);

// Nombre total de pages
$totalQuery = $pdo->query("SELECT COUNT(*) FROM cars");
$totalcars = $totalQuery->fetchColumn();
$totalPages = ceil($totalcars / $limit);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop-cars</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container my-5">
    <h1 class="text-center">Shop-cars</h1>

    <!-- Barre de recherche -->
    <div class="mb-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Rechercher des produits..." value="<?= htmlspecialchars($searchQuery) ?>">
    </div>

    <!-- Liste des voitures -->
    <div class="row">
        <?php foreach ($cars as $cars): ?>
            <div class="col-md-4">
                <div class="card mb-4">
                    <img src="<?= htmlspecialchars($cars['image_url']) ?>" class="card-img-top" alt="Image du produit">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($cars['name']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($cars['description']) ?></p>
                        <p class="text-muted"><?= number_format($cars['price'], 2) ?> €</p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

   <!-- Pagination -->
<nav>
    <ul class="pagination justify-content-center">
        <?php
        // Définir combien de pages autour de la page actuelle
        $visiblePages = 3;

        // Bouton "Premier"
        if ($page > 1) {
            echo '<li class="page-item">
                <a class="page-link" href="?page=1&q=' . htmlspecialchars($searchQuery) . '">Premier</a>
            </li>';
        }

        // Bouton "Précédent"
        if ($page > 1) {
            echo '<li class="page-item">
                <a class="page-link" href="?page=' . ($page - 1) . '&q=' . htmlspecialchars($searchQuery) . '">Précédent</a>
            </li>';
        }

        // Afficher les pages dynamiquement autour de la page actuelle
        for ($i = max(1, $page - $visiblePages); $i <= min($totalPages, $page + $visiblePages); $i++) {
            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                <a class="page-link" href="?page=' . $i . '&q=' . htmlspecialchars($searchQuery) . '">' . $i . '</a>
            </li>';
        }

        // Bouton "Suivant"
        if ($page < $totalPages) {
            echo '<li class="page-item">
                <a class="page-link" href="?page=' . ($page + 1) . '&q=' . htmlspecialchars($searchQuery) . '">Suivant</a>
            </li>';
        }

        // Bouton "Dernier"
        if ($page < $totalPages) {
            echo '<li class="page-item">
                <a class="page-link" href="?page=' . $totalPages . '&q=' . htmlspecialchars($searchQuery) . '">Dernier</a>
            </li>';
        }
        ?>
    </ul>
</nav>

</div>

<!-- Script de recherche dynamique -->
<script>
    $('#searchInput').on('keyup', function() {
        const query = $(this).val();
        $.ajax({
            url: 'index.php',
            method: 'GET',
            data: { q: query },
            success: function(response) {
                const html = $(response).find('#cars-list').html();
                const pagination = $(response).find('.pagination').html();
                $('#cars-list').html(html);
                $('.pagination').html(pagination);
            }
        });
    });
</script>
</body>
</html>