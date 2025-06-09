<?php
// Configuration
$to = "votre-email@example.com"; // À remplacer par votre email
$subject = "Nouveau message depuis votre portfolio";

// Sécurisation des données
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Vérification si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération et nettoyage des données
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';
    
    // Validation de base
    $errors = array();
    
    if (empty($name)) {
        $errors[] = "Le nom est requis";
    }
    
    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide";
    }
    
    if (empty($message)) {
        $errors[] = "Le message est requis";
    }
    
    // Si pas d'erreurs, envoi du mail
    if (empty($errors)) {
        $headers = "From: " . $email . "\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        $email_content = "Nom: " . $name . "\n";
        $email_content .= "Email: " . $email . "\n\n";
        $email_content .= "Message:\n" . $message;
        
        // Tentative d'envoi du mail
        if (mail($to, $subject, $email_content, $headers)) {
            $response = array(
                'status' => 'success',
                'message' => 'Votre message a été envoyé avec succès!'
            );
        } else {
            $response = array(
                'status' => 'error',
                'message' => "Une erreur s'est produite lors de l'envoi du message."
            );
        }
    } else {
        $response = array(
            'status' => 'error',
            'message' => "Erreurs de validation : " . implode(", ", $errors)
        );
    }
    
    // Envoi de la réponse en JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?> 