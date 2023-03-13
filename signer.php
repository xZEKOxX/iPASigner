<?php
// Vérifie si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // chemin vers le fichier .ipa
    $file = $_FILES["ipaFile"]["tmp_name"];

    // informations de signature
    $certificate = $_FILES["certificate"]["tmp_name"];
    $password = $_POST["password"];
    $mobileprovision = $_FILES["mobileprovision"]["tmp_name"];

    // chargement du fichier .ipa
    $data = file_get_contents($file);

    // création d'une instance de ZipArchive pour extraire le fichier .app
    $zip = new ZipArchive;
    if ($zip->open($file) === TRUE) {
        $zip->extractTo('mon-dossier-temporaire');
        $zip->close();
    }

    // récupération du nom de l'application et de l'identifiant de bundle depuis le fichier Info.plist
    $infoPlistData = file_get_contents("mon-dossier-temporaire/Payload/*.app/Info.plist");
    $infoPlist = new SimpleXMLElement($infoPlistData);
    $appName = (string) $infoPlist->CFBundleExecutable;
    $appBundleId = (string) $infoPlist->CFBundleIdentifier;

    // chargement du fichier de provisionnement mobile
    $mobileprovisionData = file_get_contents($mobileprovision);

    // écriture du fichier de provisionnement mobile dans le dossier temporaire
    $fp = fopen("mon-dossier-temporaire/embedded.mobileprovision", "w");
    fwrite($fp, $mobileprovisionData);
    fclose($fp);

    // signature de l'application
    $cmd = "codesign -f -s \"".$certificate."\" --keychain login.keychain --entitlements entitlements.plist --provisioning-profile mon-dossier-temporaire/embedded.mobileprovision mon-dossier-temporaire/Payload/".$appName.".app";
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
