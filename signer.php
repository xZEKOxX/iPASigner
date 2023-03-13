<?php
// Vérifie si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // chemin vers le fichier .ipa
    $file = $_FILES["ipaFile"]["tmp_name"];

    // informations de signature
    $appName = $_POST["appName"];
    $appBundleId = $_POST["appBundleId"];
    $certificate = $_FILES["certificate"]["tmp_name"];
    $password = $_POST["password"];

    // chargement du fichier .ipa
    $data = file_get_contents($file);

    // création d'une instance de ZipArchive pour extraire le fichier .app
    $zip = new ZipArchive;
    if ($zip->open($file) === TRUE) {
        $zip->extractTo('mon-dossier-temporaire');
        $zip->close();
    }

    // signature de l'application
    $cmd = "codesign -f -s \"".$certificate."\" --keychain login.keychain --entitlements entitlements.plist mon-dossier-temporaire/Payload/".$appName.".app";
    exec($cmd);

    // création d'un fichier .ipa signé
    $zip = new ZipArchive;
    if ($zip->open("mon-fichier-signe.ipa", ZipArchive::CREATE) === TRUE) {
        // ajout du fichier .app signé
        $zip->addFile("mon-dossier-temporaire/Payload/".$appName.".app", $appName.".app");
        // fermeture de l'archive
        $zip->close();
    }

    // suppression du dossier temporaire
    exec("rm -rf mon-dossier-temporaire");
}
?>
